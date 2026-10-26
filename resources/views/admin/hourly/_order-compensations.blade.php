{{-- تعويض العميل: مصروف مستقل مرتبط بالطلب؛ لا يغيّر الفاتورة ولا إجمالي الطلب. يتطلب: $order --}}
@if(\App\Services\Hourly\OrderCompensation::ready())
@php
    $compService = app(\App\Services\Hourly\OrderCompensation::class);
    $compRows = $compService->forOrder($order);
    $compCan = AdminGuardHasPerm('hourly_orders_compensate');
    $compOverride = AdminGuardHasPerm('hourly_orders_compensate_override');
    $compLimit = $compService->limit($order);
    $compVisits = $order->visits->sortBy('visit_number');
    $compVisitLimits = $compVisits->mapWithKeys(fn ($v) => [$v->id => $compService->limit($order, $v->id)]);
    $compTone = ['credited' => 'is-ok', 'pending_payout' => 'is-wait', 'paid_out' => 'is-ok'];
@endphp
@if($compRows->isNotEmpty() || $compCan)
<section class="oc" aria-labelledby="oc-title">
    <div class="oc-head">
        <span class="oc-icon" aria-hidden="true"><i class="fa fa-hand-holding-heart"></i></span>
        <div>
            <h5 id="oc-title">تعويضات العميل @if($compRows->isNotEmpty())<span class="oc-total">{{ number_format($compRows->sum('amount'), 2) }} ر.س</span>@endif</h5>
            <p>تعويض عن مشكلة في الخدمة. يُسجَّل مصروفًا مستقلًا مرتبطًا بالطلب، ولا يغيّر الفاتورة ولا إجمالي الطلب.</p>
        </div>
        @if($compCan)
            <button type="button" class="oc-btn" data-bs-toggle="modal" data-bs-target="#oc-modal"><i class="fa fa-plus"></i> تعويض العميل</button>
        @endif
    </div>
    @if($compRows->isNotEmpty())
        <div class="oc-list">
            @foreach($compRows as $row)
                <article class="oc-row">
                    <b class="oc-amount">{{ number_format((float) $row->amount, 2) }} <small>ر.س</small></b>
                    <div class="oc-main">
                        <span class="oc-reason">{{ \App\Services\Hourly\OrderCompensation::REASONS[$row->reason] ?? $row->reason }}@if($row->visit_id) · زيارة {{ $compVisits->firstWhere('id', $row->visit_id)?->visit_number ?? '#'.$row->visit_id }}@endif</span>
                        @if($row->note)<span class="oc-note">{{ $row->note }}</span>@endif
                        <span class="oc-meta"><i class="fa fa-user-pen"></i> {{ $row->created_by_name ?? '—' }} · <bdi>{{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i') }}</bdi>@if($row->over_paid_limit) · <span class="oc-flag">تجاوز المدفوع</span>@endif</span>
                    </div>
                    <div class="oc-side">
                        <span class="oc-dest"><i class="fa {{ $row->destination === 'wallet' ? 'fa-wallet' : ($row->destination === 'cash' ? 'fa-money-bill' : 'fa-building-columns') }}"></i> {{ \App\Services\Hourly\OrderCompensation::DESTINATIONS[$row->destination] ?? $row->destination }}</span>
                        <span class="oc-status {{ $compTone[$row->status] ?? '' }}">{{ \App\Services\Hourly\OrderCompensation::STATUSES[$row->status] ?? $row->status }}@if($row->status === 'paid_out' && $row->payout_reference) · {{ $row->payout_reference }}@endif</span>
                        @if($row->status === 'pending_payout' && AdminGuardHasPerm('hourly_orders_collect'))
                            <form method="POST" action="{{ route('admin.hourly-orders.compensations.paid-out', [$order, $row->id]) }}" class="oc-payout"
                                data-swal-confirm="تأكيد صرف التعويض؟" data-swal-text="{{ number_format((float) $row->amount, 2) }} ر.س {{ \App\Services\Hourly\OrderCompensation::DESTINATIONS[$row->destination] ?? '' }}. أكّد فقط بعد أن يستلم العميل المبلغ فعلًا." data-swal-ok="نعم، تم الصرف">@csrf
                                <input name="payout_reference" class="form-control form-control-sm" maxlength="100" placeholder="مرجع الصرف (اختياري)">
                                <button class="oc-btn is-sm"><i class="fa fa-check"></i> تم الصرف</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

@if($compCan)
<div class="modal fade" id="oc-modal" tabindex="-1" aria-labelledby="oc-modal-title" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content oc-form" method="POST" action="{{ route('admin.hourly-orders.compensations.store', $order) }}" data-oc-form
              data-swal-confirm="تسجيل التعويض؟" data-swal-ok="نعم، سجّل التعويض">
            @csrf
            <input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
            <div class="modal-header"><h5 class="modal-title" id="oc-modal-title"><i class="fa fa-hand-holding-heart"></i> تعويض العميل — {{ $order->order_number }}</h5>
                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button></div>
            <div class="modal-body">
                <label class="oc-field"><span>الزيارة المتأثرة <em>(اختياري)</em></span>
                    <select name="visit_id" class="form-select" data-oc-visit>
                        <option value="" data-limit="{{ $compLimit }}">الطلب كاملًا — المتاح {{ number_format($compLimit, 2) }} ر.س</option>
                        @foreach($compVisits as $v)
                            <option value="{{ $v->id }}" data-limit="{{ $compVisitLimits[$v->id] }}">زيارة {{ $v->visit_number }} — {{ $v->scheduled_start_at?->format('Y-m-d H:i') ?? 'غير مجدولة' }} — المتاح {{ number_format($compVisitLimits[$v->id], 2) }} ر.س</option>
                        @endforeach
                    </select>
                </label>
                <label class="oc-field"><span>مبلغ التعويض (ر.س)</span>
                    <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required data-oc-amount @unless($compOverride) max="{{ $compLimit }}" @endunless>
                    <small data-oc-hint>الحد: ما دفعه العميل بعد التعويضات السابقة @if($compOverride)— يمكنك تجاوزه بصلاحيتك، ويُسجَّل ذلك.@endif</small>
                </label>
                <label class="oc-field"><span>سبب التعويض</span>
                    <select name="reason" class="form-select" required>
                        <option value="">اختر السبب</option>
                        @foreach(\App\Services\Hourly\OrderCompensation::REASONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label class="oc-field"><span>تفاصيل <em>(مطلوبة مع «سبب آخر»)</em></span>
                    <textarea name="note" class="form-control" rows="2" maxlength="1000" placeholder="مثال: وصلت العاملة متأخرة ساعتين."></textarea>
                </label>
                <fieldset class="oc-field"><span>طريقة إيصال التعويض</span>
                    <div class="oc-dests">
                        @foreach(\App\Services\Hourly\OrderCompensation::DESTINATIONS as $key => $label)
                            <label class="oc-choice"><input type="radio" name="destination" value="{{ $key }}" data-label="{{ $label }}" {{ $key === 'wallet' ? 'checked' : '' }} required>
                                <span><i class="fa {{ $key === 'wallet' ? 'fa-wallet' : ($key === 'cash' ? 'fa-money-bill' : 'fa-building-columns') }}"></i> {{ $label }}
                                <small>{{ $key === 'wallet' ? 'يُضاف فورًا ويستخدمه في حجوزاته' : 'يُسجَّل بانتظار الصرف، ثم «تم الصرف»' }}</small></span></label>
                        @endforeach
                    </div>
                </fieldset>
                <p class="oc-info"><i class="fa fa-circle-info"></i> لا يغيّر التعويض الفاتورة ولا إجمالي الطلب، ويصل للعميل إشعار في التطبيق.</p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-success"><i class="fa fa-check"></i> تسجيل التعويض</button></div>
        </form>
    </div>
</div>
@endif

@once
<style>
    .oc { border: 1px solid #f1d6e1; border-radius: 16px; background: linear-gradient(180deg, #fff7fa, #fff 70%); padding: 1rem 1.2rem; margin-bottom: 1rem; }
    .oc-head { display: flex; align-items: center; gap: .8rem; flex-wrap: wrap; }
    .oc-icon { width: 44px; height: 44px; flex: 0 0 44px; border-radius: 13px; display: grid; place-items: center; color: #fff; background: linear-gradient(135deg, #db2777, #9d174d); font-size: 1.1rem; }
    .oc-head h5 { margin: 0; font-size: 1rem; font-weight: 800; color: #831843; display: flex; align-items: center; gap: .5rem; }
    .oc-head p { margin: .15rem 0 0; font-size: .8rem; color: #8b6577; }
    .oc-total { padding: .1rem .55rem; border-radius: 99px; background: #fce7f3; color: #9d174d; font-size: .8rem; font-variant-numeric: tabular-nums; }
    .oc-btn { margin-inline-start: auto; border: 0; border-radius: 10px; background: #be185d; color: #fff; font-weight: 700; padding: .45rem .95rem; font-size: .84rem; display: inline-flex; align-items: center; gap: .35rem; }
    .oc-btn:hover { background: #9d174d; }
    .oc-btn.is-sm { margin: 0; padding: .25rem .6rem; font-size: .76rem; }
    .oc-list { display: grid; gap: .55rem; margin-top: .85rem; }
    .oc-row { display: grid; grid-template-columns: 110px 1fr auto; gap: .85rem; align-items: center; background: #fff; border: 1px solid #f3e1e9; border-radius: 12px; padding: .65rem .85rem; }
    .oc-amount { font-size: 1.15rem; color: #9d174d; font-variant-numeric: tabular-nums; }
    .oc-amount small { font-size: .7rem; color: #a17b8c; }
    .oc-main { display: grid; gap: .15rem; min-width: 0; }
    .oc-reason { font-weight: 700; font-size: .88rem; }
    .oc-note { font-size: .8rem; color: #5b6676; }
    .oc-meta { font-size: .74rem; color: #8a96a5; }
    .oc-flag { color: #b45309; font-weight: 700; }
    .oc-side { display: grid; gap: .3rem; justify-items: end; }
    .oc-dest { font-size: .78rem; color: #475569; }
    .oc-status { font-size: .74rem; font-weight: 800; padding: .12rem .55rem; border-radius: 99px; background: #f1f5f9; color: #475569; }
    .oc-status.is-ok { background: #dcfce7; color: #166534; }
    .oc-status.is-wait { background: #fef3c7; color: #92400e; }
    .oc-payout { display: flex; gap: .35rem; margin: 0; }
    .oc-payout .form-control { width: 150px; }
    .oc-form .modal-header { border-bottom-color: #f3e1e9; }
    .oc-form .modal-title { font-size: 1rem; font-weight: 800; color: #831843; display: flex; gap: .45rem; align-items: center; }
    .oc-field { display: grid; gap: .35rem; margin: 0 0 .8rem; border: 0; padding: 0; }
    .oc-field > span { font-size: .82rem; font-weight: 700; }
    .oc-field em { font-style: normal; font-weight: 500; color: #8a96a5; }
    .oc-field small { font-size: .74rem; color: #8a96a5; }
    .oc-field small.is-over { color: #b45309; font-weight: 700; }
    .oc-dests { display: grid; grid-template-columns: repeat(3, 1fr); gap: .45rem; }
    .oc-choice { position: relative; margin: 0; cursor: pointer; }
    .oc-choice input { position: absolute; opacity: 0; }
    .oc-choice span { display: grid; gap: .15rem; height: 100%; border: 1px solid #e2e8f0; border-radius: 11px; padding: .55rem .6rem; font-size: .82rem; font-weight: 700; text-align: center; }
    .oc-choice span i { color: #be185d; font-size: 1rem; }
    .oc-choice small { font-weight: 500; color: #8a96a5; font-size: .7rem; line-height: 1.5; }
    .oc-choice input:checked + span { border-color: #be185d; background: #fff1f6; box-shadow: 0 0 0 .15rem rgba(190, 24, 93, .12); }
    .oc-choice input:focus-visible + span { outline: 2px solid #be185d; outline-offset: 2px; }
    .oc-info { margin: 0; font-size: .78rem; color: #5b6676; background: #f8fafc; border-radius: 10px; padding: .55rem .7rem; }
    @media (max-width: 767px) { .oc-row { grid-template-columns: 1fr; } .oc-side { justify-items: start; } .oc-dests { grid-template-columns: 1fr; } .oc-btn { margin-inline-start: 0; } }
</style>
<script>
// يحدّث حد المبلغ حسب الزيارة المختارة، وينبّه عند تجاوزه لمن يملك صلاحية التجاوز.
document.querySelectorAll('[data-oc-form]').forEach(function (form) {
    var visit = form.querySelector('[data-oc-visit]'), amount = form.querySelector('[data-oc-amount]'), hint = form.querySelector('[data-oc-hint]');
    var override = {{ $compOverride ? 'true' : 'false' }}, base = hint.textContent;
    function sync() {
        var limit = parseFloat(visit.selectedOptions[0].dataset.limit || '0');
        if (!override) amount.max = limit.toFixed(2);
        var over = parseFloat(amount.value || '0') > limit;
        hint.classList.toggle('is-over', over);
        hint.textContent = over ? 'المبلغ يتجاوز المتاح (' + limit.toFixed(2) + ' ر.س)' + (override ? ' — سيُسجَّل كتجاوز بصلاحيتك.' : '.') : base;
    }
    visit.addEventListener('change', sync); amount.addEventListener('input', sync);
    // ملخص نافذة التأكيد يتحدّث مع كل تغيير: المبلغ والوجهة والسبب كما اختارها الموظف.
    function summary() {
        var dest = form.querySelector('[name="destination"]:checked'), reason = form.reason.selectedOptions[0];
        form.dataset.swalText = (parseFloat(amount.value || '0')).toFixed(2) + ' ر.س — ' + (dest ? dest.dataset.label : '')
            + (reason && reason.value ? ' — ' + reason.textContent : '')
            + (dest && dest.value === 'wallet' ? '. يُضاف فورًا لمحفظة العميل ويصله إشعار.' : '. يُسجَّل بانتظار الصرف.');
    }
    form.addEventListener('input', summary); form.addEventListener('change', summary); summary();
});
</script>
@include('admin.hourly._swal-confirm')
@endonce
@endif
@endif
