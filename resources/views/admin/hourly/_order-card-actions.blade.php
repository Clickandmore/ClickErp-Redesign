@if($order->paid_amount > $order->total_amount)<section class="text-danger">زيادة في التحصيل تحتاج تسوية<h4>{{ number_format($order->paid_amount-$order->total_amount,2) }} ر.س</h4><small>لم تُنفذ عملية رد مبلغ تلقائية.</small></section>@endif
@if(AdminGuardHasPerm('hourly_orders_create') && in_array($order->status,['active','pending_confirmation']))
<section>
    <div class="om-order-actions">
        <button type="button" class="om-btn om-btn-soft" data-order-action="payment_method"><i class="fa fa-wallet"></i> تعديل نوع الدفع</button>
        <button type="button" class="om-btn om-btn-danger" data-order-action="cancel_order"><i class="fa fa-ban"></i> إلغاء الطلب</button>
    </div>
</section>
@endif
