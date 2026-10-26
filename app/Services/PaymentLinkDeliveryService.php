<?php

namespace App\Services;

use App\Models\RentalContract;
use App\Models\SponsorshipTransfer;
use App\Support\PaymentMessageTemplates;
use App\Support\SmsGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * إرسال رابط دفع ميسر للعميل عبر SMS أو واتساب أو تجهيزه للنسخ.
 * لا ينشئ فاتورة جديدة إن كان للسجل رابط صالح، ويحدّث حالة الفاتورة قبل الإرسال.
 */
class PaymentLinkDeliveryService
{
    public const CHANNELS = ['sms', 'whatsapp', 'copy'];

    private static ?array $availableChannels = null;

    /**
     * القنوات المتاحة فعلاً: SMS حين تكون بوابة الرسائل مفعلة ومهيأة، وواتساب حين يكون مزوده مهيأً.
     * النسخ متاح دائماً. تُحسب مرة واحدة في الطلب.
     *
     * @return array{sms: bool, whatsapp: bool, copy: bool}
     */
    public static function availableChannels(): array
    {
        if (self::$availableChannels !== null) {
            return self::$availableChannels;
        }

        $sms = $whatsapp = false;
        try {
            $sms = \App\Support\CommunicationSettings::smsIsConfigured();
        } catch (\Throwable $exception) {
            report($exception);
        }
        try {
            $whatsapp = app(\App\Services\WhatsApp\WhatsAppMessagingService::class)->isConfigured();
        } catch (\Throwable $exception) {
            report($exception);
        }

        return self::$availableChannels = ['sms' => $sms, 'whatsapp' => $whatsapp, 'copy' => true];
    }

    /** لإعادة الحساب بعد تغيير إعدادات الاتصال في نفس الطلب (والاختبارات). */
    public static function forgetAvailableChannels(): void
    {
        self::$availableChannels = null;
    }

    public static function label($record): string
    {
        $info = $record instanceof RentalContract ? $record->info : $record;

        return $info?->payment_link_sent_at ? 'إعادة إرسال رابط الدفع' : 'إرسال رابط الدفع';
    }

    /**
     * @return array{link_created: bool, link_sent: bool, payment_url?: ?string, sms_error?: ?string, failure_reason?: ?string, message: string}
     */
    public function deliverRental(RentalContract $contract, string $channel = 'sms', ?string $phone = null): array
    {
        $links = app(RentalContractMoyasarLinkService::class);
        $contract->loadMissing(['user', 'info']);
        if (!$contract->info) {
            return $this->failure('بيانات الدفع لهذا العقد غير مكتملة.');
        }

        $links->refreshBeforeSending($contract);
        if ($contract->info->payment_status === 'paid' || $contract->info->moyasar_payment_status === 'paid') {
            return $this->failure('العقد مدفوع بالكامل.');
        }

        try {
            $link = $links->ensureLink($contract);
        } catch (\RuntimeException $exception) {
            return $this->failure($exception->getMessage());
        }

        $contract->unsetRelation('info');
        $contract->load('info');

        return $this->send('rental', $contract->info, $channel, $phone ?? $contract->user?->phone, $link['url'], [
            'customer_name' => $contract->user?->name ?? 'عميلنا العزيز',
            'contract_number' => $contract->contract_number ?: $contract->id,
            'amount' => PaymentMessageTemplates::amount($link['amount']),
        ], ['contract_id' => $contract->id, 'recipient_id' => $contract->user_id]);
    }

    public function deliverTransfer(SponsorshipTransfer $transfer, string $channel = 'sms', ?string $phone = null): array
    {
        $payments = app(SponsorshipTransferMoyasarService::class);
        if ($transfer->moyasar_invoice_id) {
            try {
                $transfer = $payments->synchronize($transfer);
            } catch (\Throwable $exception) {
                Log::warning('Moyasar: unable to refresh transfer invoice before sending', [
                    'transfer_id' => $transfer->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
        if (!SponsorshipTransferMoyasarService::canSend($transfer)) {
            return $this->failure('الطلب مدفوع أو مغلق أو طريقة دفعه ليست ميسر.');
        }

        try {
            $transfer = $payments->ensureLink($transfer);
        } catch (\RuntimeException $exception) {
            return $this->failure($exception->getMessage());
        }

        $transfer->loadMissing('newSponsor');

        return $this->send('transfer', $transfer, $channel, $phone ?? $transfer->newSponsor?->phone, $transfer->moyasar_payment_url, [
            'customer_name' => $transfer->newSponsor?->name ?? 'عميلنا العزيز',
            'contract_number' => $transfer->id,
            'amount' => PaymentMessageTemplates::amount($transfer->moyasar_amount),
        ], ['sponsorship_transfer_id' => $transfer->id, 'recipient_id' => $transfer->new_sponsor_id]);
    }

    private function send(string $type, $record, string $channel, ?string $phone, string $url, array $variables, array $meta): array
    {
        $base = ['link_created' => true, 'link_sent' => false, 'payment_url' => $url];
        if ($channel === 'copy') {
            return $base + ['message' => 'رابط الدفع جاهز للنسخ.'];
        }

        // الرابط أُنشئ؛ القناة المعطلة لا تُرسل ويبقى الرابط قابلاً للنسخ.
        if (!(self::availableChannels()[$channel] ?? false)) {
            $reason = $channel === 'whatsapp'
                ? 'بوابة واتساب غير مهيأة في إعدادات الاتصال.'
                : 'إرسال الرسائل النصية غير مفعل في إعدادات الاتصال.';

            return $base + ['sms_error' => $reason, 'message' => 'تم إنشاء رابط الدفع، لكن لم يُرسل: ' . $reason];
        }

        $phone = trim((string) $phone);
        if ($phone === '') {
            Log::warning('Moyasar: no phone number, payment link created but not sent', $meta);

            return $base + ['sms_error' => 'لا يوجد رقم جوال للعميل.', 'message' => 'تم إنشاء رابط الدفع، لكن لا يوجد رقم جوال للعميل.'];
        }

        $message = PaymentMessageTemplates::render($type, $variables + ['payment_link' => $url]);
        $response = null;
        try {
            $response = $channel === 'whatsapp'
                ? app(UltraMsgWhatsAppService::class)->sendChatMessage($phone, $message, ['source' => $type . '_payment_link', 'meta' => $meta])
                : SmsGateway::send(formatSaudiPhone($phone), $message);
        } catch (\Throwable $exception) {
            Log::error('Moyasar: payment link delivery exception', $meta + ['channel' => $channel, 'message' => $exception->getMessage()]);
        }

        $sent = $channel === 'whatsapp' ? (bool) ($response['success'] ?? false) : self::smsWasSent($response);
        if ($sent) {
            $record->update(array_filter([
                'payment_link_sent_at' => now(),
                'payment_link_sent_channel' => Schema::hasColumn($record->getTable(), 'payment_link_sent_channel') ? $channel : null,
            ]));
        }

        Log::info('Moyasar payment link delivery', $meta + ['channel' => $channel, 'sent' => $sent]);

        $error = $sent ? null : ($channel === 'whatsapp'
            ? 'تعذر الإرسال عبر واتساب؛ تأكد من إعدادات بوابة واتساب ورقم الجوال.'
            : self::describeSmsFailure($response));
        $channelName = $channel === 'whatsapp' ? 'واتساب' : 'رسالة نصية';

        return array_merge($base, [
            'link_sent' => $sent,
            'sms_error' => $error,
            'message' => $sent
                ? "تم إرسال رابط الدفع للعميل عبر {$channelName} بنجاح."
                : 'تم إنشاء رابط الدفع، لكن تعذر إرسال الرسالة للعميل: ' . $error,
        ]);
    }

    private function failure(string $reason): array
    {
        return ['link_created' => false, 'link_sent' => false, 'failure_reason' => $reason, 'message' => $reason];
    }

    public static function smsWasSent($result): bool
    {
        if (is_array($result)) {
            if (array_key_exists('success', $result)) {
                return (bool) $result['success'];
            }

            if (isset($result['code']) && in_array((string) $result['code'], ['1', '200'], true)) {
                return true;
            }

            return isset($result['statusCode']) && in_array((string) $result['statusCode'], ['200', '201'], true);
        }

        if ($result === null || $result === false || trim((string) $result) === '') {
            return false;
        }

        $decoded = json_decode((string) $result, true);
        if (is_array($decoded)) {
            if (array_key_exists('success', $decoded)) {
                return (bool) $decoded['success'];
            }

            if (isset($decoded['code']) && in_array((string) $decoded['code'], ['1', '200'], true)) {
                return true;
            }

            if (isset($decoded['statusCode']) && in_array((string) $decoded['statusCode'], ['200', '201'], true)) {
                return true;
            }

            if (isset($decoded['status']) && in_array(strtolower((string) $decoded['status']), ['success', 'sent', 'accepted'], true)) {
                return true;
            }
        }

        return in_array(strtolower(trim((string) $result)), ['1', '200', 'success', 'sent', 'accepted'], true);
    }

    public static function describeSmsFailure($result): string
    {
        if ($result === null || $result === false || (is_scalar($result) && trim((string) $result) === '')) {
            return 'لم يرجع مزود الرسائل تأكيد نجاح.';
        }

        $text = is_scalar($result) ? (string) $result : json_encode($result, JSON_UNESCAPED_UNICODE);

        return 'استجابة مزود الرسائل: ' . Str::limit($text ?: 'غير معروفة', 180);
    }
}
