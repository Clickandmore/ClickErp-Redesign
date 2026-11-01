<?php

namespace App\Services\ingaz;

use App\Models\ingaz\Complaint;
use App\Services\AdminNotificationService;

/** تغيير حالة الشكوى: مصدر واحد لشاشة الشكاوى وأداة المساعد الذكي. */
class ComplaintStatusService
{
    public const STATUSES = ['pending', 'in_progress', 'resolved', 'rejected'];

    private const LABELS = [
        'pending' => 'قيد الانتظار',
        'in_progress' => 'قيد المعالجة',
        'resolved' => 'تم الحل',
        'rejected' => 'مرفوضة',
    ];

    public function change(Complaint $complaint, string $status): void
    {
        $previousStatus = $complaint->status;
        $complaint->status = $status;

        if ($status === 'resolved') {
            $complaint->resolved_at = now();
            $complaint->resolved_by = auth('admin')->id();

            // إرسال رسالة التقييم إذا كان مفعلاً وهناك رقم هاتف
            $smsEnabled = env('SMS_FEEDBACK_ENABLED', false);
            if ($smsEnabled && $previousStatus != 'resolved' && $complaint->complainant_phone) {
                try {
                    \App\Http\Controllers\admin\ComplaintFeedbackController::sendFeedbackSMS($complaint);
                } catch (\Exception $e) {
                    \Log::error('Failed to send feedback SMS: ' . $e->getMessage());
                }
            }
        }

        $complaint->save();

        if ((string) $previousStatus === (string) $complaint->status) {
            return;
        }

        app(AdminNotificationService::class)->sendToAdmins(
            [$complaint->assigned_to],
            'تم تحديث حالة الشكوى #' . str_pad((string) $complaint->id, 6, '0', STR_PAD_LEFT),
            'تغيرت حالة الشكوى من "' . $this->label($previousStatus) . '" إلى "' . $this->label($complaint->status) . '".',
            'complaint_status_changed',
            route('complaints.show', $complaint->id),
            [
                'model' => Complaint::class,
                'model_id' => $complaint->id,
                'old_status' => $previousStatus,
                'new_status' => $complaint->status,
            ]
        );
    }

    private function label(?string $status): string
    {
        return self::LABELS[$status] ?? ($status ?: 'غير محدد');
    }
}
