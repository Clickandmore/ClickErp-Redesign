<?php
namespace App\Jobs;

use App\Models\Hourly\{HourlyCategoryClosure,HourlyRentalOrder};
use App\Models\ingaz\Notification;
use App\Services\Hourly\ClosureImpact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue,SerializesModels};
use Illuminate\Support\Facades\DB;

class NotifyHourlyClosure implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
    public $tries=3;
    public function __construct(public int $closureId) {}
    public function handle(): void
    {
        $closure=HourlyCategoryClosure::find($this->closureId);
        if(!$closure)return;
        foreach(app(ClosureImpact::class)->visits($closure)->groupBy('hourly_rental_order_id') as $orderId=>$visits){
            DB::transaction(function()use($orderId,$closure){
                $order=HourlyRentalOrder::whereKey($orderId)->lockForUpdate()->first();
                if(!$order)return;
                $meta=$order->booking_metadata??[];
                if(!empty($meta['closure_notifications'][$closure->id]['push_sent_at']))return;
                $affected=app(ClosureImpact::class)->visits($closure,(int)$order->id);
                if($affected->isEmpty())return;
                $dates=$affected->map(fn($v)=>$v->scheduled_start_at->format('d/m/Y H:i'))->implode('، ');
                $title='تعارض موعد الزيارة مع إجازة الخدمة';
                $message='الطلب #'.$order->order_number.'، المواعيد: '.$dates.'. أُضيف إغلاق للخدمة في هذا الموعد. يرجى فتح الطلب لطلب إعادة الجدولة أو الإلغاء دون رسوم بعد مراجعة الإدارة. لم يتم تغيير الموعد تلقائيًا.';
                $notificationId=$meta['closure_notifications'][$closure->id]['notification_id']??null;
                $notification=$notificationId?Notification::find($notificationId):null;
                $notification??=Notification::create(['user_id'=>$order->user_id,'type'=>'hourly_booking',
                    'title'=>['ar'=>$title,'en'=>'Service closure affects your booking'],
                    'description'=>['ar'=>$message,'en'=>'Order #'.$order->order_number.': a service closure affects your visit. Open your order to request rescheduling or cancellation without fees after review. Your appointment has not been changed automatically.']]);
                $meta['closure_notifications'][$closure->id]=['notification_id'=>$notification->id,'sent_at'=>now()->toIso8601String()];
                $order->update(['booking_metadata'=>$meta]);
                $payload=['type'=>'hourly_booking','notification_id'=>(string)$notification->id,'hourly_order_id'=>(string)$order->id];
                DB::afterCommit(function()use($order,$closure,$title,$message,$payload){
                    $result=app(\App\Services\Firebase\FirebasePushService::class)->sendToUser($order->user_id,$title,$message,$payload);
                    if(!$result->wasSkipped() && !$result->successCount())throw new \RuntimeException('Closure push failed; retry using the existing notification.');
                    DB::transaction(function()use($order,$closure){
                        $locked=HourlyRentalOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
                        $meta=$locked->booking_metadata??[];
                        $meta['closure_notifications'][$closure->id]['push_sent_at']=now()->toIso8601String();
                        $locked->update(['booking_metadata'=>$meta]);
                    });
                });
            });
        }
    }
}
