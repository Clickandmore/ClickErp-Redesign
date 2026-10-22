<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyRentalOrder, HourlyOrderPayment};
use App\Services\Hourly\HourlyMoyasarGateway as MoyasarService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HourlyPaymentService
{
    public function __construct(private MoyasarService $gateway, private HourlyOrderService $orders) {}

    /**
     * $returnUrl: صفحة عودة الموقع الخارجي بعد ميسر؛ يُلحق بها معرّف الدفعة (payment). بدونه تبقى العودة كما كانت.
     */
    public function initiate(HourlyRentalOrder $order, bool $website = false, ?string $returnUrl = null): HourlyOrderPayment
    {
        HourlyOrderService::enabled();
        // Keep the order lock through invoice creation so concurrent requests cannot create two links.
        return DB::transaction(function () use ($order, $website, $returnUrl) {
            $order = HourlyRentalOrder::query()->lockForUpdate()->findOrFail($order->id);
            $this->payable($order);
            if($this->dueNow($order)<=0)HourlyOrderService::reject('لا يوجد مبلغ مستحق الآن وفق سياسة الدفع.');
            if ($order->payment_method !== 'moyasar') HourlyOrderService::reject('طريقة الدفع المختارة ليست ميسر.');
            $existing = $order->payments()->whereIn('status',['pending','creating'])->first();
            if ($existing) return $existing;
            $payment = $order->payments()->create(['user_id'=>$order->user_id,'public_id'=>(string) Str::uuid(),'method'=>'moyasar','provider'=>'moyasar','amount'=>$this->dueNow($order),'status'=>'creating','provider_payload'=>['visit_allocations'=>app(PaymentScheduleService::class)->allocations($order,(int)round($this->dueNow($order)*100))]]);
            $result = $this->gateway->createPaymentLink((float) $payment->amount, 'Hourly rental '.$order->order_number,
                route('api.hourly.moyasar.webhook'), ['hourly_payment_id'=>$payment->public_id],
                $returnUrl
                    ? $returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').http_build_query(['payment'=>$payment->public_id])
                    : route($website ? 'web.hourly.payment-return' : 'api.hourly.moyasar.callback', ['payment'=>$payment->public_id]));
            if (empty($result['id']) || empty($result['url'])) HourlyOrderService::reject('تعذر إنشاء رابط الدفع. حاول مجددًا.');
            $payment->update(['provider_reference'=>$result['id'],'payment_url'=>$result['url'],'status'=>'pending']);
            return $payment;
        });
    }

    public function synchronize(HourlyOrderPayment $payment): HourlyOrderPayment
    {
        if (!$payment->provider_reference) return $payment;
        // Only the stored invoice is fetched. Callback fields never prove payment.
        $invoice = $this->gateway->getInvoice($payment->provider_reference);
        abort_if(!$invoice,503,'تعذر التحقق من فاتورة ميسر حاليًا. أعد المحاولة.');
        if (!$invoice || !in_array($invoice['status'] ?? '', ['paid','refunded'], true)) return $payment;
        if (($invoice['id'] ?? '') !== $payment->provider_reference || ($invoice['currency'] ?? '') !== 'SAR'
            || (int) ($invoice['amount'] ?? -1) !== (int) round($payment->amount*100)) HourlyOrderService::reject('بيانات السداد لا تطابق الفاتورة.');
        return DB::transaction(function () use ($payment, $invoice) {
            $order = HourlyRentalOrder::query()->lockForUpdate()->findOrFail($payment->hourly_rental_order_id);
            $payment = HourlyOrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            $snapshot = \App\Support\MoyasarPaymentSnapshot::fromInvoice($invoice, $payment->provider_reference, (float) $payment->amount);
            if (!$snapshot) return $payment;
            $refunded = max((float) $payment->refunded_amount, $snapshot['refunded']);
            $delta = round($refunded - (float) $payment->refunded_amount, 2);
            // Keep the original receipt and post each refund delta as a separate debit.
            $payment->update(['status'=>'paid','paid_at'=>$payment->paid_at ?: now(),'provider_payload'=>$invoice+['visit_allocations'=>$payment->provider_payload['visit_allocations']??[]],'refunded_amount'=>$refunded]);
            if ($delta > 0) {
                $order->payments()->create([
                    'user_id'=>$payment->user_id, 'public_id'=>(string) Str::uuid(),
                    'method'=>'moyasar', 'provider'=>'moyasar', 'amount'=>-$delta,
                    'status'=>'paid', 'paid_at'=>now(),
                    'provider_payload'=>['refund_of'=>$payment->id, 'provider_payment_id'=>$snapshot['id'], 'refunded_total'=>$refunded],
                ]);
            }
            $this->recalculate($order, 'provider', null);
            return $payment;
        });
    }

    public function collect(HourlyRentalOrder $order, array $data, int $adminId): HourlyOrderPayment
    {
        HourlyOrderService::enabled();
        return DB::transaction(function () use ($order,$data,$adminId) {
            $order = HourlyRentalOrder::query()->lockForUpdate()->findOrFail($order->id);
            $this->payable($order, true);
            if ($order->payment_method === 'moyasar') HourlyOrderService::reject('تحصيل طلب ميسر يتم من البوابة فقط.');
            $collectionMethod=$data['collection_method']??$order->payment_method;
            if(isset($data['collection_method']))app(HourlyBookingRules::class)->assertPaymentMethod($order->package_snapshot,$collectionMethod,true);
            $amount = (int) round($data['amount']*100);
            if ($order->payment_method === 'customer_balance' && !DB::table('hourly_wallet_transactions')->where('hourly_rental_order_id',$order->id)->where('amount',-$amount/100)->exists()) HourlyOrderService::reject('يجب خصم رصيد العميل قبل تسجيل السداد.');
            if ($amount <= 0 || $amount > (int) round(($order->total_amount-$order->paid_amount)*100)) HourlyOrderService::reject('المبلغ يجب ألا يتجاوز المتبقي.');
            if ($collectionMethod === 'bank_transfer' && empty($data['attachment_path'])) HourlyOrderService::reject('أرفق إثبات التحويل البنكي.');
            $method = $collectionMethod;
            if ($method === 'cash_and_card') {
                $method = $data['method'] ?? null;
                if (!in_array($method,['cash','card_on_delivery'],true)) HourlyOrderService::reject('حدد الجزء النقدي أو البطاقة لهذه الدفعة.');
            }
            $schedule=app(PaymentScheduleService::class);
            if($schedule->policy($order)!=='legacy' && $amount>(int)round($schedule->forOrder($order)['due_now']*100))HourlyOrderService::reject('المبلغ يتجاوز المستحق الآن وفق سياسة الدفع؛ الزيارات والأقساط المستقبلية ليست مستحقة.');
            $visitAllocations=$schedule->allocations($order,$amount);
            $payment = $order->payments()->create(['user_id'=>$order->user_id,'public_id'=>(string) Str::uuid(),'method'=>$method,'amount'=>$amount/100,'status'=>'paid','paid_at'=>$data['paid_at']??now(),'collected_by_type'=>'admin','collected_by_id'=>$adminId,'attachment_path'=>$data['attachment_path'] ?? null,'provider_payload'=>['visit_allocations'=>$visitAllocations]]);
            $this->recalculate($order,'admin',$adminId);
            return $payment;
        });
    }

    public function submitBankTransfer(HourlyRentalOrder $order, string $attachmentPath, ?string $referenceNumber = null): HourlyOrderPayment
    {
        HourlyOrderService::enabled();
        return DB::transaction(function () use ($order,$attachmentPath,$referenceNumber) {
            $order=HourlyRentalOrder::query()->lockForUpdate()->findOrFail($order->id);
            $this->payable($order);
            if($this->dueNow($order)<=0)HourlyOrderService::reject('لا يوجد مبلغ مستحق الآن وفق سياسة الدفع.');
            if($order->payment_method!=='bank_transfer') HourlyOrderService::reject('طريقة الدفع المختارة ليست تحويلًا بنكيًا.');
            if($order->payments()->where('method','bank_transfer')->where('status','pending_review')->exists()) {
                HourlyOrderService::reject('يوجد إيصال تحويل قيد المراجعة بالفعل.');
            }
            $payment=$order->payments()->create([
                'user_id'=>$order->user_id,
                'public_id'=>(string)Str::uuid(),
                'method'=>'bank_transfer',
                'amount'=>$this->dueNow($order),
                'status'=>'pending_review',
                'attachment_path'=>$attachmentPath,
                'provider_payload'=>array_filter([
                    'reference_number'=>$referenceNumber,
                    'submitted_at'=>now()->toIso8601String(),
                    'visit_allocations'=>app(PaymentScheduleService::class)->allocations($order,(int)round($this->dueNow($order)*100)),
                ],fn($value)=>$value!==null && $value!==''),
            ]);
            $order->update(['payment_status'=>'pending_review']);
            return $payment;
        });
    }

    public function reviewBankTransfer(HourlyRentalOrder $order, HourlyOrderPayment $payment, string $decision, ?string $reason, int $adminId): HourlyOrderPayment
    {
        HourlyOrderService::enabled();
        $reviewed=DB::transaction(function () use ($order,$payment,$decision,$reason,$adminId) {
            $order=HourlyRentalOrder::query()->lockForUpdate()->findOrFail($order->id);
            $payment=HourlyOrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            abort_unless((int)$payment->hourly_rental_order_id===(int)$order->id,404);
            if($payment->method!=='bank_transfer' || $payment->status!=='pending_review') HourlyOrderService::reject('إيصال التحويل ليس قيد المراجعة.');
            $payload=(array)$payment->provider_payload;
            $payload['reviewed_at']=now()->toIso8601String();
            $payload['reviewed_by']=$adminId;
            if($decision==='accept') {
                $payment->update(['status'=>'paid','paid_at'=>now(),'collected_by_type'=>'admin','collected_by_id'=>$adminId,'provider_payload'=>$payload]);
                $this->recalculate($order,'admin',$adminId);
            } else {
                $payload['rejection_reason']=$reason;
                $payment->update(['status'=>'rejected','collected_by_type'=>'admin','collected_by_id'=>$adminId,'provider_payload'=>$payload]);
                $order->update(['payment_status'=>'rejected']);
            }
            return $payment->fresh();
        });
        \App\Jobs\SendHourlyBankTransferReviewNotification::dispatch($reviewed->id,$decision);
        return $reviewed;
    }

    /**
     * ألْغِ روابط ميسر غير المسددة قبل تحويل الطلب إلى طريقة دفع أخرى.
     * لا نغيّر الطريقة إذا تعذر إلغاء أي رابط، منعًا لقبول دفعتين للطلب نفسه.
     */
    public function cancelPendingMoyasar(HourlyRentalOrder $order, int $adminId): void
    {
        $payments = $order->payments()
            ->where('provider', 'moyasar')
            ->whereIn('status', ['pending', 'creating'])
            ->lockForUpdate()
            ->get();

        foreach ($payments as $payment) {
            if ($payment->provider_reference) {
                $invoice = $this->gateway->cancelInvoice($payment->provider_reference);
                if (($invoice['status'] ?? null) !== 'canceled') {
                    HourlyOrderService::reject('تعذر إلغاء رابط ميسر الحالي. تحقق من حالة الدفعة ثم أعد المحاولة.');
                }
            }

            $payload = (array) $payment->provider_payload;
            $payload['cancelled_at'] = now()->toIso8601String();
            $payload['cancelled_by'] = $adminId;
            $payload['cancellation_reason'] = 'payment_method_changed';
            $payment->update(['status' => 'cancelled', 'provider_payload' => $payload]);
        }
    }

    /**
     * المبلغ المطلوب الآن: كامل المتبقي، إلا إن كانت الفئة تعرض سعر الباقة «للزيارة» فيُحصَّل سعر الزيارة الأولى أولًا.
     * لقطة الطلب تحفظ charge_now وقت الشراء فلا يتغير المستحق بتغيير إعداد الفئة لاحقًا.
     */
    public function dueNow(HourlyRentalOrder $order): float
    {
        return app(PaymentScheduleService::class)->forOrder($order)['due_now'];
    }

    /** هل استوفى الطلب ما يلزم لتفعيله؟ (كامل المبلغ أو سعر الزيارة الأولى في وضع per_visit) */
    public static function activationPaid(HourlyRentalOrder $order): bool
    {
        $paid = (int) round($order->paid_amount * 100);
        $chargeNow = (int) round(((float) ($order->package_snapshot['charge_now'] ?? $order->total_amount)) * 100);
        return $paid >= min($chargeNow, (int) round($order->total_amount * 100));
    }

    private function payable(HourlyRentalOrder $order, bool $allowCompleted = false): void
    {
        if(in_array($order->status,['active','completed',HourlyOrderService::AWAITING_PAYMENT],true) && $order->payment_status!=='paid' && app(PaymentScheduleService::class)->policy($order)!=='legacy' && $this->dueNow($order)>0)return;
        if($allowCompleted && in_array($order->status,['completed',HourlyOrderService::AWAITING_PAYMENT],true) && $order->payment_status!=='paid')return;
        if (!in_array($order->status,['pending_confirmation','active'],true) || $order->expires_at->isPast() || $order->payment_status === 'paid') HourlyOrderService::reject('الطلب غير قابل للسداد.');
    }

    /** المبلغ المسدَّد وحالة السداد من دفعات الطلب: يستدعيها تحصيل اللوحة وتحصيل السائق من التطبيق. */
    public function recalculate(HourlyRentalOrder $order, string $actor, ?int $actorId): void
    {
        $paid = $order->payments()->where('status','paid')->sum('amount');
        $full = (int) round($paid*100) >= (int) round($order->total_amount*100);
        $hasRefund = $paid <= 0 && $order->payments()->where('status','paid')->where('amount','<',0)->exists();
        $status = $hasRefund ? 'refunded' : ($full ? 'paid' : ($paid > 0 ? 'partial' : ($order->payments()->where('status','paid')->where('amount','<',0)->exists() ? 'refunded' : 'unpaid')));
        $order->update(['paid_amount'=>$paid,'payment_status'=>$status]);
        app(PaymentScheduleService::class)->syncInstallments($order);
        // A late provider confirmation records the money without resurrecting an expired order.
        if ($order->status === 'pending_confirmation' && $order->expires_at->isFuture() && ($full || self::activationPaid($order))) {
            $this->orders->transition($order,'active',$actor,$actorId,$full ? 'اكتمال السداد' : 'سداد الزيارة الأولى');
        }
        $this->orders->settle($order,$actor,$actorId);
    }
}
