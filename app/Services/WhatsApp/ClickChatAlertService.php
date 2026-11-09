<?php

namespace App\Services\WhatsApp;

use App\Services\AdminNotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tells the admins chosen in settings when WhatsApp sending through Click Live Chat
 * stops working. Each kind of problem is announced at most once every six hours.
 */
class ClickChatAlertService
{
    private const QUIET_HOURS = 6;

    public function credentialsRejected(string $detail = ''): void
    {
        $this->notify('credentials', 'واتساب متوقف: مفتاح Click Live Chat مرفوض',
            'رفضت المنصة مفتاح API، فلن تُرسل رسائل واتساب حتى يُصحَّح. ' . $detail);
    }

    public function platformUnreachable(): void
    {
        $this->notify('unreachable', 'واتساب: تعذر الوصول إلى منصة Click Live Chat',
            'لم تستجب المنصة لفحص الاتصال. الرسائل المؤقتة ستُعاد تلقائيًا، وتحقق من حالة المنصة.');
    }

    /** @param list<array{id:int,name:string,status:string}> $channels numbers that are in use but not active */
    public function numbersDisconnected(array $channels): void
    {
        $names = implode('، ', array_map(fn ($c) => $c['name'] . ' (' . $c['status'] . ')', $channels));
        $this->notify('disconnected:' . implode(',', array_column($channels, 'id')), 'واتساب: رقم مرسِل غير متصل',
            'هذه الأرقام المستخدمة في الإرسال غير نشطة في المنصة: ' . $names . '. أعد ربطها من المنصة.');
    }

    private function notify(string $kind, string $title, string $message): void
    {
        Log::warning('Click Live Chat alert: ' . $title);
        if (!Cache::add('clickchat-alert:' . $kind, 1, now()->addHours(self::QUIET_HOURS))) {
            return;
        }

        $admins = (array) config('services.whatsapp.clickchat.alert_admin_ids', []);
        if ($admins === []) {
            return;
        }

        try {
            app(AdminNotificationService::class)->sendToAdmins($admins, $title, trim($message), 'whatsapp_clickchat',
                route('notifications.settings') . '#whatsapp-settings', [], null, false);
        } catch (Throwable $e) {
            Log::error('Click Live Chat alert could not be sent', ['exception' => get_class($e)]);
        }
    }
}
