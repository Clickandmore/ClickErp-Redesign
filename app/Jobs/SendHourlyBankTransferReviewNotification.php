<?php

namespace App\Jobs;

use App\Models\Hourly\HourlyOrderPayment;
use App\Models\ingaz\Notification;
use App\Services\Firebase\FirebasePushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\Log;

class SendHourlyBankTransferReviewNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(public int $paymentId, public string $decision) {}

    public function handle(FirebasePushService $push): void
    {
        $payment=HourlyOrderPayment::with('order')->findOrFail($this->paymentId);
        $order=$payment->order;
        $accepted=$this->decision==='accept';
        $reason=$payment->provider_payload['rejection_reason']??null;
        $titleAr=$accepted?'تم قبول التحويل البنكي':'تم رفض التحويل البنكي';
        $titleEn=$accepted?'Bank transfer accepted':'Bank transfer rejected';
        $bodyAr=$accepted
            ? 'تم قبول تحويل الطلب '.$order->order_number.' وتسجيل السداد.'
            : 'تم رفض تحويل الطلب '.$order->order_number.($reason?'، السبب: '.$reason:'. يمكنك رفع إيصال بديل.');
        $bodyEn=$accepted
            ? 'Your bank transfer for order '.$order->order_number.' was accepted and recorded.'
            : 'Your bank transfer for order '.$order->order_number.' was rejected. You can upload a replacement receipt.';
        $notification=Notification::create([
            'user_id'=>$order->user_id,
            'type'=>'hourly_bank_transfer',
            'title'=>['ar'=>$titleAr,'en'=>$titleEn],
            'description'=>['ar'=>$bodyAr,'en'=>$bodyEn],
        ]);
        try {
            $push->sendToUser((int)$order->user_id,$titleAr,$bodyAr,[
                'type'=>'hourly_bank_transfer',
                'notification_id'=>(string)$notification->id,
                'hourly_order_id'=>(string)$order->id,
                'payment_status'=>$accepted?'paid':'rejected',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Unable to send hourly bank-transfer review push notification',[
                'payment_id'=>$payment->id,
                'error'=>$e->getMessage(),
            ]);
        }
    }
}
