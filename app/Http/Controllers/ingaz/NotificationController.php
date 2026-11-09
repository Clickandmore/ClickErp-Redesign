<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\NotificationAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\StoreNotificationRequest;
use App\Models\ingaz\User;
use App\Models\SmsTemplate;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Support\CommunicationSettings;

class NotificationController extends Controller
{
    public $folderPath = 'admin.ingaz.notifications';
    public $mainRoute = 'notifications';

    public function index()
    {
        $appUsers = $this->eligibleUsers();
        $smsTemplates = SmsTemplate::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'name', 'title', 'message', 'variables']);

        $totalEligibleUsers = $appUsers->count();
        $smsSettings = CommunicationSettings::sms();
        $smsConfig = [
            'bulk_limit' => max(1, (int) ($smsSettings['bulk_send_limit'] ?? 200)),
            'bulk_keyword' => (string) ($smsSettings['bulk_confirmation_keyword'] ?? 'SEND_ALL_SMS'),
            'sender' => (string) ($smsSettings['sender'] ?? ''),
            'advertising_sender' => (string) ($smsSettings['advertising_sender'] ?? ''),
            'verification_sender' => (string) ($smsSettings['verification_sender'] ?? ''),
        ];

        return view($this->folderPath . '.index', compact('appUsers', 'smsTemplates', 'totalEligibleUsers', 'smsConfig'));
    }

    public function indexWhatsApp(WhatsAppMessagingService $whatsAppService)
    {
        $appUsers = $this->eligibleUsers();
        $totalEligibleUsers = $appUsers->count();
        $whatsAppSettings = CommunicationSettings::whatsapp();
        $chatwootTemplates = [];
        $chatwootTemplateError = null;
        if ($whatsAppService->driver() === 'clickchat') {
            // Same shape as the Chatwoot list so the page's template picker works for both.
            $chatwootTemplates = array_map(fn (array $template) => [
                'name' => $template['name'], 'language' => $template['language'], 'category' => $template['category'],
                'components' => [['type' => 'BODY', 'text' => $template['body']]],
            ], app(\App\Services\WhatsApp\ClickChatWhatsAppService::class)->templates());
        }
        if ($whatsAppService->driver() === 'chatwoot') {
            try {
                $chatwootTemplates = app(\App\Services\WhatsApp\ChatwootWhatsAppService::class)->templates();
            } catch (\Throwable $e) {
                $chatwootTemplateError = 'تعذر جلب القوالب. تحقق من الاتصال وإعدادات الصندوق ثم حدّث الصفحة.';
            }
        }

        $whatsappConfig = [
            'driver' => $whatsAppService->driver(),
            'provider_label' => $whatsAppService->providerLabel(),
            'configured' => $whatsAppService->isConfigured(),
            'missing' => $whatsAppService->missingConfigKeys(),
            'bulk_limit' => max(1, (int) ($whatsAppSettings['bulk_send_limit'] ?? 200)),
            'bulk_keyword' => (string) ($whatsAppSettings['bulk_confirmation_keyword'] ?? 'SEND_ALL_WHATSAPP'),
            'default_purpose' => (string) ($whatsAppSettings['default_purpose'] ?? 'verification'),
            'allow_advertising' => (bool) ($whatsAppSettings['allow_advertising'] ?? false),
            'webhook_verify_url' => route('api.webhooks.whatsapp.verify', [], false),
            'webhook_receive_url' => route('api.webhooks.whatsapp.receive', [], false),
            'senders' => $whatsAppService->availableSenders(),
            'default_sender_key' => $whatsAppService->defaultSenderKey(),
        ];

        return view($this->folderPath . '.whatsapp', compact('appUsers', 'totalEligibleUsers', 'whatsappConfig', 'chatwootTemplates', 'chatwootTemplateError'));
    }

    public function store(StoreNotificationRequest $request, NotificationAction $action)
    {
        return $this->dispatchByChannel($request, $action, 'sms');
    }

    public function storeWhatsApp(StoreNotificationRequest $request, NotificationAction $action)
    {
        return $this->dispatchByChannel($request, $action, 'whatsapp');
    }

    private function dispatchByChannel(StoreNotificationRequest $request, NotificationAction $action, string $channel)
    {
        if ((string) $request->input('type', $channel) !== $channel) {
            return jsonError('نوع القناة غير صحيح.', 422);
        }

        if ($channel === 'whatsapp' && !$request->filled('whatsapp_purpose')) {
            $request->merge([
                'whatsapp_purpose' => (string) (CommunicationSettings::whatsapp()['default_purpose'] ?? 'verification'),
            ]);
        }

        $result = $action->storeNotification($request->validated(), $request);

        if ($channel === 'whatsapp') {
            if (($result['driver'] ?? '') === 'chatwoot') {
                $message = "قبلت Widers {$result['sent_count']} طلب إرسال، وتعذر إرسال {$result['failed_count']}. قبول الطلب لا يؤكد وصول الرسالة للهاتف.";
                if (!empty($result['failure_reason'])) { $message .= ' السبب: '.$result['failure_reason']; }
                return ($result['sent_count'] ?? 0) > 0 ? jsonSuccess($result, $message, 201) : jsonError($message, 422);
            }
            $message = 'تم إرسال رسائل واتساب بنجاح.';
            if (($result['failed_count'] ?? 0) > 0) {
                $message = "تم إرسال {$result['sent_count']} رسالة واتساب وتعذر إرسال {$result['failed_count']} رسالة.";
                if (!empty($result['failure_reason'])) {
                    $message .= ' السبب: ' . $result['failure_reason'];
                }
                if ((int) ($result['sent_count'] ?? 0) === 0) {
                    return jsonError($message, 422);
                }
            } elseif (isset($result['sent_count'])) {
                $message = "تم إرسال {$result['sent_count']} رسالة واتساب بنجاح.";
            }

            return jsonSuccess($result, $message, 201);
        }

        $message = 'تم إرسال الرسائل النصية بنجاح.';
        if (($result['failed_count'] ?? 0) > 0) {
            $message = "تم إرسال {$result['sent_count']} رسالة وتعذر إرسال {$result['failed_count']} رسالة.";
        } elseif (isset($result['sent_count'])) {
            $message = "تم إرسال {$result['sent_count']} رسالة نصية بنجاح.";
        }

        return jsonSuccess($result, $message, 201);
    }

    private function eligibleUsers()
    {
        return User::query()
            ->where('is_blocked', false)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->select('name', 'id', 'phone')
            ->latest()
            ->get();
    }
}
