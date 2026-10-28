<?php

namespace App\Services;

use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Traits\Firebase;
use App\Models\auth\Admin;
use App\Models\CommunicationLog;
use App\Models\ingaz\Notification;
use App\Models\ingaz\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CommunicationService
{
    use Firebase;

    /**
     * المتغيرات التي يمكن حلّها فعلاً في الإرسال الجماعي: بيانات المستلم وبيانات
     * الشركة فقط. متغيرات القوالب المرتبطة بعقد أو طلب بعينه (مثل contract_number)
     * لا معنى لها هنا، ولذلك يمنعها StoreNotificationRequest قبل الوصول لهذه الخدمة
     * بدل أن تصل حرفيةً إلى آلاف العملاء.
     */
    public const SUPPORTED_VARIABLES = ['client_name', 'name', 'client_phone', 'phone', 'company_name'];

    /**
     * أسماء المتغيرات الموجودة في النص والتي لا نستطيع حلّها.
     *
     * @return array<int, string>
     */
    public static function unsupportedVariables(string $text): array
    {
        preg_match_all('/\{([A-Za-z0-9_]+)\}/', $text, $matches);

        return collect($matches[1] ?? [])
            ->reject(fn ($variable) => in_array($variable, self::SUPPORTED_VARIABLES, true))
            ->unique()
            ->values()
            ->all();
    }

    public function __construct(
        protected UltraMsgWhatsAppService $whatsAppService,
        protected TeqnyatSmsService $smsService
    ) {
    }

    public function dispatch(array $payload, ?Admin $admin = null): array
    {
        $channel = $this->normalizeChannel((string) ($payload['type'] ?? 'notification'));
        $selection = (string) ($payload['all_or_some'] ?? 'all');
        $rawTitle = trim((string) data_get($payload, 'title.ar'));
        $rawBody = trim((string) data_get($payload, 'description.ar'));
        $recipients = $this->resolveRecipients($payload);
        $batchId = (string) Str::uuid();
        $batchLimit = (int) ($payload['batch_limit'] ?? 0);
        $batchOffset = max(0, (int) ($payload['batch_offset'] ?? 0));

        $summary = [
            'batch_id' => $batchId,
            'channel' => $channel,
            'selection' => $selection,
            'batch_limit' => $batchLimit,
            'batch_offset' => $batchOffset,
            'total' => $recipients->count(),
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'logs' => [],
        ];

        foreach ($recipients as $recipient) {
            // الاستبدال يتم لكل مستلم على حدة، فاسم العميل يختلف من رسالة لأخرى.
            $title = $this->interpolate($rawTitle, $recipient);
            $body = $this->interpolate($rawBody, $recipient);
            $message = $this->composeMessage($title, $body);

            $result = $this->sendToRecipient($channel, $title, $body, $message, $recipient);

            $attributes = [
                'batch_id' => $batchId,
                'channel' => $channel,
                'recipient_type' => $selection,
                'user_id' => $recipient['user']?->id,
                'admin_id' => $admin?->id,
                'notification_id' => $result['notification_id'] ?? null,
                'recipient_name' => $recipient['name'],
                'recipient_phone' => $recipient['phone'],
                'title' => $title,
                'message' => $body,
                'provider' => $result['provider'],
                'status' => $result['status'],
                'response' => $this->stringifyResponse($result['response'] ?? $result),
                'meta' => array_merge($result['response']['sender_meta'] ?? [], [
                    'selection' => $selection,
                    'type' => $channel,
                    'message_preview' => $message,
                    'batch_limit' => $batchLimit ?: null,
                    'batch_offset' => $batchOffset ?: 0,
                ], $this->deliveryMeta($result)),
                'sent_at' => now(),
            ];

            // A failed internal notification has no row id; it is logged as a new line instead of breaking the batch.
            $log = $channel === 'notification' && filled($result['notification_id'] ?? null)
                ? CommunicationLog::updateOrCreate(
                    [
                        'channel' => $channel,
                        'notification_id' => $result['notification_id'],
                    ],
                    $attributes
                )
                : CommunicationLog::create($attributes);

            $summary['logs'][] = $log->id;

            if ($result['status'] === 'sent') {
                $summary['sent']++;
            } elseif ($result['status'] === 'failed') {
                $summary['failed']++;
            } else {
                $summary['skipped']++;
            }
        }

        $summary['message'] = $this->summaryMessage($channel, $summary);

        return $summary;
    }

    protected function sendToRecipient(
        string $channel,
        string $title,
        string $body,
        string $message,
        array $recipient
    ): array {
        return match ($channel) {
            'sms' => $this->sendSms($recipient['phone'], $message),
            'whatsapp' => $this->sendWhatsApp($recipient['phone'], $message),
            default => $this->sendNotification($recipient['user'], $title, $body),
        };
    }

    protected function sendSms(?string $phone, string $message): array
    {
        $result = $this->smsService->sendMessage($phone, $message, [
            'should_log' => false,
            'source' => 'message_center',
        ]);

        return [
            'provider' => 'teqnyat',
            'status' => $result['status'] ?? 'failed',
            'response' => $result,
        ];
    }

    protected function sendWhatsApp(?string $phone, string $message): array
    {
        $result = $this->whatsAppService->sendChatMessage($phone, $message, [
            'should_log' => false,
            'source' => 'message_center',
        ]);

        return [
            'provider' => 'ultramsg',
            'status' => $result['status'] ?? 'failed',
            'response' => $result,
        ];
    }

    protected function sendNotification(?User $user, string $title, string $body): array
    {
        if (! $user) {
            return [
                'provider' => 'internal',
                'status' => 'skipped',
                'response' => ['message' => 'لا يوجد مستخدم مرتبط بالإشعار الداخلي.'],
            ];
        }

        try {
            $notification = Notification::create([
                'user_id' => $user->id,
                'order_id' => null,
                'type' => NotificationActionEnum::General->value,
                'title' => $title,
                'description' => $body,
            ]);

            $response = $this->sendFCMNotification($title, $body, $user->id, [
                'type' => $notification->type,
                'notification_id' => $notification->id,
            ]);

            // الحالة تُشتق من نتيجة FCM لا تُفترض: إشعار لم يصل أي جهاز كان
            // يُسجَّل «تم الإرسال»، فيقرأ المشرف نجاحاً لم يحدث.
            return [
                'provider' => 'internal',
                'status' => self::deliveryStatus($response),
                'notification_id' => $notification->id,
                'response' => ['fcm' => $response],
            ];
        } catch (\Throwable $e) {
            return [
                'provider' => 'internal',
                'status' => 'failed',
                'response' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * حالة التسليم الحقيقية كما ترويها نتيجة FCM لا كما نفترضها.
     * معرّفة هنا لأن trait Firebase في هذا النظام لا يحملها، وهو جزء من مسار الجوال فلا نعدّله.
     *
     * @param array $result ناتج FirebasePushResult::toArray()
     */
    protected static function deliveryStatus(array $result): string
    {
        if (!empty($result['skipped'])) {
            return 'skipped';
        }

        return ((int) ($result['success_count'] ?? 0)) > 0 ? 'sent' : 'failed';
    }

    /**
     * حصيلة التسليم لعرضها في سجل الرسائل. هذا الـ meta يُكتب فوق ما سجّله
     * trait Firebase، فلا بد أن يحمل نفس المفاتيح وإلا ضاعت التفاصيل.
     *
     * @return array<string, mixed>
     */
    protected function deliveryMeta(array $result): array
    {
        $fcm = $result['response']['fcm'] ?? null;

        if (!is_array($fcm)) {
            return [];
        }

        return array_filter([
            'devices_reached' => (int) ($fcm['success_count'] ?? 0),
            'devices_failed' => (int) ($fcm['failure_count'] ?? 0),
            'delivery_note' => $fcm['failures'][0]['reason'] ?? null,
        ], fn ($value) => !is_null($value));
    }

    protected function resolveRecipients(array $payload): Collection
    {
        $selection = (string) ($payload['all_or_some'] ?? 'all');

        if ($selection === 'manual') {
            return collect($this->parseManualNumbers((string) ($payload['manual_numbers'] ?? '')))
                ->map(fn ($phone) => [
                    'user' => null,
                    'name' => 'رقم يدوي',
                    'phone' => $phone,
                ]);
        }

        $query = User::query()
            ->where('is_blocked', false)
            ->select('id', 'name', 'phone')
            ->orderBy('id');

        if ($selection === 'some') {
            $query->whereIn('id', $payload['user_id'] ?? []);
        } else {
            $batchOffset = max(0, (int) ($payload['batch_offset'] ?? 0));
            $maxBatchLimit = (int) config('services.message_center.max_batch_size', 250);
            $defaultBatchLimit = (int) config('services.message_center.default_batch_size', 100);
            $batchLimit = (int) ($payload['batch_limit'] ?? $defaultBatchLimit);
            $batchLimit = min(max($batchLimit, 1), $maxBatchLimit);

            $query->offset($batchOffset)->limit($batchLimit);
        }

        return $query->get()->map(fn ($user) => [
            'user' => $user,
            'name' => $user->name,
            'phone' => $user->phone,
        ]);
    }

    protected function parseManualNumbers(string $value): array
    {
        return collect(preg_split('/[\s,;\r\n]+/', $value))
            ->filter()
            ->map(fn ($phone) => preg_replace('/[^\d+]/', '', $phone))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * يستبدل متغيرات القالب بقيم هذا المستلم.
     *
     * الاسم المجهول (رقم يدوي، أو حساب بلا اسم) يُستبدل بفراغ لا بصيغة عامة:
     * قوالبنا تبدأ عادةً بـ «عميلنا العزيز {client_name}»، فوضع صيغة عامة مكان
     * الاسم كان سيكرّرها مرتين. نظّفنا بعدها المسافات وعلامات الترقيم المعلّقة.
     */
    protected function interpolate(string $text, array $recipient): string
    {
        if ($text === '' || !str_contains($text, '{')) {
            return $text;
        }

        $user = $recipient['user'] ?? null;
        $name = trim((string) ($user?->name ?? ''));
        $phone = trim((string) ($recipient['phone'] ?? ''));

        $values = [
            'client_name' => $name,
            'name' => $name,
            'client_phone' => $phone,
            'phone' => $phone,
            'company_name' => (string) config('services.message_center.company_name'),
        ];

        foreach ($values as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $this->tidy($text);
    }

    /**
     * يزيل أثر المتغير الفارغ: مسافة مزدوجة، أو مسافة قبل فاصلة/نقطة.
     */
    protected function tidy(string $text): string
    {
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text);
        $text = preg_replace('/ +([،,.:!؟])/u', '$1', $text);
        $text = preg_replace('/[ \t]+$/mu', '', $text);

        return trim((string) $text);
    }

    protected function composeMessage(string $title, string $body): string
    {
        return trim(collect([$title, $body])->filter()->implode(PHP_EOL));
    }

    protected function normalizeChannel(string $channel): string
    {
        return $channel === 'normal' ? 'notification' : $channel;
    }

    protected function stringifyResponse($response): ?string
    {
        if (blank($response)) {
            return null;
        }

        if (is_string($response)) {
            return $response;
        }

        return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function summaryMessage(string $channel, array $summary): string
    {
        $channelLabel = match ($channel) {
            'sms' => 'الرسائل النصية',
            'whatsapp' => 'رسائل واتساب',
            default => 'الإشعارات',
        };

        $message = sprintf(
            'تمت معالجة %d من %s. نجح: %d، فشل: %d، تم تخطي: %d.',
            $summary['total'],
            $channelLabel,
            $summary['sent'],
            $summary['failed'],
            $summary['skipped']
        );

        if (($summary['selection'] ?? null) === 'all') {
            $message .= sprintf(
                ' هذه دفعة جزئية بعد تخطي %d مستخدم.',
                (int) ($summary['batch_offset'] ?? 0)
            );
        }

        return $message;
    }
}
