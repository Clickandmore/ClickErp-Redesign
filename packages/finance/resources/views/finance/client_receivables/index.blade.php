@extends('click.layout.indexs.index')
@section('page-title', $bladeTitle)
@section('page-links')<li class="breadcrumb-item">{{ __('finance::finance.reports') }}</li><li class="breadcrumb-item active">{{ $bladeTitle }}</li>@endsection
@section('style')
<style>
.receivables{direction:rtl;color:#183b49}.receivables .panel{background:#fff;border:1px solid #dce6e9;border-radius:12px;padding:18px;margin-bottom:16px}
.receivables .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}.receivables .kpi{background:#f3f7f8;border-radius:8px;padding:12px}.receivables .kpi strong{display:block;font-size:20px}.receivables .kpi.is-due{background:#fdf1ef}.receivables .kpi.is-credit{background:#eef8f1}
.receivables table{width:100%;font-size:13px}.receivables th,.receivables td{padding:10px;border-bottom:1px solid #e6edef;text-align:right;vertical-align:top}.receivables th{background:#f3f7f8}.receivables .money{white-space:nowrap;direction:ltr;text-align:left;font-variant-numeric:tabular-nums}.receivables summary{cursor:pointer;color:#216b78}.receivables .details{background:#f8fafb;padding:12px;min-width:640px}.receivables .badge{white-space:normal}.receivables .pagination{flex-wrap:wrap}
.receivables tr.client-head td{background:#eaf2f4;border-top:2px solid #183b49}.receivables tr.client-head .money strong{font-size:15px}.receivables .older{color:#a3412f;font-weight:600}
.receivables .recon td{padding:6px 10px}.receivables .recon .total td{font-weight:700;border-top:2px solid #183b49}.receivables .note{color:#6b7f86;font-size:12px}
</style>
@endsection
@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 2);
    $recon = $totals->reconciliation;
    $statementUrl = \Illuminate\Support\Facades\Route::has('finance.account-statement-clients.index');
@endphp
<div class="receivables p-3">
    <h3>{{ $bladeTitle }}</h3>
    <p class="text-muted">
        كشف حركة كل عقد تأجير حتى {{ $filters['to'] }}: الفواتير والتمديدات وتسويات الإغلاق، وكل سند قبض أو صرف مرتبط بالعقد (ومنها دفعات ميسر)،
        وقيود الإنهاء والإشعارات من النظام القديم، والحركات على حساب العميل غير المربوطة بعقد. الأرقام تُقرأ من حساب العميل في دفتر الأستاذ
        فتطابق كشف حسابه، والمستند غير المرحّل بعد يُحتسب بقيمته حتى لا يختفي الدين.
    </p>
    <form class="panel" method="GET" action="{{ route('finance.client-receivables.index') }}">
        @if(request('branch_id'))<input type="hidden" name="branch_id" value="{{ request('branch_id') }}">@endif
        @if(request('client_id'))<input type="hidden" name="client_id" value="{{ request('client_id') }}"><p>عرض عقود العميل المحدد — <a href="{{ route('finance.client-receivables.index', request()->only('branch_id', 'to')) }}">كل العملاء</a></p>@endif
        <div class="row align-items-end">
            <div class="col-md-3 mb-2"><label for="cr-search">العميل / الجوال / رقم العقد</label><input id="cr-search" class="form-control" name="search" value="{{ $filters['search'] }}"></div>
            <div class="col-md-2 mb-2"><label for="cr-to">الرصيد حتى تاريخ</label><input id="cr-to" type="date" class="form-control" name="to" value="{{ $filters['to'] }}"></div>
            <div class="col-md-2 mb-2"><label for="cr-state">حالة العقد</label><select id="cr-state" class="form-control" name="state">@foreach(\Nami\Finance\Services\ClientReceivablesService::STATES as $key => $label)<option value="{{ $key }}" @selected($filters['state'] === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-1 mb-2"><label for="cr-extended">التمديد</label><select id="cr-extended" class="form-control" name="extended"><option value="">الكل</option><option value="1" @selected($filters['extended'] === '1')>ممددة</option><option value="0" @selected($filters['extended'] === '0')>دون تمديد</option></select></div>
            <div class="col-md-2 mb-2"><label for="cr-balance">الرصيد</label><select id="cr-balance" class="form-control" name="balance">@foreach(\Nami\Finance\Services\ClientReceivablesService::BALANCES as $key => $label)<option value="{{ $key }}" @selected($filters['balance'] === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2 mb-2"><label for="cr-sort">الترتيب</label><select id="cr-sort" class="form-control" name="sort">@foreach(\Nami\Finance\Services\ClientReceivablesService::SORTS as $key => $label)<option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-1 mb-2"><label for="cr-per">عملاء/صفحة</label><select id="cr-per" class="form-control" name="per_page">@foreach([25,50,100] as $size)<option value="{{ $size }}" @selected((int)$filters['per_page'] === $size)>{{ $size }}</option>@endforeach</select></div>
            <div class="col-md-1 mb-2"><button class="btn btn-success">عرض</button> <a class="btn btn-outline-secondary mt-1" href="{{ route('finance.client-receivables.index') }}">مسح</a></div>
        </div>
    </form>
    <div class="panel kpis">
        <div class="kpi">العملاء<strong>{{ number_format($totals->clients) }}</strong></div>
        <div class="kpi">العقود<strong>{{ number_format($totals->contracts) }}</strong></div>
        <div class="kpi">المفوتر (فواتير وتمديدات وتسويات)<strong>{{ $money($totals->billed) }}</strong></div>
        <div class="kpi">المسدّد (قبض − استرداد)<strong>{{ $money($totals->paid) }}</strong></div>
        <div class="kpi">إشعارات دائنة وتسويات<strong>{{ $money($totals->adjustments) }}</strong></div>
        <div class="kpi">حركات غير مربوطة ومقاصة<strong>{{ $money($totals->offsets) }}</strong></div>
        <div class="kpi is-due">المستحق على العملاء (ريال)<strong>{{ $money($totals->due) }}</strong></div>
        <div class="kpi is-credit">أرصدة دائنة للعملاء<strong>{{ $money($totals->credit) }}</strong></div>
    </div>

    <div class="panel">
        <h6>مطابقة مع دفتر الأستاذ — لعملاء النتائج ({{ number_format($totals->clients) }} عميل، كل عقودهم)</h6>
        <table class="recon" style="max-width:720px">
            <tr><td>رصيد حساباتهم في الأستاذ (قيود معتمدة)</td><td class="money">{{ $money($recon->ledger) }}</td></tr>
            <tr><td>قيود على حساباتهم بانتظار الاعتماد (تُضاف)</td><td class="money">{{ $money($recon->pending) }}</td></tr>
            <tr><td>قيود استقدام / نقل كفالة / تفاويض على نفس الحسابات (تُستبعد)</td><td class="money">{{ $money(-$recon->other_business) }}</td></tr>
            <tr><td>مستندات تأجير لم تُرحَّل للأستاذ بعد (تُضاف)</td><td class="money">{{ $money($recon->unposted) }}</td></tr>
            <tr><td>دفعات مسجلة في المستند بلا سند قبض (تُخصم)</td><td class="money">{{ $money(-$recon->unbacked) }}</td></tr>
            @if(abs($recon->mixed_cap) > 0.005)<tr><td>حدّ العملاء المشتركين: رصيد التأجير لا يتجاوز رصيد حساب العميل كاملاً</td><td class="money">{{ $money(-$recon->mixed_cap) }}</td></tr>@endif
            <tr class="total"><td>المجموع = صافي رصيد التأجير لهؤلاء العملاء</td><td class="money">{{ $money($recon->rental_net) }}</td></tr>
        </table>
        <p class="note mt-2">
            صافي رصيد التأجير = المستحق − الأرصدة الدائنة على كل عقود هؤلاء العملاء.
            @if($recon->mixed_clients) {{ $recon->mixed_clients }} عميل منهم لديه استقدام أو نقل كفالة أيضاً؛ استُبعدت قيود تلك الخدمات المعروفة، وما لم يُعرف مصدره وُزِّع على عقود التأجير فراجع كشف حسابه عند الشك. @endif
            @if($recon->clients_without_account) {{ $recon->clients_without_account }} عميل بلا حساب محاسبي، فرصيده من المستندات وحدها. @endif
        </p>
    </div>

    <p class="text-muted">كل عميل يظهر مرة واحدة وتحته عقوده {{ $filters['sort'] === 'latest' ? 'من الأحدث إلى الأقدم' : 'من الأعلى مستحقاً' }}، ومنها العقود السابقة التي لم تُصفَّ حساباتها. «كشف الحركة» يعرض كل حركة على العقد.</p>
    <div class="panel">
    <div class="table-responsive"><table>
        <thead><tr><th>العقد / الحالة</th><th>المدة</th><th>المفوتر</th><th>المسدّد</th><th>إشعارات وتسويات</th><th>قيود الحساب / مقاصة</th><th>المتبقي</th><th>التفاصيل</th></tr></thead>
        <tbody>
        @forelse($rows as $client)
            <tr class="client-head">
                <td colspan="4">
                    @if($client->user_id)<a href="{{ route('finance.client-receivables.index', ['client_id' => $client->user_id, 'balance' => 'all', 'branch_id' => request('branch_id'), 'to' => $filters['to']]) }}"><strong>{{ $client->client_name ?: 'عميل غير متاح' }}</strong></a>@else <strong>عميل غير متاح</strong> @endif
                    <span class="text-muted">{{ $client->client_phone }}</span>
                    @if($client->client_mixed)<span class="badge bg-warning text-dark">لديه استقدام / نقل كفالة</span>@endif
                    <div class="note">
                        عدد عقوده: {{ $client->all_contracts_count }} — المعروض هنا {{ $client->contracts_count }}، منها {{ $client->due_contracts }} عليه مستحق
                        @if($client->account_id)
                            — رصيد حسابه في الأستاذ: <span class="money">{{ $money($client->client_ledger_balance) }}</span>
                            @if(abs($client->client_pending) > 0.005) + بانتظار الاعتماد: <span class="money">{{ $money($client->client_pending) }}</span>@endif
                            @if($statementUrl) — <a href="{{ route('finance.account-statement-clients.index', ['account_id' => $client->account_id]) }}">كشف حساب العميل</a>@endif
                        @else — لا يوجد حساب محاسبي للعميل @endif
                    </div>
                </td>
                <td colspan="3" class="money">إجمالي على العميل: <strong>{{ $money($client->net) }}</strong>
                    @if($client->credit > 0.005)<div class="note">مستحق {{ $money($client->due) }} − رصيد دائن {{ $money($client->credit) }}</div>@endif
                </td>
                <td></td>
            </tr>
            @foreach($client->contracts as $row)
            @php
                $ended = $row->effective_end && \Carbon\Carbon::parse($row->effective_end)->startOfDay()->lt(now()->startOfDay());
                $closed = $row->closed_at || $row->status === 'completed';
                $status = (new \App\Models\RentalContract(['status' => $row->status]))->status_text;
                $isOlder = \Nami\Finance\Services\ClientReceivablesService::contractDate($row) < $client->newest_contract_date;
            @endphp
            <tr>
                <td>{{ $row->contract_number ?: $row->id }}@if($isOlder && $row->remaining > 0.005) <div class="older">عقد سابق لم يُصفَّ</div>@endif<div>{{ $status }}</div>@if(!$closed && $row->status !== 'cancelled')<small>{{ $row->effective_end ? ($ended ? 'منتهي المدة' : 'ساري المدة') : 'تاريخ الانتهاء غير محدد' }}</small>@endif</td>
                <td>من: {{ $row->start_date ?: '—' }}<br>النهاية الأصلية: {{ $row->original_end ?: '—' }}<br>النهاية بعد التمديد: {{ $row->effective_end ?: '—' }}@if($row->closed_at)<br>الإغلاق: {{ $row->closed_at }}@endif<br>{{ $row->extension_count ? 'ممدد (' . $row->extension_count . ')' : 'دون تمديد' }}</td>
                <td class="money">{{ $money($row->billed) }}</td><td class="money">{{ $money($row->paid) }}</td><td class="money">{{ $money($row->adjustments) }}</td><td class="money">{{ $money($row->offsets) }}</td>
                <td class="money"><strong>{{ $money($row->remaining) }}</strong><div>{{ $row->remaining > 0.005 ? 'مستحق' : ($row->remaining < -0.005 ? 'رصيد للعميل' : 'لا يوجد مستحق') }}</div>
                    @if(abs($row->unposted) > 0.005)<div class="note">منه غير مرحّل: {{ $money($row->unposted) }}</div>@endif
                    @if(abs($row->pending) > 0.005)<div class="note">منه بانتظار الاعتماد: {{ $money($row->pending) }}</div>@endif
                </td>
                <td><details><summary>كشف الحركة</summary><div class="details">
                    <table><thead><tr><th>التاريخ</th><th>الحركة</th><th>البيان</th><th>مدين</th><th>دائن</th></tr></thead><tbody>
                    @forelse($movements->get($row->id, collect()) as $movement)
                        <tr><td>{{ $movement->date ?: '—' }}</td><td>{{ $movement->label }}</td><td>{{ $movement->statement }}</td><td class="money">{{ $movement->debit ? $money($movement->debit) : '' }}</td><td class="money">{{ $movement->credit ? $money($movement->credit) : '' }}</td></tr>
                    @empty<tr><td colspan="5">لا توجد حركات مالية على العقد.</td></tr>@endforelse
                    </tbody></table>
                    <h6 class="mt-3">المطالبات بعد التوزيع</h6>
                    <table><thead><tr><th>المطالبة</th><th>التاريخ</th><th>القيمة</th><th>المسدّد</th><th>إشعارات</th><th>مقاصة</th><th>المتبقي</th></tr></thead><tbody>
                    @forelse($documents->get($row->id, collect()) as $document)
                        <tr><td>{{ $document->label }}</td><td>{{ \Nami\Finance\Services\RentalReceivablesLedger::dateOf($document->date) }}</td><td class="money">{{ $money($document->total_due) }}</td><td class="money">{{ $money($document->paid_amount) }}</td><td class="money">{{ $money($document->adjustment_amount) }}</td><td class="money">{{ $money($document->offset_amount) }}</td><td class="money">{{ $money($document->remaining_amount) }}</td></tr>
                    @empty<tr><td colspan="7">لا توجد مطالبات.</td></tr>@endforelse
                    </tbody></table>
                    <h6 class="mt-3">سجل التمديدات</h6>
                    @forelse($extensions->get($row->id, collect()) as $extension)<p>#{{ $extension->id }}: {{ $extension->start_date ?: '—' }} إلى {{ $extension->end_date ?: '—' }} — {{ $extension->extension_value !== null ? $money($extension->extension_value) . ' ريال' : 'القيمة غير مسجلة' }}</p>@empty<p>لا توجد تمديدات.</p>@endforelse
                </div></details></td>
            </tr>
            @endforeach
        @empty<tr><td colspan="8">لا توجد عقود تطابق الفلاتر.</td></tr>@endforelse
        </tbody>
    </table></div>
    @if($rows->total())<p class="mt-3">عرض العملاء {{ $rows->firstItem() }} – {{ $rows->lastItem() }} من {{ $rows->total() }} عميل</p>@endif
    {{ $rows->onEachSide(1)->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection
