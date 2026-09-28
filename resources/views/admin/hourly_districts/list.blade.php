@extends('admin.layout.indexs.index')

@section('page-title', 'إدارة المناطق')

@section('style')
    @include('admin.hourly_teams._style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">
    <style>
        .dl-teams summary { list-style: none; cursor: pointer; }
        .dl-teams summary::-webkit-details-marker { display: none; }
        .dl-teams[open] .dl-teams-list { display: grid; }
        .dl-teams-list { gap: .3rem; margin-top: .4rem; font-size: .8rem; }
    </style>
@endsection

@section('content')
    @php
        $canEdit = AdminGuardHasPerm('hourly_teams_edit');
        $canDelete = AdminGuardHasPerm('hourly_teams_delete');
        $sort = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';
        $sortLink = fn (string $key) => route('admin.hourly-districts.index', array_merge($filters, ['sort' => $key, 'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc']));
        $sortIcon = fn (string $key) => $sort !== $key ? 'fa-sort' : ($direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
        $coverage = $total ? round($mapped * 100 / $total) : 0;
        $activeFilters = count(array_filter(\Illuminate\Support\Arr::only($filters, ['city_id', 'search']), fn ($v) => $v !== null && $v !== ''));
        $cityName = $selectedCity ? ($selectedCity->name_ar ?: $selectedCity->name_en) : null;
        $fromModal = in_array(old('_form'), ['district-create', 'district-edit'], true);
        $editingId = old('_form') === 'district-edit' ? (int) old('_record_id') : null;
    @endphp
    <div class="container-fluid ht-page" dir="rtl">
        <div id="district-feedback" class="alert border-0 rounded-3 {{ session('success') ? 'alert-success' : 'd-none' }}" role="status">{{ session('success') }}</div>
        @if ($errors->any() && !$fromModal)
            <div class="alert alert-danger border-0 rounded-3"><i class="fa fa-circle-xmark ms-1"></i> {{ $errors->first() }}</div>
        @endif

        <div class="ht-card">
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa fa-map-pin"></i></div>
                <div>
                    <h5>{{ $selectedCity ? 'مناطق وأحياء '.$selectedCity->name_ar : 'مناطق وأحياء مدن المملكة' }}</h5>
                    <p>
                        <a href="{{ route('admin.hourly-cities.index') }}" class="text-decoration-none">المدن</a>
                        <i class="fa fa-angle-left mx-1 small"></i>
                        {{ $cityName ?? 'جميع المدن' }}
                    </p>
                </div>
                <div class="ht-actions">
                    @if ($selectedCity)
                        <a class="btn btn-outline-info rounded-3" href="{{ route('admin.hourly-cities.polygons', $selectedCity) }}"><i class="fa fa-draw-polygon ms-1"></i> خريطة الحدود</a>
                    @endif
                    @if ($canEdit)
                        <button type="button" class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#districtModal"><i class="fa fa-plus ms-1"></i> إضافة منطقة جديدة</button>
                    @endif
                    <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-cities.index') }}"><i class="fa fa-arrow-right ms-1"></i> الرجوع إلى المدن</a>
                </div>
            </div>
        </div>

        <div class="hd-stats">
            <div class="hd-stat"><div class="hd-stat-icon"><i class="fa fa-map-pin"></i></div><div><span>المناطق {{ $activeFilters ? 'المطابقة' : '' }}</span><strong class="ht-num">{{ $total }}</strong></div></div>
            <div class="hd-stat hd-stat-on"><div class="hd-stat-icon"><i class="fa fa-draw-polygon"></i></div><div><span>لها حدود محفوظة</span><strong class="ht-num">{{ $mapped }}</strong></div></div>
            <div class="hd-stat {{ $total - $mapped ? 'hd-stat-warn' : 'hd-stat-off' }}"><div class="hd-stat-icon"><i class="fa fa-triangle-exclamation"></i></div><div><span>بدون حدود</span><strong class="ht-num">{{ $total - $mapped }}</strong></div></div>
            <div class="hd-stat hd-stat-info" title="نسبة المناطق ذات حدود محفوظة ضمن نتائج البحث">
                <div class="hd-stat-icon"><i class="fa fa-chart-pie"></i></div>
                <div class="flex-fill">
                    <span>تغطية الحدود</span>
                    <strong class="ht-num">{{ $coverage }}%</strong>
                    <div class="ht-progress-bar mt-1 {{ $coverage === 0 ? 'is-zero' : ($coverage < 100 ? 'is-low' : '') }}"><span style="width: {{ $coverage }}%"></span></div>
                </div>
            </div>
        </div>

        <div class="ht-card">
            <div class="ht-body">
                <form method="GET" action="{{ route('admin.hourly-districts.index') }}" class="hd-filter mb-4">
                    <div class="hd-filter-head">
                        <div class="hd-filter-title">
                            <i class="fa fa-sliders"></i> فلتر قائمة المناطق
                            @if ($activeFilters)<span class="hd-filter-count">{{ $activeFilters }} فلتر مفعّل</span>@endif
                        </div>
                        <div class="hd-filter-search">
                            <i class="fa fa-magnifying-glass"></i>
                            <input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="150" placeholder="الاسم بالعربية أو الإنجليزية أو الرابط المختصر" aria-label="بحث">
                        </div>
                    </div>
                    <div class="hd-filter-body">
                        <div class="hd-filter-field">
                            <label for="filter_city"><i class="fa fa-city"></i> المدينة</label>
                            <select name="city_id" id="filter_city" class="form-select {{ !empty($filters['city_id']) ? 'is-set' : '' }}" onchange="this.form.submit()">
                                <option value="">جميع المدن</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city->id }}" {{ (string) ($filters['city_id'] ?? '') === (string) $city->id ? 'selected' : '' }}>{{ $city->name_ar ?: $city->name_en }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hd-filter-field">
                            <label for="per_page"><i class="fa fa-list-ol"></i> عدد الصفوف</label>
                            <select class="form-select" name="per_page" id="per_page" onchange="this.form.submit()">
                                @foreach ([10, 25, 50, 100] as $size)
                                    <option value="{{ $size }}" {{ (int) ($filters['per_page'] ?? 25) === $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="hd-filter-foot">
                        <span class="text-muted small">النتائج: <strong class="ht-num">{{ $districts->total() }}</strong> منطقة</span>
                        <div class="d-flex gap-2">
                            @if ($activeFilters)
                                <a class="btn btn-light rounded-3" href="{{ route('admin.hourly-districts.index') }}"><i class="fa fa-rotate-left ms-1"></i> مسح الفلاتر</a>
                            @endif
                            <button class="btn btn-primary rounded-3 px-4"><i class="fa fa-filter ms-1"></i> بحث</button>
                        </div>
                    </div>
                </form>

                @if ($districts->isEmpty())
                    <div class="ht-empty"><i class="fa fa-map-pin"></i> لا توجد مناطق مطابقة.</div>
                @else
                    <div class="table-responsive">
                        <table class="ht-table">
                            <thead>
                                <tr>
                                    <th><a class="ht-sort {{ $sort === 'id' ? 'is-sorted' : '' }}" href="{{ $sortLink('id') }}"># <i class="fa {{ $sortIcon('id') }}"></i></a></th>
                                    <th><a class="ht-sort {{ $sort === 'name' ? 'is-sorted' : '' }}" href="{{ $sortLink('name') }}">المنطقة <i class="fa {{ $sortIcon('name') }}"></i></a></th>
                                    @unless ($selectedCity)<th>المدينة</th>@endunless
                                    <th><a class="ht-sort {{ $sort === 'latitude' ? 'is-sorted' : '' }}" href="{{ $sortLink('latitude') }}">الإحداثيات <i class="fa {{ $sortIcon('latitude') }}"></i></a></th>
                                    <th>الحدود</th>
                                    <th>الفرق</th>
                                    <th><a class="ht-sort {{ $sort === 'is_active' ? 'is-sorted' : '' }}" href="{{ $sortLink('is_active') }}">الحالة <i class="fa {{ $sortIcon('is_active') }}"></i></a></th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($districts as $district)
                                    <tr>
                                        <td class="text-muted ht-num">{{ $district->id }}</td>
                                        <td>
                                            <strong>{{ $district->name }}</strong>
                                            @if ($district->name_en || $district->slug)
                                                <div class="small text-muted" dir="ltr" style="text-align: right">
                                                    {{ $district->name_en }}@if ($district->name_en && $district->slug) · @endif<code class="small">{{ $district->slug }}</code>
                                                </div>
                                            @endif
                                        </td>
                                        @unless ($selectedCity)
                                            <td><a href="{{ route('admin.hourly-cities.districts', $district->city_id) }}" class="text-decoration-none">{{ $district->city->name_ar }}</a></td>
                                        @endunless
                                        <td class="ht-num small" dir="ltr" style="text-align: right">
                                            @if ($district->latitude !== null && $district->longitude !== null)
                                                {{ $district->latitude }}, {{ $district->longitude }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.hourly-cities.polygons', [$district->city_id, 'district' => $district->id]) }}" class="text-decoration-none" title="{{ $district->polygon ? 'تعديل الحدود' : 'رسم الحدود' }}">
                                                <span data-polygon-chip="{{ $district->id }}" class="ht-chip {{ $district->polygon ? 'ht-chip-on' : 'hd-chip-warn' }}"><i class="fa {{ $district->polygon ? 'fa-draw-polygon' : 'fa-pen-ruler' }}"></i> {{ $district->polygon ? 'محفوظة' : 'ارسم الحدود' }}</span>
                                            </a>
                                        </td>
                                        <td>
                                            @if ($district->teams_count)
                                                <details class="dl-teams">
                                                    <summary><span class="ht-chip hd-chip-primary"><i class="fa fa-people-group"></i> {{ $district->teams_count }} فريق <i class="fa fa-angle-down"></i></span></summary>
                                                    <div class="dl-teams-list">
                                                        @foreach ($district->teams as $team)
                                                            <div>
                                                                <a href="{{ route('admin.hourly-teams.manage', $team) }}#areas">{{ $team->name }}</a>
                                                                <span class="{{ $team->pivot->is_active ? 'text-success' : 'text-muted' }}">— {{ $team->pivot->is_active ? 'تغطية مفعلة' : 'تغطية متوقفة' }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            @else
                                                <span class="text-muted small">لا توجد فرق</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="ht-toggle district-switch" role="switch" aria-label="حالة {{ $district->name }}" aria-checked="{{ $district->is_active ? 'true' : 'false' }}" data-url="{{ route('admin.hourly-districts.status', $district) }}" {{ $canEdit ? '' : 'disabled' }}><span>{{ $district->is_active ? '✓' : '' }}</span></button>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" data-bs-toggle="modal" data-bs-target="#teamsModal"
                                                    data-teams="{{ json_encode(['id' => $district->id, 'name' => $district->name, 'url' => route('admin.hourly-districts.teams', $district), 'teams' => $district->teams->sortBy('name')->map(fn ($team) => ['id' => $team->id, 'name' => $team->name, 'active' => (bool) $team->is_active, 'coverage' => (bool) $team->pivot->is_active, 'manage_url' => route('admin.hourly-teams.manage', $team).'#areas'])->values()]) }}">
                                                <i class="fa fa-people-group ms-1"></i> الفرق <span class="badge rounded-pill bg-light text-dark" data-teams-count>{{ $district->teams->count() }}</span>
                                            </button>
                                            @if ($canEdit)
                                                <button type="button" class="btn btn-sm btn-outline-info rounded-3" data-bs-toggle="modal" data-bs-target="#polygonModal"
                                                        data-polygon="{{ json_encode(['id' => $district->id, 'name' => $district->name, 'city_id' => $district->city_id, 'city' => $district->city->name_ar ?: $district->city->name_en, 'polygon' => $district->polygon, 'latitude' => $district->latitude, 'longitude' => $district->longitude, 'save_url' => route('admin.hourly-districts.polygon', [$district->city_id, $district]), 'osm_url' => route('admin.hourly-districts.osm-polygon', $district), 'boundary_url' => route('admin.hourly-cities.boundary', $district->city_id)]) }}">
                                                    <i class="fa fa-draw-polygon ms-1"></i> رسم الحدود
                                                </button>
                                                <a class="btn btn-sm btn-outline-primary ht-icon-btn" href="{{ route('admin.hourly-districts.edit', $district) }}" title="تعديل"
                                                   data-bs-toggle="modal" data-bs-target="#districtModal"
                                                   data-record="{{ json_encode(['id' => $district->id, 'url' => route('admin.hourly-districts.update', $district), 'name' => $district->name, 'city_id' => $district->city_id, 'name_en' => $district->name_en, 'latitude' => $district->latitude, 'longitude' => $district->longitude, 'is_active' => (bool) $district->is_active, 'team_ids' => $district->teams->pluck('id')]) }}"><i class="fa fa-pen"></i></a>
                                            @endif
                                            @if ($canDelete)
                                                <form method="POST" action="{{ route('admin.hourly-districts.destroy', $district) }}" class="d-inline" data-confirm-title="حذف المنطقة" data-confirm="حذف المنطقة من الدليل؟" data-confirm-button="نعم، احذف">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger ht-icon-btn" title="حذف"><i class="fa fa-trash"></i></button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $districts->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    @include('admin.hourly_districts._teams-modal')

    @if ($canEdit)
        @include('admin.hourly_districts._polygon-modal')

        {{-- مودال واحد لإضافة المنطقة وتعديلها --}}
        <div class="modal fade ht-page" id="districtModal" tabindex="-1" aria-labelledby="districtModalLabel" aria-hidden="true" dir="rtl">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form method="POST" action="{{ $editingId ? route('admin.hourly-districts.update', $editingId) : route('admin.hourly-districts.store') }}" class="modal-content border-0 rounded-4">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" {{ $editingId ? '' : 'disabled' }}>
                    <input type="hidden" name="_form" value="{{ $editingId ? 'district-edit' : 'district-create' }}">
                    <input type="hidden" name="_record_id" value="{{ $editingId }}">
                    <div class="modal-header ht-head">
                        <div class="ht-head-icon"><i data-modal-icon class="fa {{ $editingId ? 'fa-pen-to-square' : 'fa-map-pin' }}"></i></div>
                        <div>
                            <h5 id="districtModalLabel" data-modal-title>{{ $editingId ? 'تعديل المنطقة' : 'إضافة منطقة جديدة' }}</h5>
                            <p>{{ $cityName ? 'في مدينة '.$cityName : 'المدينة والاسم، ثم الموقع، ثم الحالة والفرق' }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body ht-body">
                        @if ($fromModal && $errors->any())
                            <div class="alert alert-danger border-0 rounded-3" data-modal-errors>
                                <div class="fw-bold mb-1"><i class="fa fa-circle-xmark ms-1"></i> تعذّر الحفظ، راجع الحقول التالية:</div>
                                <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                        @include('admin.hourly_districts._district-fields', ['district' => $newDistrict, 'teamIds' => []])
                    </div>
                    <div class="modal-footer ht-foot">
                        <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="fa fa-floppy-disk ms-1"></i> <span data-modal-submit>{{ $editingId ? 'حفظ التعديلات' : 'حفظ المنطقة' }}</span></button>
                        <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('js')
@include('admin.hourly._confirm-script')
@if ($canEdit)
    @include('admin.hourly_districts._district-script')
    @include('admin.hourly_districts._modal-form-script')
    <script>
        hourlyModalForm('districtModal', {
            key: 'district', icon: 'fa-map-pin', reopen: @json($fromModal),
            createAction: @json(route('admin.hourly-districts.store')), createTitle: 'إضافة منطقة جديدة', createSubmit: 'حفظ المنطقة',
            editTitle: record => 'تعديل المنطقة — ' + record.name,
            defaults: {city_id: @json($selectedCity?->id), name: '', name_en: '', latitude: '', longitude: '', is_active: true, team_ids: []},
        });
    </script>
@endif
<script>
document.querySelectorAll('.district-switch').forEach(button => button.addEventListener('click', async () => {
    button.disabled = true;
    const feedback = document.getElementById('district-feedback');
    try {
        const response = await fetch(button.dataset.url, {method: 'PUT', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify({is_active: button.getAttribute('aria-checked') !== 'true'})});
        const data = await response.json();
        if (!response.ok || typeof data.is_active !== 'boolean') throw new Error('تعذر تحديث الحالة. تحقق من الاتصال والصلاحيات.');
        button.setAttribute('aria-checked', String(data.is_active));
        button.querySelector('span').textContent = data.is_active ? '✓' : '';
        feedback.className = 'alert alert-success border-0 rounded-3';
        feedback.textContent = data.message;
    } catch (e) {
        feedback.className = 'alert alert-danger border-0 rounded-3';
        feedback.textContent = e.message;
    } finally {
        button.disabled = false;
    }
}));
</script>
@endpush
