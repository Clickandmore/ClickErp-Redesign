<?php

namespace App\Http\Actions\ingaz;

use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Actions\MainAction;
use App\Http\Traits\Firebase;
use App\Http\Traits\SendSMSTrait;
use App\Models\ingaz\Notification;
use App\Models\ingaz\User;
use App\Models\SmsTemplate;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Support\CommunicationSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NotificationAction extends MainAction
{
    use Firebase, SendSMSTrait;

    private WhatsAppMessagingService $whatsAppService;

    public function __construct(Notification $model, WhatsAppMessagingService $whatsAppService)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Notification/';
        $this->whatsAppService = $whatsAppService;
    }


    public function storeNotification($data, Request $request): array
    {
        $type = (string) $request->input('type', 'sms');

        if ($type === 'sms') {
            return $this->storeSmsMessages($request);
        }

        if ($type === 'whatsapp') {
            return $this->storeWhatsAppMessages($request);
        }

        return $this->storeGeneralNotifications($data, $request);
    }

    private function storeSmsMessages(Request $request): array
    {
        $senderNameType = (string) $request->input('sender_name_type', 'advertising');

        $templateId = $request->input('sms_template_id');

        $template = null;

        if ($this->usesSmsTemplate($templateId)) {
            $template = SmsTemplate::query()
                ->whereKey($templateId)
                ->where('is_active', true)
                ->first();
        }

        if ($this->usesSmsTemplate($templateId) && !$template) {
            throw ValidationException::withMessages([
                'sms_template_id' => 'القالب المحدد غير موجود أو غير نشط.',
            ]);
        }

        $message = $this->buildSmsMessage($template, $request);
        $recipients = $this->resolveSmsRecipients($request);

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                'user_id' => 'لا يوجد عملاء صالحون للإرسال.',
            ]);
        }

        $this->assertBulkProtection($request, $recipients->count(), 'sms');

        $sentCount = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            $response = $this->sendSMS($recipient['phone'], $message, null, $senderNameType);

            if (!$this->isSuccessfulSmsResponse($response)) {
                $failedCount++;
                continue;
            }

            $this->storeSmsLog($recipient['user'], $template, $message, $recipient['is_external'] ? $recipient['phone'] : null);
            $sentCount++;
        }

        return [
            'template_id' => $template?->id,
            'template_title' => $template?->title ?? 'بدون قالب',
            'sender_name_type' => $senderNameType,
            'channel' => 'sms',
            'total_count' => $recipients->count(),
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'internal_count' => $recipients->where('is_external', false)->count(),
            'external_count' => $recipients->where('is_external', true)->count(),
        ];
    }

    private function storeWhatsAppMessages(Request $request): array
    {
        $whatsAppSettings = CommunicationSettings::whatsapp();
        $purpose = (string) $request->input('whatsapp_purpose', $whatsAppSettings['default_purpose'] ?? 'verification');
        $message = trim((string) $request->input('message_override', ''));
        $senderKey = trim((string) $request->input('whatsapp_sender_key', ''));
        $templateContext = [];
        if ($this->whatsAppService->driver() === 'clickchat' && $request->filled('chatwoot_template')) {
            try {
                $clickChat = app(\App\Services\WhatsApp\ClickChatWhatsAppService::class);
                $prepared = $clickChat->prepareTemplate(
                    (string) $request->input('chatwoot_template'), (array) $request->input('chatwoot_variables', []),
                    $clickChat->channelAccountId(['sender_key' => $senderKey])
                );
                $message = $prepared['message'];
                $templateContext = ['clickchat_template' => $prepared['clickchat_template']];
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages(['chatwoot_template' => $e->getMessage()]);
            }
        }
        if ($this->whatsAppService->driver() === 'chatwoot' && $request->filled('chatwoot_template')) {
            try {
                $prepared = app(\App\Services\WhatsApp\ChatwootWhatsAppService::class)->prepareTemplate(
                    (string) $request->input('chatwoot_template'), (array) $request->input('chatwoot_variables', [])
                );
                $message = $prepared['message'];
                $templateContext = ['template_params' => $prepared['template_params']];
            } catch (\Throwable $e) {
                throw ValidationException::withMessages(['chatwoot_template' => get_class($e) === \RuntimeException::class ? $e->getMessage() : 'تعذر التحقق من القالب.']);
            }
        }

        $allowedPurposes = ['verification', 'follow_up'];
        if ((bool) ($whatsAppSettings['allow_advertising'] ?? false)) {
            $allowedPurposes[] = 'advertising';
        }

        if (!in_array($purpose, $allowedPurposes, true)) {
            throw ValidationException::withMessages([
                'whatsapp_purpose' => 'نوع رسالة واتساب غير مسموح في الإعدادات الحالية.',
            ]);
        }

        if ($message === '') {
            throw ValidationException::withMessages([
                'message_override' => 'نص رسالة واتساب لا يمكن أن يكون فارغًا.',
            ]);
        }

        $resolvedSender = null;
        if ($this->whatsAppService->driver() === 'ultramsg') {
            $resolvedSender = $this->whatsAppService->resolveSender($senderKey);

            if (!$resolvedSender) {
                throw ValidationException::withMessages([
                    'whatsapp_sender_key' => 'اختر رقم واتساب صالحًا من الحسابات المفعلة.',
                ]);
            }

            $senderKey = (string) ($resolvedSender['key'] ?? '');
        }

        if (!$this->whatsAppService->isConfigured()) {
            $missing = implode(', ', $this->whatsAppService->missingConfigKeys());
            throw ValidationException::withMessages([
                'type' => "إعدادات واتساب غير مكتملة. القيم المفقودة: {$missing}",
            ]);
        }

        $users = $this->resolveTargetUsers($request);

        if ($users->isEmpty()) {
            throw ValidationException::withMessages([
                'user_id' => 'لا يوجد عملاء صالحون للإرسال.',
            ]);
        }

        $this->assertBulkProtection($request, $users->count(), 'whatsapp');

        $sentCount = 0;
        $failedCount = 0;
        $scheduledCount = 0;
        // Each send waits for the provider; a few hundred can outlast PHP's default limit.
        if ($users->count() > 20) {
            @set_time_limit(max(300, $users->count() * 3));
        }

        foreach ($users as $user) {
            $response = $this->whatsAppService->sendText((string) $user->phone, $message, [
                'purpose' => $purpose,
                'user_id' => $user->id,
                'sender_key' => $senderKey,
                ...$templateContext,
            ]);

            // Click Live Chat answers past 60 a minute are queued and resent within minutes:
            // the customer still gets it, so it is neither "sent" nor "failed" yet.
            if (!empty($response['retry_scheduled'])) {
                $this->storeWhatsAppLog($user, $purpose, $message);
                $scheduledCount++;
                continue;
            }

            if (!$this->isSuccessfulWhatsAppResponse($response)) {
                $failedCount++;
                $failureReason ??= (string) data_get($response, 'response.message',
                    $this->whatsAppService->driver() === 'chatwoot' ? 'تعذر إرسال الرسالة عبر Widers.' : '');
                continue;
            }

            $this->storeWhatsAppLog($user, $purpose, $message);
            $sentCount++;
        }

        return [
            'channel' => 'whatsapp',
            'purpose' => $purpose,
            'driver' => $this->whatsAppService->driver(),
            'failure_reason' => ($failureReason ?? '') !== '' ? $failureReason : null,
            'sender_key' => $senderKey !== '' ? $senderKey : null,
            'sender_name' => $resolvedSender['name'] ?? null,
            'total_count' => $users->count(),
            'sent_count' => $sentCount,
            'scheduled_count' => $scheduledCount,
            'failed_count' => $failedCount,
        ];
    }

    private function buildSmsMessage(?SmsTemplate $template, Request $request): string
    {
        $variables = collect((array) $request->input('template_variables', []))
            ->mapWithKeys(function ($value, $key) {
                return [(string) $key => is_scalar($value) ? trim((string) $value) : ''];
            })
            ->toArray();

        $message = trim((string) $request->input('message_override', ''));
        if ($message === '' && $template) {
            $message = trim($template->processMessage($variables));
        }

        if ($message === '') {
            throw ValidationException::withMessages([
                'message_override' => 'نص الرسالة لا يمكن أن يكون فارغًا.',
            ]);
        }

        return $message;
    }

    private function resolveTargetUsers(Request $request): Collection
    {
        $usersQuery = User::query()
            ->where('is_blocked', false)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->select('id', 'name', 'phone');

        if ($request->input('all_or_some') === 'some') {
            $userIds = collect((array) $request->input('user_id', []))
                ->filter()
                ->unique()
                ->values()
                ->all();
            $usersQuery->whereIn('id', $userIds);
        }

        return $usersQuery->get();
    }

    private function resolveSmsRecipients(Request $request): Collection
    {
        $internalRecipients = $this->resolveTargetUsers($request)
            ->map(fn (User $user): array => [
                'user' => $user,
                'phone' => $this->normalizeSmsPhone((string) $user->phone),
                'is_external' => false,
            ])
            ->filter(fn (array $recipient): bool => $this->isValidSmsPhone($recipient['phone']))
            ->values();

        if ($request->input('all_or_some') !== 'some') {
            return $internalRecipients;
        }

        $internalPhones = $internalRecipients->pluck('phone')->all();
        $externalRecipients = collect($this->externalRecipientPhones($request))
            ->reject(fn (string $phone): bool => in_array($phone, $internalPhones, true))
            ->map(fn (string $phone): array => [
                'user' => null,
                'phone' => $phone,
                'is_external' => true,
            ]);

        return $internalRecipients
            ->concat($externalRecipients)
            ->values();
    }

    private function externalRecipientPhones(Request $request): array
    {
        $phones = preg_split('/[\r\n,;]+/', (string) $request->input('external_recipient_phones', '')) ?: [];

        return collect($phones)
            ->map(fn (string $phone): string => trim($phone))
            ->filter()
            ->map(fn (string $phone): string => $this->normalizeSmsPhone($phone))
            ->filter(fn (string $phone): bool => $this->isValidSmsPhone($phone))
            ->unique()
            ->values()
            ->all();
    }

    private function usesSmsTemplate($templateId): bool
    {
        return !in_array((string) $templateId, ['', 'none'], true);
    }

    private function normalizeSmsPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '05')) {
            return '966' . substr($digits, 1);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            return '966' . $digits;
        }

        return formatSaudiPhone($digits);
    }

    private function isValidSmsPhone(string $phone): bool
    {
        return (bool) preg_match('/^\d{8,15}$/', $phone);
    }

    private function assertBulkProtection(Request $request, int $totalRecipients, string $channel = 'sms'): void
    {
        if ($request->input('all_or_some') !== 'all') {
            return;
        }

        if (!$request->boolean('confirm_bulk_send')) {
            throw ValidationException::withMessages([
                'confirm_bulk_send' => 'يجب تفعيل تأكيد الإرسال الجماعي.',
            ]);
        }

        [$keyword, $bulkLimit] = $this->resolveBulkProtectionConfig($channel);

        $keyword = strtoupper($keyword);
        $providedKeyword = strtoupper(trim((string) $request->input('bulk_confirmation_text')));
        if ($providedKeyword !== $keyword) {
            throw ValidationException::withMessages([
                'bulk_confirmation_text' => 'كلمة التأكيد غير صحيحة.',
            ]);
        }

        if ($totalRecipients > $bulkLimit) {
            throw ValidationException::withMessages([
                'all_or_some' => "عدد المستلمين ({$totalRecipients}) يتجاوز الحد الآمن للإرسال الجماعي ({$bulkLimit}) في الدفعة الواحدة.",
            ]);
        }
    }

    private function resolveBulkProtectionConfig(string $channel): array
    {
        if ($channel === 'whatsapp') {
            $settings = CommunicationSettings::whatsapp();
            $keyword = (string) ($settings['bulk_confirmation_keyword'] ?? 'SEND_ALL_WHATSAPP');
            $limit = max(1, (int) ($settings['bulk_send_limit'] ?? 200));

            return [$keyword, $limit];
        }

        $settings = CommunicationSettings::sms();
        $keyword = (string) ($settings['bulk_confirmation_keyword'] ?? 'SEND_ALL_SMS');
        $limit = max(1, (int) ($settings['bulk_send_limit'] ?? 200));

        return [$keyword, $limit];
    }

    private function storeSmsLog(?User $user, ?SmsTemplate $template, string $message, ?string $externalPhone = null): void
    {
        $titleTranslations = [];
        $descriptionTranslations = [];
        $title = $template?->title ?? 'رسالة SMS بدون قالب';

        if ($externalPhone) {
            $title .= ' - ' . $externalPhone;
        }

        foreach (config('translatable.locales', ['ar']) as $locale) {
            $titleTranslations[$locale] = $title;
            $descriptionTranslations[$locale] = $message;
        }

        $this->model->create([
            'title' => $titleTranslations,
            'description' => $descriptionTranslations,
            'user_id' => $user?->id,
            'type' => 'sms',
        ]);
    }

    private function storeWhatsAppLog(User $user, string $purpose, string $message): void
    {
        $title = match ($purpose) {
            'verification' => 'واتساب - تحقق',
            'follow_up' => 'واتساب - متابعة',
            default => 'واتساب - إعلاني',
        };
        $summary = Str::limit($message, 320, '...');

        $titleTranslations = [];
        $descriptionTranslations = [];

        foreach (config('translatable.locales', ['ar']) as $locale) {
            $titleTranslations[$locale] = $title;
            $descriptionTranslations[$locale] = $summary;
        }

        $this->model->create([
            'title' => $titleTranslations,
            'description' => $descriptionTranslations,
            'user_id' => $user->id,
            'type' => 'whatsapp',
        ]);
    }

    private function isSuccessfulSmsResponse($response): bool
    {
        if ($response === false || $response === null) {
            return false;
        }

        $raw = trim((string) $response);
        if ($raw === '') {
            return false;
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return true;
        }

        if (isset($decoded['success'])) {
            return (bool) $decoded['success'];
        }

        if (isset($decoded['status'])) {
            return in_array(strtolower((string) $decoded['status']), ['ok', 'success'], true);
        }

        if (isset($decoded['code'])) {
            return in_array((string) $decoded['code'], ['1', '200'], true);
        }

        return true;
    }

    private function isSuccessfulWhatsAppResponse(array $response): bool
    {
        return (bool) ($response['success'] ?? false);
    }

    private function storeGeneralNotifications(array $data, Request $request): array
    {
        $usersIDs = [];
        $data['type'] = NotificationActionEnum::General->value;

        if ($request->all_or_some == 'all') {
            $data['user_id'] = null;
            $this->store($data);
            $usersIDs = User::where('is_blocked', false)->pluck('id')->toArray();
        } elseif ($request->all_or_some == 'some') {
            $usersIDs = (array) $request->user_id;
            foreach ($usersIDs as $user_id) {
                $data['user_id'] = $user_id;
                $this->store($data);
            }
        }

        foreach ($usersIDs as $to_user_id) {
            $data['user_id'] = $to_user_id;
            $this->sendFCMNotification($request->title, $request->description, $to_user_id, $data);
        }

        return [
            'type' => 'normal',
            'total_count' => count($usersIDs),
        ];
    }
}
