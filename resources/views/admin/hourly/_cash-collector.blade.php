@unless($hideFinancial ?? false)
@include('admin.hourly._visit-financial')
@endunless
@php $cashPaymentPolicy=app(\App\Services\Hourly\PaymentScheduleService::class)->policy($order); @endphp
@if($cashPaymentPolicy!=='prepaid' && \App\Services\Hourly\CashCollectionService::ready() && in_array($order->payment_method,['cash','cash_and_card'],true) && in_array($order->status,['active','completed'],true))
    @php $cashPolicy=app(\App\Services\Hourly\CashCollectionService::class)->collector($visit); @endphp
    <div class="alert alert-info mt-2 mb-2 small">مسؤول التحصيل: <strong>{{ $cashPolicy['label'] }}</strong>
        @if($cashPolicy['id']) #{{ $cashPolicy['id'] }}@elseif($cashPolicy['type']!=='admin') — يلزم تحديد الشخص قبل التحصيل@endif
    </div>
    @if($visit->status==='scheduled' && !$visit->has_started_execution && $order->status==='active' && AdminGuardHasPerm('hourly_orders_create') && AdminGuardHasPerm('hourly_orders_collect'))
    <details class="small"><summary>تحديد مسؤول التحصيل النقدي</summary>
        <form method="POST" action="{{ route('admin.hourly-cash.collector',[$order,$visit]) }}" class="mt-2">
            @csrf<label>المسؤول</label><select name="type" class="form-select form-select-sm" required>
                @if($visit->delivery_driver_id)
<option value="driver" selected>سائق التوصيل (مسؤول التحصيل)</option>
@else
@foreach(['auto'=>'تلقائي حسب السائق والعاملة','driver'=>'سائق التوصيل','worker'=>'عاملة محددة من الزيارة','admin'=>'موظف التحصيل'] as $value=>$label)<option value="{{ $value }}" {{ ($visit->cash_collector_type ?: 'auto')===$value?'selected':'' }}>{{ $label }}</option>@endforeach
@endif
            </select>
            @if(!$visit->delivery_driver_id)
            <label>العاملة المسؤولة (عند اختيار العاملة)</label><select name="worker_id" class="form-select form-select-sm"><option value="">اختر العاملة</option>@foreach($visit->workers as $cashWorker)<option value="{{ $cashWorker->id }}" {{ $visit->cash_collector_worker_id==$cashWorker->id?'selected':'' }}>{{ $cashWorker->name }}</option>@endforeach</select>
            @endif
            <label>سبب التحديد</label><input name="reason" class="form-control form-control-sm" maxlength="500" required><button class="btn btn-sm btn-primary mt-2">حفظ مسؤول التحصيل</button>
            <p class="text-muted">التلقائي: السائق المعيّن، ثم العاملة الوحيدة التي لا تحتاج سائقًا، وإلا موظف التحصيل. التحصيل النقدي للطاقم يسجّل عهدة حتى توريدها.</p>
        </form>
    </details>
    @endif
@endif
