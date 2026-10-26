<?php
namespace App\Services\Hourly;

use App\Models\Hourly\HourlyRentalOrder;
use App\Models\ingaz\User;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

class OrderRefundService
{
    /** Refund only the excess over the remaining contract value, under the order lock. */
    public function refund(HourlyRentalOrder $candidate, string $method, string $reason, ?int $admin = null,
        string $actor = 'admin', ?int $actorId = null, bool $externalConfirmed = false, ?float $visitAmount = null): float
    {
        return DB::transaction(function () use ($candidate,$method,$reason,$admin,$actor,$actorId,$externalConfirmed,$visitAmount) {
            $order = HourlyRentalOrder::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            if (!in_array($method,['wallet','cash','bank_transfer'],true)) HourlyOrderService::reject('اختر طريقة استرداد صحيحة.');
            $amount = max(0,(int)round((float)$order->paid_amount*100)-(int)round((float)$order->total_amount*100));
            if ($visitAmount !== null) {
                $amount=(int)round($visitAmount*100);
                if ($amount<0 || $amount>(int)round($order->paid_amount*100)) HourlyOrderService::reject('المبلغ المسترد يتجاوز المبلغ المسدد المتاح.');
            }
            if (!$amount) return 0.0;
            if ($order->payments()->whereIn('status',['pending','creating','pending_review'])->exists()) HourlyOrderService::reject('يوجد دفع قيد التنفيذ أو المراجعة؛ سوّه قبل الاسترداد.');
            if ($method !== 'wallet' && !$externalConfirmed) HourlyOrderService::reject('أكد تسليم المبلغ النقدي أو تنفيذ التحويل البنكي فعليًا قبل تسجيل الاسترداد.');
            $user = User::whereKey($order->user_id)->lockForUpdate()->firstOrFail();
            $walletTransaction = null;
            if ($method === 'wallet') {
                if (!Schema::hasTable('hourly_wallet_transactions') || !Schema::hasColumn('users','hourly_wallet_balance')) HourlyOrderService::reject('محفظة العميل غير مهيأة للاسترداد.');
                $balance = (int)round((float)$user->hourly_wallet_balance*100)+$amount;
                $user->update(['hourly_wallet_balance'=>$balance/100]);
                // This legacy column is unique; the payment payload links each refund to its order and wallet entry.
                $entry = ['user_id'=>$user->id,'hourly_rental_order_id'=>null,'amount'=>$amount/100,
                    'balance_after'=>$balance/100,'admin_id'=>$admin,'created_at'=>now(),'updated_at'=>now()];
                if (Schema::hasColumn('hourly_wallet_transactions','type')) $entry += ['type'=>'refund','payment_method'=>'wallet',
                    'collected_by_type'=>$actor,'description'=>'استرداد طلب '.$order->order_number.' — '.$reason,'paid_at'=>now()];
                $walletTransaction = DB::table('hourly_wallet_transactions')->insertGetId($entry);
            }
            $payment = $order->payments()->create(['user_id'=>$user->id,'public_id'=>(string)Str::uuid(),
                'method'=>$method,'amount'=>-$amount/100,'status'=>'paid','paid_at'=>now(),
                'collected_by_type'=>$actor,'collected_by_id'=>$actorId ?? $admin,
                'provider_payload'=>['type'=>'refund','reason'=>$reason,'wallet_transaction_id'=>$walletTransaction,
                    'external_confirmed'=>$method !== 'wallet']]);
            $net = ((int)round((float)$order->paid_amount*100)-$amount)/100;
            $order->update(['paid_amount'=>$net,'payment_status'=>$net<=0?'refunded':($net>=(float)$order->total_amount?'paid':'partial')]);
            app(PaymentScheduleService::class)->syncInstallments($order);
            app(OrderManagementService::class)->audit($order,'refund',[],['amount'=>$amount/100,'method'=>$method,
                'payment_id'=>$payment->id,'wallet_transaction_id'=>$walletTransaction,'actor_type'=>$actor,'actor_id'=>$actorId ?? $admin],$admin,null,$reason);
            return $amount/100;
        });
    }
}
