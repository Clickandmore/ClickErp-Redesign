@php
    $paymentSchedule = app(\App\Services\Hourly\PaymentScheduleService::class)->forOrder($order);
    $psTotal = (float) $order->total_amount;
    $psPaid = (float) $order->paid_amount;
    $psShare = $psTotal > 0 ? min(100, round($psPaid / $psTotal * 100)) : 100;
    $psMoney = fn ($amount) => number_format((float) $amount, 2);
    $psCustody = \App\Services\Hourly\CashCollectionService::ready() && AdminGuardHasPerm('hourly_orders_collect');
@endphp
@once
@push('css')
<style>
    /* لوحة استحقاق الدفع: مستقلة الأنماط لأنها تُعرض في صفحة الإدارة وصفحة الملخص */
    .ps-card { background: #fff; border: 1px solid #e6ecf3; border-radius: 16px; margin-bottom: 1.25rem; box-shadow: 0 6px 18px rgba(20, 80, 127, .05); overflow: hidden; }
    .ps-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; padding: .9rem 1.15rem; border-bottom: 1px solid #e6ecf3; }
    .ps-head h5 { margin: 0; font-size: 1rem; font-weight: 700; color: #16212e; display: flex; align-items: center; gap: .5rem; }
    .ps-head h5 i { color: #2794eb; }
    .ps-policy { font-size: .76rem; font-weight: 700; color: #1b6fb8; background: #eaf5fe; border: 1px solid #c4e2f8; border-radius: 99px; padding: .2rem .7rem; }
    .ps-body { padding: 1rem 1.15rem; }
    .ps-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .75rem; }
    .ps-tile { border: 1px solid #e6ecf3; border-inline-start: 4px solid var(--ps-tone, #9fb0c2); border-radius: 12px; padding: .7rem .9rem; background: var(--ps-tint, #f6f9fc); }
    .ps-tile span { display: block; font-size: .78rem; color: #6b7a8c; }
    .ps-tile b { display: block; font-size: 1.25rem; color: var(--ps-ink, #16212e); font-variant-numeric: tabular-nums; line-height: 1.35; }
    .ps-tile b small { font-size: .72rem; font-weight: 600; color: #6b7a8c; }
    .ps-tile.is-due { --ps-tone: #e08a17; --ps-tint: #fef8ec; --ps-ink: #9a6206; }
    .ps-tile.is-late { --ps-tone: #e5484d; --ps-tint: #fdf2f2; --ps-ink: #b42a2e; }
    .ps-tile.is-clear { --ps-tone: #17996f; --ps-tint: #f0faf6; --ps-ink: #0f7a5a; }
    .ps-progress { margin-top: .9rem; }
    .ps-progress-label { display: flex; justify-content: space-between; gap: .5rem; font-size: .78rem; color: #6b7a8c; margin-bottom: .3rem; }
    .ps-progress-label b { color: #16212e; font-variant-numeric: tabular-nums; }
    .ps-bar { height: 8px; border-radius: 99px; background: #eef3f8; overflow: hidden; }
    .ps-bar span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #068a7d, #00ac9a); }
    .ps-hint { display: flex; gap: .45rem; align-items: flex-start; margin: .85rem 0 0; font-size: .78rem; color: #6b7a8c; }
    .ps-hint i { color: #9fb0c2; margin-top: .15rem; }
    .ps-table { width: 100%; margin-top: 1rem; font-size: .84rem; border-collapse: collapse; }
    .ps-table th { font-size: .76rem; color: #6b7a8c; font-weight: 700; padding: .45rem .6rem; border-bottom: 1px solid #e6ecf3; text-align: start; white-space: nowrap; }
    .ps-table td { padding: .55rem .6rem; border-bottom: 1px solid #eef2f6; font-variant-numeric: tabular-nums; }
    .ps-table tr:last-child td { border-bottom: 0; }
    .ps-state { display: inline-block; border-radius: 7px; padding: .1rem .5rem; font-size: .74rem; font-weight: 700; border: 1px solid transparent; }
    .ps-state.is-late { background: #fdeeee; color: #b42a2e; border-color: #f7cdcf; }
    .ps-state.is-paid { background: #e8f7f1; color: #0f7a5a; border-color: #bfe9d9; }
    .ps-state.is-due { background: #fef5e7; color: #9a6206; border-color: #f7ddb2; }
    .ps-state.is-later { background: #eef3f8; color: #5a6b7d; border-color: #dbe4ee; }
    .ps-foot { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; padding: .75rem 1.15rem; border-top: 1px solid #e6ecf3; background: #fbfcfe; }
    .ps-foot p { margin: 0; font-size: .8rem; color: #6b7a8c; display: flex; align-items: center; gap: .5rem; }
    .ps-foot p i { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: #e6f7f5; color: #068a7d; }
    .ps-foot p b { display: block; color: #16212e; font-size: .84rem; }
    .ps-foot a { display: inline-flex; align-items: center; gap: .4rem; border: 1px solid #c4e2f8; background: #fff; color: #1b6fb8; border-radius: 10px; padding: .4rem .85rem; font-size: .8rem; font-weight: 700; text-decoration: none; }
    .ps-foot a:hover { background: #eaf5fe; color: #14507f; }
</style>
@endpush
@endonce
<section class="ps-card" dir="rtl" aria-labelledby="ps-title">
    <header class="ps-head">
        <h5 id="ps-title"><i class="fa fa-file-invoice-dollar"></i> استحقاق الدفع</h5>
        <span class="ps-policy">{{ $paymentSchedule['policy_label'] }}</span>
    </header>
    <div class="ps-body">
        <div class="ps-tiles">
            <div class="ps-tile {{ $paymentSchedule['due_now'] > 0 ? 'is-due' : 'is-clear' }}">
                <span>المستحق الآن</span>
                <b>{{ $psMoney($paymentSchedule['due_now']) }} <small>ر.س</small></b>
            </div>
            <div class="ps-tile {{ $paymentSchedule['overdue'] > 0 ? 'is-late' : 'is-clear' }}">
                <span>متأخر السداد</span>
                <b>@if($paymentSchedule['overdue'] > 0){{ $psMoney($paymentSchedule['overdue']) }} <small>ر.س</small>@else لا يوجد @endif</b>
            </div>
            <div class="ps-tile">
                <span>باقي قيمة الطلب</span>
                <b>{{ $psMoney($paymentSchedule['remaining']) }} <small>ر.س</small></b>
            </div>
        </div>

        <div class="ps-progress">
            <div class="ps-progress-label"><span>المدفوع <b>{{ $psMoney($psPaid) }}</b> من <b>{{ $psMoney($psTotal) }}</b> ر.س</span><b>{{ $psShare }}%</b></div>
            <div class="ps-bar" role="progressbar" aria-valuenow="{{ $psShare }}" aria-valuemin="0" aria-valuemax="100" aria-label="نسبة سداد الطلب"><span style="width: {{ $psShare }}%"></span></div>
        </div>

        @if($paymentSchedule['policy'] === 'installments' && $paymentSchedule['installments'])
            <div class="table-responsive">
                <table class="ps-table">
                    <thead><tr><th>تاريخ الاستحقاق</th><th>القسط</th><th>المدفوع</th><th>المتبقي</th><th>الحالة</th></tr></thead>
                    <tbody>
                    @foreach($paymentSchedule['installments'] as $inst)
                        @php [$stateClass, $stateText] = $inst['is_overdue'] ? ['is-late', 'متأخر'] : ($inst['payment_status'] === 'paid' ? ['is-paid', 'مسدد'] : ($inst['is_due'] ? ['is-due', 'مستحق'] : ['is-later', 'لم يحِن موعده'])); @endphp
                        <tr>
                            <td><span dir="ltr">{{ $inst['due_date'] }}</span></td>
                            <td>{{ $psMoney($inst['amount']) }}</td>
                            <td>{{ $psMoney($inst['paid']) }}</td>
                            <td>{{ $psMoney($inst['remaining']) }}</td>
                            <td><span class="ps-state {{ $stateClass }}">{{ $stateText }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <p class="ps-hint"><i class="fa fa-circle-info"></i> اكتمال الخدمة لا يثبت السداد. المبالغ تُسدَّد عند تسجيل التحصيل الفعلي، ولا يلزم انتظار انتهاء كل الزيارات.</p>
    </div>
    @if($psCustody)
        <footer class="ps-foot">
            <p><i class="fa fa-wallet"></i><span><b>العهد النقدية لدى العاملات والسائقين</b>تحصيل العميل وتوريد النقد للشركة عمليتان منفصلتان.</span></p>
            <a href="{{ route('admin.hourly-cash.index') }}">إدارة العهد والتوريد <i class="fa fa-arrow-left"></i></a>
        </footer>
    @endif
</section>
