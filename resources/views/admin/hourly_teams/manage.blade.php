@extends('admin.layout.indexs.index')

@section('page-title', 'إدارة الفريق — '.$team->name)

@section('style')
    @include('admin.hourly_teams._style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <style>
        .tm-map { height: 460px; border-radius: 14px; border: 1px solid var(--ht-line); }
        .tm-multi { min-height: 220px; }
        .tm-bulk { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; padding: .7rem .9rem; border-radius: 12px; background: var(--ht-soft); border: 1px solid var(--ht-line); margin-bottom: 1rem; }
        .tm-details summary { cursor: pointer; font-weight: 600; color: #4338ca; list-style: none; }
        .tm-details summary::-webkit-details-marker { display: none; }
        .tm-status { display: inline-flex; align-items: center; gap: .6rem; }
        .tm-status-text { font-size: .8rem; font-weight: 600; color: var(--ht-muted); min-width: 3.5em; }
        .ht-toggle[aria-checked="true"] + .tm-status-text { color: #4338ca; }
        .ht-toggle[aria-busy="true"] span { opacity: .6; }
        .tm-location-choice { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
        .tm-location-choice label { cursor: pointer; border: 1px solid var(--ht-line); border-radius: 12px; padding: .75rem; background: #fff; }
        .tm-location-choice label:has(input:checked) { border-color: #4f46e5; background: #eef2ff; color: #3730a3; }
        #tm-toast { position: fixed; bottom: 1.25rem; left: 50%; transform: translateX(-50%); z-index: 1080; min-width: 260px; text-align: center; box-shadow: 0 12px 30px rgba(15, 23, 42, .18); }
    </style>
@endsection

@section('content')
    @php
        $canEdit = AdminGuardHasPerm('hourly_teams_edit');
        $hasLocation = $team->base_lat !== null && $team->base_lng !== null;
        $locationType = old('base_location_type', $team->base_location_type ?: ($team->base_shelter_place_id ? 'shelter' : 'custom'));
        $liveMembers = $team->driverMembers->filter(fn ($m) => $m->driver !== null);
        $activeMembers = $liveMembers->filter(fn ($m) => $m->is_active && $m->driver->is_active)->count();
        $activeDistricts = $team->districts->filter(fn ($d) => $d->pivot->is_active)->count();
        $tabs = [
            'location' => ['موقع الفريق', 'fa-location-dot', $hasLocation ? '✓' : '!'],
            'drivers' => ['السائقون', 'fa-car-side', $team->driverMembers->count()],
            'maids' => ['الخادمات', 'fa-users', $team->maidMembers->whereNotNull('maid')->count()],
            'leaders' => ['قادة الفريق', 'fa-user-tie', $team->leaders->count()],
            'areas' => ['مناطق الخدمة', 'fa-map', $team->districts->count()],
        ];
    @endphp
    <div class="container-fluid ht-page" dir="rtl">
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3"><i class="fa fa-circle-check ms-1"></i> {{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3"><i class="fa fa-circle-xmark ms-1"></i> {{ $errors->first() }}</div>
        @endif

        {{-- ترويسة الفريق --}}
        <div class="ht-card">
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa fa-people-group"></i></div>
                <div>
                    <h5>{{ $team->name }}</h5>
                    <p>{{ $team->description ?: 'إعداد موقع الفريق وسائقيه وقادته ومناطق خدمته' }}</p>
                </div>
                <div class="ht-actions">
                    <span class="ht-chip {{ $team->is_active ? 'ht-chip-on' : 'ht-chip-off' }}"><i class="fa {{ $team->is_active ? 'fa-circle-check' : 'fa-circle-pause' }}"></i> {{ $team->is_active ? 'نشط' : 'متوقف' }}</span>
                    @if ($canEdit)
                        <a class="btn btn-outline-primary rounded-3" href="{{ route('admin.hourly-teams.edit', $team) }}"><i class="fa fa-pen ms-1"></i> تعديل البيانات</a>
                    @endif
                    <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-teams.index') }}"><i class="fa fa-arrow-right ms-1"></i> قائمة الفرق</a>
                </div>
            </div>
        </div>

        @unless ($team->is_active)
            <div class="ht-note ht-note-warn mb-3"><i class="fa fa-triangle-exclamation mt-1"></i><div>الفريق متوقف؛ لا يمكن إسناد سائقين جدد إليه حتى يُفعَّل.</div></div>
        @endunless

        {{-- ملخص الجاهزية --}}
        <div class="hd-stats">
            <div class="hd-stat {{ $hasLocation ? 'hd-stat-on' : 'hd-stat-warn' }}"><div class="hd-stat-icon"><i class="fa fa-location-dot"></i></div><div><span>موقع الانطلاق</span><strong style="font-size: 1rem">{{ $hasLocation ? 'محدد' : 'غير محدد' }}</strong></div></div>
            <div class="hd-stat hd-stat-info"><div class="hd-stat-icon"><i class="fa fa-car-side"></i></div><div><span>السائقون النشطون</span><strong class="ht-num">{{ $activeMembers }} <small class="text-muted fs-6">/ {{ $liveMembers->count() }}</small></strong></div></div>
            <div class="hd-stat"><div class="hd-stat-icon"><i class="fa fa-user-tie"></i></div><div><span>قادة الفريق</span><strong class="ht-num">{{ $team->leaders->count() }}</strong></div></div>
            <div class="hd-stat {{ $team->districts->isEmpty() ? 'hd-stat-warn' : 'hd-stat-on' }}"><div class="hd-stat-icon"><i class="fa fa-map"></i></div><div><span>المناطق النشطة</span><strong class="ht-num">{{ $activeDistricts }} <small class="text-muted fs-6">/ {{ $team->districts->count() }}</small></strong></div></div>
        </div>

        <ul class="nav ht-tabs mb-3" role="tablist">
            @foreach ($tabs as $key => [$label, $icon, $badge])
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#{{ $key }}" role="tab">
                        <i class="fa {{ $icon }}"></i> {{ $label }} <span class="badge rounded-pill">{{ $badge }}</span>
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content">
            {{-- ===== الموقع ===== --}}
            <div class="tab-pane show active" id="location" role="tabpanel">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="ht-card">
                            <div class="ht-head">
                                <div class="ht-head-icon"><i class="fa fa-map-location-dot"></i></div>
                                <div><h5>الخريطة</h5><p>{{ $canEdit ? 'اضغط على الخريطة لتحديد نقطة الانطلاق' : 'نقطة انطلاق الفريق' }}</p></div>
                            </div>
                            <div class="ht-body">
                                @if ($canEdit)
                                    <form id="team-location-search" class="mb-3">
                                        <label for="team-location-query" class="form-label">البحث عن عنوان أو حي أو معلم</label>
                                        <div class="input-group">
                                            <input id="team-location-query" class="form-control" type="search" required minlength="2" maxlength="200" placeholder="مثال: حي النرجس، الرياض" autocomplete="off">
                                            <button class="btn btn-primary" type="submit">بحث <i class="fa fa-search"></i></button>
                                        </div>
                                        <div id="team-location-results" class="list-group mt-2" aria-live="polite"></div>
                                    </form>
                                    <p id="team-location-map-hint" class="text-muted">اختر نتيجة البحث، أو اضغط على الخريطة واسحب العلامة لضبط الموقع، ثم اضغط حفظ الموقع.</p>
                                @endif
                                <div id="team-location-map" class="tm-map" dir="ltr"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="ht-card">
                            <div class="ht-head">
                                <div class="ht-head-icon"><i class="fa fa-crosshairs"></i></div>
                                <div><h5>نقطة الانطلاق</h5><p>منها يبدأ السائقون أول زيارة وإليها يعودون بعد آخر زيارة</p></div>
                            </div>
                            <form method="POST" action="{{ route('admin.hourly-teams.location', $team) }}">
                                @csrf @method('PUT')
                                <div class="ht-body">
                                    <div class="tm-location-choice mb-3">
                                        <label>
                                            <input class="form-check-input ms-1" type="radio" name="base_location_type" value="custom" {{ $locationType === 'custom' ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                                            موقع مخصص
                                        </label>
                                        <label>
                                            <input class="form-check-input ms-1" type="radio" name="base_location_type" value="shelter" {{ $locationType === 'shelter' ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                                            مقر إيواء
                                        </label>
                                    </div>

                                    <div id="shelter-location-fields" class="mb-3" {{ $locationType === 'shelter' ? '' : 'hidden' }}>
                                        <label for="base_shelter_place_id" class="form-label">مقر الإيواء</label>
                                        <select class="form-select @error('base_shelter_place_id') is-invalid @enderror" id="base_shelter_place_id" name="base_shelter_place_id" {{ $canEdit ? '' : 'disabled' }}>
                                            <option value="">— اختر مقر الإيواء —</option>
                                            @foreach ($shelters as $shelter)
                                                <option value="{{ $shelter->id }}"
                                                        data-lat="{{ $shelter->latitude }}" data-lng="{{ $shelter->longitude }}"
                                                        {{ (string) old('base_shelter_place_id', $team->base_shelter_place_id) === (string) $shelter->id ? 'selected' : '' }}>
                                                    {{ $shelter->title ?? ('#'.$shelter->id) }}{{ $shelter->city ? ' — '.$shelter->city : '' }}{{ $shelter->latitude === null || $shelter->longitude === null ? ' (الموقع غير محدد)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('base_shelter_place_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <div id="shelter-location-help" class="form-text">تُستخدم إحداثيات المقر المحفوظة في شاشة أماكن الإيواء.</div>
                                    </div>

                                    <div id="custom-location-fields" {{ $locationType === 'custom' ? '' : 'hidden' }}>
                                        <div class="mb-3">
                                            <label for="base_lat" class="form-label">خط العرض</label>
                                            <input class="form-control ht-num" dir="ltr" id="base_lat" name="base_lat" type="number" step="any" min="-90" max="90" value="{{ old('base_lat', $team->base_lat) }}" {{ $canEdit ? '' : 'readonly' }}>
                                        </div>
                                        <div class="mb-3">
                                            <label for="base_lng" class="form-label">خط الطول</label>
                                            <input class="form-control ht-num" dir="ltr" id="base_lng" name="base_lng" type="number" step="any" min="-180" max="180" value="{{ old('base_lng', $team->base_lng) }}" {{ $canEdit ? '' : 'readonly' }}>
                                        </div>
                                    </div>
                                    <div class="ht-note {{ $hasLocation ? 'ht-note-info' : 'ht-note-warn' }}">
                                        <i class="fa {{ $hasLocation ? 'fa-circle-info' : 'fa-triangle-exclamation' }} mt-1"></i>
                                        <div>{{ $hasLocation ? ($locationType === 'shelter' ? 'نقطة الانطلاق مرتبطة بمقر الإيواء المختار.' : 'الموقع محفوظ. يمكنك تغييره بالضغط على نقطة أخرى.') : 'لم يُحدد موقع الانطلاق بعد.' }}</div>
                                    </div>
                                </div>
                                @if ($canEdit)
                                    <div class="ht-foot"><button class="btn btn-primary rounded-3 flex-fill"><i class="fa fa-floppy-disk ms-1"></i> حفظ الموقع</button></div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== السائقون ===== --}}
            @include('admin.hourly_teams._maids')
            <div class="tab-pane" id="drivers" role="tabpanel">
                <div class="row g-3">
                    @if ($canEdit)
                        <div class="col-lg-4">
                            <div class="ht-card">
                                <div class="ht-head">
                                    <div class="ht-head-icon"><i class="fa fa-user-plus"></i></div>
                                    <div><h5>إسناد سائقين</h5><p>السائقون النشطون فقط</p></div>
                                </div>
                                <form method="POST" action="{{ route('admin.hourly-teams.drivers', $team) }}">
                                    @csrf
                                    <div class="ht-body">
                                        <div class="mb-3">
                                            <label for="driver_ids" class="form-label">السائقون</label>
                                            <select class="form-select tm-multi" id="driver_ids" name="driver_ids[]" multiple required>
                                                @foreach ($drivers as $driver)
                                                    <option value="{{ $driver->id }}">{{ $driver->name }} — {{ $driver->employee_number }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <label for="role" class="form-label">نوع الارتباط</label>
                                        <select class="form-select" name="role" id="role">
                                            <option value="primary">أساسي</option>
                                            <option value="secondary">ثانوي</option>
                                        </select>
                                        <div class="form-text">الأساسي ينقل ارتباط السائق الأساسي إلى هذا الفريق.</div>
                                    </div>
                                    <div class="ht-foot"><button class="btn btn-primary rounded-3 flex-fill" {{ $team->is_active ? '' : 'disabled' }}><i class="fa fa-plus ms-1"></i> إضافة إلى الفريق</button></div>
                                </form>
                            </div>
                        </div>
                    @endif
                    <div class="{{ $canEdit ? 'col-lg-8' : 'col-12' }}">
                        <div class="ht-card">
                            <div class="ht-head">
                                <div class="ht-head-icon"><i class="fa fa-users"></i></div>
                                <div><h5>سائقو الفريق</h5><p>نشط {{ $activeMembers }} من {{ $liveMembers->count() }}</p></div>
                            </div>
                            <div class="ht-body">
                                @if ($team->driverMembers->isEmpty())
                                    <div class="ht-empty"><i class="fa fa-car-side"></i> لا يوجد سائقون مرتبطون بهذا الفريق.</div>
                                @else
                                    <div class="table-responsive">
                                        <table class="ht-table">
                                            <thead><tr><th>السائق</th><th>نوع الارتباط</th><th>الحالة</th>@if ($canEdit)<th>الإجراءات</th>@endif</tr></thead>
                                            <tbody>
                                                @foreach ($team->driverMembers as $member)
                                                    @php $memberActive = $member->is_active && optional($member->driver)->is_active; @endphp
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $member->driver->name ?? 'سائق محذوف' }}</strong>
                                                            <div class="small text-muted">رقم الموظف <span class="ht-num" dir="ltr">{{ $member->driver->employee_number ?? '—' }}</span></div>
                                                        </td>
                                                        <td><span class="ht-chip {{ $member->role === 'primary' ? 'hd-chip-primary' : 'ht-chip-off' }}">{{ $member->role === 'primary' ? 'أساسي' : 'ثانوي' }}</span></td>
                                                        <td>
                                                            @if ($canEdit)
                                                                <div class="tm-status">
                                                                    <button type="button" class="ht-toggle" role="switch" aria-checked="{{ $member->is_active ? 'true' : 'false' }}" aria-label="عضوية {{ $member->driver->name ?? 'السائق' }} في الفريق"
                                                                            data-status-url="{{ route('admin.hourly-teams.member', [$team, $member->id]) }}"><span><i class="fa {{ $member->is_active ? 'fa-check' : 'fa-minus' }}"></i></span></button>
                                                                    <span class="tm-status-text" data-on="نشط" data-off="موقوف">{{ $member->is_active ? 'نشط' : 'موقوف' }}</span>
                                                                </div>
                                                            @else
                                                                <span class="ht-chip {{ $memberActive ? 'ht-chip-on' : 'ht-chip-off' }}">{{ $memberActive ? 'نشط' : 'غير نشط' }}</span>
                                                            @endif
                                                            @if ($member->driver && !$member->driver->is_active)<div class="small text-warning mt-1"><i class="fa fa-triangle-exclamation ms-1"></i>السائق نفسه متوقف</div>@endif
                                                        </td>
                                                        @if ($canEdit)
                                                            <td class="text-nowrap">
                                                                <form method="POST" action="{{ route('admin.hourly-teams.member', [$team, $member->id]) }}" class="d-flex gap-1">
                                                                    @csrf @method('PUT')
                                                                    <button name="action" value="remove" class="btn btn-sm btn-outline-danger ht-icon-btn" title="إزالة من الفريق" data-confirm-title="إزالة السائق" data-confirm="إزالة السائق من الفريق؟" data-confirm-button="نعم، إزالة"><i class="fa fa-user-minus"></i></button>
                                                                </form>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== القادة ===== --}}
            <div class="tab-pane" id="leaders" role="tabpanel">
                <div class="row g-3">
                    @if ($canEdit)
                        <div class="col-lg-4">
                            <div class="ht-card">
                                <div class="ht-head">
                                    <div class="ht-head-icon"><i class="fa fa-user-shield"></i></div>
                                    <div><h5>تحديد القادة</h5><p>اختر قادة الفريق من السائقين</p></div>
                                </div>
                                <form method="POST" action="{{ route('admin.hourly-teams.leaders', $team) }}">
                                    @csrf @method('PUT')
                                    <div class="ht-body">
                                        <label for="leader_ids" class="form-label">السائقون</label>
                                        <select name="leader_ids[]" id="leader_ids" multiple class="form-select tm-multi">
                                            @foreach ($leaderDrivers as $driver)
                                                <option value="{{ $driver->id }}" {{ in_array($driver->id, old('leader_ids', session()->hasOldInput() ? [] : $team->leaders->modelKeys())) ? 'selected' : '' }}>{{ $driver->name }} — {{ $driver->employee_number }}{{ $driver->is_active ? '' : ' (غير نشط)' }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">إلغاء جميع الاختيارات ثم الحفظ يزيل قادة الفريق.</div>
                                    </div>
                                    <div class="ht-foot"><button class="btn btn-primary rounded-3 flex-fill"><i class="fa fa-floppy-disk ms-1"></i> حفظ القادة</button></div>
                                </form>
                            </div>
                        </div>
                    @endif
                    <div class="{{ $canEdit ? 'col-lg-8' : 'col-12' }}">
                        <div class="ht-card">
                            <div class="ht-head">
                                <div class="ht-head-icon"><i class="fa fa-user-tie"></i></div>
                                <div><h5>قادة الفريق الحاليون</h5></div>
                            </div>
                            <div class="ht-body">
                                @if ($team->leaders->isEmpty())
                                    <div class="ht-empty"><i class="fa fa-user-tie"></i> لم يُعيّن قائد للفريق.</div>
                                @else
                                    <table class="ht-table">
                                        <thead><tr><th>اسم السائق</th><th>رقم الموظف</th><th>الهاتف</th></tr></thead>
                                        <tbody>
                                            @foreach ($team->leaders as $leader)
                                                <tr><td><strong>{{ $leader->name }}</strong></td><td>{{ $leader->employee_number }}</td><td dir="ltr" class="text-end">{{ $leader->phone }}</td></tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== مناطق الخدمة ===== --}}
            <div class="tab-pane" id="areas" role="tabpanel">
                <div class="row g-3">
                    @if ($canEdit)
                        <div class="col-lg-4">
                            <div class="ht-card">
                                <div class="ht-head">
                                    <div class="ht-head-icon"><i class="fa fa-map-pin"></i></div>
                                    <div><h5>تعيين مناطق</h5><p>اختر المدينة ثم أحياءها</p></div>
                                </div>
                                <form method="POST" action="{{ route('admin.hourly-teams.districts', $team) }}">
                                    @csrf
                                    <div class="ht-body">
                                        <div class="mb-3">
                                            <label for="district_city" class="form-label">1. المدينة</label>
                                            <select class="form-select" name="city_id" id="district_city" required>
                                                <option value="">اختر المدينة</option>
                                                @foreach ($cities as $city)
                                                    <option value="{{ $city->id }}">{{ $city->name_ar ?: $city->name_en }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <label for="district_ids" class="form-label">2. الأحياء / المناطق</label>
                                        <select class="form-select" id="district_ids" name="district_ids[]" multiple required data-placeholder="ابحث عن حي…" data-empty="اختر المدينة أولًا لعرض أحيائها."></select>
                                    </div>
                                    <div class="ht-foot"><button class="btn btn-primary rounded-3 flex-fill"><i class="fa fa-check ms-1"></i> تعيين المناطق</button></div>
                                </form>
                            </div>

                            <div class="ht-card">
                                <div class="ht-body">
                                    <details class="tm-details">
                                        <summary><i class="fa fa-plus ms-1"></i> حي غير موجود؟ أضفه إلى دليل المناطق</summary>
                                        <form method="POST" action="{{ route('admin.hourly-teams.district-create', $team) }}" class="mt-3">
                                            @csrf
                                            <div class="mb-2">
                                                <label for="new_city" class="form-label">المدينة</label>
                                                <select id="new_city" name="city_id" class="form-select" required>
                                                    @foreach ($cities as $city)
                                                        <option value="{{ $city->id }}">{{ $city->name_ar ?: $city->name_en }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="district_name" class="form-label">اسم الحي</label>
                                                <input id="district_name" name="name" class="form-control" maxlength="150" required>
                                            </div>
                                            <button class="btn btn-outline-primary rounded-3 w-100"><i class="fa fa-floppy-disk ms-1"></i> حفظ الحي</button>
                                        </form>
                                    </details>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="{{ $canEdit ? 'col-lg-8' : 'col-12' }}">
                        <div class="ht-card">
                            <div class="ht-head">
                                <div class="ht-head-icon"><i class="fa fa-map"></i></div>
                                <div><h5>مناطق خدمة الفريق</h5><p>نشطة {{ $activeDistricts }} من {{ $team->districts->count() }}</p></div>
                            </div>
                            <div class="ht-body">
                                @if ($team->districts->isEmpty())
                                    <div class="ht-empty"><i class="fa fa-map-location-dot"></i> لم تُعيّن مناطق خدمة لهذا الفريق.</div>
                                @else
                                    @if ($canEdit)
                                        <form method="POST" action="{{ route('admin.hourly-teams.district-action', $team) }}" class="tm-bulk">
                                            @csrf @method('PUT')
                                            <span class="small text-muted"><i class="fa fa-layer-group ms-1"></i> إجراء على جميع المناطق</span>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <button name="action" value="activate" class="btn btn-sm btn-outline-success rounded-3"><i class="fa fa-play ms-1"></i> تفعيل الكل</button>
                                                <button name="action" value="deactivate" class="btn btn-sm btn-outline-warning rounded-3"><i class="fa fa-pause ms-1"></i> إلغاء تفعيل الكل</button>
                                                <button name="action" value="remove" class="btn btn-sm btn-outline-danger rounded-3" data-confirm-title="حذف كل المناطق" data-confirm="إزالة جميع مناطق الخدمة من هذا الفريق؟" data-confirm-button="نعم، احذف الكل"><i class="fa fa-trash ms-1"></i> حذف الكل</button>
                                            </div>
                                        </form>
                                    @endif
                                    <div class="table-responsive">
                                        <table class="ht-table">
                                            <thead><tr><th>المدينة</th><th>المنطقة</th><th>الحالة</th>@if ($canEdit)<th>الإجراءات</th>@endif</tr></thead>
                                            <tbody>
                                                @foreach ($team->districts->sortBy(fn ($d) => ($d->city->name_ar ?? '').'|'.$d->name) as $district)
                                                    <tr>
                                                        <td class="text-muted">{{ $district->city->name_ar ?: $district->city->name_en }}</td>
                                                        <td><strong>{{ $district->name }}</strong></td>
                                                        <td>
                                                            @if ($canEdit)
                                                                <div class="tm-status">
                                                                    <button type="button" class="ht-toggle" role="switch" aria-checked="{{ $district->pivot->is_active ? 'true' : 'false' }}" aria-label="تغطية {{ $district->name }}"
                                                                            data-status-url="{{ route('admin.hourly-teams.district-action', $team) }}" data-district-id="{{ $district->id }}"><span><i class="fa {{ $district->pivot->is_active ? 'fa-check' : 'fa-minus' }}"></i></span></button>
                                                                    <span class="tm-status-text" data-on="نشطة" data-off="موقوفة">{{ $district->pivot->is_active ? 'نشطة' : 'موقوفة' }}</span>
                                                                </div>
                                                            @else
                                                                <span class="ht-chip {{ $district->pivot->is_active ? 'ht-chip-on' : 'ht-chip-off' }}">{{ $district->pivot->is_active ? 'نشطة' : 'غير نشطة' }}</span>
                                                            @endif
                                                        </td>
                                                        @if ($canEdit)
                                                            <td class="text-nowrap">
                                                                <form method="POST" action="{{ route('admin.hourly-teams.district-action', $team) }}" class="d-flex gap-1">
                                                                    @csrf @method('PUT')
                                                                    <input type="hidden" name="district_id" value="{{ $district->id }}">
                                                                    <button class="btn btn-sm btn-outline-danger ht-icon-btn" name="action" value="remove" title="إزالة"><i class="fa fa-xmark"></i></button>
                                                                </form>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
@include('admin.hourly._confirm-script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
@include('admin.hourly._map-provider')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lat = document.getElementById('base_lat'), lng = document.getElementById('base_lng');
    const locationTypes = document.querySelectorAll('input[name="base_location_type"]');
    const shelterSelect = document.getElementById('base_shelter_place_id');
    const shelterFields = document.getElementById('shelter-location-fields');
    const customFields = document.getElementById('custom-location-fields');
    const searchForm = document.getElementById('team-location-search');
    const mapHint = document.getElementById('team-location-map-hint');
    const currentLocationType = () => document.querySelector('input[name="base_location_type"]:checked')?.value || 'custom';
    if (typeof L !== 'undefined') {
        const hasPoint = lat.value !== '' && lng.value !== '';
        const map = L.map('team-location-map').setView(hasPoint ? [Number(lat.value), Number(lng.value)] : [24.7136, 46.6753], hasPoint ? 14 : 6);
        HourlyMaps.attach(map);
        let marker = null;
        function point(y, x) {
            if (marker) marker.setLatLng([y, x]);
            else {
                marker = L.marker([y, x], {draggable: @json($canEdit)}).addTo(map);
                marker.on('dragend', () => selectPoint(marker.getLatLng().lat, marker.getLatLng().lng));
            }
        }
        function selectPoint(y, x) { lat.value = Number(y).toFixed(7); lng.value = Number(x).toFixed(7); point(y, x); }
        if (hasPoint) point(Number(lat.value), Number(lng.value));
        if (@json($canEdit)) map.on('click', e => {
            if (currentLocationType() === 'custom') selectPoint(e.latlng.lat, e.latlng.lng);
        });
        if (searchForm) {
            let sequence = 0;
            const query = document.getElementById('team-location-query'), results = document.getElementById('team-location-results');
            query.addEventListener('input', () => { sequence++; results.replaceChildren(); });
            searchForm.addEventListener('submit', async e => {
                e.preventDefault();
                const current = ++sequence;
                results.textContent = 'جارٍ البحث…';
                try {
                    const response = await fetch(@json(route('admin.hourly-teams.locate', $team)), {
                        method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())},
                        body: JSON.stringify({name: query.value.trim()})
                    });
                    const data = await response.json();
                    if (current !== sequence) return;
                    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || 'تعذر البحث الآن. أعد المحاولة بعد قليل.');
                    results.replaceChildren();
                    (data.results || []).forEach(item => {
                        const button = document.createElement('button');
                        button.type = 'button'; button.className = 'list-group-item list-group-item-action text-end'; button.textContent = item.label;
                        button.addEventListener('click', () => {
                            selectPoint(item.latitude, item.longitude); map.setView([item.latitude, item.longitude], 16);
                            query.value = item.label; results.replaceChildren();
                        });
                        results.append(button);
                    });
                    if (!results.children.length) results.textContent = 'لا توجد نتائج. جرّب كتابة اسم المدينة مع العنوان.';
                } catch (error) { if (current === sequence) results.textContent = error.message; }
            });
        }
        [lat, lng].forEach(input => input.addEventListener('change', function () {
            if (lat.value !== '' && lng.value !== '' && lat.checkValidity() && lng.checkValidity()) { point(Number(lat.value), Number(lng.value)); map.setView([Number(lat.value), Number(lng.value)], 14); }
        }));
        function syncLocationMode(moveToShelter = false) {
            const isShelter = currentLocationType() === 'shelter';
            shelterFields.hidden = !isShelter;
            customFields.hidden = isShelter;
            shelterSelect.required = isShelter;
            lat.required = !isShelter;
            lng.required = !isShelter;
            if (searchForm) searchForm.hidden = isShelter;
            if (mapHint) mapHint.textContent = isShelter
                ? 'تُعرض إحداثيات مقر الإيواء المختار، ولا يمكن تحريكها من هنا.'
                : 'اختر نتيجة البحث، أو اضغط على الخريطة واسحب العلامة لضبط الموقع، ثم اضغط حفظ الموقع.';
            if (marker?.dragging) isShelter ? marker.dragging.disable() : marker.dragging.enable();

            if (isShelter && (moveToShelter || !hasPoint)) {
                const option = shelterSelect.selectedOptions[0];
                if (option?.dataset.lat && option?.dataset.lng) {
                    selectPoint(option.dataset.lat, option.dataset.lng);
                    map.setView([Number(option.dataset.lat), Number(option.dataset.lng)], 16);
                    if (marker?.dragging) marker.dragging.disable();
                }
            }
        }
        locationTypes.forEach(input => input.addEventListener('change', () => syncLocationMode(true)));
        shelterSelect.addEventListener('change', () => syncLocationMode(true));
        syncLocationMode();
        document.querySelector('[data-bs-target="#location"]').addEventListener('shown.bs.tab', () => map.invalidateSize());
    }
    function openTab() {
        const allowed = ['location', 'drivers', 'maids', 'leaders', 'areas'];
        const tab = location.hash.slice(1);
        if (allowed.includes(tab) && window.bootstrap) bootstrap.Tab.getOrCreateInstance(document.querySelector('[data-bs-target="#' + tab + '"]')).show();
    }
    openTab();
    window.addEventListener('hashchange', openTab);
});
</script>
@php($teamDistrictData = $districts->map(fn ($d) => ['id' => $d->id, 'city_id' => $d->city_id, 'name' => $d->name])->values())
<script id="team-district-data" type="application/json">@json($teamDistrictData)</script>
<script src="{{ asset('admin/assets/js/hourly-team-manage.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-team-manage.js')) }}"></script>
@endpush
