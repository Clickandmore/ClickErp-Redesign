<?php

namespace App\Services\Complaints;

use App\Models\ingaz\Complaint;
use App\Services\AdminNotificationService;
use App\Support\SmsGateway;
use Illuminate\Support\Facades\Mail;

class ComplaintAssignmentNotifier
{
    public function send(Complaint $complaint, array $channels, ?string $notes = null): array
    {
        $admin = $complaint->assignedTo;
        $title = 'إسناد الشكوى #' . $complaint->id;
        $message = $title . ': ' . $complaint->subject . "\n" . route('complaints.show', $complaint->id);
        if ($notes) {
            $message .= "\n" . $notes;
        }
        $results = [];
        foreach (array_unique($channels) as $channel) {
            try {
                if (!$admin) {
                    throw new \RuntimeException('الموظف غير موجود');
                }
                if ($channel === 'database') {
                    $sent = app(AdminNotificationService::class)->sendToAdmin($admin->id, $title, $message,
                        'complaint_assigned', route('complaints.show', $complaint->id), ['complaint_id' => $complaint->id], null, false);
                    if (!$sent) {
                        throw new \RuntimeException('تعذر إنشاء الإشعار');
                    }
                    $results[$channel] = 'تم إنشاء الإشعار';
                } elseif ($channel === 'email') {
                    if (!filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                        throw new \RuntimeException('لا يوجد بريد إلكتروني صالح للموظف');
                    }
                    Mail::raw($message, fn ($mail) => $mail->to($admin->email)->subject($title));
                    $results[$channel] = 'تم تسليم البريد لخدمة الإرسال';
                } elseif ($channel === 'sms') {
                    if (!$admin->contact_phone) {
                        throw new \RuntimeException('لا يوجد رقم جوال للموظف');
                    }
                    $response = SmsGateway::send(formatSaudiPhone($admin->contact_phone), $message);
                    $payload = json_decode((string) $response, true);
                    $code = (string) ($payload['code'] ?? $payload['statusCode'] ?? '');
                    if (!$response || !in_array($code, ['1', 'M0000', '200', '201'], true)) {
                        throw new \RuntimeException('لم تؤكد بوابة الرسائل قبول الرسالة');
                    }
                    $results[$channel] = 'قبلت بوابة الرسائل طلب الإرسال';
                }
            } catch (\Throwable $e) {
                report($e);
                $results[$channel] = 'تعذر الإرسال؛ تحقق من بيانات الموظف وإعدادات القناة';
            }
        }
        activity()->performedOn($complaint)->causedBy(auth('admin')->user())
            ->withProperties(['notification_results' => $results, 'notes' => $notes, 'assigned_to' => $admin?->name])
            ->log('إخطار الموظف عند الإسناد');
        return $results;
    }
}
