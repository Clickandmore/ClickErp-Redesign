@php
    $refundDue = max(0, round((float)$order->paid_amount - (float)$order->total_amount, 2));
    $refundEntries = $order->payments->where('status','paid')->filter(fn($payment)=>(float)$payment->amount<0);
    $refundedTotal = -(float)$refundEntries->sum('amount');
@endphp
@if($refundDue>0 || $refundedTotal>0)
<div class="card mb-3"><div class="card-body" dir="rtl">
    @if($refundedTotal>0)<p class="text-success">المبلغ المسترد المسجّل: <strong>{{ number_format($refundedTotal,2) }} ر.س</strong></p>@endif
    @if($refundDue>0)
        <div class="alert alert-warning">يوجد للعميل مبلغ مستحق للاسترداد: <strong>{{ number_format($refundDue,2) }} ر.س</strong>. لم يُرَد هذا المبلغ بعد.</div>
        @if(AdminGuardHasPerm('hourly_orders_collect') && AdminGuardHasPerm('hourly_orders_create'))
        <form method="POST" action="{{ route('admin.hourly-orders.operations',$order) }}">
            @csrf<input type="hidden" name="action" value="refund">
            <label class="form-label">طريقة الاسترداد</label>
            <select name="refund_method" class="form-select mb-2" required><option value="wallet">إلى محفظة العميل</option><option value="cash">تسجيل مبلغ سُلّم نقدًا</option><option value="bank_transfer">تسجيل تحويل بنكي منفّذ</option></select>
            <label class="d-block mb-2"><input type="checkbox" name="external_refund_confirmed" value="1"> للنقد أو التحويل: أؤكد صرف المبلغ كاملًا للعميل خارج النظام.</label>
            <label class="form-label">سبب الاسترداد / مرجع الصرف</label><input name="reason" class="form-control mb-2" maxlength="2000" required>
            <button type="submit" class="btn btn-warning">استرداد المبلغ المستحق</button>
            <p class="small text-muted mt-2">المحفظة تُحدّث فورًا. النقد والتحويل يوثّقان صرفًا منفّذًا ولا يرسلان تحويلًا بنكيًا.</p>
        </form>
        @endif
    @endif
</div></div>
@endif
