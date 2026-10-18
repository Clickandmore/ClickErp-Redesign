@extends('admin.layout.indexs.index')
@section('page-title', 'التجديدات')
@section('style')
@include('admin.hourly_teams._style')
<style>
    /* ——— التجديدات: هوية وحدة الساعات ——— */
    .rn { --brand: #2794eb; --brand-ink: #1b6fb8; --brand-deep: #14507f; --teal: #00ac9a; --ink: #16212e; --muted: #6b7a8c; --line: #e6ecf3; --soft: #f6f9fc; color: var(--ink); }
    .rn [hidden] { display: none !important; }
    .rn-box { background: #fff; border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 10px 30px rgba(20, 80, 127, .06); margin-bottom: 1.25rem; }
    .rn-hero { border-radius: 18px; background: linear-gradient(105deg, var(--brand-deep) 0%, var(--brand-ink) 42%, var(--brand) 78%, var(--teal) 140%); color: #fff; padding: 1.2rem 1.4rem; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .rn-hero h3 { margin: 0; font-size: 1.3rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: .55rem; }
    .rn-hero p { margin: .3rem 0 0; font-size: .84rem; opacity: .88; }
    .rn-hero-note { background: rgba(255, 255, 255, .16); border-radius: 12px; padding: .5rem .85rem; font-size: .8rem; display: inline-flex; align-items: center; gap: .45rem; }

    /* الفلتر: ترويسة بالبحث، ثم شريط حالة العقد (الفلتر الأساسي)، ثم الفلاتر الإضافية، وتذييل بالإجراءات */
    .rn-filter { overflow: visible; }
    .rn-filter-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem 1rem; flex-wrap: wrap; padding: 1rem 1.2rem; border-bottom: 1px solid var(--line); }
    .rn-filter-head h5 { font-size: 1rem; font-weight: 700; color: var(--brand-ink); margin: 0; display: flex; align-items: center; gap: .5rem; }
    .rn-filter-count { background: #eaf5fe; color: var(--brand-ink); border: 1px solid #c4e2f8; border-radius: 99px; padding: .1rem .6rem; font-size: .74rem; font-weight: 700; }
    .rn-search { position: relative; flex: 0 1 340px; min-width: 220px; }
    .rn-search i { position: absolute; inset-inline-start: .85rem; top: 50%; transform: translateY(-50%); color: #9fb0c2; pointer-events: none; }
    .rn-search .form-control { height: 42px; border-radius: 11px; border-color: #dfe7ef; padding-inline-start: 2.4rem; font-size: .86rem; }
    .rn-section { padding: 1rem 1.2rem; }
    .rn-section + .rn-section { border-top: 1px dashed var(--line); }
    .rn-section-title { display: flex; align-items: center; gap: .4rem; font-size: .78rem; font-weight: 700; color: var(--muted); margin: 0 0 .6rem; }
    .rn-section-title i { color: #9fb0c2; }
    .rn-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .9rem 1rem; }
    .rn-field label { display: flex; align-items: center; gap: .35rem; font-size: .78rem; font-weight: 600; color: var(--muted); margin-bottom: .35rem; }
    .rn-field label i { color: #9fb0c2; font-size: .75rem; }
    .rn-field .form-control, .rn-field .form-select { min-height: 42px; border-radius: 10px; border-color: #dfe7ef; font-size: .85rem; }
    .rn-field .is-set, .rn-search .is-set { border-color: #9fd0f4; background-color: #f2f9ff; font-weight: 700; color: var(--brand-deep); }
    .rn-presets { display: flex; flex-wrap: wrap; gap: .4rem; }
    .rn-presets label { margin: 0; cursor: pointer; }
    .rn-presets input { position: absolute; opacity: 0; pointer-events: none; }
    .rn-presets span { display: inline-flex; align-items: center; gap: .35rem; padding: .48rem .9rem; border: 1px solid var(--line); border-radius: 99px; font-size: .8rem; font-weight: 700; color: var(--muted); background: #fff; transition: background .15s, border-color .15s, color .15s; }
    .rn-presets label:hover span { border-color: #c4e2f8; color: var(--brand-ink); }
    .rn-presets input:checked + span { background: var(--brand-ink); border-color: var(--brand-ink); color: #fff; box-shadow: 0 4px 12px rgba(27, 111, 184, .22); }
    .rn-presets input:focus-visible + span { outline: 3px solid #9fd0f4; outline-offset: 2px; }
    .rn-presets .rn-sep { width: 1px; align-self: stretch; background: var(--line); margin: 0 .2rem; }
    .rn-custom { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: .8rem 1rem; padding: .9rem; margin-top: .8rem; border: 1px dashed #9fd0f4; border-radius: 14px; background: #f5fbff; }
    .rn-filter-foot { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; padding: .85rem 1.2rem; border-top: 1px solid var(--line); background: var(--soft); border-radius: 0 0 18px 18px; }
    .rn-filter-foot .rn-sub { font-size: .8rem; }
    .rn-actions { display: flex; gap: .5rem; }
    .rn-btn { height: 42px; border-radius: 11px; padding: 0 1.1rem; font-weight: 700; font-size: .86rem; display: inline-flex; align-items: center; gap: .45rem; border: 1px solid var(--line); background: #fff; color: var(--ink); text-decoration: none; }
    .rn-btn:hover { border-color: var(--brand); color: var(--brand-ink); }
    .rn-btn.is-primary { background: var(--brand-ink); border-color: var(--brand-ink); color: #fff; }
    .rn-btn.is-primary:hover { background: var(--brand-deep); color: #fff; }
    .rn-btn.is-export { background: #17996f; border-color: #17996f; color: #fff; }
    .rn-btn.is-export:hover { background: #0f7a5a; color: #fff; }

    .rn-list-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; padding: 1rem 1.2rem; border-bottom: 1px solid var(--line); }
    .rn-list-head h5 { margin: 0; font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }
    .rn-count { background: #fef6d8; color: #7a5a06; border: 1px solid #f2d785; border-radius: 8px; padding: .15rem .55rem; font-size: .78rem; font-weight: 700; }
    .rn-window { font-size: .8rem; color: var(--muted); }
    .rn-table { width: 100%; font-size: .85rem; }
    .rn-table th { font-size: .76rem; color: var(--muted); font-weight: 700; padding: .7rem .8rem; background: var(--soft); border-bottom: 1px solid var(--line); white-space: nowrap; }
    .rn-table td { padding: .8rem; border-bottom: 1px solid #eef2f7; vertical-align: middle; }
    .rn-table tbody tr:hover td { background: #fbfdff; }
    .rn-table a { color: var(--brand-ink); font-weight: 700; text-decoration: none; }
    .rn-table a:hover { text-decoration: underline; }
    .rn-num { font-variant-numeric: tabular-nums; }
    .rn-sub { display: block; font-size: .74rem; color: var(--muted); }
    .rn-address { min-width: 220px; max-width: 300px; }
    .rn-table td.rn-num, .rn-nowrap { white-space: nowrap; }
    .rn-order { display: block; max-width: 170px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rn-period { display: block; white-space: nowrap; margin-bottom: .25rem; }
    .rn-days { display: inline-flex; align-items: center; gap: .3rem; border-radius: 8px; padding: .15rem .5rem; font-size: .74rem; font-weight: 700; border: 1px solid transparent; white-space: nowrap; }
    .rn-days.is-today { background: #fdeeee; color: #b42a2e; border-color: #f7cdcf; }
    .rn-days.is-soon { background: #fef5e7; color: #9a6206; border-color: #f7ddb2; }
    .rn-days.is-later { background: #eaf5fe; color: #1b6fb8; border-color: #c4e2f8; }
    .rn-days.is-past { background: #eef3f8; color: #5a6b7d; border-color: #dbe4ee; }
    .rn-progress { display: flex; align-items: center; gap: .4rem; min-width: 110px; }
    .rn-progress-bar { flex: 1; height: 6px; border-radius: 99px; background: #e3eaf2; overflow: hidden; }
    .rn-progress-bar span { display: block; height: 100%; background: linear-gradient(90deg, var(--teal), #17996f); }
    .rn-renew { display: inline-flex; align-items: center; gap: .35rem; background: var(--brand-ink); color: #fff !important; border-radius: 9px; padding: .4rem .8rem; font-size: .8rem; font-weight: 700; text-decoration: none !important; white-space: nowrap; }
    .rn-renew:hover { background: var(--brand-deep); }
    .rn-renewed { display: inline-flex; align-items: center; gap: .3rem; background: #e8f7f1; color: #0f7a5a; border: 1px solid #bfe9d9; border-radius: 9px; padding: .3rem .65rem; font-size: .78rem; font-weight: 700; white-space: nowrap; }
    .rn-renewed + a { margin-top: .25rem; font-weight: 700; }
    .rn-empty { text-align: center; padding: 3rem 1rem; color: var(--muted); }
    .rn-empty i { font-size: 1.9rem; color: #c3d2e0; display: block; margin-bottom: .6rem; }
    .rn-foot { padding: .9rem 1.2rem; display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
    .rn-foot .pagination { margin: 0; }
</style>
@endsection
@section('content')
@php
    $status = $filters['status'] ?? 'today';
    $set = fn ($key) => isset($filters[$key]) ? 'is-set' : '';
    [$windowFrom, $windowTo] = $window;
@endphp
<div class="container-fluid ht-page rn" dir="rtl">
    <header class="rn-hero">
        <div>
            <h3><i class="fa fa-rotate"></i> التجديدات (عقود الطلبات)</h3>
            <p>الطلبات التي تقترب آخر زيارة فيها، للتواصل مع العميل وتجديد الباقة قبل انتهاء الخدمة. تاريخ انتهاء العقد = تاريخ آخر زيارة.</p>
        </div>
        <span class="rn-hero-note"><i class="fa fa-bell"></i> {{ $reminderDays > 0 ? 'يُرسل تذكير التجديد للعميل قبل آخر زيارة بـ '.$reminderDays.' أيام' : 'تذكير التجديد التلقائي معطّل' }}</span>
    </header>

    @include('admin.hourly._filter-loader', ['id' => 'rn-filter-loader', 'title' => 'جارٍ تحديث العقود'])
    <p id="rn-filter-status" class="alert" role="alert" hidden></p>
    <div id="rn-results" class="hx-results" data-ajax-filter data-ajax-loader="rn-filter-loader" data-ajax-status="rn-filter-status">
    @php
        $activeFilters = collect([
            $filters['order_number'] ?? null, $filters['service_ids'] ?? null, $filters['team_ids'] ?? null, $filters['installment'] ?? null,
            ($filters['report'] ?? 'all') !== 'all', ($filters['renewal'] ?? 'all') !== 'all', $status !== 'today',
        ])->filter()->count();
        $quickPresets = ['today', 'd5', 'd10', 'd15', 'd30', 'active_all'];
    @endphp
    <form method="GET" action="{{ route('admin.hourly-renewals.index') }}" class="rn-box rn-filter" id="rn-filter" role="search" aria-label="فلتر تجديد الطلبات" data-ajax-form>
        <div class="rn-filter-head">
            <h5><i class="fa fa-sliders"></i> فلترة العقود @if($activeFilters)<span class="rn-filter-count">{{ $activeFilters }} فلتر مفعّل</span>@endif</h5>
            <div class="rn-search">
                <i class="fa fa-magnifying-glass"></i>
                <input class="form-control {{ $set('order_number') }}" id="rn-order" name="order_number" maxlength="40" value="{{ $filters['order_number'] ?? '' }}" placeholder="ابحث برقم الطلب أو المرجع، ثم Enter" aria-label="رقم الطلب">
            </div>
        </div>

        <fieldset class="rn-section m-0 border-0">
            <legend class="rn-section-title float-none w-auto"><i class="fa fa-hourglass-end"></i> حالة العقد — تاريخ انتهاء العقد هو تاريخ آخر زيارة</legend>
            <div class="rn-presets">
                @foreach(\App\Services\Hourly\RenewalSearch::PRESETS as $key => $label)
                    @if($key === 'active_all')<span class="rn-sep" aria-hidden="true"></span>@endif
                    <label><input type="radio" name="status" value="{{ $key }}" {{ $status === $key ? 'checked' : '' }} @if(in_array($key, $quickPresets, true)) data-auto-apply @endif><span>@if($key === 'custom')<i class="fa fa-calendar-days"></i>@elseif($key === 'active_on')<i class="fa fa-calendar-day"></i>@elseif($key === 'active_all')<i class="fa fa-circle-play"></i>@else<i class="fa fa-hourglass-half"></i>@endif {{ $label }}</span></label>
                @endforeach
            </div>
            <div class="rn-custom" id="rn-active-on" @if($status !== 'active_on') hidden @endif>
                <div class="rn-field"><label for="rn-active_on"><i class="fa fa-calendar-day"></i> نشط في تاريخ</label><input class="form-control {{ $set('active_on') }}" type="date" id="rn-active_on" name="active_on" value="{{ $filters['active_on'] ?? today()->toDateString() }}"></div>
                <p class="rn-sub" style="align-self:end">العقود التي يقع هذا اليوم بين أول زيارة وآخر زيارة فيها.</p>
            </div>
            <div class="rn-custom" id="rn-custom" @if($status !== 'custom') hidden @endif>
                @foreach(['active_from' => 'نشط من', 'active_to' => 'نشط حتى', 'expiry_from' => 'انتهاء العقد من', 'expiry_to' => 'انتهاء العقد إلى'] as $key => $label)
                    <div class="rn-field"><label for="rn-{{ $key }}"><i class="fa {{ str_starts_with($key, 'active') ? 'fa-play' : 'fa-flag-checkered' }}"></i> {{ $label }}</label><input class="form-control {{ $set($key) }}" type="date" id="rn-{{ $key }}" name="{{ $key }}" value="{{ $filters[$key] ?? '' }}"></div>
                @endforeach
            </div>
        </fieldset>

        <div class="rn-section">
            <p class="rn-section-title"><i class="fa fa-filter"></i> تصفية إضافية</p>
            <div class="rn-grid">
                <div class="rn-field"><label for="rn-services"><i class="fa fa-briefcase"></i> الخدمة</label>
                    <select class="form-select" id="rn-services" name="service_ids[]" multiple data-compact data-auto-apply data-placeholder="جميع الخدمات">
                        @foreach($services as $service)<option value="{{ $service->id }}" {{ in_array($service->id, array_map('intval', $filters['service_ids'] ?? []), true) ? 'selected' : '' }}>{{ $service->name_ar }}</option>@endforeach
                    </select>
                </div>
                <div class="rn-field"><label for="rn-team"><i class="fa fa-people-group"></i> الفريق</label>
                    @php $pickedTeams = array_map('strval', $filters['team_ids'] ?? []); @endphp
                    <select class="form-select" id="rn-team" name="team_ids[]" multiple data-compact data-auto-apply data-placeholder="كل الفرق">
                        <option value="{{ \App\Services\Hourly\RenewalSearch::NO_TEAM }}" {{ in_array(\App\Services\Hourly\RenewalSearch::NO_TEAM, $pickedTeams, true) ? 'selected' : '' }}>بدون فريق</option>
                        @foreach($teams as $team)<option value="{{ $team->id }}" {{ in_array((string) $team->id, $pickedTeams, true) ? 'selected' : '' }}>{{ $team->name }}</option>@endforeach
                    </select>
                </div>
                <div class="rn-field"><label for="rn-report"><i class="fa fa-list-check"></i> الزيارات</label>
                    <select class="form-select {{ ($filters['report'] ?? 'all') !== 'all' ? 'is-set' : '' }}" id="rn-report" name="report" data-auto-apply>@foreach(\App\Services\Hourly\RenewalSearch::REPORTS as $key => $label)<option value="{{ $key }}" {{ ($filters['report'] ?? 'all') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
                </div>
                <div class="rn-field"><label for="rn-renewal"><i class="fa fa-rotate"></i> حالة التجديد</label>
                    <select class="form-select {{ ($filters['renewal'] ?? 'all') !== 'all' ? 'is-set' : '' }}" id="rn-renewal" name="renewal" data-auto-apply>@foreach(\App\Services\Hourly\RenewalSearch::RENEWAL as $key => $label)<option value="{{ $key }}" {{ ($filters['renewal'] ?? 'all') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
                </div>
                <div class="rn-field"><label for="rn-installment"><i class="fa fa-money-check-dollar"></i> الأقساط</label>
                    <select class="form-select {{ $set('installment') }}" id="rn-installment" name="installment" data-auto-apply><option value="">الكل</option>@foreach(\App\Services\Hourly\RenewalSearch::INSTALLMENTS as $key => $label)<option value="{{ $key }}" {{ ($filters['installment'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
                </div>
            </div>
        </div>
        @isset($filters['per_page'])<input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">@endisset
        <div class="rn-filter-foot">
            <span class="rn-sub"><i class="fa fa-circle-info"></i> الفلاتر تُطبَّق فورًا عند اختيارها؛ البحث والتواريخ بزر «تطبيق» أو Enter.</span>
            <div class="rn-actions">
                @if($activeFilters)<a class="rn-btn" data-ajax-link href="{{ route('admin.hourly-renewals.index') }}"><i class="fa fa-rotate-left"></i> مسح الفلاتر</a>@endif
                <button class="rn-btn is-primary"><i class="fa fa-filter"></i> تطبيق</button>
            </div>
        </div>
    </form>

    <section class="rn-box">
        <div class="rn-list-head">
            <h5><i class="fa fa-file-contract"></i> الطلبات (العقود) <span class="rn-count rn-num">{{ $orders->total() }} سجلات</span></h5>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="rn-window"><i class="fa fa-calendar"></i>
                    @if($status === 'active_all') عقود نشطة اليوم
                    @elseif($status === 'active_on') عقود نشطة في <span class="rn-num" dir="ltr">{{ $filters['active_on'] ?? '' }}</span>
                    @else انتهاء العقد: <span class="rn-num" dir="ltr">{{ $windowFrom ? substr($windowFrom, 0, 10) : '…' }} → {{ $windowTo ? substr($windowTo, 0, 10) : '…' }}</span>
                    @endif
                </span>
                <a class="rn-btn is-export" href="{{ route('admin.hourly-renewals.export', request()->query()) }}"><i class="fa fa-file-excel"></i> تصدير</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="rn-table">
                <thead><tr><th>رقم العقد</th><th>اسم العميل</th><th>فئة الخدمة</th><th>العنوان</th><th>اسم الموظف</th><th>سعر الزيارة</th><th>المبلغ الإجمالي</th><th>فترة العقد</th><th>الزيارات</th><th>عمل</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    @php
                        $days = $row['days_left'];
                        $tone = $days < 0 ? 'is-past' : ($days === 0 ? 'is-today' : ($days <= 5 ? 'is-soon' : 'is-later'));
                    @endphp
                    <tr>
                        <td><a class="rn-num rn-order" dir="ltr" title="{{ $row['order_number'] }}" href="{{ route('admin.hourly-orders.show', $row['id']) }}">#{{ $row['order_number'] }}</a><span class="rn-sub">{{ __('hourly.'.$row['status']) }}</span></td>
                        <td class="rn-nowrap"><b>{{ $row['customer'] }}</b><span class="rn-sub rn-num" dir="ltr">{{ $row['phone'] }}</span></td>
                        <td>{{ $row['service'] }}</td>
                        <td class="rn-address">{{ $row['address'] }}</td>
                        <td>{{ $row['workers'] }}</td>
                        <td class="rn-num">{{ $row['visit_price'] }} ر.س</td>
                        <td class="rn-num"><b>{{ $row['total'] }} ر.س</b></td>
                        <td>
                            <span class="rn-num rn-period" dir="ltr">{{ $row['start'] }} → {{ $row['end'] }}</span>
                            <span class="rn-days {{ $tone }}"><i class="fa fa-hourglass-half"></i> {{ $days < 0 ? 'انتهى منذ '.abs($days).' يوم' : ($days === 0 ? 'تنتهي اليوم' : 'متبقٍ '.$days.' يوم') }}</span>
                        </td>
                        <td><div class="rn-progress" title="الزيارات المنجزة"><div class="rn-progress-bar"><span style="width: {{ $row['visits'] ? round($row['done'] / $row['visits'] * 100) : 0 }}%"></span></div><small class="rn-num">{{ $row['done'] }}/{{ $row['visits'] }}</small></div></td>
                        <td>
                            @if($row['renewal'])
                                <span class="rn-renewed"><i class="fa fa-circle-check"></i> تم التجديد</span>
                                <a class="rn-sub rn-num" dir="ltr" href="{{ route('admin.hourly-orders.show', $row['renewal']['id']) }}">#{{ $row['renewal']['order_number'] }}</a>
                            @elseif($canRenew)
                                @if($row['renewal_blocked_reason'])<span class="rn-sub" role="status">{{ $row['renewal_blocked_reason'] }}</span>@else
                                <a class="rn-renew" href="{{ route('admin.hourly-orders.create', ['renew' => $row['id']]) }}"><i class="fa fa-rotate"></i> تجديد</a>
                                @endif
                            @else<span class="rn-sub">—</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10"><div class="rn-empty"><i class="fa fa-calendar-check"></i> ليست هناك بيانات متاحة في الجدول لهذه الفلاتر.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="rn-foot">
            <span class="rn-sub">يعرض {{ $orders->firstItem() ?? 0 }} إلى {{ $orders->lastItem() ?? 0 }} من أصل {{ $orders->total() }} سجل</span>
            {{ $orders->links() }}
        </div>
    </section>
    </div>
</div>
@endsection
@push('js')
<script src="{{ asset('admin/assets/js/hourly-ajax-filter.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-ajax-filter.js')) }}"></script>
<script>
(function () {
    // يُعاد تهيئة النموذج بعد كل تحديث لأن hourly-ajax-filter.js يستبدل القسم كاملًا (النموذج والنتائج).
    // الحقول الفارغة وحقول اللوحة المخفية (النطاق المخصص، نشط في تاريخ) لا تُرسل أصلًا.
    function init(root) {
        const form = root.querySelector('#rn-filter');
        if (!form) return;
        const custom = form.querySelector('#rn-custom'), activeOn = form.querySelector('#rn-active-on');
        const chosen = () => form.querySelector('input[name="status"]:checked')?.value;
        form.querySelectorAll('input[name="status"]').forEach(radio => radio.addEventListener('change', () => {
            custom.hidden = chosen() !== 'custom'; activeOn.hidden = chosen() !== 'active_on';
        }));
        form.querySelectorAll('select:not([multiple]), input.form-control').forEach(el => el.addEventListener('change', () =>
            el.classList.toggle('is-set', el.value !== '' && !(['report', 'renewal'].includes(el.name) && el.value === 'all'))));
        // قوائم الخدمة والفريق المتعددة يحوّلها HourlyMultiSelect (hourly_teams._multiselect) تلقائيًا، ومنها بعد كل تحديث.
    }
    init(document);
    document.addEventListener('hx-filter:updated', event => init(event.detail.root));
})();
</script>
@endpush
