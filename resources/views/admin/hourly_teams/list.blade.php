@extends('admin.layout.indexs.index')

@section('page-title', 'إدارة الفرق')

@section('style')
    @include('admin.hourly_teams._style')
@endsection

@section('content')
    @php
        $canEdit = AdminGuardHasPerm('hourly_teams_edit');
        $canDelete = AdminGuardHasPerm('hourly_teams_delete');
        $activeFilters = count(array_filter($filters, fn ($v) => $v !== null && $v !== ''));
        $assignedDrivers = $teams->flatMap(fn ($t) => $t->driverMembers->pluck('member_id'))->unique()->count();
        $noCoverage = $teams->filter(fn ($t) => $t->districts->isEmpty())->count();
        $canCreate = AdminGuardHasPerm('hourly_teams_create');
        $fromModal = in_array(old('_form'), ['team-create', 'team-edit'], true);
        $editingId = old('_form') === 'team-edit' ? (int) old('_record_id') : null;
    @endphp
    <div class="container-fluid ht-page" dir="rtl">
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3"><i class="fa fa-circle-check ms-1"></i> {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger border-0 rounded-3"><i class="fa fa-circle-xmark ms-1"></i> {{ session('error') }}</div>
        @endif

        {{-- ملخص --}}
        <div class="hd-stats">
            <div class="hd-stat"><div class="hd-stat-icon"><i class="fa fa-people-group"></i></div><div><span>الفرق المعروضة</span><strong class="ht-num">{{ $teams->count() }}</strong></div></div>
            <div class="hd-stat hd-stat-on"><div class="hd-stat-icon"><i class="fa fa-circle-check"></i></div><div><span>فرق نشطة</span><strong class="ht-num">{{ $teams->where('is_active', true)->count() }}</strong></div></div>
            <div class="hd-stat hd-stat-info"><div class="hd-stat-icon"><i class="fa fa-car-side"></i></div><div><span>سائقون مُسندون</span><strong class="ht-num">{{ $assignedDrivers }}</strong></div></div>
            <div class="hd-stat hd-stat-warn"><div class="hd-stat-icon"><i class="fa fa-map-location-dot"></i></div><div><span>فرق بلا مناطق خدمة</span><strong class="ht-num">{{ $noCoverage }}</strong></div></div>
        </div>

        <div class="ht-card">
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa fa-people-group"></i></div>
                <div>
                    <h5>فرق التأجير بالساعة</h5>
                    <p>كل فريق له موقع انطلاق وسائقون وقادة ومناطق خدمة داخل المدن</p>
                </div>
                <div class="ht-actions">
                    @if (AdminGuardHasPerm('hourly_teams_create'))
                        <a class="btn btn-primary rounded-3" href="{{ route('admin.hourly-teams.create') }}" data-bs-toggle="modal" data-bs-target="#teamModal"><i class="fa fa-plus ms-1"></i> إضافة فريق جديد</a>
                    @endif
                </div>
            </div>
            <div class="ht-body">
                <form method="GET" action="{{ route('admin.hourly-teams.index') }}" class="hd-filter mb-4">
                    <div class="hd-filter-head">
                        <div class="hd-filter-title">
                            <i class="fa fa-sliders"></i> فلتر قائمة الفرق
                            @if ($activeFilters)<span class="hd-filter-count">{{ $activeFilters }} فلتر مفعّل</span>@endif
                        </div>
                        <div class="d-flex align-items-center gap-2" style="flex: 0 1 540px">
                            <div class="hd-filter-field flex-shrink-0">
                                <select class="form-select {{ !empty($filters['status']) ? 'is-set' : '' }}" name="status" id="status" onchange="this.form.submit()" aria-label="الحالة" style="height: 38px; min-width: 150px">
                                    <option value="">كل الحالات</option>
                                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>نشط</option>
                                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>متوقف</option>
                                </select>
                            </div>
                            <div class="hd-filter-search">
                                <i class="fa fa-magnifying-glass"></i>
                                <input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ابحث باسم الفريق" aria-label="اسم الفريق">
                            </div>
                        </div>
                    </div>
                    <div class="hd-filter-foot">
                        <span class="text-muted small">النتائج: <strong class="ht-num">{{ $teams->count() }}</strong> فريق</span>
                        <div class="d-flex gap-2">
                            @if ($activeFilters)
                                <a class="btn btn-light rounded-3" href="{{ route('admin.hourly-teams.index') }}"><i class="fa fa-rotate-left ms-1"></i> مسح الفلاتر</a>
                            @endif
                            <button class="btn btn-primary rounded-3 px-4" type="submit"><i class="fa fa-filter ms-1"></i> تطبيق</button>
                        </div>
                    </div>
                </form>

                @if ($teams->isEmpty())
                    <div class="ht-empty">
                        <i class="fa fa-people-group"></i>
                        {{ $activeFilters ? 'لا توجد فرق مطابقة لشروط الفلتر.' : 'لا توجد فرق بعد. أنشئ أول فريق ثم أسند إليه السائقين ومناطق الخدمة.' }}
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="ht-table">
                            <thead>
                                <tr>
                                    <th>الفريق</th>
                                    <th>السائقون</th>
                                    <th>مناطق الخدمة</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($teams as $team)
                                    @php
                                        $members = $team->driverMembers->filter(fn ($m) => $m->driver !== null);
                                        $activeMembers = $members->filter(fn ($m) => $m->is_active && $m->driver->is_active)->count();
                                        $byCity = $team->districts->groupBy('city_id');
                                    @endphp
                                    <tr>
                                        <td style="min-width: 220px">
                                            <strong>{{ $team->name }}</strong>
                                            @if ($team->description)<div class="small text-muted text-truncate" style="max-width: 320px" title="{{ $team->description }}">{{ $team->description }}</div>@endif
                                        </td>
                                        <td>
                                            @if ($members->isEmpty())
                                                <span class="ht-chip hd-chip-warn"><i class="fa fa-user-slash"></i> لا يوجد سائقون</span>
                                            @else
                                                <div class="d-flex flex-wrap gap-1 mb-1">
                                                    <span class="ht-chip hd-chip-primary" title="أساسي">أساسي <span class="ht-num">{{ $members->where('role', 'primary')->count() }}</span></span>
                                                    <span class="ht-chip ht-chip-off" title="ثانوي">ثانوي <span class="ht-num">{{ $members->where('role', 'secondary')->count() }}</span></span>
                                                </div>
                                                <div class="small">
                                                    <span class="text-success"><i class="fa fa-circle fa-2xs"></i> نشط {{ $activeMembers }}</span>
                                                    @if ($members->count() - $activeMembers)
                                                        <span class="text-muted ms-2"><i class="fa fa-circle fa-2xs"></i> غير نشط {{ $members->count() - $activeMembers }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse ($byCity as $districts)
                                                @php
                                                    $city = $districts->first()->city;
                                                    $activeDistricts = $districts->filter(fn ($d) => $d->pivot->is_active)->count();
                                                @endphp
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <i class="fa fa-city text-muted small"></i>
                                                    <strong class="small">{{ $city->name_ar ?: $city->name_en }}</strong>
                                                    <span class="ht-chip {{ $activeDistricts ? 'ht-chip-on' : 'ht-chip-off' }}" title="نشطة / إجمالي المناطق"><span class="ht-num">{{ $activeDistricts }} / {{ $districts->count() }}</span> منطقة</span>
                                                </div>
                                            @empty
                                                <span class="ht-chip hd-chip-warn"><i class="fa fa-triangle-exclamation"></i> لا توجد مناطق معيّنة</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @if ($canEdit)
                                                <form method="POST" action="{{ route('admin.hourly-teams.toggle', $team) }}" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="{{ $team->is_active ? 'اضغط لإيقاف الفريق' : 'اضغط لتفعيل الفريق' }}">
                                                        <span class="ht-chip {{ $team->is_active ? 'ht-chip-on' : 'ht-chip-off' }}"><i class="fa {{ $team->is_active ? 'fa-circle-check' : 'fa-circle-pause' }}"></i> {{ $team->is_active ? 'نشط' : 'متوقف' }}</span>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="ht-chip {{ $team->is_active ? 'ht-chip-on' : 'ht-chip-off' }}">{{ $team->is_active ? 'نشط' : 'متوقف' }}</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            <a class="btn btn-sm btn-primary rounded-3" href="{{ route('admin.hourly-teams.manage', $team) }}"><i class="fa fa-sliders ms-1"></i> إدارة الفريق</a>
                                            @if ($canEdit)
                                                <a class="btn btn-sm btn-outline-secondary ht-icon-btn" href="{{ route('admin.hourly-teams.edit', $team) }}" title="تعديل البيانات"
                                                   data-bs-toggle="modal" data-bs-target="#teamModal"
                                                   data-record="{{ json_encode(['id' => $team->id, 'url' => route('admin.hourly-teams.update', $team), 'name' => $team->name, 'description' => $team->description, 'is_active' => (bool) $team->is_active]) }}"><i class="fa fa-pen"></i></a>
                                            @endif
                                            @if ($canDelete)
                                                <form method="POST" action="{{ route('admin.hourly-teams.destroy', $team) }}" class="d-inline" data-confirm-title="حذف الفريق" data-confirm="حذف الفريق وإنهاء عضويات السائقين فيه؟" data-confirm-button="نعم، احذف">
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
                @endif
            </div>
        </div>
    </div>

    @if ($canCreate || $canEdit)
        {{-- مودال واحد لإضافة الفريق وتعديل بياناته الأساسية --}}
        <div class="modal fade ht-page" id="teamModal" tabindex="-1" aria-labelledby="teamModalLabel" aria-hidden="true" dir="rtl">
            <div class="modal-dialog">
                <form method="POST" action="{{ $editingId ? route('admin.hourly-teams.update', $editingId) : route('admin.hourly-teams.store') }}" class="modal-content border-0 rounded-4">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" {{ $editingId ? '' : 'disabled' }}>
                    <input type="hidden" name="_form" value="{{ $editingId ? 'team-edit' : 'team-create' }}">
                    <input type="hidden" name="_record_id" value="{{ $editingId }}">
                    <div class="modal-header ht-head">
                        <div class="ht-head-icon"><i data-modal-icon class="fa {{ $editingId ? 'fa-pen-to-square' : 'fa-people-group' }}"></i></div>
                        <div>
                            <h5 id="teamModalLabel" data-modal-title>{{ $editingId ? 'تعديل الفريق' : 'إضافة فريق جديد' }}</h5>
                            <p>الاسم والوصف والحالة</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body ht-body">
                        @if ($fromModal && ($errors->any() || session('error')))
                            <div class="alert alert-danger border-0 rounded-3" data-modal-errors>
                                <div class="fw-bold mb-1"><i class="fa fa-circle-xmark ms-1"></i> تعذّر الحفظ، راجع ما يلي:</div>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                    @if (session('error'))<li>{{ session('error') }}</li>@endif
                                </ul>
                            </div>
                        @endif
                        @include('admin.hourly_teams._team-fields', ['team' => new \App\Models\Hourly\HourlyTeam(['is_active' => true])])
                        <div class="{{ $editingId ? 'd-none' : '' }}">@include('admin.hourly_teams._team-next-steps')</div>
                    </div>
                    <div class="modal-footer ht-foot">
                        <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="fa fa-floppy-disk ms-1"></i> <span data-modal-submit>{{ $editingId ? 'حفظ التعديلات' : 'إنشاء الفريق والمتابعة' }}</span></button>
                        <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('js')
@include('admin.hourly._confirm-script')
@if ($canCreate || $canEdit)
    @include('admin.hourly_districts._modal-form-script')
    <script>
        hourlyModalForm('teamModal', {
            key: 'team', icon: 'fa-people-group', reopen: @json($fromModal),
            createAction: @json(route('admin.hourly-teams.store')), createTitle: 'إضافة فريق جديد', createSubmit: 'إنشاء الفريق والمتابعة',
            editTitle: record => 'تعديل الفريق — ' + record.name,
            defaults: {name: '', description: '', is_active: true},
        });
        // خطوات ما بعد الإنشاء تخص الإضافة فقط
        document.getElementById('teamModal').addEventListener('show.bs.modal', event => {
            if (!event.relatedTarget) return;
            document.querySelector('#teamModal [data-team-next-steps]').parentElement.classList.toggle('d-none', Boolean(event.relatedTarget.dataset.record));
        });
    </script>
@endif
@endpush
