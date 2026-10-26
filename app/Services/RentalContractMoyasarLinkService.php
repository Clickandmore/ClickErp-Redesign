<?php

namespace App\Services;

use App\Models\MoyasarContractPayment;
use App\Models\RentalContract;
use App\Support\MoyasarSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/** إنشاء رابط ميسر لعقد التأجير أو إعادة استخدامه؛ التحصيل والقيود تبقى في MoyasarContractPaymentService. */
class RentalContractMoyasarLinkService
{
    /** حالات فاتورة لم يعد رابطها صالحاً للدفع، فيُنشأ بدلها رابط جديد. */
    public const CLOSED_STATUSES = ['expired', 'canceled', 'cancelled', 'voided'];

    public function __construct(private MoyasarService $gateway) {}

    /**
     * @return array{id: string, url: string, created: bool, amount: float}
     * @throws \RuntimeException برسالة عربية تصلح للعرض للموظف
     */
    public function ensureLink(RentalContract $contract): array
    {
        if (!MoyasarSettings::isEnabled()) {
            Log::info('Moyasar: payment link skipped because gateway is disabled', ['contract_id' => $contract->id]);
            throw new \RuntimeException('بوابة ميسر غير مفعلة.');
        }

        $contract->loadMissing(['user', 'info']);
        $info = $contract->info;
        if (!$info) {
            throw new \RuntimeException('بيانات الدفع لهذا العقد غير مكتملة.');
        }

        $amount = (float) ($info->remaining_amount ?? $info->contract_value ?? 0);
        if ($amount <= 0) {
            Log::warning('Moyasar: contract value is zero, skipping payment link', ['contract_id' => $contract->id]);
            throw new \RuntimeException('لا يوجد مبلغ متبقٍ لإرساله في رابط الدفع.');
        }

        if (empty(MoyasarSettings::current()['secret_key'] ?? '')) {
            Log::error('Moyasar: secret key is not configured', ['contract_id' => $contract->id]);
            throw new \RuntimeException('مفتاح ميسر السري غير مضبوط.');
        }

        $reusable = !empty($info->moyasar_payment_id) && !empty($info->moyasar_payment_url)
            && !in_array((string) $info->moyasar_payment_status, self::CLOSED_STATUSES, true);
        if ($reusable) {
            $registered = MoyasarContractPayment::where('invoice_id', $info->moyasar_payment_id)->value('amount');
            app(MoyasarContractPaymentService::class)->register($info, $info->moyasar_payment_id, $registered !== null ? (float) $registered : $amount);
            $info->update(['payment_type' => 'moyasar']);

            return ['id' => $info->moyasar_payment_id, 'url' => $info->moyasar_payment_url, 'created' => false,
                'amount' => $registered !== null ? (float) $registered : $amount];
        }

        $number = $contract->contract_number ?: $contract->id;
        $clientName = $contract->user?->name ?? 'العميل';
        $callbackUrl = route('moyasar.callback', ['contract_id' => $contract->id]);
        $result = $this->gateway->createPaymentLink(
            $amount,
            "دفع عقد إيجار رقم {$number} - {$clientName}",
            $callbackUrl,
            ['contract_id' => $contract->id, 'contract_number' => $number],
            $callbackUrl
        );

        if (!$result || empty($result['id']) || empty($result['url'])) {
            Log::error('Moyasar: failed to create payment link', ['contract_id' => $contract->id]);
            throw new \RuntimeException('تعذر إنشاء رابط الدفع من ميسر.');
        }

        app(MoyasarContractPaymentService::class)->register($info, $result['id'], $amount);
        $info->update([
            'payment_type' => 'moyasar',
            'moyasar_payment_id' => $result['id'],
            'moyasar_payment_url' => $result['url'],
            'moyasar_payment_status' => 'initiated',
            // رابط جديد لم يُرسل بعد؛ يظهر زر الإرسال لا إعادة الإرسال.
            'payment_link_sent_at' => null,
        ]);

        return ['id' => $result['id'], 'url' => $result['url'], 'created' => true, 'amount' => $amount];
    }

    /**
     * يُلغي فواتير ميسر غير المدفوعة للعقد قبل إغلاقه، حتى لا يُسدد العميل بعد التسوية
     * (أو يُمدَّد عقد مغلق بسداد تجديد متأخر). ما سُدد للتو يُسجل أولاً ويوقف الإغلاق للمراجعة.
     * يُستدعى خارج معاملة الإغلاق كي لا يُلغى تسجيل سداد مؤكد مع التراجع.
     *
     * @throws ValidationException
     */
    public function cancelOpenInvoices(RentalContract $contract): void
    {
        if (empty(MoyasarSettings::current()['secret_key'] ?? '')) {
            return;
        }

        $info = $contract->info()->first();
        $references = MoyasarContractPayment::where('rental_contract_id', $contract->id)->whereNull('paid_at')
            ->pluck('invoice_id')->all();
        if ($info?->moyasar_payment_id && !$info->moyasar_paid_at
            && !in_array((string) $info->moyasar_payment_status, array_merge(self::CLOSED_STATUSES, ['paid', 'refunded', 'partially_refunded']), true)) {
            $references[] = $info->moyasar_payment_id;
        }

        $payments = app(MoyasarContractPaymentService::class);
        foreach (array_unique(array_filter($references)) as $reference) {
            $lookup = $this->gateway->lookupInvoice($reference);
            $invoice = $lookup['invoice'];
            if (!$invoice || ($invoice['id'] ?? null) !== $reference) {
                // غير موجودة لدى الحساب الحالي (مفاتيح أخرى، أو رقم عملية في مراجع التجديد القديمة): لا شيء يُلغى.
                if ($lookup['found'] === false) {
                    Log::warning('Moyasar: contract invoice not found while closing; skipped', ['contract_id' => $contract->id, 'invoice_id' => $reference]);
                    continue;
                }
                throw ValidationException::withMessages(['payment' => 'تعذر التحقق من فاتورة ميسر قبل الإغلاق. أعد المحاولة بعد قليل.']);
            }

            $status = (string) ($invoice['status'] ?? '');
            if (in_array($status, self::CLOSED_STATUSES, true)) {
                continue;
            }
            if (in_array($status, ['paid', 'refunded'], true)) {
                try {
                    $payment = $payments->resolve($reference);
                    if ($payment) $payments->synchronize($payment);
                } catch (\Throwable $exception) {
                    report($exception);
                }
                throw ValidationException::withMessages(['payment' => 'سدد العميل فاتورة ميسر لهذا العقد. حدّث الصفحة وراجع التسوية ثم أعد محاولة الإغلاق.']);
            }

            $result = $this->gateway->cancelInvoice($reference);
            if (!in_array($result['status'] ?? null, ['canceled', 'cancelled'], true)) {
                throw ValidationException::withMessages(['payment' => 'تعذر إلغاء فاتورة ميسر المفتوحة لهذا العقد. أعد محاولة الإغلاق بعد قليل.']);
            }
            if ($info && $info->moyasar_payment_id === $reference) {
                $info->update(['moyasar_payment_status' => 'canceled']);
            }
            Log::info('Moyasar: open contract invoice canceled before closure', ['contract_id' => $contract->id, 'invoice_id' => $reference]);
        }
    }

    /** تحديث حالة الفاتورة قبل الإرسال حتى لا يُرسل رابط سُدد للتو؛ تعذر التحقق لا يمنع الإرسال كما كان. */
    public function refreshBeforeSending(RentalContract $contract): void
    {
        $invoiceId = $contract->info?->moyasar_payment_id;
        if (!$invoiceId) {
            return;
        }

        try {
            $payments = app(MoyasarContractPaymentService::class);
            $payment = $payments->resolve($invoiceId);
            if ($payment) {
                $payments->synchronize($payment);
            }
        } catch (\Throwable $exception) {
            Log::warning('Moyasar: unable to refresh contract invoice before sending', [
                'contract_id' => $contract->id,
                'message' => $exception->getMessage(),
            ]);
        }

        $contract->unsetRelation('info');
        $contract->load('info');
    }
}
