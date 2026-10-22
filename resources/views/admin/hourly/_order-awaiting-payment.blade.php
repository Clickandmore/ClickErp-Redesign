{{-- الطلب أنهى زياراته وعليه مبلغ: يبقى مفتوحًا حتى السداد، أو يُغلق استثنائيًا بصلاحية وسبب. --}}
@if($order->status === \App\Services\Hourly\HourlyOrderService::AWAITING_PAYMENT)
@php
    $apOwed = max(0, round((float) $order->total_amount - (float) $order->paid_amount, 2));
    $apPaid = (float) $order->paid_amount;
    $apPercent = (float) $order->total_amount > 0 ? min(100, round($apPaid / (float) $order->total_amount * 100)) : 0;
@endphp
<style>
    .ap { border: 1px solid #f3d29a; border-radius: 16px; background: linear-gradient(180deg, #fffaf0, #fff 75%); padding: 1rem 1.2rem; margin-bottom: 1rem; }
    .ap-head { display: flex; align-items: center; gap: .8rem; flex-wrap: wrap; }
    .ap-icon { width: 44px; height: 44px; flex: 0 0 44px; border-radius: 13px; display: grid; place-items: center; color: #fff; font-size: 1.1rem; background: linear-gradient(135deg, #f59e0b, #d97706); }
    .ap-head h5 { margin: 0; font-size: 1rem; font-weight: 800; color: #7c4a03; }
    .ap-head p { margin: .15rem 0 0; font-size: .82rem; color: #8a6a3a; }
    .ap-owed { margin-inline-start: auto; text-align: end; }
    .ap-owed span { display: block; font-size: .72rem; font-weight: 700; color: #8a7a62; }
    .ap-owed b { font-size: 1.25rem; color: #b45309; font-variant-numeric: tabular-nums; }
    .ap-bar { height: 8px; border-radius: 99px; background: #f6e7cc; margin: .85rem 0 .35rem; overflow: hidden; }
    .ap-bar i { display: block; height: 100%; background: linear-gradient(90deg, #068a7d, #00ac9a); border-radius: inherit; }
    .ap-legend { display: flex; justify-content: space-between; font-size: .76rem; color: #6b5a3e; }
    .ap-close { margin-top: .85rem; border-top: 1px dashed #f1dcb4; padding-top: .75rem; }
    .ap-close summary { cursor: pointer; font-size: .82rem; font-weight: 700; color: #8a5a00; }
    .ap-close form { display: grid; gap: .55rem; margin-top: .65rem; }
</style>
<section class="ap" aria-labelledby="ap-title">
    <div class="ap-head">
        <span class="ap-icon" aria-hidden="true"><i class="fa fa-file-invoice-dollar"></i></span>
        <div>
            <h5 id="ap-title">{{ \App\Services\Hourly\HourlyOrderService::statusLabel($order) }}</h5>
            <p>انتهت كل زيارات الطلب؛ يُغلق «مكتملًا» تلقائيًا عند سداد المتبقي بأي طريقة دفع.</p>
        </div>
        <div class="ap-owed"><span>المتبقي على العميل</span><b>{{ number_format($apOwed, 2) }} <small>ر.س</small></b></div>
    </div>
    <div class="ap-bar" role="progressbar" aria-valuenow="{{ $apPercent }}" aria-valuemin="0" aria-valuemax="100"><i style="width: {{ $apPercent }}%"></i></div>
    <div class="ap-legend"><span>مدفوع {{ number_format($apPaid, 2) }} ر.س</span><span>الإجمالي {{ number_format((float) $order->total_amount, 2) }} ر.س</span></div>

    @if(AdminGuardHasPerm('hourly_orders_close_unpaid'))
        <details class="ap-close">
            <summary><i class="fa fa-lock-open"></i> إغلاق الطلب دون اكتمال السداد</summary>
            <form method="POST" action="{{ route('admin.hourly-orders.close-unpaid', $order) }}">
                @csrf
                <p class="small text-muted mb-0">يُغلق الطلب «مكتملًا» ويبقى المتبقي مسجّلًا عليه؛ يُحفظ الإجراء في سجل حالات الطلب باسمك.</p>
                <input name="reason" class="form-control" maxlength="1000" required placeholder="سبب الإغلاق، مثال: تسوية متفق عليها مع العميل">
                <label class="small"><input type="checkbox" name="confirmed" value="1" required> أؤكد إغلاق الطلب مع بقاء مبلغ {{ number_format($apOwed, 2) }} ر.س غير مسدد.</label>
                <button type="submit" class="btn btn-outline-warning btn-sm justify-self-start" style="justify-self:start">إغلاق الطلب</button>
            </form>
        </details>
    @endif
</section>
@endif
