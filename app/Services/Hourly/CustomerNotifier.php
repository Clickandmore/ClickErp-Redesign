<?php

namespace App\Services\Hourly;

use App\Models\auth\Admin;
use App\Models\CommunicationLog;
use App\Models\ingaz\User;
use App\Services\CommunicationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * «إرسال إشعار» من ملف العميل عبر خدمات الإرسال الموجودة:
 * التطبيق والرسائل النصية عبر CommunicationService (وتُسجَّل في communication_logs)، والبريد بـ Mail::raw مع سطر في نفس السجل.
 * النص يُكتب بلغة العميل المفضلة (تظهر للموظف في النافذة)؛ لا ترجمة آلية.
 */
class CustomerNotifier
{
    public const CHANNELS = ['app', 'sms', 'email'];

    public function __construct(private CommunicationService $communication, private CustomerDirectory $directory) {}

    /** @return array{status:string,message:string} */
    public function send(User $user, string $channel, string $title, string $body, Admin $admin): array
    {
        if ($channel === 'email') {
            $result = $this->email($user, $title, $body, $admin);
        } else {
            $summary = $this->communication->dispatch(['type' => $channel === 'app' ? 'notification' : 'sms', 'all_or_some' => 'some', 'user_id' => [$user->id],
                'title' => ['ar' => $title], 'description' => ['ar' => $body]], $admin);
            $result = ($summary['sent'] ?? 0) > 0 ? ['status' => 'sent'] : ['status' => 'failed', 'error' => ($summary['skipped'] ?? 0) ? __('hourly_customers.notify.skipped') : __('hourly_customers.notify.failed')];
        }
        $this->directory->log($user, 'notification_sent', ['channel' => $channel, 'title' => $title, 'status' => $result['status']], $admin->id);

        return ['status' => $result['status'], 'message' => $result['status'] === 'sent' ? __('hourly_customers.notify.sent') : ($result['error'] ?? __('hourly_customers.notify.failed'))];
    }

    private function email(User $user, string $title, string $body, Admin $admin): array
    {
        $status = 'failed';
        $error = null;
        if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            $error = __('hourly_customers.notify.no_email');
        } else {
            try {
                Mail::raw($body, fn ($mail) => $mail->to($user->email)->subject($title));
                $status = 'sent';
            } catch (\Throwable $e) {
                report($e);
                $error = __('hourly_customers.notify.failed');
            }
        }
        CommunicationLog::create(['batch_id' => (string) Str::uuid(), 'channel' => 'email', 'recipient_type' => 'some', 'user_id' => $user->id, 'admin_id' => $admin->id,
            'recipient_name' => $user->name, 'recipient_phone' => $user->phone, 'title' => $title, 'message' => $body, 'provider' => 'mail', 'status' => $status,
            'response' => $error, 'meta' => ['source' => 'hourly_customer_profile', 'email' => $user->email], 'sent_at' => now()]);

        return ['status' => $status, 'error' => $error];
    }
}
