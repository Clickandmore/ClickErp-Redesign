@extends('click.layout.indexs.index')
@section('page-title', $bladeTitle)
@section('page-links')
    <li class="breadcrumb-item active">{{ $bladeTitle }}</li>
@endsection
@section('style')
<style id="position-style">
    .position-report{direction:rtl;color:#172b46;font-size:14px}.position-hero{background:linear-gradient(120deg,#13374b,#21685f);color:white;border-radius:16px;padding:26px;margin-bottom:20px}.position-hero h2{font-size:25px;color:white;margin-bottom:8px}.position-hero p{margin:0;opacity:.9}.position-filters,.position-panel{background:white;border:1px solid #e1e7ed;border-radius:12px;padding:20px;margin-bottom:20px}.position-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}.position-metric{padding:18px;background:#fff;border:1px solid #e1e7ed;border-top:3px solid #238276;border-radius:12px}.position-metric span{display:block;color:#66758a;font-size:13px}.position-metric strong{display:block;font-size:23px;margin-top:8px;font-variant-numeric:tabular-nums}.position-table{width:100%;border-collapse:collapse}.position-table th,.position-table td{padding:11px 14px;border-bottom:1px solid #e4eaf0}.position-table thead th{background:#f3f6fa;color:#53657c}.position-table .position-section td{background:#eaf2f6;font-weight:700;color:#194d65}.position-table .position-total td{background:#f1f7f4;font-weight:700;border-top:2px solid #c4ddd3}.position-table .position-grand td{background:#163f4a;color:#fff;font-weight:700;font-size:16px}.position-number{text-align:left;direction:ltr;white-space:nowrap;font-variant-numeric:tabular-nums}.position-negative{color:#b42318}.position-note{font-size:13px;color:#617085;line-height:1.9}.position-code{color:#617085;font-variant-numeric:tabular-nums}.position-parent{font-weight:600}.position-check{padding:16px;border-radius:10px;margin-bottom:18px}.position-check.good{background:#e9f7ef;color:#17653c}.position-check.bad{background:#fff0ed;color:#9d2920}@media(max-width:800px){.position-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.position-hero h2{font-size:20px}}@media print{.position-no-print{display:none!important}.position-report{font-family:Arial,sans-serif;font-size:11px}.position-hero{background:white;color:#172b46;padding:8px}.position-hero h2{color:#172b46}.position-metric strong{font-size:17px}.position-panel{padding:0;border:0}.position-table th,.position-table td{padding:6px}.position-table thead{display:table-header-group}tr{break-inside:avoid}.position-table .position-grand td{color:#172b46;background:#eaf2f6}}
</style>
@endsection
@section('content')
@php
    $money = fn ($minor) => ($minor < 0 ? '(' : '') . number_format(abs($minor) / 100, 2) . ($minor < 0 ? ')' : '');
    $comparisonRows = [];
    foreach (($comparison['sections'] ?? []) as $key => $section) $comparisonRows[$key] = collect($section['rows'])->keyBy('id');
    $cols = $comparison ? 5 : 3;
@endphp
<div class="position-report p-3">
    <div class="position-hero position-no-print">
        <h2><i class="fa fa-balance-scale me-2"></i> قائمة المركز المالي</h2>
        <p>صورة واضحة للأصول والالتزامات وحقوق الملكية في تاريخ محدد</p>
    </div>
    <form method="GET" action="{{ route('finance.finance-center-list.index') }}" class="position-filters position-no-print">
        <div class="row g-3 align-items-end">
            <div class="col-md-3"><label for="position-date" class="form-label">المركز المالي كما في</label><input id="position-date" type="date" name="to_date" value="{{ $report['date'] }}" required class="form-control"></div>
            <div class="col-md-3"><label for="position-compare" class="form-label">تاريخ المقارنة (اختياري)</label><input id="position-compare" type="date" name="compare_date" value="{{ $comparison['date'] ?? '' }}" class="form-control"></div>
            <div class="col-md-2"><label for="position-level" class="form-label">تفصيل الحسابات</label><select id="position-level" name="level" class="form-control">@foreach(['0'=>'رئيسي','1'=>'متوسط','2'=>'تفصيلي','all'=>'جميع الحسابات'] as $value=>$label)<option value="{{ $value }}" {{ (string)$level === (string)$value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><label><input type="checkbox" name="show_zero" value="1" {{ $showZero ? 'checked' : '' }}> إظهار الأرصدة الصفرية</label></div>
            <div class="col-md-2"><button class="btn btn-success w-100" type="submit"><i class="fa fa-search me-1"></i> عرض القائمة</button></div>
        </div>
        @if($errors->any())<div class="alert alert-danger mt-3">{{ $errors->first() }}</div>@endif
    </form>
    <div class="d-flex justify-content-end gap-2 mb-3 position-no-print">
        <a class="btn btn-outline-secondary" href="{{ route('finance.income-list.index') }}">قائمة الدخل</a>
        <button type="button" id="position-print" class="btn btn-outline-success"><i class="fa fa-print me-1"></i> طباعة / PDF</button>
    </div>
    <div id="position-print-content">
        @include('finance::finance.print-header')
        <div class="text-center mb-4"><h4>قائمة المركز المالي (الميزانية العمومية)</h4><div>كما في {{ $report['date'] }} @if($comparison) — المقارنة: {{ $comparison['date'] }} @endif</div><small class="text-muted">جميع المبالغ بالريال السعودي</small></div>
        <div class="position-metrics">
            @foreach(['assets'=>'إجمالي الأصول','liabilities'=>'إجمالي الالتزامات','equity'=>'إجمالي حقوق الملكية','difference'=>'فرق المعادلة المحاسبية'] as $key=>$label)
                <div class="position-metric"><span>{{ $label }}</span><strong class="position-number {{ $report[$key] < 0 ? 'position-negative' : '' }}">{{ $money($report[$key]) }}</strong>@if($comparison)<small>المقارنة: {{ $money($comparison[$key]) }}</small>@endif</div>
            @endforeach
        </div>
        @foreach(array_filter([$report, $comparison]) as $check)
            <div class="position-check {{ $check['balanced'] && !$check['incomplete'] && !$check['issues'] && $check['postingCount'] ? 'good' : 'bad' }}">
                <strong>{{ $check['date'] }} — {{ !$check['postingCount'] ? 'لا توجد حركات معتمدة حتى هذا التاريخ' : ($check['balanced'] ? 'المعادلة المحاسبية متوازنة' : 'القائمة غير متوازنة — تحتاج إلى مراجعة') }}</strong>
                @if(!$check['balanced'])<div>الأصول − الالتزامات − حقوق الملكية = {{ $money($check['difference']) }} ر.س. فرق المدين والدائن لجميع الحسابات: {{ $money($check['ledger']) }} ر.س.</div>@endif
                @if($check['incomplete'])<div>توجد أرصدة تحتاج إلى تصنيف؛ راجع الأقسام الموضحة أدناه قبل الاعتماد على مؤشرات السيولة.</div>@endif
                @if($check['pending_count'])<div>عدد القيود غير المعتمدة المستبعدة: {{ $check['pending_count'] }}.</div>@endif
                @foreach($check['issues'] as $issue)<div>{{ $issue }}</div>@endforeach
            </div>
        @endforeach
        <div class="position-panel table-responsive">
            <table class="position-table">
                <thead><tr><th>الكود</th><th>الحساب / البند</th><th class="position-number">{{ $report['date'] }}</th>@if($comparison)<th class="position-number">{{ $comparison['date'] }}</th><th class="position-number">التغير</th>@endif</tr></thead>
                <tbody>
                @foreach($report['sections'] as $key=>$section)
                    @php
                        $visible = [];
                        foreach ($section['rows'] as $row) {
                            $previous = $comparisonRows[$key][$row['id']]['amount'] ?? 0;
                            $previousOwn = $comparisonRows[$key][$row['id']]['own'] ?? 0;
                            if ($row['own'] || $row['amount'] || $previous || $previousOwn) {
                                foreach ($row['path'] ?: [$row['id']] as $id) $visible[$id] = true;
                            }
                        }
                        $optional = in_array($key, ['other_assets','other_liabilities','unclassified']);
                    @endphp
                    @if(!$optional || count($visible) || $showZero)
                    <tr class="position-section"><td colspan="{{ $cols }}">{{ $section['label'] }}</td></tr>
                    @foreach($section['rows'] as $row)
                        @continue(!$showZero && !isset($visible[$row['id']]))
                        @continue($level !== 'all' && $row['depth'] > (int)$level)
                        @php $previous = $comparisonRows[$key][$row['id']]['amount'] ?? 0; @endphp
                        <tr class="{{ $row['depth'] === 0 ? 'position-parent' : '' }}">
                            <td class="position-code">{{ $row['code'] }}</td><td style="padding-inline-start:{{ 14 + $row['depth'] * 18 }}px">{{ $row['name'] }}</td>
                            <td class="position-number {{ $row['amount'] < 0 ? 'position-negative' : '' }}">{{ $money($row['amount']) }}</td>
                            @if($comparison)<td class="position-number">{{ $money($previous) }}</td><td class="position-number">{{ $money($row['amount'] - $previous) }}</td>@endif
                        </tr>
                    @endforeach
                    @if($key === 'equity')
                        <tr><td>—</td><td>نتيجة الأعمال غير المقفلة (ربح / خسارة)<div class="position-note">رصيد الإيرادات ناقص المصروفات بعد القيود الختامية، حتى تاريخ القائمة</div></td><td class="position-number">{{ $money($report['result']) }}</td>@if($comparison)<td class="position-number">{{ $money($comparison['result']) }}</td><td class="position-number">{{ $money($report['result'] - $comparison['result']) }}</td>@endif</tr>
                    @endif
                    @php
                        $total = $key === 'equity' ? $report['equity'] : $section['total'];
                        $previousTotal = $key === 'equity' ? ($comparison['equity'] ?? 0) : ($comparison['sections'][$key]['total'] ?? 0);
                    @endphp
                    <tr class="position-total"><td colspan="2">إجمالي {{ $section['label'] }}</td><td class="position-number">{{ $money($total) }}</td>@if($comparison)<td class="position-number">{{ $money($previousTotal) }}</td><td class="position-number">{{ $money($total - $previousTotal) }}</td>@endif</tr>
                    @endif
                    @if(in_array($key, ['other_assets','other_liabilities','equity']))
                        @php
                            $summaryKey = ['other_assets'=>'assets','other_liabilities'=>'liabilities','equity'=>'combined'][$key];
                            $summaryLabel = ['assets'=>'إجمالي الأصول','liabilities'=>'إجمالي الالتزامات','combined'=>'إجمالي الالتزامات وحقوق الملكية'][$summaryKey];
                            $total = $summaryKey === 'combined' ? $report['liabilities'] + $report['equity'] : $report[$summaryKey];
                            $previousTotal = !$comparison ? 0 : ($summaryKey === 'combined' ? $comparison['liabilities'] + $comparison['equity'] : $comparison[$summaryKey]);
                        @endphp
                        <tr class="position-grand"><td colspan="2">{{ $summaryLabel }}</td><td class="position-number">{{ $money($total) }}</td>@if($comparison)<td class="position-number">{{ $money($previousTotal) }}</td><td class="position-number">{{ $money($total - $previousTotal) }}</td>@endif</tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="position-panel">
            <h5>مؤشرات لدعم القرار</h5>
            @if($report['incomplete'] || !$report['balanced'] || $report['issues'] || !$report['postingCount'])
                <p class="position-note mb-0">لا تُعرض مؤشرات السيولة حتى اكتمال تصنيف الأرصدة والتحقق من توازن القائمة.</p>
            @else
                <div class="row"><div class="col-md-6">رأس المال العامل: <strong>{{ $money($report['working_capital']) }} ر.س.</strong><div class="position-note">الأصول المتداولة − الالتزامات المتداولة</div></div><div class="col-md-6">نسبة التداول: <strong>{{ $report['current_ratio'] === null ? 'غير قابلة للاحتساب' : number_format($report['current_ratio'], 2) }}</strong><div class="position-note">الأصول المتداولة ÷ الالتزامات المتداولة</div></div></div>
            @endif
        </div>
        <div class="position-note">
            <strong>أساس إعداد القائمة:</strong> أرصدة تراكمية من بداية السجلات حتى التاريخ المحدد، تشمل القيود الافتتاحية والختامية المعتمدة وتستبعد القيود المحذوفة وغير المعتمدة. تعتمد التصنيفات على شجرة الحسابات؛ الأرصدة بين قوسين تخصم من القسم، والحسابات خارج التصنيف تعرض بصافي المدين ناقص الدائن. إجماليات الأقسام لا تجمع الحساب الأب مع فروعه مرتين. تغيير مستوى العرض لا يغيّر الإجماليات.
            <br>التوازن الحسابي لا يغني عن مراجعة اكتمال القيود وصحة تصنيفها. نتيجة الأعمال المعروضة تراكمية غير مقفلة، وليست بالضرورة ربح السنة وحدها.
        </div>
    </div>
</div>
@endsection
@section('js')
<script>
    document.getElementById('position-print').addEventListener('click', function () {
        const popup = window.open('', '_blank');
        if (!popup) { alert('يرجى السماح بالنوافذ المنبثقة للطباعة'); return; }
        popup.document.documentElement.lang = 'ar';
        popup.document.documentElement.dir = 'rtl';
        popup.document.title = 'قائمة المركز المالي';
        popup.document.body.className = 'position-report';
        const charset = popup.document.createElement('meta');
        charset.setAttribute('charset', 'utf-8');
        popup.document.head.appendChild(charset);
        popup.document.head.appendChild(document.getElementById('position-style').cloneNode(true));
        popup.document.body.appendChild(document.getElementById('position-print-content').cloneNode(true));
        Promise.all(Array.from(popup.document.images).map(img => img.complete ? Promise.resolve() : new Promise(resolve => { img.onload = resolve; img.onerror = resolve; }))).then(() => { popup.focus(); popup.print(); });
    });
</script>
@endsection
