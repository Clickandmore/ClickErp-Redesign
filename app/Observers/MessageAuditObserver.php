<?php

namespace App\Observers;

use App\Models\AdminNotification;
use App\Support\OutboundMessageRecorder;

class MessageAuditObserver
{
    public function created($notification): void
    {
        try { $this->record($notification); }
        catch (\Throwable $e) { logger()->error('In-app message audit failed.', ['exception_class' => get_class($e)]); }
    }

    private function record($notification): void
    {
        // Legacy SMS/WhatsApp notification rows are presentation history, not a new send.
        if (!$notification instanceof AdminNotification && in_array($notification->type, ['sms', 'whatsapp'], true)) return;
        $isAdmin = $notification instanceof AdminNotification;
        $recipient = $isAdmin ? $notification->admin : $notification->user;
        $body = $isAdmin ? $notification->message : $notification->description;
        $title = $notification->title;
        if (is_array($body)) $body = $body['ar'] ?? reset($body);
        if (is_array($title)) $title = $title['ar'] ?? reset($title);
        $log = OutboundMessageRecorder::begin('notification', ($isAdmin ? 'admin:' : 'client:') . ($isAdmin ? $notification->admin_id : $notification->user_id),
            $title . "\n" . $body, 'database', [
                'recipient_name' => $recipient?->name,
                'branch_id' => $recipient?->branch_id,
                'notification_id' => $notification->id,
                'user_id' => $isAdmin ? null : $notification->user_id,
            ]);
        OutboundMessageRecorder::finish($log, 'delivered');
    }

    public function updated($notification): void
    {
        if (!$notification instanceof AdminNotification || !$notification->wasChanged('read_at') || !$notification->read_at) return;
        try {
            \App\Models\OutboundMessageLog::withoutBranchScope()->where('channel', 'notification')->where('provider', 'database')
                ->where('recipient', 'admin:' . $notification->admin_id)->where('context->notification_id', $notification->id)
                ->update(['status' => 'read', 'read_at' => $notification->read_at]);
        } catch (\Throwable $e) { logger()->error('In-app read audit failed.', ['exception_class' => get_class($e)]); }
    }
}
