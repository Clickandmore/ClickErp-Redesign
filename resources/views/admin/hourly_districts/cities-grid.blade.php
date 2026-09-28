@extends('admin.layout.indexs.index')

@section('page-title', 'مدن المملكة — التأجير بالساعة')

@section('style')
    @include('admin.hourly_teams._style')
@endsection

@section('content')
    @php
        $canEdit = AdminGuardHasPerm('hourly_teams_edit');
        $sort = $filters['sort'] ?? 'sort_order';
        $direction = $filters['direction'] ?? 'asc';
        $sortLink = function (string $key) use ($filters, $sort, $direction) {
            return route('admin.hourly-cities.index', array_merge($filters, ['sort' => $key, 'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc']));
        };
        $sortIcon = fn (string $key) => $sort !== $key ? 'fa-sort' : ($direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
        $fromModal = in_array(old('_form'), ['city-create', 'city-edit'], true);
        $editingId = old('_form') === 'city-edit' ? (int) old('_record_id') : null;
        $columns = ['id' => '#', 'name_ar' => 'المدينة', 'sort_order' => 'الترتيب', 'service_districts_count' => 'المناطق', 'mapped_count' => 'تغطية الحدود', 'is_active' => 'الحالة'];
        $hasFilters = collect(['search', 'status', 'districts', 'coverage', 'location'])->contains(fn ($key) => filled($filters[$key] ?? null));
    @endphp
    <div class="container-fluid ht-page" dir="rtl">
        <div id="city-feedback" class="alert border-0 rounded-3 {{ session('success') ? 'alert-success' : 'd-none' }}" role="status">{{ session('success') }}</div>

        <div class="ht-card">
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa fa-city"></i></div>
                <div>
                    <h5>مدن المملكة</h5>
                    <p>المدينة ← مناطقها وأحياؤها ← حدود كل منطقة على الخريطة</p>
                </div>
                <div class="ht-actions">
                    <a class="btn btn-outline-primary rounded-3" href="{{ route('admin.hourly-districts.index') }}"><i class="fa fa-map-pin ms-1"></i> كل المناطق</a>
                    @if ($canEdit)
                        <button type="button" class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#cityModal"><i class="fa fa-plus ms-1"></i> إضافة مدينة جديدة</button>
                    @endif
                </div>
            </div>
            <div class="ht-body">
                <form method="GET" action="{{ route('admin.hourly-cities.index') }}" class="hd-filter mb-4">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="direction" value="{{ $direction }}">
                    <div class="hd-filter-head">
                        <div class="hd-filter-title"><i class="fa fa-sliders"></i> فلتر قائمة المدن</div>
                        <div class="hd-filter-search">
                            <i class="fa fa-magnifying-glass"></i>
                            <input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="اسم المدينة بالعربية أو الإنجليزية" maxlength="150" aria-label="بحث">
                        </div>
                    </div>
                    <div class="hd-filter-body">
                        <div class="hd-filter-field">
                            <label for="status"><i class="fa fa-power-off"></i> حالة المدينة</label>
                            <select class="form-select {{ filled($filters['status'] ?? null) ? 'is-set' : '' }}" id="status" name="status">
                                <option value="">الكل</option>
                                <option value="active" @selected(($filters['status'] ?? '') === 'active')>نشطة</option>
                                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>غير نشطة</option>
                            </select>
                        </div>
                        <div class="hd-filter-field">
                            <label for="districts"><i class="fa fa-map-pin"></i> المناطق والأحياء</label>
                            <select class="form-select {{ filled($filters['districts'] ?? null) ? 'is-set' : '' }}" id="districts" name="districts">
                                <option value="">الكل</option>
                                <option value="with" @selected(($filters['districts'] ?? '') === 'with')>بها مناطق</option>
                                <option value="without" @selected(($filters['districts'] ?? '') === 'without')>بدون مناطق</option>
                            </select>
                        </div>
                        <div class="hd-filter-field">
                            <label for="coverage"><i class="fa fa-draw-polygon"></i> تغطية الحدود</label>
                            <select class="form-select {{ filled($filters['coverage'] ?? null) ? 'is-set' : '' }}" id="coverage" name="coverage">
                                <option value="">الكل</option>
                                <option value="complete" @selected(($filters['coverage'] ?? '') === 'complete')>مكتملة</option>
                                <option value="partial" @selected(($filters['coverage'] ?? '') === 'partial')>جزئية</option>
                                <option value="none" @selected(($filters['coverage'] ?? '') === 'none')>بدون حدود</option>
                            </select>
                        </div>
                        <div class="hd-filter-field">
                            <label for="location"><i class="fa fa-location-crosshairs"></i> إحداثيات المركز</label>
                            <select class="form-select {{ filled($filters['location'] ?? null) ? 'is-set' : '' }}" id="location" name="location">
                                <option value="">الكل</option>
                                <option value="available" @selected(($filters['location'] ?? '') === 'available')>متوفرة</option>
                                <option value="missing" @selected(($filters['location'] ?? '') === 'missing')>غير مكتملة</option>
                            </select>
                        </div>
                    </div>
                    <div class="hd-filter-foot">
                        <div class="d-flex align-items-center gap-2">
                            <label for="per_page" class="small text-muted text-nowrap mb-0">عدد الصفوف</label>
                            <select class="form-select form-select-sm" id="per_page" name="per_page" onchange="this.form.submit()" style="width: 80px">
                                @foreach ([10, 25, 50, 100] as $size)
                                    <option value="{{ $size }}" {{ (int) ($filters['per_page'] ?? 25) === $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
                            <span class="small text-muted ms-2">النتائج: <strong class="ht-num">{{ $cities->total() }}</strong> مدينة</span>
                        </div>
                        <div class="d-flex gap-2">
                            @if ($hasFilters)
                                <a href="{{ route('admin.hourly-cities.index') }}" class="btn btn-light rounded-3"><i class="fa fa-rotate-left ms-1"></i> مسح</a>
                            @endif
                            <button class="btn btn-primary rounded-3 px-4"><i class="fa fa-magnifying-glass ms-1"></i> بحث</button>
                        </div>
                    </div>
                </form>

                @if ($cities->isEmpty())
                    <div class="ht-empty"><i class="fa fa-city"></i> لا توجد مدن مطابقة.</div>
                @else
                    @if ($canEdit)
                        <form method="POST" action="{{ route('admin.hourly-cities.bulk-status') }}" id="city-bulk-form">
                            @csrf
                            @method('PUT')
                            <div class="d-flex align-items-center gap-2 flex-wrap p-3 mb-3 rounded-3 border bg-light">
                                <strong class="small ms-2"><i class="fa fa-list-check ms-1 text-primary"></i> إجراء جماعي</strong>
                                <span class="small text-muted">المحدد: <strong id="city-selected-count" class="ht-num">0</strong></span>
                                <select class="form-select form-select-sm" name="is_active" aria-label="الإجراء الجماعي" style="width: auto" required>
                                    <option value="">اختر الإجراء</option>
                                    <option value="1">تفعيل المدن المحددة</option>
                                    <option value="0">إيقاف المدن المحددة</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary rounded-3" id="city-bulk-submit" disabled>تطبيق</button>
                                <span class="small text-muted">يُطبق على المدن المحددة في هذه الصفحة فقط.</span>
                            </div>
                    @endif
                    <div class="table-responsive">
                        <table class="ht-table">
                            <thead>
                                <tr>
                                    @if ($canEdit)
                                        <th style="width: 42px"><input class="form-check-input" type="checkbox" id="city-select-all" aria-label="تحديد كل المدن في الصفحة"></th>
                                    @endif
                                    @foreach ($columns as $key => $label)
                                        <th><a class="ht-sort {{ $sort === $key ? 'is-sorted' : '' }}" href="{{ $sortLink($key) }}">{{ $label }} <i class="fa {{ $sortIcon($key) }}"></i></a></th>
                                    @endforeach
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cities as $city)
                                    @php
                                        $total = $city->service_districts_count;
                                        $coverage = $total ? round($city->mapped_count * 100 / $total) : 0;
                                    @endphp
                                    <tr>
                                        @if ($canEdit)
                                            <td><input class="form-check-input city-select" type="checkbox" name="city_ids[]" value="{{ $city->id }}" aria-label="تحديد {{ $city->name_ar }}"></td>
                                        @endif
                                        <td class="text-muted ht-num">{{ $city->id }}</td>
                                        <td>
                                            <strong>{{ $city->name_ar }}</strong>
                                            <div class="small text-muted" dir="ltr" style="text-align: right">{{ $city->name_en }}</div>
                                        </td>
                                        <td class="ht-num">{{ $city->sort_order }}</td>
                                        <td>
                                            @if ($total)
                                                <a href="{{ route('admin.hourly-cities.districts', $city) }}" class="text-decoration-none">
                                                    <strong class="ht-num text-body">{{ $total }}</strong> <span class="small text-muted">منطقة</span>
                                                </a>
                                                <div class="small">
                                                    <span class="text-success">نشط: {{ $city->active_count }}</span>
                                                    @if ($total - $city->active_count)<span class="text-danger ms-2">غير نشط: {{ $total - $city->active_count }}</span>@endif
                                                </div>
                                            @else
                                                <span class="text-muted small">لا توجد مناطق</span>
                                            @endif
                                        </td>
                                        <td title="المناطق ذات حدود محفوظة / إجمالي المناطق">
                                            <div class="ht-progress">
                                                <div class="ht-progress-bar {{ $coverage === 0 ? 'is-zero' : ($coverage < 100 ? 'is-low' : '') }}"><span style="width: {{ max($coverage, $total ? 4 : 0) }}%"></span></div>
                                                <small class="ht-num">{{ $coverage }}% · {{ $city->mapped_count }} / {{ $total }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button" class="ht-toggle city-switch" role="switch" aria-label="حالة {{ $city->name_ar }}" aria-checked="{{ $city->is_active ? 'true' : 'false' }}" data-url="{{ route('admin.hourly-cities.status', $city) }}" {{ $canEdit ? '' : 'disabled' }}><span>{{ $city->is_active ? '✓' : '' }}</span></button>
                                        </td>
                                        <td class="text-nowrap">
                                            <a class="btn btn-sm btn-outline-primary rounded-3" href="{{ route('admin.hourly-cities.districts', $city) }}"><i class="fa fa-map-pin ms-1"></i> إدارة المناطق</a>
                                            <a class="btn btn-sm btn-outline-info rounded-3" href="{{ route('admin.hourly-cities.polygons', $city) }}"><i class="fa fa-draw-polygon ms-1"></i> الحدود</a>
                                            @if ($canEdit)
                                                <a class="btn btn-sm btn-outline-secondary ht-icon-btn" href="{{ route('admin.hourly-cities.edit', $city) }}" title="تعديل المدينة"
                                                   data-bs-toggle="modal" data-bs-target="#cityModal"
                                                   data-record="{{ json_encode(['id' => $city->id, 'url' => route('admin.hourly-cities.update', $city), 'name_ar' => $city->name_ar, 'name_en' => $city->name_en, 'latitude' => $city->latitude, 'longitude' => $city->longitude, 'sort_order' => $city->sort_order, 'is_active' => (bool) $city->is_active]) }}"><i class="fa fa-pen"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($canEdit)
                        </form>
                    @endif
                    <div class="mt-3">{{ $cities->onEachSide(1)->links('pagination::hourly-bootstrap-5') }}</div>
                    <p class="text-muted small mb-0 mt-2"><i class="fa fa-circle-info ms-1"></i> تغطية الحدود = عدد المناطق ذات مضلع محفوظ ÷ إجمالي مناطق المدينة.</p>
                @endif
            </div>
        </div>
    </div>

    @if ($canEdit)
        {{-- مودال واحد لإضافة المدينة وتعديلها --}}
        <div class="modal fade ht-page" id="cityModal" tabindex="-1" aria-labelledby="cityModalLabel" aria-hidden="true" dir="rtl">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form method="POST" action="{{ $editingId ? route('admin.hourly-cities.update', $editingId) : route('admin.hourly-cities.store') }}" class="modal-content border-0 rounded-4">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" {{ $editingId ? '' : 'disabled' }}>
                    <input type="hidden" name="_form" value="{{ $editingId ? 'city-edit' : 'city-create' }}">
                    <input type="hidden" name="_record_id" value="{{ $editingId }}">
                    <div class="modal-header ht-head">
                        <div class="ht-head-icon"><i data-modal-icon class="fa {{ $editingId ? 'fa-pen-to-square' : 'fa-city' }}"></i></div>
                        <div>
                            <h5 id="cityModalLabel" data-modal-title>{{ $editingId ? 'تعديل المدينة' : 'إضافة مدينة جديدة' }}</h5>
                            <p>الاسم باللغتين، ثم مركز المدينة، ثم ترتيب ظهورها وحالتها</p>
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
                        @include('admin.hourly_districts._city-fields', ['city' => new \App\Models\City(['sort_order' => 1, 'is_active' => true])])
                    </div>
                    <div class="modal-footer ht-foot">
                        <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="fa fa-floppy-disk ms-1"></i> <span data-modal-submit>{{ $editingId ? 'حفظ التعديلات' : 'حفظ المدينة' }}</span></button>
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
    @include('admin.hourly_districts._city-script')
    @include('admin.hourly_districts._modal-form-script')
    <script>
        hourlyModalForm('cityModal', {
            key: 'city', icon: 'fa-city', reopen: @json($fromModal),
            createAction: @json(route('admin.hourly-cities.store')), createTitle: 'إضافة مدينة جديدة', createSubmit: 'حفظ المدينة',
            editTitle: record => 'تعديل المدينة — ' + record.name_ar,
            defaults: {name_ar: '', name_en: '', latitude: '', longitude: '', sort_order: 1, is_active: true},
        });
    </script>
@endif
<script>
const cityBulkForm = document.getElementById('city-bulk-form');
if (cityBulkForm) {
    const selectAll = document.getElementById('city-select-all');
    const boxes = [...cityBulkForm.querySelectorAll('.city-select')];
    const count = document.getElementById('city-selected-count');
    const submit = document.getElementById('city-bulk-submit');
    const refreshSelection = () => {
        const selected = boxes.filter(box => box.checked).length;
        count.textContent = selected;
        submit.disabled = selected === 0;
        selectAll.checked = selected === boxes.length;
        selectAll.indeterminate = selected > 0 && selected < boxes.length;
    };
    selectAll.addEventListener('change', () => { boxes.forEach(box => box.checked = selectAll.checked); refreshSelection(); });
    boxes.forEach(box => box.addEventListener('change', refreshSelection));
    cityBulkForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!cityBulkForm.elements.is_active.value) return;
        const activate = cityBulkForm.elements.is_active.value === '1';
        const action = activate ? 'تفعيل' : 'إيقاف';
        const confirmed = await hourlyConfirm({
            title: `${action} المدن المحددة`,
            text: `هل تريد ${action} ${count.textContent} مدينة محددة؟`,
            button: `نعم، ${action}`,
            danger: !activate,
        });
        if (!confirmed) return;
        submit.disabled = true;
        cityBulkForm.submit();
    });
    refreshSelection();
}

document.querySelectorAll('.city-switch[data-url]').forEach(button => button.addEventListener('click', async () => {
    const feedback = document.getElementById('city-feedback');
    button.disabled = true;
    try {
        const response = await fetch(button.dataset.url, {method: 'PUT', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify({is_active: button.getAttribute('aria-checked') !== 'true'})});
        if (!response.ok) throw new Error('تعذر تحديث الحالة. حاول مجددًا.');
        const data = await response.json();
        if (typeof data.is_active !== 'boolean') throw new Error('يرجى تسجيل الدخول مجددًا.');
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
