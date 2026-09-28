@extends('admin.layout.indexs.index')

@section('page-title', 'إدارة سائقي الساعات')

@section('style')
    @include('admin.hourly_teams._style')
    @include('admin.hourly_drivers._style')
@endsection

@section('content')
    @php
        $accessLabels = ['web' => 'الموقع', 'mobile' => 'الجوال', 'both' => 'الموقع والجوال'];
        $accessIcons = ['web' => 'fa-globe', 'mobile' => 'fa-mobile-screen', 'both' => 'fa-laptop-code'];
        $docLabels = \App\Models\Hourly\HourlyDriver::DATES;
        $dayShort = ['sunday' => 'ح', 'monday' => 'ن', 'tuesday' => 'ث', 'wednesday' => 'ر', 'thursday' => 'خ', 'friday' => 'ج', 'saturday' => 'س'];
        $modeLabels = ['off' => 'إجازة', 'hours' => 'ساعات محددة', 'all_day' => 'طوال اليوم'];
        $canEdit = AdminGuardHasPerm('hourly_drivers_edit');
        $canDelete = AdminGuardHasPerm('hourly_drivers_delete');
        $canImpersonate = AdminGuardHasPerm('hourly_drivers_impersonate');
    @endphp
    <div class="container-fluid ht-page" dir="rtl">
        <div id="driver-status-message" class="alert d-none" role="status" aria-live="polite"></div>
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3"><i class="fa fa-circle-check ms-1"></i> {{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3"><i class="fa fa-circle-xmark ms-1"></i> {{ $errors->first() }}</div>
        @endif

        {{-- ملخص --}}
        <div class="hd-stats">
            <div class="hd-stat"><div class="hd-stat-icon"><i class="fa fa-id-card"></i></div><div><span>إجمالي السائقين</span><strong class="ht-num">{{ $total }}</strong></div></div>
            <div class="hd-stat hd-stat-on"><div class="hd-stat-icon"><i class="fa fa-circle-check"></i></div><div><span>نشط</span><strong class="ht-num">{{ $active }}</strong></div></div>
            <div class="hd-stat hd-stat-off"><div class="hd-stat-icon"><i class="fa fa-circle-pause"></i></div><div><span>متوقف</span><strong class="ht-num">{{ $total - $active }}</strong></div></div>
            <div class="hd-stat hd-stat-info"><div class="hd-stat-icon"><i class="fa fa-right-to-bracket"></i></div><div><span>يمكنهم تسجيل الدخول</span><strong class="ht-num">{{ $canLogin }}</strong></div></div>
        </div>

        <div class="ht-card">
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa fa-car-side"></i></div>
                <div>
                    <h5>سائقو التأجير بالساعة</h5>
                    <p>بيانات السائق ووثائقه، وجدول عمله الأسبوعي، وصلاحية دخوله لتطبيق السائقين</p>
                </div>
                <div class="ht-actions">
                    @if (AdminGuardHasPerm('hourly_drivers_create'))
                        <a class="btn btn-primary rounded-3" href="{{ route('admin.hourly-drivers.create') }}"><i class="fa fa-plus ms-1"></i> إضافة سائق جديد</a>
                    @endif
                </div>
            </div>
            <div class="ht-body">
                <nav class="nav ht-tabs d-inline-flex mb-3">
                    <a class="nav-link {{ empty($filters['deleted']) ? 'active' : '' }}" href="{{ route('admin.hourly-drivers.index') }}"><i class="fa fa-id-card"></i> السائقون <span class="badge rounded-pill ht-num">{{ $total }}</span></a>
                    <a class="nav-link {{ !empty($filters['deleted']) ? 'active' : '' }}" href="{{ route('admin.hourly-drivers.index', ['deleted' => 1]) }}"><i class="fa fa-trash-can"></i> المحذوفون <span class="badge rounded-pill ht-num">{{ $deletedCount }}</span></a>
                </nav>
                @php
                    $activeFilters = count(array_filter(\Illuminate\Support\Arr::except($filters, 'deleted'), fn ($v) => $v !== null && $v !== ''));
                    $selects = [
                        'team_id' => ['الفريق', 'fa-people-group', $teams->pluck('name', 'id')->all()],
                        'nationality_id' => ['الجنسية', 'fa-flag', $nationalities->pluck('sub_title', 'id')->all()],
                        'gender' => ['الجنس', 'fa-venus-mars', ['male' => 'ذكر', 'female' => 'أنثى']],
                        'status' => ['الحالة', 'fa-toggle-on', ['active' => 'نشط', 'inactive' => 'متوقف']],
                        'account' => ['حساب الدخول', 'fa-key', ['both' => 'الموقع والجوال', 'web' => 'الموقع فقط', 'mobile' => 'الجوال فقط', 'disabled' => 'معطّل']],
                        'documents' => ['الوثائق', 'fa-folder-open', ['expiring' => 'منتهية أو تنتهي خلال ٣٠ يومًا', 'expired' => 'منتهية فقط']],
                    ];
                @endphp
                <form method="GET" action="{{ route('admin.hourly-drivers.index') }}" class="hd-filter mb-4">
                    @if (!empty($filters['deleted']))<input type="hidden" name="deleted" value="1">@endif
                    <div class="hd-filter-head">
                        <div class="hd-filter-title">
                            <i class="fa fa-sliders"></i> فلتر قائمة السائقين
                            @if ($activeFilters)<span class="hd-filter-count">{{ $activeFilters }} فلتر مفعّل</span>@endif
                        </div>
                        <div class="hd-filter-search">
                            <i class="fa fa-magnifying-glass"></i>
                            <input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ابحث بالاسم أو الهاتف أو رقم الموظف" aria-label="بحث">
                        </div>
                    </div>
                    <div class="hd-filter-body hd-filter-body-compact">
                        @foreach ($selects as $name => [$label, $icon, $options])
                            @php $current = (string) ($filters[$name] ?? ''); @endphp
                            <div class="hd-filter-field">
                                <label for="f_{{ $name }}"><i class="fa {{ $icon }}"></i> {{ $label }}</label>
                                <select id="f_{{ $name }}" name="{{ $name }}" class="form-select {{ $current !== '' ? 'is-set' : '' }}" onchange="this.form.submit()">
                                    <option value="">الكل</option>
                                    @foreach ($options as $value => $text)
                                        <option value="{{ $value }}" {{ $current === (string) $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                    <div class="hd-filter-foot">
                        <span class="text-muted small">النتائج: <strong class="ht-num">{{ $drivers->total() }}</strong> سائق</span>
                        <div class="d-flex gap-2">
                            @if ($activeFilters)
                                <a class="btn btn-light rounded-3" href="{{ route('admin.hourly-drivers.index', !empty($filters['deleted']) ? ['deleted' => 1] : []) }}"><i class="fa fa-rotate-left ms-1"></i> مسح الفلاتر</a>
                            @endif
                            <button class="btn btn-primary rounded-3 px-4" type="submit"><i class="fa fa-filter ms-1"></i> تطبيق</button>
                        </div>
                    </div>
                </form>

                @if ($drivers->isEmpty())
                    <div class="ht-note ht-note-info">
                        <i class="fa fa-circle-info mt-1"></i>
                        <div>{{ array_filter($filters) ? 'لا يوجد سائقون مطابقون لشروط التصفية.' : 'لا يوجد سائقون بعد. أضف أول سائق وحدد جدول عمله وبيانات دخوله.' }}</div>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="ht-table hd-table">
                            <thead>
                                <tr>
                                    <th>السائق</th>
                                    <th>التواصل</th>
                                    <th>الفرق</th>
                                    <th>أيام العمل</th>
                                    <th>الوثائق</th>
                                    <th>الحساب</th>
                                    <th>الحالة والإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($drivers as $driver)
                                    @php
                                        $workDays = $driver->workingDays();
                                        $expiring = $driver->expiringDocuments();
                                    @endphp
                                    <tr class="{{ $driver->is_active ? '' : 'hd-row-off' }}">
                                        <td>
                                            <div class="d-flex align-items-center gap-2 text-nowrap">
                                                <div class="hd-avatar">{{ mb_substr($driver->name, 0, 1) }}</div>
                                                <div>
                                                    <strong class="d-block">{{ $driver->name }}</strong>
                                                    <span class="small text-muted"># <span class="ht-num">{{ $driver->employee_number }}</span></span>
                                                    <span class="small text-muted ms-2" title="سعة السيارة"><i class="fa fa-car-side"></i> <span class="ht-num">{{ $driver->vehicle_capacity }}</span> ركاب</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="ht-num text-nowrap">
                                            <a href="tel:{{ $driver->phone }}" class="hd-phone" dir="ltr"><i class="fa fa-phone"></i> {{ $driver->phone }}</a>
                                            @if ($driver->phone2)<div class="small text-muted mt-1" dir="ltr">{{ $driver->phone2 }}</div>@endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column align-items-start gap-1">
                                                @forelse($driver->teamMemberships->filter(fn($m) => $m->team !== null) as $membership)
                                                    <span class="ht-chip {{ $membership->is_active ? ($membership->role === 'primary' ? 'hd-chip-primary' : 'ht-chip-off') : 'hd-chip-warn' }}" title="{{ $membership->role === 'primary' ? 'فريق أساسي' : 'فريق ثانوي' }}{{ $membership->is_active ? '' : ' — عضوية متوقفة' }}">
                                                        <i class="fa {{ $membership->role === 'primary' ? 'fa-star' : 'fa-people-group' }}"></i> {{ $membership->team->name }}
                                                    </span>
                                                @empty<span class="text-muted small">بدون فريق</span>@endforelse
                                            </div>
                                        </td>
                                        <td>
                                            <div class="hd-week">
                                                @foreach (\App\Models\Hourly\HourlyDriver::DAYS as $day => $label)
                                                    @php($mode = $driver->work_schedule[$day]['mode'] ?? 'off')
                                                    @php($slot = $driver->work_schedule[$day] ?? [])
                                                    <span class="hd-day hd-day-{{ $mode }}" title="{{ $label }}: {{ $mode === 'hours' ? ($slot['start'] ?? '').' – '.($slot['end'] ?? '') : ($modeLabels[$mode] ?? '') }}">{{ $dayShort[$day] }}</span>
                                                @endforeach
                                            </div>
                                            <div class="small text-muted mt-1">{{ count($workDays) }} من ٧ أيام</div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column align-items-start gap-1">
                                                @forelse ($expiring as $field => $left)
                                                    <span class="ht-chip {{ $left < 0 ? 'hd-chip-danger' : 'hd-chip-warn' }}" title="{{ $docLabels[$field] }}">
                                                        <i class="fa {{ $left < 0 ? 'fa-circle-exclamation' : 'fa-hourglass-half' }}"></i>
                                                        {{ str_replace(['تاريخ انتهاء ', 'تاريخ نهاية '], '', $docLabels[$field]) }}
                                                        · {{ $left < 0 ? 'منتهية' : ($left === 0 ? 'اليوم' : $left.' يوم') }}
                                                    </span>
                                                @empty
                                                    <span class="ht-chip ht-chip-on"><i class="fa fa-check"></i> سارية</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            @if ($driver->account_enabled)
                                                <span class="ht-chip hd-chip-info"><i class="fa {{ $accessIcons[$driver->login_access] ?? 'fa-key' }}"></i> {{ $accessLabels[$driver->login_access] ?? $driver->login_access }}</span>
                                            @else
                                                <span class="ht-chip ht-chip-off"><i class="fa fa-lock"></i> معطّل</span>
                                            @endif
                                            <div class="small text-muted mt-1 ht-num" dir="ltr">{{ $driver->username }}</div>
                                        </td>
                                        <td class="text-nowrap">
                                            <div class="d-inline-flex align-items-center gap-2">
                                            @if ($driver->trashed())
                                                <span class="ht-chip ht-chip-off">محذوف</span>
                                            @elseif ($canEdit)
                                                <button type="button" class="ht-toggle" role="switch"
                                                    aria-checked="{{ $driver->is_active ? 'true' : 'false' }}"
                                                    aria-label="حالة السائق {{ $driver->name }}"
                                                    data-status-url="{{ route('admin.hourly-drivers.status', $driver) }}"
                                                    title="{{ $driver->is_active ? 'اضغط لإيقاف السائق' : 'اضغط لتفعيل السائق' }}">
                                                    <span><i class="fa {{ $driver->is_active ? 'fa-check' : 'fa-minus' }}" aria-hidden="true"></i></span>
                                                </button>
                                            @else
                                                <span class="ht-chip {{ $driver->is_active ? 'ht-chip-on' : 'ht-chip-off' }}">{{ $driver->is_active ? 'نشط' : 'متوقف' }}</span>
                                            @endif
                                            <div class="d-inline-flex gap-1">
                                            @if ($driver->trashed())
                                                @if ($canDelete)
                                                    <form method="POST" action="{{ route('admin.hourly-drivers.restore', $driver->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-3"><i class="fa fa-undo ms-1" aria-hidden="true"></i> استرجاع</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.hourly-drivers.force-destroy', $driver->id) }}"
                                                          data-confirm-title="حذف نهائي" data-confirm="سيتم حذف بيانات السائق {{ $driver->name }} نهائيًا ولن يمكن استرجاعها." data-confirm-button="نعم، احذف نهائيًا">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger ht-icon-btn" title="حذف نهائي" aria-label="حذف نهائي"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                                    </form>
                                                @endif
                                            @else
                                                @if ($canImpersonate)
                                                    @if ($driver->canLoginVia('web'))
                                                        <form method="POST" action="{{ route('admin.hourly-drivers.login-as', $driver) }}" target="_blank">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary ht-icon-btn" title="دخول كسائق — يفتح بوابة السائق في تبويب جديد" aria-label="دخول كسائق"><i class="fa fa-right-to-bracket" aria-hidden="true"></i></button>
                                                        </form>
                                                    @else
                                                        <span class="d-inline-block" tabindex="0" title="الحساب متوقف أو معطّل أو لا يسمح بالدخول من الموقع">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary ht-icon-btn" disabled aria-label="دخول كسائق"><i class="fa fa-right-to-bracket" aria-hidden="true"></i></button>
                                                        </span>
                                                    @endif
                                                @endif
                                                @if ($canEdit)
                                                    <a href="{{ route('admin.hourly-drivers.edit', $driver) }}" class="btn btn-sm btn-outline-primary ht-icon-btn" title="تعديل" aria-label="تعديل"><i class="fa fa-pen"></i></a>
                                                @endif
                                                @if ($canDelete)
                                                    <form method="POST" action="{{ route('admin.hourly-drivers.destroy', $driver) }}"
                                                          data-confirm-title="حذف السائق" data-confirm="نقل {{ $driver->name }} إلى المحذوفين ومنع دخوله؟ يمكنك استرجاعه لاحقًا." data-confirm-button="نعم، احذف">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger ht-icon-btn" title="حذف" aria-label="حذف"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                                    </form>
                                                @endif
                                            @endif
                                            </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $drivers->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('js')
@include('admin.hourly._confirm-script')
<script>
document.querySelectorAll('[data-status-url]').forEach(function (button) {
    button.addEventListener('click', async function () {
        const message = document.getElementById('driver-status-message');
        const active = button.getAttribute('aria-checked') !== 'true';
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(button.dataset.statusUrl, {
                method: 'PUT', credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())},
                body: JSON.stringify({is_active: active})
            });
            if (!response.ok) throw new Error('تعذر تحديث الحالة. تحقق من الاتصال والصلاحيات ثم حاول مجددًا.');
            const data = await response.json();
            if (typeof data.is_active !== 'boolean') throw new Error('انتهت الجلسة، يرجى تسجيل الدخول مجددًا.');
            button.setAttribute('aria-checked', String(data.is_active));
            button.title = data.is_active ? 'اضغط لإيقاف السائق' : 'اضغط لتفعيل السائق';
            button.querySelector('i').className = 'fa ' + (data.is_active ? 'fa-check' : 'fa-minus');
            button.closest('tr').classList.toggle('hd-row-off', !data.is_active);
            document.querySelector('.hd-stat-on strong').textContent = data.counts.active;
            document.querySelector('.hd-stat-off strong').textContent = data.counts.inactive;
            document.querySelector('.hd-stat-info strong').textContent = data.counts.canLogin;
            message.className = 'alert alert-success';
            message.textContent = data.message;
        } catch (error) {
            message.className = 'alert alert-danger';
            message.textContent = error.message;
        } finally {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    });
});
</script>
@endpush
