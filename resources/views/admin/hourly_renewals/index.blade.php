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

    .rn-filter { padding: 1.1rem 1.2rem; }
    .rn-filter h5 { font-size: 1rem; font-weight: 700; color: var(--brand-ink); margin: 0 0 1rem; display: flex; align-items: center; gap: .5rem; }
    .rn-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .9rem 1rem; }
    .rn-field label { display: flex; align-items: center; gap: .35rem; font-size: .78rem; font-weight: 600; color: var(--muted); margin-bottom: .35rem; }
    .rn-field label i { color: #9fb0c2; font-size: .75rem; }
    .rn-field .form-control, .rn-field .form-select { height: 42px; border-radius: 10px; border-color: #dfe7ef; font-size: .85rem; }
    .rn-field .is-set { border-color: #9fd0f4; background-color: #f2f9ff; font-weight: 700; color: var(--brand-deep); }
    .rn-status { grid-column: span 2; border: 0; padding: 0; margin: 0; min-width: 0; }
    .rn-legend { float: none; width: auto; display: flex; align-items: center; gap: .35rem; font-size: .78rem; font-weight: 600; color: var(--muted); margin-bottom: .35rem; }
    .rn-legend i { color: #9fb0c2; font-size: .75rem; }
    @media (max-width: 575px) { .rn-status { grid-column: auto; } }
    .rn-presets { display: flex; flex-wrap: wrap; gap: .35rem; }
    .rn-presets label { margin: 0; cursor: pointer; }
    .rn-presets input { position: absolute; opacity: 0; pointer-events: none; }
    .rn-presets span { display: inline-flex; align-items: center; gap: .3rem; padding: .45rem .8rem; border: 1px solid var(--line); border-radius: 99px; font-size: .8rem; font-weight: 700; color: var(--muted); background: #fff; }
    .rn-presets input:checked + span { background: #fff4e5; border-color: #f5b25b; color: #b35c00; }
    .rn-presets input:focus-visible + span { outline: 3px solid #9fd0f4; }
    .rn-custom { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: .8rem 1rem; padding: .9rem; border: 1px dashed #f5b25b; border-radius: 14px; background: #fffaf2; }
    .rn-actions { display: flex; gap: .5rem; margin-top: 1rem; }
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

    <form method="GET" action="{{ route('admin.hourly-renewals.index') }}" class="rn-box rn-filter" id="rn-filter" role="search" aria-label="فلتر تجديد الطلبات">
        <h5><i class="fa fa-filter"></i> فلتر تجديد الطلبات</h5>
        <div class="rn-grid">
            <div class="rn-field"><label for="rn-order"><i class="fa fa-hashtag"></i> رقم الطلب</label><input class="form-control {{ $set('order_number') }}" id="rn-order" name="order_number" maxlength="40" value="{{ $filters['order_number'] ?? '' }}" placeholder="رقم الطلب أو المرجع"></div>
            <div class="rn-field"><label for="rn-services"><i class="fa fa-briefcase"></i> الخدمة</label>
                <select class="form-select" id="rn-services" name="service_ids[]" multiple data-placeholder="جميع الخدمات">
                    @foreach($services as $service)<option value="{{ $service->id }}" {{ in_array($service->id, array_map('intval', $filters['service_ids'] ?? []), true) ? 'selected' : '' }}>{{ $service->name_ar }}</option>@endforeach
                </select>
            </div>
            <div class="rn-field"><label for="rn-report"><i class="fa fa-list-check"></i> نوع التقرير</label>
                <select class="form-select {{ ($filters['report'] ?? 'all') !== 'all' ? 'is-set' : '' }}" id="rn-report" name="report">@foreach(\App\Services\Hourly\RenewalSearch::REPORTS as $key => $label)<option value="{{ $key }}" {{ ($filters['report'] ?? 'all') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="rn-field"><label for="rn-team"><i class="fa fa-people-group"></i> الفريق</label>
                @php $pickedTeams = array_map('strval', $filters['team_ids'] ?? []); @endphp
                <select class="form-select" id="rn-team" name="team_ids[]" multiple data-placeholder="كل الفرق">
                    <option value="{{ \App\Services\Hourly\RenewalSearch::NO_TEAM }}" {{ in_array(\App\Services\Hourly\RenewalSearch::NO_TEAM, $pickedTeams, true) ? 'selected' : '' }}>بدون فريق</option>
                    @foreach($teams as $team)<option value="{{ $team->id }}" {{ in_array((string) $team->id, $pickedTeams, true) ? 'selected' : '' }}>{{ $team->name }}</option>@endforeach
                </select>
            </div>
            <div class="rn-field"><label for="rn-renewal"><i class="fa fa-rotate"></i> حالة التجديد</label>
                <select class="form-select {{ ($filters['renewal'] ?? 'all') !== 'all' ? 'is-set' : '' }}" id="rn-renewal" name="renewal">@foreach(\App\Services\Hourly\RenewalSearch::RENEWAL as $key => $label)<option value="{{ $key }}" {{ ($filters['renewal'] ?? 'all') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="rn-field"><label for="rn-installment"><i class="fa fa-money-check-dollar"></i> القسط</label>
                <select class="form-select {{ $set('installment') }}" id="rn-installment" name="installment"><option value="">الكل</option>@foreach(\App\Services\Hourly\RenewalSearch::INSTALLMENTS as $key => $label)<option value="{{ $key }}" {{ ($filters['installment'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select>
            </div>
            <fieldset class="rn-field rn-status">
                <legend class="rn-legend"><i class="fa fa-hourglass-end"></i> حالة العقد</legend>
                <div class="rn-presets">
                    @foreach(\App\Services\Hourly\RenewalSearch::PRESETS as $key => $label)
                        <label><input type="radio" name="status" value="{{ $key }}" {{ $status === $key ? 'checked' : '' }}><span>@if($key === 'custom')<i class="fa fa-calendar-days"></i>@elseif(str_starts_with($key, 'active'))<i class="fa fa-circle-play"></i>@endif {{ $label }}</span></label>
                    @endforeach
                </div>
            </fieldset>
            <div class="rn-custom" id="rn-active-on" @if($status !== 'active_on') hidden @endif>
                <div class="rn-field"><label for="rn-active_on"><i class="fa fa-calendar-day"></i> نشط في تاريخ</label><input class="form-control {{ $set('active_on') }}" type="date" id="rn-active_on" name="active_on" value="{{ $filters['active_on'] ?? today()->toDateString() }}"></div>
                <p class="rn-sub" style="align-self:end">العقود التي يقع هذا اليوم بين أول زيارة وآخر زيارة فيها.</p>
            </div>
            <div class="rn-custom" id="rn-custom" @if($status !== 'custom') hidden @endif>
                @foreach(['active_from' => 'نشط من', 'active_to' => 'نشط حتى', 'expiry_from' => 'انتهاء الصلاحية من', 'expiry_to' => 'انتهاء الصلاحية إلى'] as $key => $label)
                    <div class="rn-field"><label for="rn-{{ $key }}"><i class="fa {{ str_starts_with($key, 'active') ? 'fa-play' : 'fa-flag-checkered' }}"></i> {{ $label }}</label><input class="form-control {{ $set($key) }}" type="date" id="rn-{{ $key }}" name="{{ $key }}" value="{{ $filters[$key] ?? '' }}"></div>
                @endforeach
            </div>
        </div>
        @isset($filters['per_page'])<input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">@endisset
        <div class="rn-actions">
            <button class="rn-btn is-primary"><i class="fa fa-magnifying-glass"></i> فلتر</button>
            <a class="rn-btn" href="{{ route('admin.hourly-renewals.index') }}"><i class="fa fa-rotate-left"></i> إفراغ الخانات</a>
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
                                <a class="rn-renew" href="{{ route('admin.hourly-orders.create', ['renew' => $row['id']]) }}"><i class="fa fa-rotate"></i> تجديد</a>
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
@endsection
@push('js')
<script>
(function () {
    const form = document.getElementById('rn-filter'), custom = document.getElementById('rn-custom'), activeOn = document.getElementById('rn-active-on');
    const chosen = () => form.querySelector('input[name="status"]:checked')?.value;
    form.querySelectorAll('input[name="status"]').forEach(radio => radio.addEventListener('change', () => { custom.hidden = chosen() !== 'custom'; activeOn.hidden = chosen() !== 'active_on'; }));
    form.querySelectorAll('select:not([multiple]), input.form-control').forEach(el => el.addEventListener('change', () => el.classList.toggle('is-set', el.value !== '' && !(['report', 'renewal'].includes(el.name) && el.value === 'all'))));
    // لا تُرسل الحقول الفارغة، ولا حقول النطاق المخصص أو «نشط في تاريخ» إلا مع خيارها.
    form.addEventListener('submit', () => {
        form.querySelectorAll('[name]').forEach(el => { if (el.value === '' || (chosen() !== 'custom' && custom.contains(el)) || (chosen() !== 'active_on' && activeOn.contains(el))) el.disabled = true; });
    });
})();
</script>
@endpush
