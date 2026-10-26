{{-- حقول الفترة المشتركة: شهر، أو من/إلى (يتقدّم على الشهر). المتغيرات: $period --}}
<div>
    <label for="pg-month">{{ __('payment_gateways.period.month') }}</label>
    <input type="month" id="pg-month" name="month" class="form-control" value="{{ $period->mode === 'month' ? $period->month() : '' }}">
</div>
<div>
    <label for="pg-from">{{ __('payment_gateways.period.from') }}</label>
    <input type="date" id="pg-from" name="from" class="form-control" value="{{ $period->mode === 'range' ? $period->from->format('Y-m-d') : '' }}">
</div>
<div>
    <label for="pg-to">{{ __('payment_gateways.period.to') }}</label>
    <input type="date" id="pg-to" name="to" class="form-control" value="{{ $period->mode === 'range' ? $period->to->format('Y-m-d') : '' }}">
</div>
