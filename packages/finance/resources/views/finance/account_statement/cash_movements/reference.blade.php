@extends('click.layout.indexs.index')
@section('page-title', $bladeTitle)
@section('page-links')<li class="breadcrumb-item active">{{ $bladeTitle }}</li>@endsection
@section('style')
<style id="daily-reference-style">
.daily-reference{direction:rtl;color:#183b49}.daily-reference .report-heading{padding:24px;border-radius:14px;background:#194f57;color:white;margin-bottom:20px}.daily-reference h3{color:inherit}.daily-reference .report-panel{padding:20px;background:white;border:1px solid #dce6e9;border-radius:12px;margin-bottom:20px}.daily-reference table{width:100%;border-collapse:collapse;font-size:14px}.daily-reference th,.daily-reference td{border:1px solid #e0e8eb;padding:10px 14px;vertical-align:top}.daily-reference th{background:#e8f4f0}.daily-reference .expense-heading{background:#fff0e4}.daily-reference .amount{direction:ltr;text-align:left;white-space:nowrap;font-variant-numeric:tabular-nums}.daily-reference .negative{color:#b42318}.daily-reference .total{font-weight:700;background:#edf4f7;font-size:17px}.daily-reference .note{color:#647985;font-size:13px;line-height:1.9}.daily-reference .two-totals{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}.daily-reference .two-totals strong{font-size:28px;display:block;margin-top:8px}.daily-reference .report-title{text-align:center;margin-bottom:20px}@media print{.daily-reference{font-family:Arial,sans-serif}.daily-reference .no-print{display:none!important}.daily-reference .report-panel{padding:0;border:0}.daily-reference th,.daily-reference td{padding:5px;font-size:11px}.daily-reference thead{display:table-header-group}.daily-reference tr{break-inside:avoid}.daily-reference .table-responsive{overflow:visible}}
</style>
@endsection
@section('content')
@php
$money = fn($n) => ($n < 0 ? '− ' : '') . number_format(abs($n)/100,2);
$receipts=$report['groups']['receipts']; $expenses=$report['groups']['expenses'];
@endphp
<div class="daily-reference p-3">
<div class="report-heading no-print"><h3>تقرير حركة صندوق اليومية</h3><div>المقبوضات والمصروفات — بنود وترتيب النموذج المرفق</div></div>
<form class="report-panel no-print" method="GET" action="{{ route('finance.cash-movements.index') }}">
<input type="hidden" name="view" value="reference">
<div class="row g-3 align-items-end">
<div class="col-md-3"><label for="reference-from">من تاريخ (اختياري)</label><input class="form-control" id="reference-from" type="date" name="from_date" value="{{ $report['from'] }}"><small>اتركه فارغًا لعرض جميع السجلات حتى تاريخ النهاية</small></div>
<div class="col-md-3"><label for="reference-to">إلى تاريخ</label><input class="form-control" id="reference-to" type="date" name="to_date" value="{{ $report['to'] }}" required></div>
<div class="col-md-3"><label for="reference-approval">القيود المحتسبة</label><select class="form-control" id="reference-approval" name="approval"><option value="approved" {{ $report['approval']==='approved' ? 'selected' : '' }}>المعتمدة فقط</option><option value="all" {{ $report['approval']==='all' ? 'selected' : '' }}>جميع القيود غير المحذوفة (للمراجعة)</option></select></div>
<div class="col-md-3"><button type="submit" class="btn btn-success">عرض التقرير</button><a class="btn btn-outline-secondary" href="{{ route('finance.cash-movements.index',['view'=>'movement']) }}">تفاصيل حركة الحسابات</a> <a class="btn btn-outline-secondary" href="{{ route('finance.daily-cash-reference-lines.index') }}">إدارة البنود</a></div>
</div>
@if($errors->any())<div class="alert alert-danger mt-3">{{ $errors->first() }}</div>@endif
</form>
<div class="mb-3 no-print"><button class="btn btn-outline-success" id="daily-reference-print" type="button">طباعة / PDF</button> <a class="btn btn-success" href="{{ route('finance.cash-movements.index', ['view'=>'reference','from_date'=>$report['from'],'to_date'=>$report['to'],'approval'=>$report['approval'],'export'=>'xlsx']) }}">تصدير Excel</a></div>
<div id="daily-reference-content">
@include('finance::finance.print-header')
<div class="report-title"><h3>تقرير حركة صندوق اليومية</h3><div>{{ $report['from'] ? 'من '.$report['from'] : 'جميع السجلات' }} حتى {{ $report['to'] }}</div><div>{{ $report['approval']==='approved' ? 'القيود المعتمدة فقط' : 'جميع القيود غير المحذوفة — يشمل غير المعتمد' }} — المبالغ بالريال السعودي</div></div>
@if($report['pending'])<div class="alert alert-warning">{{ $report['pending'] }} قيدًا غير معتمد مرتبطًا ببنود التقرير {{ $report['approval']==='approved' ? 'مستبعد من الأرقام المعروضة.' : 'محتسب ضمن الأرقام المعروضة للمراجعة.' }}</div>@endif
@if($report['issues'])<div class="alert alert-danger">التقرير يحتاج إلى مراجعة ربط الحسابات:@foreach($report['issues'] as $issue)<div>{{ $issue }}</div>@endforeach</div>@endif
<div class="two-totals"><div class="report-panel">إجمالي المقبوضات<strong class="amount">{{ $money($receipts['total']) }}</strong></div><div class="report-panel">إجمالي المصروفات<strong class="amount">{{ $money($expenses['total']) }}</strong></div></div>
<div class="report-panel table-responsive"><table><thead><tr><th colspan="2">المقبوضات</th><th colspan="2" class="expense-heading">المصروفات</th></tr><tr><th>الحساب</th><th>المبلغ</th><th>الحساب</th><th>المبلغ</th></tr></thead><tbody>
@for($i=0;$i<max(count($receipts['rows']),count($expenses['rows']));$i++)
<tr>@foreach([$receipts,$expenses] as $group)
@php $row=$group['rows'][$i] ?? null; @endphp
@if($row)<td>{{ $row['code'] }} — {{ $row['name'] }}</td><td class="amount {{ $row['amount'] < 0 ? 'negative' : '' }}">{{ $row['missing'] ? 'غير مربوط' : $money($row['amount']) }}</td>@else<td></td><td></td>@endif
@endforeach</tr>
@endfor
</tbody><tfoot><tr class="total"><td>إجمالي المقبوضات</td><td class="amount">{{ $money($receipts['total']) }}</td><td>إجمالي المصروفات</td><td class="amount">{{ $money($expenses['total']) }}</td></tr></tfoot></table></div>
<div class="note">يعرض التقرير البنود المحددة في المرفق بنفس ترتيبها، بما فيها السلف والعهد المدرجة في المصروفات. المبالغ محسوبة من سجلات البرنامج بصافي المدين ناقص الدائن لكل بند وفروعه ضمن الفترة، مع إظهار الأرصدة السالبة والصفرية. عند ترك تاريخ البداية فارغًا تظهر الأرصدة التراكمية. الأرقام ليست مبالغ ثابتة من الملف المرفق؛ قد تختلف حسب القيود والتواريخ وحالة الاعتماد. إجمالي المصروفات هنا يشمل بنود النموذج ولا يمثل وحده مصروفات قائمة الدخل. الحسابات غير الموجودة أو المكررة تظهر بتنبيه ولا تُعرض كأرصدة صفرية مؤكدة.</div>
</div></div>
@endsection
@section('js')
<script>
document.getElementById('daily-reference-print').addEventListener('click', function () {
 const popup=window.open('', '_blank');
 if (!popup) { alert('يرجى السماح بالنوافذ المنبثقة للطباعة'); return; }
 popup.document.documentElement.lang='ar'; popup.document.documentElement.dir='rtl';
 popup.document.title='تقرير حركة صندوق اليومية'; popup.document.body.className='daily-reference';
 popup.document.head.appendChild(document.getElementById('daily-reference-style').cloneNode(true));
 popup.document.body.appendChild(document.getElementById('daily-reference-content').cloneNode(true));
 Promise.all(Array.from(popup.document.images).map(img=>img.complete ? Promise.resolve() : new Promise(resolve=>{img.onload=resolve;img.onerror=resolve;}))).then(()=>{popup.focus();popup.print();});
});
</script>
@endsection
