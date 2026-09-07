<?php

namespace App\Jobs;

use App\Models\AdminNotification;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Support\SmsGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Mail;

class DeliverAdminDispatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(public int $notificationId) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping('admin-dispatch:'.$this->notificationId))->dontRelease()->expireAfter(600)];
    }

    public function handle(WhatsAppMessagingService $whatsApp): void
    {
        $notification = AdminNotification::with('admin')->find($this->notificationId);
        if (!$notification || $notification->type !== 'admin_dispatch') {
            return;
        }
        $data = $notification->data;
        foreach ($data['delivery'] ?? [] as $channel => $status) {
            if ($status !== 'pending') {
                continue;
            }
            // Persist before contacting providers: a worker retry must not resend a channel.
            $data['delivery'][$channel] = 'processing';
            $notification->update(['data' => $data]);
            try {
                $admin = $notification->admin;
                if (!$admin || $admin->status === 'suspended') {
                    throw new \RuntimeException('Recipient unavailable');
                }
                if ($channel === 'email') {
                    if (!filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                        throw new \RuntimeException('Recipient email unavailable');
                    }
                    Mail::raw($notification->message, fn ($mail) => $mail->to($admin->email)->subject($notification->title));
                } elseif ($channel === 'sms') {
                    $phone = $admin->formatted_contact_phone ?: $admin->formatted_whatsapp_phone;
                    $result = $phone ? SmsGateway::send($phone, $notification->title."\n".$notification->message) : null;
                    $response = json_decode($result ?? '', true);
                    if (!$response || !in_array((string) ($response['code'] ?? $response['statusCode'] ?? ''), ['1', 'M0000', '200', '201'], true)) {
                        throw new \RuntimeException('SMS provider did not accept the message');
                    }
                } elseif ($channel === 'whatsapp') {
                    $phone = $admin->whatsapp_phone ?: $admin->contact_phone;
                    $result = $phone ? $whatsApp->sendText($phone, $notification->title."\n".$notification->message, ['source' => 'admin_dispatch']) : [];
                    if (!($result['success'] ?? false)) {
                        throw new \RuntimeException('WhatsApp provider did not accept the message');
                    }
                } else {
                    throw new \RuntimeException('Unsupported delivery channel');
                }
                $data['delivery'][$channel] = 'accepted';
            } catch (\Throwable $exception) {
                $data['delivery'][$channel] = 'failed';
                report($exception);
            }
            $notification->update(['data' => $data]);
        }
    }
}
