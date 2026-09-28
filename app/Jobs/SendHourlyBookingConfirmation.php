<?php

namespace App\Jobs;

use App\Models\Hourly\HourlyRentalOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{DB, Mail};

class SendHourlyBookingConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;
    public function __construct(public int $orderId) {}
    public function handle(): void
    {
        // Commit each channel independently so a mail failure cannot repeat a delivered app notification.
        foreach (['app','email'] as $channel) DB::transaction(function () use ($channel) {
            $order=HourlyRentalOrder::whereKey($this->orderId)->lockForUpdate()->firstOrFail();
            $meta=$order->booking_metadata ?? [];
            $message='تم تسجيل طلب الخدمة '.$order->order_number.'، عدد الزيارات: '.$order->visits_total.'، الإجمالي: '.$order->total_amount.' ر.س.';
            if ($channel==='app' && !empty($meta['notify_app']) && empty($meta['app_sent_at'])) {
                $notification=\App\Models\ingaz\Notification::create(['user_id'=>$order->user_id,'type'=>'hourly_booking','title'=>['ar'=>'تأكيد حجز الخدمة','en'=>'Service booking confirmation'],'description'=>['ar'=>$message,'en'=>$message]]);
                // طلب بزيارة واحدة يفتح تفاصيل الزيارة مباشرة؛ الطلب متعدد الزيارات يفتح الطلب.
                $payload=['type'=>'hourly_booking','notification_id'=>(string)$notification->id,'hourly_order_id'=>(string)$order->id];
                if((int)$order->visits_total===1 && $visitId=$order->visits()->value('id'))$payload['hourly_visit_id']=(string)$visitId;
                app(\App\Services\Firebase\FirebasePushService::class)->sendToUser($order->user_id,'تأكيد حجز الخدمة',$message,$payload);
                $meta['app_sent_at']=now()->toIso8601String();
            }
            if ($channel==='email' && !empty($meta['notify_email']) && empty($meta['email_sent_at']) && $order->user->email) {
                Mail::raw($message,fn ($mail)=>$mail->to($order->user->email)->subject('تأكيد حجز الخدمة'));
                $meta['email_sent_at']=now()->toIso8601String();
            }
            $order->update(['booking_metadata'=>$meta]);
        });
    }
}
