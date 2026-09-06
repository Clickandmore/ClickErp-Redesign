@extends('click.layout.indexs.index')
@section('page-title', $bladeTitle)
@section('page-links')<li class="breadcrumb-item active">{{ $bladeTitle }}</li>@endsection
@section('style')
<style id="cash-report-style">
.cash-report{direction:rtl;color:#183448}.cash-hero{background:linear-gradient(120deg,#173f57,#237666);color:#fff;border-radius:16px;padding:25px;margin-bottom:20px}.cash-hero h3{color:white}.cash-panel{background:white;border:1px solid #e0e7ed;border-radius:12px;padding:20px;margin-bottom:20px}.cash-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}.cash-card{border:1px solid #e0e7ed;border-top:3px solid #288b78;background:white;border-radius:12px;padding:18px}.cash-card span{display:block;color:#627587}.cash-card strong{display:block;font-size:24px;margin-top:8px}.cash-number{direction:ltr;text-align:left;white-space:nowrap;font-variant-numeric:tabular-nums}.cash-negative{color:#b42318}.cash-table{width:100%;border-collapse:collapse;font-size:13px}.cash-table th,.cash-table td{padding:10px 12px;border-bottom:1px solid #e1e8ee;vertical-align:top}.cash-table th{background:#edf4f7}.cash-table tfoot{font-weight:bold;background:#e9f5ef}.cash-note{font-size:13px;color:#647587;line-height:1.9}.cash-table .cash-statement{min-width:230px;white-space:normal}.cash-toolbar{display:flex;gap:10px;justify-content:flex-end;margin-bottom:16px}.cash-code{color:#61788b}.cash-report details summary{cursor:pointer;color:#216e62}.cash-print-header{text-align:center;margin-bottom:20px}@media(max-width:800px){.cash-cards{grid-template-columns:repeat(2,minmax(0,1fr))}.cash-card strong{font-size:19px}}@media print{.cash-no-print{display:none!important}.cash-report{font-family:Arial,sans-serif;font-size:11px}.cash-panel{padding:0;border:0}.cash-table th,.cash-table td{padding:5px}.cash-table thead{display:table-header-group}.cash-table tr{break-inside:avoid}.cash-card strong{font-size:16px}.cash-table{font-size:10px}.table-responsive{overflow:visible}}
</style>
@endsection
@section('content')
@php
$money = fn ($value) => ($value < 0 ? '(' : '') . number_format(abs($value)/100,2) . ($value < 0 ? ')' : '');
$kinds = ['all'=>'الصناديق والبنوك والمحافظ','cash'=>'الصناديق','bank'=>'البنوك','wallet'=>'المحافظ الإلكترونية'];
$scopeLabel = $selectedAccount ? $accounts[$selectedAccount]->name . ' — ' . $accounts[$selectedAccount]->code : $kinds[$scope];
@endphp
<div class="cash-report p-3">
    <div class="cash-hero cash-no-print"><h3>حركة الصندوق والبنوك اليومية</h3><div>متابعة الأرصدة وحركة القبض والصرف والسيولة اليومية</div></div>
    <form method="GET" action="{{ route('finance.cash-movements.index') }}" class="cash-panel cash-no-print">
        <input type="hidden" name="view" value="movement"><div class="row g-3 align-items-end">
            <div class="col-md-2"><label for="cash-from" class="form-label">من تاريخ</label><input type="date" id="cash-from" name="from_date" value="{{ $report['from'] }}" required class="form-control"></div>
            <div class="col-md-2"><label for="cash-to" class="form-label">إلى تاريخ</label><input type="date" id="cash-to" name="to_date" value="{{ $report['to'] }}" required class="form-control"></div>
            <div class="col-md-3"><label for="cash-scope" class="form-label">مجموعة الحسابات</label><select id="cash-scope" name="scope" class="form-control">@foreach($kinds as $key=>$label)<option value="{{ $key }}" {{ $scope === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3"><label for="cash-account" class="form-label">حساب محدد (اختياري)</label><select id="cash-account" name="account_id" class="form-control select2"><option value="">كل حسابات المجموعة</option>@foreach($accounts as $account)<option value="{{ $account->id }}" {{ (string)$selectedAccount === (string)$account->id ? 'selected' : '' }}>{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><button type="submit" class="btn btn-success w-100">عرض التقرير</button></div>
        </div>
        <div class="cash-note mt-2">عند اختيار حساب محدد يعرض التقرير حركاته المباشرة فقط، ويكون هو النطاق المستخدم بدل مجموعة الحسابات.</div>
        @if($errors->any())<div class="alert alert-danger mt-3">{{ $errors->first() }}</div>@endif
    </form>
    <div class="cash-toolbar cash-no-print"><a class="btn btn-success" href="{{ route('finance.cash-movements.index') }}">المقبوضات والمصروفات — النموذج المرفق</a><a class="btn btn-outline-success" href="{{ route('finance.cash-movements.index', array_merge(request()->except('page'), ['export'=>'xlsx'])) }}">تصدير Excel كامل</a><button type="button" id="cash-print" class="btn btn-outline-secondary"><i class="fa fa-print"></i> طباعة / PDF</button></div>
    <div id="cash-print-content">
        @include('finance::finance.print-header')
        <div class="cash-print-header"><h4>تقرير حركة الصندوق والبنوك اليومية</h4><div>{{ $scopeLabel }}</div><div>من {{ $report['from'] }} إلى {{ $report['to'] }} — المبالغ بالريال السعودي</div></div>
        <div class="cash-cards">@foreach(['opening'=>'رصيد أول الفترة','receipts'=>'الحركة المدينة (قبض / زيادة)','payments'=>'الحركة الدائنة (صرف / نقص)','closing'=>'رصيد آخر الفترة'] as $key=>$label)<div class="cash-card"><span>{{ $label }}</span><strong class="cash-number {{ $report[$key] < 0 ? 'cash-negative' : '' }}">{{ $money($report[$key]) }}</strong></div>@endforeach</div>
        <div class="cash-panel"><strong>صافي الحركة: <span class="{{ $report['net'] < 0 ? 'cash-negative' : '' }}">{{ $money($report['net']) }} ر.س.</span></strong><div class="cash-note">رصيد أول الفترة + الحركة المدينة − الحركة الدائنة = رصيد آخر الفترة. التحويلات بين الحسابات المختارة تظهر في الطرفين ولا تغيّر صافي السيولة الإجمالي.</div></div>
        @if($report['pending'])<div class="alert alert-warning">يوجد {{ $report['pending'] }} قيدًا غير معتمد متعلقًا بهذه الحسابات حتى نهاية الفترة؛ مستبعد من الأرصدة والحركة.</div>@endif
        @if($report['negative_accounts'])<div class="alert alert-danger">يوجد {{ $report['negative_accounts'] }} حسابًا برصيد ختامي سالب. راجع تفاصيله وتسوياته قبل الاعتماد على الرصيد.</div>@endif
        @if(!$report['accounts'])<div class="alert alert-info">لا توجد حسابات ضمن المجموعة المختارة.</div>@endif
        <div class="cash-panel table-responsive"><h5>ملخص الحسابات</h5><p class="cash-note">لكل حساب رصيده المباشر؛ تظهر الحسابات ذات رصيد أو حركة، دون تكرار رصيد الحساب الأب مع فروعه.</p><table class="cash-table"><thead><tr><th>الكود</th><th>الصندوق / الحساب</th><th>النوع</th><th>أول الفترة</th><th>مدين</th><th>دائن</th><th>آخر الفترة</th></tr></thead><tbody>
        @foreach($report['accounts'] as $row)
            @continue(!$row['opening'] && !$row['receipts'] && !$row['payments'])
            <tr><td class="cash-code">{{ $row['account']->code }}</td><td><a href="{{ route('finance.cash-movements.index', ['view'=>'movement','from_date'=>$report['from'],'to_date'=>$report['to'],'account_id'=>$row['account']->id]) }}">{{ $row['account']->name }}</a></td><td>{{ $kinds[$row['account']->kind] }}</td>@foreach(['opening','receipts','payments','closing'] as $key)<td class="cash-number {{ $row[$key] < 0 ? 'cash-negative' : '' }}">{{ $money($row[$key]) }}</td>@endforeach</tr>
        @endforeach
        </tbody><tfoot><tr><td colspan="3">الإجمالي</td>@foreach(['opening','receipts','payments','closing'] as $key)<td class="cash-number">{{ $money($report[$key]) }}</td>@endforeach</tr></tfoot></table></div>
        <div class="cash-panel table-responsive"><h5>الحركة اليومية</h5><table class="cash-table"><thead><tr><th>التاريخ</th><th>الرصيد الافتتاحي</th><th>مدين</th><th>دائن</th><th>صافي الحركة</th><th>الرصيد الختامي</th></tr></thead><tbody>
        @forelse($report['days'] as $day)<tr><td>{{ $day['date'] }}</td>@foreach(['opening','receipts','payments'] as $key)<td class="cash-number">{{ $money($day[$key]) }}</td>@endforeach<td class="cash-number">{{ $money($day['receipts'] - $day['payments']) }}</td><td class="cash-number {{ $day['closing'] < 0 ? 'cash-negative' : '' }}">{{ $money($day['closing']) }}</td></tr>@empty<tr><td colspan="6" class="text-center">لا توجد حركات معتمدة خلال الفترة. الرصيد الافتتاحي يظل ظاهرًا أعلاه.</td></tr>@endforelse
        </tbody></table><div class="cash-note mt-2">تعرض الأيام التي بها حركة فقط. الرصيد الافتتاحي لكل يوم يشمل رصيد جميع الأيام السابقة.</div></div>
        <div class="cash-panel table-responsive"><h5>تفاصيل الحركة</h5><p class="cash-note">{{ $report['count'] }} حركة — صفحة {{ $report['page'] }} من {{ $report['pages'] }} (١٠٠ حركة لكل صفحة). الطباعة تشمل التفاصيل الظاهرة؛ Excel يتضمن كل حركات الفترة.</p>
        <table class="cash-table"><thead><tr><th>التاريخ / القيد</th><th>الحساب</th><th>البيان / الحسابات المقابلة</th><th>مدين</th><th>دائن</th><th>رصيد الحساب</th><th>رصيد النطاق</th></tr></thead><tbody>
        @forelse($report['movements'] as $row)
            <tr><td>{{ $row['date'] }}<br><a href="{{ route('finance.constraints.show', $row['constraint_id']) }}">قيد {{ $row['code'] ?: $row['constraint_id'] }}</a><div class="cash-note">{{ \Nami\Finance\Enums\ConstraintTypiesEnum::tryFrom((int)$row['type'])?->lang() ?? 'قيد محاسبي' }}</div></td><td>{{ $row['account']->code }} — {{ $row['account']->name }}</td><td class="cash-statement">{{ $row['statement'] ?: $row['entry_statement'] }}<details><summary>الحسابات الأخرى بالقيد</summary>@foreach($row['counterparts'] as $counterpart)<div class="cash-note">{{ $counterpart }}</div>@endforeach</details></td>@foreach(['receipts','payments','account_balance','balance'] as $key)<td class="cash-number {{ $row[$key] < 0 ? 'cash-negative' : '' }}">{{ $money($row[$key]) }}</td>@endforeach</tr>
        @empty<tr><td colspan="7" class="text-center">لا توجد حركة خلال الفترة المختارة.</td></tr>@endforelse
        </tbody></table>
        <div class="cash-toolbar cash-no-print mt-3">@if($report['page'] > 1)<a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['page'=>$report['page']-1]) }}">السابق</a>@endif @if($report['page'] < $report['pages'])<a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['page'=>$report['page']+1]) }}">التالي</a>@endif</div></div>
        <div class="cash-note">يعتمد التقرير على القيود المعتمدة غير المحذوفة، وفق تاريخ تفاصيل القيد. الرصيد الافتتاحي يشمل جميع السنوات السابقة للفترة. تشمل الحركة القيود الافتتاحية والتسويات والتحويلات عند وقوعها داخل الفترة؛ لذلك لا تمثل مجاميعها إيرادات أو مصروفات قائمة الدخل. تضم المجموعة الحسابات المصنفة في الدليل كصناديق وبنوك ومحافظ وفروعها، بما فيها العهد أو حسابات مساند المصنفة ضمنها. الأرقام بين قوسين أرصدة سالبة. الحسابات الأخرى في القيد المركب معروضة للمراجعة دون توزيع افتراضي للمبلغ عليها.</div>
    </div>
</div>
@endsection
@section('js')
<script>
if (window.jQuery && $.fn.select2) $('#cash-account').select2();
document.getElementById('cash-print').addEventListener('click', function () {
    const popup = window.open('', '_blank');
    if (!popup) { alert('يرجى السماح بالنوافذ المنبثقة للطباعة'); return; }
    popup.document.documentElement.lang = 'ar';
    popup.document.documentElement.dir = 'rtl';
    popup.document.title = 'حركة الصندوق والبنوك اليومية';
    popup.document.body.className = 'cash-report';
    popup.document.head.appendChild(document.getElementById('cash-report-style').cloneNode(true));
    popup.document.body.appendChild(document.getElementById('cash-print-content').cloneNode(true));
    Promise.all(Array.from(popup.document.images).map(img => img.complete ? Promise.resolve() : new Promise(resolve => { img.onload = resolve; img.onerror = resolve; }))).then(() => { popup.focus(); popup.print(); });
});
</script>
@endsection
