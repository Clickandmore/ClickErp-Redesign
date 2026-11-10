<?php

namespace App\Services;

use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Traits\Firebase;
use App\Models\CommunicationLog;
use App\Models\SmsTemplate;
use App\Models\ingaz\Notification;

class TemplateMessageService
{
    use Firebase;

    public function __construct(
        protected TeqnyatSmsService $smsService,
        protected UltraMsgWhatsAppService $whatsAppService
    ) {
    }

    public function renderByName(string $name, array $variables = [], array $fallback = []): array
    {
        $template = SmsTemplate::query()
            ->where('name', $name)
            ->first();

        $rawMessage = $template?->message ?? (string) ($fallback['message'] ?? '');
        $channel = $template?->channel ?? (string) ($fallback['channel'] ?? SmsTemplate::CHANNEL_SMS);
        $title = $template?->title ?? ($fallback['title'] ?? null);

        return [
            'template' => $template,
            'template_id' => $template?->id,
            'template_name' => $template?->name ?? $name,
            'template_exists' => (bool) $template,
            'template_active' => $template ? (bool) $template->is_active : true,
            'channel' => $channel,
            'title' => $title,
            'message' => $this->interpolateMessage($rawMessage, $variables),
        ];
    }

    public function sendByName(string $name, array $variables = [], array $recipient = [], array $options = []): array
    {
        $payload = $this->renderByName($name, $variables, $options['fallback'] ?? []);

        if ($payload['template_exists'] && ! $payload['template_active']) {
            return $this->decorateResult([
                'success' => false,
                'status' => 'skipped',
                'message' => 'تم تخطي الإرسال لأن القالب غير نشط.',
            ], $payload);
        }

        if (blank($payload['message'])) {
            return $this->decorateResult([
                'success' => false,
                'status' => 'failed',
                'message' => 'نص القالب فارغ ولا يمكن إرساله.',
            ], $payload);
        }

        $context = $this->buildProviderContext($payload, $recipient, $options);
        $context['template_id'] = $payload['template_id'];
        $context['template_variables'] = $variables;

        $result = match ($payload['channel']) {
            SmsTemplate::CHANNEL_WHATSAPP => $this->whatsAppService->sendChatMessage($recipient['phone'] ?? null, $payload['message'], $context),
            SmsTemplate::CHANNEL_NOTIFICATION => $this->sendNotification($payload, $recipient, $context, $options),
            default => $this->smsService->sendMessage($recipient['phone'] ?? null, $payload['message'], $context),
        };

        return $this->decorateResult($result, $payload);
    }

    protected function sendNotification(array $payload, array $recipient, array $context, array $options = []): array
    {
        $userId = (int) ($recipient['user_id'] ?? data_get($recipient, 'user.id'));

        if (! $userId) {
            return [
                'success' => false,
                'status' => 'skipped',
                'message' => 'تعذر إرسال الإشعار الداخلي لعدم وجود مستخدم مرتبط.',
            ];
        }

        $title = trim((string) ($payload['title'] ?? $options['notification_title'] ?? 'إشعار جديد'));

        try {
            $notification = Notification::create([
                'user_id' => $userId,
                'order_id' => $options['order_id'] ?? null,
                'type' => NotificationActionEnum::General->value,
                'title' => $title,
                'description' => $payload['message'],
            ]);

            $response = $this->sendFCMNotification($title, $payload['message'], $userId, [
                'type' => $notification->type,
                'notification_id' => $notification->id,
            ]);

            $this->augmentNotificationLogMeta($notification->id, $context['meta'] ?? []);

            return [
                'success' => true,
                'status' => 'sent',
                'message' => 'تم إرسال الإشعار الداخلي بنجاح.',
                'notification_id' => $notification->id,
                'response' => ['fcm' => $response],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'تعذر إرسال الإشعار الداخلي.',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function augmentNotificationLogMeta(int $notificationId, array $meta = []): void
    {
        $log = CommunicationLog::query()
            ->where('channel', SmsTemplate::CHANNEL_NOTIFICATION)
            ->where('notification_id', $notificationId)
            ->latest('id')
            ->first();

        if (! $log) {
            return;
        }

        $log->update([
            'meta' => array_merge((array) $log->meta, $meta),
        ]);
    }

    protected function buildProviderContext(array $payload, array $recipient, array $options = []): array
    {
        $meta = array_filter(array_merge($options['meta'] ?? [], [
            'template_id' => $payload['template_id'],
            'template_name' => $payload['template_name'],
            'template_channel' => $payload['channel'],
            'template_title' => $payload['title'],
            'source' => $options['source'] ?? $payload['template_name'],
        ]), fn ($value) => filled($value));

        return [
            'batch_id' => $options['batch_id'] ?? null,
            'recipient_type' => $options['recipient_type'] ?? 'template',
            'user_id' => $recipient['user_id'] ?? data_get($recipient, 'user.id'),
            'admin_id' => $options['admin_id'] ?? auth('admin')->id(),
            'notification_id' => $options['notification_id'] ?? null,
            'recipient_name' => $recipient['name'] ?? data_get($recipient, 'user.name'),
            'recipient_phone' => $recipient['phone'] ?? data_get($recipient, 'user.phone'),
            'title' => $options['title'] ?? $payload['title'],
            'message' => $payload['message'],
            'source' => $options['source'] ?? $payload['template_name'],
            'should_log' => $options['should_log'] ?? true,
            'whatsapp_account' => $options['whatsapp_account'] ?? 'default',
            'meta' => $meta,
        ];
    }

    protected function decorateResult(array $result, array $payload): array
    {
        return array_merge($result, [
            'channel' => $payload['channel'],
            'template_id' => $payload['template_id'],
            'template_name' => $payload['template_name'],
            'template_title' => $payload['title'],
            'resolved_message' => $payload['message'],
        ]);
    }

    protected function interpolateMessage(string $message, array $variables = []): string
    {
        foreach ($variables as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $message = str_replace('{' . $key . '}', (string) ($value ?? ''), $message);
            }
        }

        return trim($message);
    }
}
