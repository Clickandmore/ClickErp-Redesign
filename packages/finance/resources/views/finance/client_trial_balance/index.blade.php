@extends('click.layout.indexs.index')
@section('page-title', $bladeTitle)
@section('page-links')<li class="breadcrumb-item active">{{ $bladeTitle }}</li>@endsection
@section('style')
<style>
.ctb{direction:rtl;color:#183b49}
.ctb .panel{background:#fff;border:1px solid #dce6e9;border-radius:14px;padding:18px;margin-bottom:16px}
.ctb .filters label{font-size:13px;color:#4c6470;margin-bottom:4px}
.ctb .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-bottom:16px}
.ctb .kpi{background:#fff;border:1px solid #dce6e9;border-radius:12px;padding:12px 14px}
.ctb .kpi span{display:block;color:#647985;font-size:12.5px}
.ctb .kpi b{font-size:18px;font-variant-numeric:tabular-nums}
.ctb table{width:100%;border-collapse:collapse;font-size:13.5px}
.ctb th,.ctb td{border-bottom:1px solid #e6edef;padding:8px 10px;text-align:right;vertical-align:middle}
.ctb thead th{background:#f3f7f8;font-weight:600;white-space:nowrap;position:sticky;top:0}
.ctb .n{direction:ltr;text-align:left;white-space:nowrap;font-variant-numeric:tabular-nums}
.ctb .muted{color:#9aa9b0}
.ctb tfoot td{font-weight:700;background:#f3f7f8}
.ctb .chips span{display:inline-block;background:#eef5f3;border-radius:6px;padding:2px 8px;margin:2px 0 2px 4px;font-size:12.5px}
@media print{.ctb .no-print{display:none!important}.ctb .panel{border:0;padding:0}.ctb th,.ctb td{padding:4px;font-size:11px}}
</style>
@endsection
@section('content')
@php $money = fn ($v) => (float) $v == 0.0 ? '<span class="muted">0.00</span>' : number_format((float) $v, 2); @endphp
<div class="ctb p-3">
    <form method="GET" action="{{ route('finance.client-trial-balance.index') }}" class="panel filters no-print">
        <div class="row g-3 align-items-end">
            <div class="col-md-2"><label for="ctb-from">من تاريخ</label><input id="ctb-from" class="form-control" type="date" name="from_date" value="{{ $filters['from_date'] }}"></div>
            <div class="col-md-2"><label for="ctb-to">إلى تاريخ</label><input id="ctb-to" class="form-control" type="date" name="to_date" value="{{ $filters['to_date'] }}"></div>
            <div class="col-md-3"><label for="ctb-balance">الرصيد</label>
                <select id="ctb-balance" class="form-control" name="balance">@foreach(\Nami\Finance\Services\ClientTrialBalanceService::BALANCE_FILTERS as $key => $label)<option value="{{ $key }}" @selected($filters['balance'] === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><label for="ctb-movement">الحركة</label>
                <select id="ctb-movement" class="form-control" name="movement">@foreach(\Nami\Finance\Services\ClientTrialBalanceService::MOVEMENT_FILTERS as $key => $label)<option value="{{ $key }}" @selected($filters['movement'] === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3"><label for="ctb-search">بحث بالاسم أو الكود</label><input id="ctb-search" class="form-control" type="search" name="search" value="{{ $filters['search'] }}" placeholder="مثال: الفرج أو 1203"></div>

            <div class="col-md-2"><label for="ctb-min">الرصيد لا يقل عن</label><input id="ctb-min" class="form-control" type="number" step="0.01" min="0" name="min_balance" value="{{ $filters['min_balance'] }}"></div>
            <div class="col-md-2"><label for="ctb-opening">رصيد أول المدة</label>
                <select id="ctb-opening" class="form-control" name="with_opening"><option value="1" @selected($filters['with_opening'])>يشمل أول المدة</option><option value="0" @selected(!$filters['with_opening'])>حركة الفترة فقط</option></select></div>
            <div class="col-md-2"><label for="ctb-approved">القيود</label>
                <select id="ctb-approved" class="form-control" name="approved_only"><option value="0" @selected(!$filters['approved_only'])>كل القيود</option><option value="1" @selected($filters['approved_only'])>المعتمدة فقط</option></select></div>
            <div class="col-md-2"><label for="ctb-sort">الترتيب</label>
                <select id="ctb-sort" class="form-control" name="sort">@foreach(\Nami\Finance\Services\ClientTrialBalanceService::SORTS as $key => $label)<option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-1"><label for="ctb-per">لكل صفحة</label>
                <select id="ctb-per" class="form-control" name="per_page">@foreach($perPageOptions as $n)<option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>@endforeach</select></div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-success" type="submit"><i class="fa fa-search"></i> عرض</button>
                <a class="btn btn-outline-secondary" href="{{ route('finance.client-trial-balance.index') }}">مسح الفلاتر</a>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-2 no-print">
        <div class="chips">@foreach($description as $line)<span>{{ $line }}</span>@endforeach</div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" type="button" onclick="window.print()"><i class="fa fa-print"></i> طباعة</button>
            <a class="btn btn-primary btn-sm" href="{{ route('finance.client-trial-balance.export', request()->except('page')) }}"><i class="fa fa-file-excel-o"></i> تصدير Excel بنفس الفلاتر</a>
        </div>
    </div>

    <div class="kpis">
        <div class="kpi"><span>عدد العملاء</span><b>{{ number_format($count) }}</b></div>
        <div class="kpi"><span>حركة الفترة مدين</span><b class="n">{{ number_format($totals['period_debit'], 2) }}</b></div>
        <div class="kpi"><span>حركة الفترة دائن</span><b class="n">{{ number_format($totals['period_credit'], 2) }}</b></div>
        <div class="kpi"><span>أرصدة مدينة (على العملاء)</span><b class="n">{{ number_format($totals['closing_debit'], 2) }}</b></div>
        <div class="kpi"><span>أرصدة دائنة (للعملاء)</span><b class="n">{{ number_format($totals['closing_credit'], 2) }}</b></div>
        <div class="kpi"><span>الصافي</span><b class="n">{{ number_format($totals['net'], 2) }}</b></div>
    </div>

    <div class="panel">
        <div class="d-none d-print-block mb-2"><h4>{{ $bladeTitle }}</h4>@foreach($description as $line)<div>{{ $line }}</div>@endforeach</div>
        @if($count === 0)
            <p class="text-muted m-0">لا يوجد عملاء يطابقون الفلاتر.</p>
        @else
        <div class="table-responsive"><table>
            <thead>
                <tr><th rowspan="2">#</th><th rowspan="2">الكود</th><th rowspan="2">العميل</th><th colspan="2">أول المدة</th><th colspan="2">حركة الفترة</th><th colspan="2">الرصيد</th></tr>
                <tr><th>مدين</th><th>دائن</th><th>مدين</th><th>دائن</th><th>مدين</th><th>دائن</th></tr>
            </thead>
            <tbody>
            @foreach($rows as $i => $row)
                <tr>
                    <td>{{ $rows->firstItem() + $i }}</td>
                    <td class="n">{{ $row->code }}</td>
                    <td>{{ $row->name }}</td>
                    <td class="n">{!! $money($row->opening_debit) !!}</td>
                    <td class="n">{!! $money($row->opening_credit) !!}</td>
                    <td class="n">{!! $money($row->period_debit) !!}</td>
                    <td class="n">{!! $money($row->period_credit) !!}</td>
                    <td class="n">{!! $money($row->closing_debit) !!}</td>
                    <td class="n">{!! $money($row->closing_credit) !!}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="3">الإجمالي (كل النتائج المفلترة)</td>
                    <td class="n">{{ number_format($totals['opening_debit'], 2) }}</td><td class="n">{{ number_format($totals['opening_credit'], 2) }}</td>
                    <td class="n">{{ number_format($totals['period_debit'], 2) }}</td><td class="n">{{ number_format($totals['period_credit'], 2) }}</td>
                    <td class="n">{{ number_format($totals['closing_debit'], 2) }}</td><td class="n">{{ number_format($totals['closing_credit'], 2) }}</td></tr>
            </tfoot>
        </table></div>
        <div class="mt-3 no-print">{{ $rows->links() }}</div>
        @endif
    </div>
</div>
@endsection
