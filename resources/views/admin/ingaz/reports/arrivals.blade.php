@extends('admin.layout.indexs.index')
@section('page-title', 'تقارير الوصول والاستقدام')
@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.arrival-report{--line:#e5e7eb;--muted:#64748b;--ink:#1f2937;--brand:var(--bs-primary,#0d6efd)}
.arrival-report .panel{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px 20px;margin-bottom:18px}
.arrival-report .panel-title{font-size:1rem;font-weight:700;color:var(--ink);margin:0}
.arrival-report .panel-title i{color:var(--brand);margin-inline-end:6px}
.arrival-report .panel-sub{font-size:.82rem;color:var(--muted);margin:2px 0 0}
/* Header */
.arrival-report .report-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.arrival-report .report-head-title{display:flex;align-items:center;gap:12px;min-width:0}
.arrival-report .report-head-icon{width:46px;height:46px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;background:rgba(13,110,253,.1);color:var(--brand);font-size:20px;flex-shrink:0}
.arrival-report .report-head h3{font-size:1.35rem;font-weight:700;color:var(--ink);margin:0 0 2px}
.arrival-report .head-actions{display:flex;gap:8px;flex-wrap:wrap}
.arrival-report .head-actions .btn,.arrival-report .soft-btn{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);background:#fff;color:#374151;border-radius:10px;padding:7px 14px;font-size:.88rem;text-decoration:none}
.arrival-report .head-actions .btn:hover,.arrival-report .soft-btn:hover{border-color:var(--brand);color:var(--brand)}
.arrival-report .report-tabs{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9}
.arrival-report .segmented{display:inline-flex;flex-wrap:wrap;gap:4px;padding:4px;border-radius:12px;background:#f1f5f9}
.arrival-report .segmented a,.arrival-report .segmented button{display:inline-flex;align-items:center;gap:6px;border:0;background:transparent;border-radius:9px;padding:7px 14px;color:#475569;font-size:.88rem;text-decoration:none;white-space:nowrap}
.arrival-report .segmented a:hover,.arrival-report .segmented button:hover{color:var(--ink)}
.arrival-report .segmented .active{background:#fff;color:var(--brand);font-weight:600;box-shadow:0 1px 3px rgba(15,23,42,.12)}
.arrival-report .delay-link{color:#b91c1c;border-color:#fecaca;background:#fef2f2}
.arrival-report .delay-link:hover{color:#991b1b;border-color:#f87171}
/* Metric cards */
.arrival-report .metric-section+.metric-section{margin-top:18px}
.arrival-report .metric-section-head{display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;margin-bottom:10px}
.arrival-report .metric-section-head h6{margin:0;font-weight:700;color:var(--ink)}
.arrival-report .metric-badge{font-size:.75rem;border-radius:999px;padding:3px 10px;background:#eef2ff;color:#4338ca}
.arrival-report .metric-badge.now{background:#fef3c7;color:#92400e}
.arrival-report .metric-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
.arrival-report .metric{--tone:#334155;--tone-soft:#f1f5f9;display:flex;flex-direction:column;gap:4px;padding:14px 16px;border:1px solid var(--line);border-top:4px solid var(--tone);border-radius:14px;background:#fff;color:var(--ink);text-decoration:none;transition:transform .15s ease,box-shadow .15s ease}
.arrival-report .metric:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(0,0,0,.07);color:var(--ink)}
.arrival-report .metric.active{box-shadow:0 0 0 .2rem rgba(13,110,253,.2);border-color:rgba(13,110,253,.6);border-top-color:var(--tone)}
.arrival-report .metric-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px}
.arrival-report .metric-icon{width:38px;height:38px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;background:var(--tone-soft);color:var(--tone);font-size:17px}
.arrival-report .metric-value{font-size:1.75rem;font-weight:800;line-height:1}
.arrival-report .metric-title{font-weight:700}
.arrival-report .metric-hint{font-size:.78rem;color:var(--muted)}
.arrival-report .metric-trend{margin-top:6px;padding-top:6px;border-top:1px dashed var(--line);font-size:.78rem;color:#4b5563}
.arrival-report .metric-trend.is-up{color:#15803d}.arrival-report .metric-trend.is-down{color:#b91c1c}
.arrival-report .tone-success{--tone:#16a34a;--tone-soft:#dcfce7}.arrival-report .tone-info{--tone:#2563eb;--tone-soft:#dbeafe}
.arrival-report .tone-teal{--tone:#0d9488;--tone-soft:#ccfbf1}.arrival-report .tone-warning{--tone:#d97706;--tone-soft:#fef3c7}
.arrival-report .tone-danger{--tone:#dc2626;--tone-soft:#fee2e2}.arrival-report .tone-slate{--tone:#475569;--tone-soft:#e2e8f0}
.arrival-report .tone-amber{--tone:#b45309;--tone-soft:#fef3c7}
/* Filters */
.arrival-report .filter-group{border:1px solid var(--line);border-radius:14px;padding:14px 16px 16px;margin:0 0 14px}
.arrival-report .filter-group legend{float:none;width:auto;margin:0 0 10px;padding:0;font-size:.92rem;font-weight:700;color:var(--ink)}
.arrival-report .filter-group legend i{color:var(--brand);margin-inline-end:6px}
.arrival-report label,.arrival-report .field-label{font-size:.85rem;color:#4b5563;margin-bottom:6px;display:block}
.arrival-report .shortcuts{display:flex;flex-wrap:wrap;gap:6px}
.arrival-report .shortcut{border:1px solid var(--line);background:#fff;border-radius:999px;padding:6px 14px;font-size:.85rem;color:#374151;text-decoration:none;min-height:38px;display:inline-flex;align-items:center}
.arrival-report .shortcut:hover{border-color:var(--brand);color:var(--brand)}
.arrival-report .shortcut.active{background:var(--brand);border-color:var(--brand);color:#fff}
.arrival-report .filter-actions{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap}
.arrival-report .filter-actions .btn{min-width:130px}
.arrival-report .more-filters>summary{list-style:none;cursor:pointer;color:var(--brand);font-weight:600;font-size:.9rem;margin-bottom:12px}
.arrival-report .more-filters>summary::-webkit-details-marker{display:none}
.arrival-report .more-filters>summary i{transition:transform .15s ease}
.arrival-report .more-filters[open]>summary i{transform:rotate(180deg)}
.arrival-report .filter-chip{display:inline-flex;align-items:center;gap:4px;background:#f1f5f9;border-radius:999px;padding:4px 12px;margin:3px;font-size:.8rem;color:#334155}
.arrival-report .hint-box{display:flex;gap:10px;align-items:flex-start;border-radius:12px;padding:10px 14px;font-size:.85rem;margin-bottom:14px}
.arrival-report .hint-box i{margin-top:3px}
.arrival-report .hint-info{background:#eff6ff;color:#1e40af;border:1px solid #bfdbfe}
.arrival-report .hint-warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
/* Table */
.arrival-report .results-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px}
.arrival-report .count-pill{display:inline-flex;align-items:center;gap:6px;background:#eef2ff;color:#3730a3;border-radius:999px;padding:3px 12px;font-size:.82rem;font-weight:600}
.arrival-report .table{margin:0}
.arrival-report .panel{min-width:0;max-width:100%}
/* The theme forces .table-responsive to overflow:visible, so the report uses its own scroll box. */
.arrival-report .report-table-scroll{width:100%;max-width:100%;max-height:70vh;overflow:auto;border:1px solid var(--line);border-radius:12px;-webkit-overflow-scrolling:touch}
.arrival-report .report-table-scroll .table{width:max-content;min-width:100%}
.arrival-report .table thead th{position:sticky;top:0;z-index:1;background:#f8fafc;color:#475569;font-size:.82rem;font-weight:700;white-space:nowrap;border-bottom:1px solid var(--line);padding:10px 12px}
.arrival-report .table td{vertical-align:middle;white-space:nowrap;font-size:.88rem;padding:10px 12px}
.arrival-report .table td.delivery-cell{white-space:normal;min-width:150px}
.arrival-report details.delivery summary{cursor:pointer;color:var(--brand);font-size:.85rem}
/* Monthly bars */
.arrival-report .month-row{display:grid;grid-template-columns:110px 1fr 60px;gap:12px;align-items:center;padding:6px 0}
.arrival-report .month-row a{text-decoration:none;font-weight:600}
.arrival-report .month-bar{height:10px;background:#eef2f7;border-radius:999px;overflow:hidden}
.arrival-report .month-bar span{display:block;height:100%;background:linear-gradient(90deg,#60a5fa,#2563eb);border-radius:999px}
.arrival-report .month-count{font-weight:700;text-align:end}
/* Method notes */
.arrival-report .method>summary{list-style:none;cursor:pointer;font-weight:700;color:var(--ink)}
.arrival-report .method>summary::-webkit-details-marker{display:none}
.arrival-report .method>summary i{color:var(--brand);margin-inline-end:6px}
.arrival-report .method ul{margin:12px 0 0;padding-inline-start:20px;color:#374151;font-size:.88rem;line-height:1.8}
@media (max-width:767.98px){
    .arrival-report .panel{padding:14px}
    .arrival-report .segmented,.arrival-report .head-actions{width:100%}
    .arrival-report .segmented a,.arrival-report .head-actions .btn{flex:1 1 calc(50% - 8px);justify-content:center}
    .arrival-report .month-row{grid-template-columns:80px 1fr 44px}
}
</style>
@endsection
@section('content')
@php
    $filterKeys = ['date_from','date_to','date_type','bucket','view','office_id','marketer_id','nationality_id','branch_id','status','global_search','sort','direction'];
    $filters = array_merge(request()->only($filterKeys), ['date_from'=>$payload['from'],'date_to'=>$payload['to'],'date_type'=>$payload['date_type'],'bucket'=>$payload['bucket'],'view'=>$payload['view']]);
    $dateTypes = ['arrival'=>'تاريخ الوصول للمملكة','contract'=>'تاريخ العقد','created'=>'تاريخ إنشاء السجل','delivery'=>'تاريخ التسليم للعميل'];
    $buckets = ['all'=>'كل العقود ضمن الفترة (حسب التاريخ المختار)','arrived'=>'وصلت خلال الفترة','previous'=>'وصلت في الفترة السابقة','delivered'=>'سُلّمت للعميل خلال الفترة','waiting'=>'بانتظار الوصول الآن','delayed'=>'متأخرة عن موعد الرحلة الآن','undelivered'=>'وصلت ولم يُوثَّق تسليمها','missing'=>'وصلت دون تاريخ وصول صالح'];
    $summary = $payload['summary'];
    $periodCards = [
        'arrived' => ['tone-success','fa-plane-arrival','وصلت خلال الفترة','حسب تاريخ الوصول الفعلي للمملكة'],
        'previous' => ['tone-slate','fa-clock-rotate-left','وصلت في الفترة السابقة',$payload['previous_from'].' — '.$payload['previous_to'].' (نفس عدد الأيام)'],
        'delivered' => ['tone-teal','fa-handshake','سُلّمت للعميل خلال الفترة','حسب تاريخ توثيق التسليم'],
    ];
    $nowCards = [
        'waiting' => ['tone-warning','fa-hourglass-half','بانتظار الوصول','كل العقود التي لم تصل بعد'],
        'delayed' => ['tone-danger','fa-triangle-exclamation','متأخرة عن موعد الرحلة','مضى تاريخ الرحلة ولم يُسجَّل الوصول — جزء من المنتظرة'],
        'undelivered' => ['tone-slate','fa-handshake-slash','وصلت ولم يُوثَّق تسليمها','غياب التوثيق لا يعني عدم التسليم'],
        'missing' => ['tone-amber','fa-circle-exclamation','وصلت دون تاريخ وصول صالح','بيانات ناقصة تحتاج تصحيح'],
    ];
    $diff = $summary['difference'];
    $trendClass = $diff > 0 ? 'is-up' : ($diff < 0 ? 'is-down' : '');
    $trendIcon = $diff > 0 ? 'fa-arrow-trend-up' : ($diff < 0 ? 'fa-arrow-trend-down' : 'fa-equals');
    $periodShortcuts = ['اليوم'=>[today(),today()],'هذا الأسبوع'=>[now()->startOfWeek(\Carbon\Carbon::SUNDAY),today()],'هذا الشهر'=>[now()->startOfMonth(),today()],'الشهر السابق'=>[now()->subMonthNoOverflow()->startOfMonth(),now()->subMonthNoOverflow()->endOfMonth()],'هذه السنة'=>[now()->startOfYear(),today()]];
    $moreFilterKeys = ['global_search','office_id','marketer_id','nationality_id','status','branch_id','sort','direction'];
    $hasMoreFilters = collect($moreFilterKeys)->contains(fn($key) => request()->filled($key));
    $currentPage = max(1,(int)request('page',1));
    $pageRows = array_slice($payload['rows'],($currentPage-1)*50,50,true);
@endphp
<div class="page-body arrival-report"><div class="container-fluid">

    {{-- Header --}}
    <div class="panel">
        <div class="report-head">
            <div class="report-head-title">
                <span class="report-head-icon"><i class="fas fa-plane-arrival"></i></span>
                <div>
                    <h3>تقارير الوصول والاستقدام</h3>
                    <p class="panel-sub">متابعة وصول العاملات والتأخير والتسليم للعميل، وأداء المكاتب والموظفين. كل رقم = عدد عقود (وليس عدد عاملات).</p>
                </div>
            </div>
            <div class="head-actions">
                @foreach(['excel'=>['تصدير Excel','fa-file-excel'],'pdf'=>['طباعة / PDF','fa-print']] as $format=>[$label,$icon])
                    @if($formats[$format])<a class="btn" href="{{ route('reports.export',array_merge($filters,['report'=>'recruitment_contracts','arrival_report'=>'1','format'=>$format])) }}"><i class="fas {{ $icon }}"></i> {{ $label }}</a>@endif
                @endforeach
                <a class="btn" href="{{ route('reports.index') }}"><i class="fas fa-table-list"></i> كل التقارير</a>
            </div>
        </div>
        <div class="report-tabs">
            <nav class="segmented" aria-label="نوع التقرير">
                @foreach(['contracts'=>['قائمة العقود','fa-list'],'offices'=>['أداء المكاتب الخارجية','fa-building'],'employees'=>['أداء الموظفين','fa-user-tie']] as $key=>[$label,$icon])
                    <a class="{{ $payload['view']===$key ? 'active' : '' }}" @if($payload['view']===$key) aria-current="page" @endif href="{{ route('reports.arrivals',array_merge($filters,['view'=>$key])) }}"><i class="fas {{ $icon }}"></i> {{ $label }}</a>
                @endforeach
            </nav>
            <a class="soft-btn delay-link" href="{{ route('reports.arrivals',array_merge($filters,['bucket'=>'delayed','view'=>'contracts'])) }}"><i class="fas fa-triangle-exclamation"></i> العقود المتأخرة ({{ number_format($summary['delayed']) }})</a>
        </div>
    </div>

    @include('admin.partials.branch-report-scope', ['scopeClass'=>'alert alert-light border'])
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

    {{-- Metrics --}}
    <div class="panel">
        <section class="metric-section" aria-labelledby="period-metrics">
            <div class="metric-section-head">
                <h6 id="period-metrics"><i class="fas fa-calendar-days me-1 text-primary"></i> خلال الفترة المختارة</h6>
                <span class="metric-badge">{{ $payload['from'] }} — {{ $payload['to'] }}</span>
                <span class="panel-sub">تتغير بتغيير الفترة</span>
            </div>
            <div class="metric-grid">
                @foreach($periodCards as $key=>[$tone,$icon,$title,$hint])
                    <a class="metric {{ $tone }} {{ $payload['bucket']===$key||($key==='arrived'&&$payload['bucket']==='all'&&$payload['date_type']==='arrival')?'active':'' }}" href="{{ route('reports.arrivals',array_merge($filters,['bucket'=>$key,'date_type'=>'arrival','view'=>'contracts'])) }}">
                        <span class="metric-top"><span class="metric-icon"><i class="fas {{ $icon }}"></i></span><span class="metric-value">{{ number_format($summary[$key]) }}</span></span>
                        <span class="metric-title">{{ $title }}</span>
                        <span class="metric-hint">{{ $hint }}</span>
                        @if($key==='arrived')
                            <span class="metric-trend {{ $trendClass }}"><i class="fas {{ $trendIcon }}"></i>
                                {{ $diff>0?'+':'' }}{{ $summary['percentage']!==null ? $summary['percentage'].'%' : $diff }} عن الفترة السابقة
                                @if($summary['percentage']!==null)({{ $diff>0?'+':'' }}{{ $diff }} عقد)@endif
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
        <section class="metric-section" aria-labelledby="now-metrics">
            <div class="metric-section-head">
                <h6 id="now-metrics"><i class="fas fa-clock me-1 text-warning"></i> الوضع الحالي</h6>
                <span class="metric-badge now">حتى {{ $payload['as_of'] }}</span>
                <span class="panel-sub">لا تتأثر بالفترة — تتأثر فقط بفلاتر المكتب والموظف والجنسية والحالة والفرع</span>
            </div>
            <div class="metric-grid">
                @foreach($nowCards as $key=>[$tone,$icon,$title,$hint])
                    <a class="metric {{ $tone }} {{ $payload['bucket']===$key?'active':'' }}" href="{{ route('reports.arrivals',array_merge($filters,['bucket'=>$key,'date_type'=>'arrival','view'=>'contracts'])) }}">
                        <span class="metric-top"><span class="metric-icon"><i class="fas {{ $icon }}"></i></span><span class="metric-value">{{ number_format($summary[$key]) }}</span></span>
                        <span class="metric-title">{{ $title }}</span>
                        <span class="metric-hint">{{ $hint }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>

    {{-- Filters --}}
    <form method="get" action="{{ route('reports.arrivals') }}" class="panel">
        <input type="hidden" name="view" value="{{ $payload['view'] }}">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h6 class="panel-title"><i class="fas fa-filter"></i> الفلاتر</h6>
            <div>
                <span class="filter-chip"><i class="fas fa-table"></i> {{ $buckets[$payload['bucket']] }}</span>
                @if($payload['bucket']==='all')<span class="filter-chip">حسب: {{ $dateTypes[$payload['date_type']] }}</span>@endif
                @foreach(['global_search'=>'البحث','office_id'=>'المكتب','marketer_id'=>'الموظف','nationality_id'=>'الجنسية','status'=>'الحالة'] as $key=>$label)
                    @if(request()->filled($key))<span class="filter-chip">{{ $label }}: {{ $key==='office_id' ? optional($filterOptions['offices']->firstWhere('id',request($key)))->title : ($key==='marketer_id' ? optional($filterOptions['admins']->firstWhere('id',request($key)))->name : ($key==='nationality_id' ? optional($filterOptions['nationalities']->firstWhere('id',request($key)))->title : ($key==='status' ? ($statuses[request($key)]??request($key)) : request($key)))) }}</span>@endif
                @endforeach
            </div>
        </div>

        <fieldset class="filter-group">
            <legend><i class="fas fa-calendar-days"></i> الفترة</legend>
            <div class="row g-3 align-items-end">
                <div class="col-lg-6">
                    <span class="field-label">اختصارات</span>
                    <div class="shortcuts">
                        @foreach($periodShortcuts as $label=>$dates)
                            @php $isActive = $payload['from']===$dates[0]->toDateString() && $payload['to']===$dates[1]->toDateString(); @endphp
                            <a class="shortcut {{ $isActive?'active':'' }}" href="{{ route('reports.arrivals',array_merge($filters,['date_from'=>$dates[0]->toDateString(),'date_to'=>$dates[1]->toDateString()])) }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3"><label for="arrival-from">من تاريخ</label><input id="arrival-from" class="form-control" type="date" name="date_from" value="{{ $payload['from'] }}" min="1900-01-01" required></div>
                <div class="col-sm-6 col-lg-3"><label for="arrival-to">إلى تاريخ</label><input id="arrival-to" class="form-control" type="date" name="date_to" value="{{ $payload['to'] }}" required></div>
            </div>
        </fieldset>

        <fieldset class="filter-group">
            <legend><i class="fas fa-table"></i> ماذا يعرض الجدول؟</legend>
            <div class="row g-3">
                <div class="col-lg-6"><label for="report-bucket">مجموعة العقود</label><select id="report-bucket" name="bucket" class="form-select">@foreach($buckets as $key=>$label)<option value="{{ $key }}" {{ $payload['bucket']===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="col-lg-6" id="date-type-field"><label for="date-type">الفترة تُطبَّق على</label><select id="date-type" name="date_type" class="form-select">@foreach($dateTypes as $key=>$label)<option value="{{ $key }}" {{ $payload['date_type']===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select>
                    <small class="text-muted d-block mt-1">يُستخدم فقط مع "كل العقود ضمن الفترة". مثال: "تاريخ العقد" يعرض العقود الموقّعة في الفترة مهما كان موعد وصولها.</small>
                </div>
            </div>
        </fieldset>

        <details class="more-filters" {{ $hasMoreFilters ? 'open' : '' }}>
            <summary><i class="fas fa-chevron-down me-1"></i> فلاتر إضافية: بحث، مكتب، موظف، جنسية، حالة، ترتيب</summary>
            <fieldset class="filter-group">
                <legend><i class="fas fa-sliders"></i> تضييق النتائج</legend>
                <div class="row g-3">
                    <div class="col-lg-6"><label for="arrival-search">بحث: رقم العقد، العميل، الهوية، العاملة، الجواز أو التأشيرة</label><input id="arrival-search" class="form-control" name="global_search" value="{{ request('global_search') }}" maxlength="200" placeholder="اكتب الاسم أو الرقم"></div>
                    <div class="col-lg-3 col-md-6"><label for="arrival-office">المكتب الخارجي</label><select id="arrival-office" name="office_id" class="form-select"><option value="">كل المكاتب</option>@foreach($filterOptions['offices'] as $office)<option value="{{ $office->id }}" {{ request('office_id')==$office->id?'selected':'' }}>{{ $office->title }}</option>@endforeach</select></div>
                    <div class="col-lg-3 col-md-6"><label for="arrival-admin">الموظف / المسوق</label><select id="arrival-admin" name="marketer_id" class="form-select"><option value="">كل الموظفين</option>@foreach($filterOptions['admins'] as $admin)<option value="{{ $admin->id }}" {{ request('marketer_id')==$admin->id?'selected':'' }}>{{ $admin->name }}</option>@endforeach</select></div>
                    <div class="col-lg-3 col-md-6"><label for="arrival-nationality">الجنسية</label><select id="arrival-nationality" name="nationality_id" class="form-select"><option value="">كل الجنسيات</option>@foreach($filterOptions['nationalities'] as $nationality)<option value="{{ $nationality->id }}" {{ request('nationality_id')==$nationality->id?'selected':'' }}>{{ $nationality->title }}</option>@endforeach</select></div>
                    <div class="col-lg-3 col-md-6"><label for="arrival-status">مرحلة العقد الحالية</label><select id="arrival-status" name="status" class="form-select"><option value="">كل المراحل</option>@foreach($statuses as $key=>$label)<option value="{{ $key }}" {{ request('status')===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
                    @if($branchSelector['enabled'])<div class="col-lg-3 col-md-6"><label for="arrival-branch">الفرع</label><select id="arrival-branch" name="branch_id" class="form-select">@if(!$branchSelector['forced_branch_id'])<option value="">كل الفروع المتاحة</option>@endif @foreach($branchSelector['branches'] as $branch)<option value="{{ $branch->id }}" {{ $branchSelector['selected_branch_id']==$branch->id?'selected':'' }}>{{ $branch->name }}</option>@endforeach</select></div>@endif
                    <div class="col-lg-3 col-md-6"><label for="arrival-sort">ترتيب العقود حسب</label><select id="arrival-sort" name="sort" class="form-select">@foreach(['arrival'=>'الوصول الفعلي','expected'=>'الوصول المتوقع','delivery'=>'التسليم','contract'=>'تاريخ العقد','created'=>'الإنشاء','delay'=>'أيام التأخير','id'=>'رقم العقد'] as $key=>$label)<option value="{{ $key }}" {{ request('sort','arrival')===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-lg-3 col-md-6"><label for="arrival-direction">اتجاه الترتيب</label><select id="arrival-direction" name="direction" class="form-select"><option value="desc" {{ request('direction','desc')==='desc'?'selected':'' }}>الأحدث أولًا</option><option value="asc" {{ request('direction')==='asc'?'selected':'' }}>الأقدم أولًا</option></select></div>
                </div>
            </fieldset>
        </details>

        <div class="filter-actions">
            <a href="{{ route('reports.arrivals') }}" class="btn btn-light border"><i class="fas fa-rotate-left me-1"></i> مسح الفلاتر</a>
            <button class="btn btn-primary"><i class="fas fa-search me-1"></i> تطبيق</button>
        </div>
    </form>

    @if($payload['snapshot'])<div class="hint-box hint-info"><i class="fas fa-circle-info"></i><div>هذه قائمة بالوضع الحالي حتى {{ $payload['as_of'] }} وتشمل كل الفترات؛ الفترة المختارة تؤثر فقط على بطاقات "خلال الفترة المختارة".</div></div>@endif
    @if(!$payload['delivery_tracking'])<div class="hint-box hint-warn"><i class="fas fa-triangle-exclamation"></i><div>توثيق التسليم يحتاج تطبيق تحديث قاعدة البيانات. لا تعني الحالات «غير موثق» أن العاملة لم تُسلّم فعليًا.</div></div>@endif

    {{-- Results --}}
    <div class="panel">
        <div class="results-head">
            <div>
                <h6 class="panel-title"><i class="fas fa-table-list"></i> {{ $payload['title'] }}</h6>
                <p class="panel-sub">{{ $buckets[$payload['bucket']] }}</p>
            </div>
            <span class="count-pill"><i class="fas fa-hashtag"></i> {{ number_format(count($payload['rows'])) }} {{ $payload['view']==='contracts'?'عقد':'مجموعة' }}</span>
        </div>
        <div class="report-table-scroll" tabindex="0" role="region" aria-label="جدول النتائج — اسحب أفقيًا لرؤية بقية الأعمدة"><table class="table table-hover"><thead><tr>@foreach($payload['headings'] as $heading)<th scope="col">{{ $heading }}</th>@endforeach @if($payload['view']==='contracts'&&$canDocumentDelivery&&$payload['delivery_tracking'])<th>توثيق التسليم</th>@endif</tr></thead><tbody>
            @forelse($pageRows as $index=>$row)<tr>
                @foreach($row as $column=>$cell)<td>
                    @if(isset($payload['cell_links'][$index][$column]))<a href="{{ $payload['cell_links'][$index][$column] }}">{{ $cell }}</a>
                    @elseif($payload['view']!=='contracts'&&$column===0&&$payload['groups'][$index]['id'])<a href="{{ route('reports.arrivals',array_merge($filters,['view'=>'contracts',($payload['view']==='offices'?'office_id':'marketer_id')=>$payload['groups'][$index]['id']])) }}">{{ $cell }}</a>
                    @else{{ $cell }}@endif
                </td>@endforeach
                @if($payload['view']==='contracts'&&$canDocumentDelivery&&$payload['delivery_tracking'])<td class="delivery-cell">
                    @if($payload['records'][$index]['arrival']&&$payload['records'][$index]['arrival']->lte(now()))
                    <details class="delivery"><summary>{{ $payload['records'][$index]['delivery']?'تصحيح التسليم':'توثيق التسليم' }}</summary><form method="post" action="{{ route('reports.arrivals.delivery',$row[0]) }}" class="p-2" style="min-width:240px">@csrf
                        <label for="delivery-{{ $row[0] }}">تاريخ ووقت التسليم الفعلي</label><input id="delivery-{{ $row[0] }}" class="form-control mb-2" type="datetime-local" name="client_delivered_at" value="{{ $payload['records'][$index]['delivery']?->format('Y-m-d\TH:i') }}" min="{{ $payload['records'][$index]['arrival']->format('Y-m-d\TH:i') }}" max="{{ now()->format('Y-m-d\TH:i') }}" required>
                        <label for="delivery-note-{{ $row[0] }}">ملاحظة التوثيق</label><textarea id="delivery-note-{{ $row[0] }}" name="client_delivery_note" class="form-control mb-2" maxlength="1000"></textarea><button class="btn btn-sm btn-primary">حفظ التسليم الفعلي</button>
                    </form></details>@else — @endif
                </td>@endif
            </tr>@empty<tr><td colspan="{{ count($payload['headings'])+1 }}" class="text-center text-muted py-4"><i class="fas fa-inbox d-block mb-2 fs-4"></i>لا توجد نتائج مطابقة. جرّب تغيير الفترة أو مسح الفلاتر.</td></tr>@endforelse
        </tbody></table></div>
        <div class="mt-3">{{ (new \Illuminate\Pagination\LengthAwarePaginator([],count($payload['rows']),50,$currentPage,['path'=>route('reports.arrivals'),'query'=>$filters]))->links() }}</div>
    </div>

    {{-- Monthly distribution --}}
    <div class="panel">
        <h6 class="panel-title"><i class="fas fa-chart-column"></i> الوصول شهريًا خلال الفترة</h6>
        <p class="panel-sub mb-3">عدد العقود التي وصلت في كل شهر، بنفس الفلاتر. اضغط الشهر لعرض عقوده.</p>
        @forelse($payload['monthly'] as $month=>$count)
            <div class="month-row">
                <a href="{{ route('reports.arrivals',array_merge($filters,['view'=>'contracts','bucket'=>'arrived','date_from'=>max($payload['from'],$month.'-01'),'date_to'=>min($payload['to'],\Carbon\Carbon::parse($month.'-01')->endOfMonth()->toDateString())])) }}">{{ \Carbon\Carbon::parse($month.'-01')->locale('ar')->translatedFormat('F Y') }}</a>
                <div class="month-bar" role="img" aria-label="{{ $count }} عقد"><span style="width:{{ round($count/max(1,max($payload['monthly']))*100) }}%"></span></div>
                <div class="month-count">{{ number_format($count) }}</div>
            </div>
        @empty<p class="text-muted mb-0">لا توجد بيانات وصول خلال الفترة المختارة.</p>@endforelse
    </div>

    {{-- Methodology --}}
    <details class="panel method">
        <summary><i class="fas fa-circle-question"></i> كيف تُحسب الأرقام؟</summary>
        <ul>
            <li><strong>تاريخ الوصول:</strong> بداية الضمان، ثم رحلة مكتملة، ثم تاريخ وصول العقد أو العاملة عند وجود إثبات الوصول، ثم سجل مرحلة الوصول. تاريخ إنشاء العقد والرحلة المجدولة لا يُعدّان وصولًا.</li>
            <li><strong>الوصول التاريخي</strong> يبقى محسوبًا بعد إرجاع العقد أو إلغائه عند وجود إثبات وصول محفوظ. يُحتسب كل عقد مرة واحدة مهما تعددت الرحلات أو سجلات المراحل.</li>
            <li><strong>المقارنة بالفترة السابقة:</strong> فترة بنفس عدد الأيام تنتهي قبل بداية الفترة المختارة مباشرة ({{ $payload['previous_from'] }} — {{ $payload['previous_to'] }}). لا تُحسب نسبة التغير إذا كانت الفترة السابقة صفرًا.</li>
            <li><strong>التأخير:</strong> عقد بانتظار الوصول وله رحلة غير ملغاة تجاوز تاريخ وصولها المتوقع اليوم الحالي. الوصول المتوقع اليوم لا يعد متأخرًا، والمنتظرة بلا موعد لا تدخل في التأخير.</li>
            <li><strong>التسليم:</strong> تاريخ صريح يوثقه موظف مخوّل. طباعة النموذج واكتمال العقد لا يثبتان تسليم العاملة للعميل.</li>
            <li><strong>أداء الموظف</strong> ينسب العقد للمسوق المسجل، وإلا للموظف المسؤول عن الطلب. متوسط مدة الاستقدام هو الأيام من تاريخ العقد إلى الوصول الفعلي، ويستبعد التواريخ الناقصة أو المتعارضة.</li>
            <li><strong>بطاقات الوضع الحالي</strong> قد تتداخل (المتأخرة جزء من المنتظرة)، فلا تُجمع مع بعضها ولا مع أرقام الفترة. العقود الراجعة والمستردة والملغاة لا تدخل في انتظار التسليم.</li>
        </ul>
    </details>
</div></div>
@endsection
@section('js')
<script>
    // "Date type" only narrows the "all contracts in period" view, so hide it otherwise.
    (function () {
        const bucket = document.getElementById('report-bucket');
        const field = document.getElementById('date-type-field');
        if (!bucket || !field) return;
        const sync = () => { field.style.display = bucket.value === 'all' ? '' : 'none'; };
        bucket.addEventListener('change', sync);
        sync();
    })();
</script>
@endsection
