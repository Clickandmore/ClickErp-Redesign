@extends('admin.layout.indexs.index')

@section('page-title', $driver->exists ? 'تعديل السائق — '.$driver->name : 'إضافة سائق جديد')

@section('style')
    @include('admin.hourly_teams._style')
    @include('admin.hourly_drivers._style')
@endsection

@section('content')
    @php
        $expiring = $driver->exists ? $driver->expiringDocuments() : [];
        $dates = \App\Models\Hourly\HourlyDriver::DATES;
        $isActive = (bool) old('is_active', $driver->is_active);
        $accountEnabled = (bool) old('account_enabled', $driver->account_enabled);
    @endphp
    <div class="container-fluid ht-page" dir="rtl">
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3"><i class="fa fa-circle-check ms-1"></i> {{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3">
                <div class="fw-bold mb-1"><i class="fa fa-circle-xmark ms-1"></i> تعذّر الحفظ، راجع الحقول التالية:</div>
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- ترويسة الصفحة --}}
        <div class="ht-card">
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa {{ $driver->exists ? 'fa-user-pen' : 'fa-user-plus' }}"></i></div>
                <div>
                    <h5>{{ $driver->exists ? $driver->name : 'إضافة سائق جديد' }}</h5>
                    <p>
                        @if ($driver->exists)
                            رقم الموظف <span class="ht-num" dir="ltr">{{ $driver->employee_number }}</span> · اسم المستخدم <span class="ht-num" dir="ltr">{{ $driver->username }}</span>
                        @else
                            أدخل بيانات السائق ووثائقه وجدول عمله، ثم حدد طريقة دخوله. يُنشأ اسم المستخدم تلقائيًا عند الحفظ.
                        @endif
                    </p>
                </div>
                <div class="ht-actions">
                    @if ($driver->exists)
                        <span class="ht-chip {{ $driver->is_active ? 'ht-chip-on' : 'ht-chip-off' }}"><i class="fa {{ $driver->is_active ? 'fa-circle-check' : 'fa-circle-pause' }}"></i> {{ $driver->is_active ? 'نشط' : 'متوقف' }}</span>
                        @if (count($expiring))
                            <span class="ht-chip hd-chip-warn"><i class="fa fa-triangle-exclamation"></i> {{ count($expiring) }} وثيقة تحتاج متابعة</span>
                        @endif
                    @endif
                    <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-drivers.index') }}"><i class="fa fa-arrow-right ms-1"></i> القائمة</a>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ $driver->exists ? route('admin.hourly-drivers.update', $driver) : route('admin.hourly-drivers.store') }}">
            @csrf
            @if ($driver->exists) @method('PUT') @endif

            <div class="row g-3">
                {{-- ===== العمود الرئيسي: من هو السائق ومتى يعمل ===== --}}
                <div class="col-xl-8">

                    {{-- 1. الهوية والتواصل --}}
                    <div class="ht-card">
                        <div class="ht-head">
                            <div class="ht-head-icon"><i class="fa fa-id-card"></i></div>
                            <div><h5>البيانات الشخصية</h5><p>الهوية الأساسية للسائق ووسائل التواصل معه</p></div>
                        </div>
                        <div class="ht-body">
                            <div class="hd-section-title">الهوية</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">@include('admin.hourly_drivers._field', ['name' => 'name', 'label' => 'الاسم الكامل', 'required' => true, 'attrs' => ['maxlength' => 150]])</div>
                                <div class="col-md-6">@include('admin.hourly_drivers._field', ['name' => 'employee_number', 'label' => 'رقم الموظف', 'required' => true, 'attrs' => ['maxlength' => 50, 'dir' => 'ltr']])</div>
                                <div class="col-md-4">
                                    <label class="form-label" for="gender">الجنس <span class="text-danger">*</span></label>
                                    <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                        @foreach (['male' => 'ذكر', 'female' => 'أنثى'] as $value => $label)
                                            <option value="{{ $value }}" {{ old('gender', $driver->gender) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="nationality_id">الجنسية</label>
                                    <select name="nationality_id" id="nationality_id" class="form-select @error('nationality_id') is-invalid @enderror">
                                        <option value="">— اختر —</option>
                                        @foreach ($nationalities as $nationality)
                                            <option value="{{ $nationality->id }}" {{ (string) old('nationality_id', $driver->nationality_id) === (string) $nationality->id ? 'selected' : '' }}>{{ $nationality->sub_title }}</option>
                                        @endforeach
                                    </select>
                                    @error('nationality_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">@include('admin.hourly_drivers._field', ['name' => 'birth_date', 'label' => $dates['birth_date'], 'type' => 'date', 'attrs' => ['max' => now()->subDay()->toDateString()]])</div>
                            </div>

                            <div class="hd-section-title">التواصل</div>
                            <div class="row g-3">
                                <div class="col-md-6">@include('admin.hourly_drivers._field', ['name' => 'phone', 'label' => 'الهاتف', 'type' => 'tel', 'required' => true, 'attrs' => ['maxlength' => 30, 'dir' => 'ltr']])</div>
                                <div class="col-md-6">@include('admin.hourly_drivers._field', ['name' => 'phone2', 'label' => 'الهاتف الثاني', 'type' => 'tel', 'attrs' => ['maxlength' => 30, 'dir' => 'ltr']])</div>
                                <div class="col-12">@include('admin.hourly_drivers._field', ['name' => 'address', 'label' => 'العنوان', 'attrs' => ['maxlength' => 500]])</div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. الوثائق الرسمية: كل رقم بجوار تاريخه --}}
                    <div class="ht-card">
                        <div class="ht-head">
                            <div class="ht-head-icon"><i class="fa fa-folder-open"></i></div>
                            <div><h5>الوثائق الرسمية</h5><p>كل وثيقة برقمها وتاريخها؛ يُنبَّه على ما انتهى أو ينتهي خلال ٣٠ يومًا</p></div>
                        </div>
                        <div class="ht-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="hd-doc">
                                        <h6><i class="fa fa-address-card"></i> الإقامة</h6>
                                        <div class="mb-2">@include('admin.hourly_drivers._field', ['name' => 'residency_number', 'label' => 'رقم الإقامة', 'attrs' => ['maxlength' => 50, 'dir' => 'ltr']])</div>
                                        @include('admin.hourly_drivers._field', ['name' => 'residency_expiry_date', 'label' => 'تاريخ الانتهاء', 'type' => 'date'])
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="hd-doc">
                                        <h6><i class="fa fa-passport"></i> جواز السفر</h6>
                                        <div class="mb-2">@include('admin.hourly_drivers._field', ['name' => 'passport_number', 'label' => 'رقم الجواز', 'attrs' => ['maxlength' => 50, 'dir' => 'ltr']])</div>
                                        @include('admin.hourly_drivers._field', ['name' => 'passport_expiry_date', 'label' => 'تاريخ الانتهاء', 'type' => 'date'])
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="hd-doc">
                                        <h6><i class="fa fa-plane-arrival"></i> الدخول للمملكة</h6>
                                        <div class="mb-2">@include('admin.hourly_drivers._field', ['name' => 'border_number', 'label' => 'رقم الحدود', 'attrs' => ['maxlength' => 50, 'dir' => 'ltr']])</div>
                                        @include('admin.hourly_drivers._field', ['name' => 'entry_date', 'label' => $dates['entry_date'], 'type' => 'date'])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. التعاقد: تسلسل زمني من بدء العمل حتى نهاية العقد --}}
                    <div class="ht-card">
                        <div class="ht-head">
                            <div class="ht-head-icon"><i class="fa fa-file-signature"></i></div>
                            <div><h5>العقد والتأمين</h5><p>مدة العقد وتواريخ التأمين والاستبدال</p></div>
                        </div>
                        <div class="ht-body">
                            <div class="hd-section-title">فترة العمل</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">@include('admin.hourly_drivers._field', ['name' => 'work_start_date', 'label' => $dates['work_start_date'], 'type' => 'date'])</div>
                                <div class="col-md-4">@include('admin.hourly_drivers._field', ['name' => 'contract_start_date', 'label' => $dates['contract_start_date'], 'type' => 'date'])</div>
                                <div class="col-md-4">@include('admin.hourly_drivers._field', ['name' => 'contract_end_date', 'label' => $dates['contract_end_date'], 'type' => 'date', 'help' => 'لا تسبق بداية العقد.'])</div>
                            </div>
                            <div class="hd-section-title">صلاحيات تنتهي</div>
                            <div class="row g-3">
                                <div class="col-md-6">@include('admin.hourly_drivers._field', ['name' => 'insurance_expiry_date', 'label' => $dates['insurance_expiry_date'], 'type' => 'date'])</div>
                                <div class="col-md-6">@include('admin.hourly_drivers._field', ['name' => 'replacement_expiry_date', 'label' => $dates['replacement_expiry_date'], 'type' => 'date'])</div>
                            </div>
                        </div>
                    </div>

                    {{-- 4. جدول العمل --}}
                    <div class="ht-card">
                        <div class="ht-head">
                            <div class="ht-head-icon"><i class="fa fa-calendar-week"></i></div>
                            <div><h5>جدول العمل الأسبوعي</h5><p>لكل يوم: إجازة، أو ساعات محددة (النهاية بعد البداية في اليوم نفسه)، أو طوال اليوم بلا قيود</p></div>
                            <div class="ht-actions">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" data-copy-first-day><i class="fa fa-copy ms-1"></i> تطبيق الأحد على كل الأيام</button>
                            </div>
                        </div>
                        <div class="ht-body">
                            @error('work_schedule')<div class="ht-note ht-note-warn mb-3"><i class="fa fa-triangle-exclamation mt-1"></i><div>{{ $message }}</div></div>@enderror
                            <div class="table-responsive">
                                <table class="ht-table hd-schedule">
                                    <thead><tr><th style="width: 110px">اليوم</th><th>نظام العمل</th><th>الوقت</th></tr></thead>
                                    <tbody>
                                        @foreach (\App\Models\Hourly\HourlyDriver::DAYS as $day => $label)
                                            @php
                                                $schedule = old('work_schedule.'.$day, $driver->work_schedule[$day] ?? ['mode' => 'off', 'start' => null, 'end' => null]);
                                                $mode = $schedule['mode'] ?? 'off';
                                            @endphp
                                            <tr data-schedule-row>
                                                <th>{{ $label }}</th>
                                                <td>
                                                    <div class="btn-group" role="group" aria-label="نظام العمل يوم {{ $label }}">
                                                        @foreach (['hours' => ['ساعات محددة', 'fa-clock', 'primary'], 'all_day' => ['طوال اليوم', 'fa-sun', 'success'], 'off' => ['إجازة', 'fa-bed', 'secondary']] as $value => [$text, $icon, $color])
                                                            <input type="radio" class="btn-check" name="work_schedule[{{ $day }}][mode]" id="mode_{{ $day }}_{{ $value }}" value="{{ $value }}" data-schedule-mode {{ $mode === $value ? 'checked' : '' }}>
                                                            <label class="btn btn-outline-{{ $color }}" for="mode_{{ $day }}_{{ $value }}"><i class="fa {{ $icon }} ms-1"></i>{{ $text }}</label>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="hd-times" data-times>
                                                        <input type="time" class="form-control form-control-sm @error('work_schedule.'.$day.'.start') is-invalid @enderror" name="work_schedule[{{ $day }}][start]" value="{{ $schedule['start'] ?? '' }}" aria-label="بداية العمل يوم {{ $label }}" data-schedule-time>
                                                        <span class="text-muted">إلى</span>
                                                        <input type="time" class="form-control form-control-sm @error('work_schedule.'.$day.'.end') is-invalid @enderror" name="work_schedule[{{ $day }}][end]" value="{{ $schedule['end'] ?? '' }}" aria-label="نهاية العمل يوم {{ $label }}" data-schedule-time>
                                                    </div>
                                                    <div class="hd-times-note" data-times-note></div>
                                                    @error('work_schedule.'.$day.'.start')<div class="small text-danger">{{ $message }}</div>@enderror
                                                    @error('work_schedule.'.$day.'.end')<div class="small text-danger">{{ $message }}</div>@enderror
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- 5. ملاحظات --}}
                    <div class="ht-card">
                        <div class="ht-head">
                            <div class="ht-head-icon"><i class="fa fa-note-sticky"></i></div>
                            <div><h5>ملاحظات</h5></div>
                        </div>
                        <div class="ht-body">
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3" maxlength="5000" aria-label="ملاحظات">{{ old('notes', $driver->notes) }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- ===== العمود الجانبي: التشغيل والدخول ===== --}}
                <div class="col-xl-4">
                    <div class="hd-sticky">
                        {{-- التشغيل --}}
                        <div class="ht-card">
                            <div class="ht-head">
                                <div class="ht-head-icon"><i class="fa fa-car-side"></i></div>
                                <div><h5>التشغيل</h5><p>هل يُسند للسائق عمل، وكم راكبًا تتسع سيارته</p></div>
                            </div>
                            <div class="ht-body">
                                <div class="ht-switch form-switch mb-3">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ $isActive ? 'checked' : '' }}>
                                    <div>
                                        <label for="is_active">السائق نشط</label>
                                        <small>إيقافه يمنع دخوله فورًا حتى لو كان حسابه مفعّلًا.</small>
                                    </div>
                                </div>
                                @include('admin.hourly_drivers._field', ['name' => 'vehicle_capacity', 'label' => 'سعة السيارة (ركاب)', 'type' => 'number', 'required' => true, 'attrs' => ['min' => 3, 'max' => 50, 'step' => 1], 'help' => 'من ٣ إلى ٥٠ راكبًا.'])
                                @include('admin.hourly_drivers._teams')
                            </div>
                        </div>

                        {{-- حساب الدخول --}}
                        <div class="ht-card">
                            <div class="ht-head">
                                <div class="ht-head-icon"><i class="fa fa-key"></i></div>
                                <div><h5>حساب الدخول</h5><p>بيانات دخول السائق لبوابة وتطبيق السائقين</p></div>
                            </div>
                            <div class="ht-body">
                                <div class="ht-switch form-switch mb-3">
                                    <input type="hidden" name="account_enabled" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="account_enabled" name="account_enabled" value="1" {{ $accountEnabled ? 'checked' : '' }}>
                                    <div>
                                        <label for="account_enabled">الحساب مفعّل</label>
                                        <small>عند التعطيل تُحفظ البيانات ولا يستطيع السائق الدخول.</small>
                                    </div>
                                </div>

                                <div data-account-fields class="{{ $accountEnabled ? '' : 'hd-dim' }}">
                                    <div class="mb-3">
                                        <label class="form-label" for="login_access">السماح بالدخول من</label>
                                        <select name="login_access" id="login_access" class="form-select @error('login_access') is-invalid @enderror">
                                            @foreach (['both' => 'الموقع والجوال', 'web' => 'الموقع الإلكتروني فقط', 'mobile' => 'تطبيق الجوال فقط'] as $value => $label)
                                                <option value="{{ $value }}" {{ old('login_access', $driver->login_access) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('login_access')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="hd-section-title">معرّفات الدخول</div>
                                    <div class="mb-3">
                                        <label class="form-label" for="username">اسم المستخدم</label>
                                        <input id="username" class="form-control bg-light" readonly value="{{ $driver->username }}" placeholder="يُنشأ تلقائيًا عند الحفظ" dir="ltr">
                                    </div>
                                    <div class="mb-3">@include('admin.hourly_drivers._field', ['name' => 'login_phone', 'label' => 'هاتف تسجيل الدخول', 'attrs' => ['inputmode' => 'numeric', 'pattern' => '[0-9]{5,20}', 'maxlength' => 20, 'dir' => 'ltr'], 'help' => 'اختياري، أرقام فقط؛ بديل عن اسم المستخدم.'])</div>
                                    <div class="mb-3">@include('admin.hourly_drivers._field', ['name' => 'email', 'label' => 'البريد الإلكتروني', 'type' => 'email', 'attrs' => ['maxlength' => 255, 'dir' => 'ltr']])</div>

                                    <div class="hd-section-title">كلمة المرور</div>
                                    <div class="mb-3">
                                        <label class="form-label" for="password">{{ $driver->password ? 'كلمة مرور جديدة' : 'كلمة المرور' }}</label>
                                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="8" maxlength="72" dir="ltr">
                                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        <div class="form-text">٨ أحرف على الأقل. {{ $driver->password ? 'اتركها فارغة للاحتفاظ بالحالية.' : 'مطلوبة عند تفعيل الحساب.' }}</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="password_confirmation">تأكيد كلمة المرور</label>
                                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password" maxlength="72" dir="ltr">
                                    </div>
                                </div>

                                <div class="ht-note ht-note-info">
                                    <i class="fa fa-circle-info mt-1"></i>
                                    <div>تغيير الحالة أو بيانات الدخول يُنهي جلسات السائق الحالية. <a href="{{ route('hourly-driver.login') }}" target="_blank" rel="noopener">صفحة دخول السائقين <i class="fa fa-arrow-up-right-from-square"></i></a></div>
                                </div>
                            </div>
                        </div>

                        <div class="ht-card hd-save">
                            <div class="ht-foot">
                                <button class="btn btn-primary rounded-3 flex-fill" type="submit"><i class="fa fa-floppy-disk ms-1"></i> {{ $driver->exists ? 'حفظ التعديلات' : 'إنشاء السائق' }}</button>
                                <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-drivers.index') }}">إلغاء</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
<script>
(function () {
    var notes = { all_day: 'متاح طوال اليوم بلا قيود', off: 'يوم إجازة' };

    function syncRow(row) {
        var checked = row.querySelector('[data-schedule-mode]:checked');
        var mode = checked ? checked.value : 'off';
        var hours = mode === 'hours';
        row.classList.toggle('is-off', mode === 'off');
        row.querySelector('[data-times]').classList.toggle('d-none', !hours);
        row.querySelector('[data-times-note]').textContent = hours ? '' : notes[mode];
        row.querySelectorAll('[data-schedule-time]').forEach(function (input) {
            input.disabled = !hours;
            input.required = hours;
        });
    }

    var rows = Array.prototype.slice.call(document.querySelectorAll('[data-schedule-row]'));
    rows.forEach(function (row) {
        row.querySelectorAll('[data-schedule-mode]').forEach(function (radio) {
            radio.addEventListener('change', function () { syncRow(row); });
        });
        syncRow(row);
    });

    var copy = document.querySelector('[data-copy-first-day]');
    if (copy && rows.length) copy.addEventListener('click', function () {
        var first = rows[0];
        var mode = (first.querySelector('[data-schedule-mode]:checked') || {}).value;
        var times = first.querySelectorAll('[data-schedule-time]');
        rows.slice(1).forEach(function (row) {
            var radio = row.querySelector('[data-schedule-mode][value="' + mode + '"]');
            if (radio) radio.checked = true;
            row.querySelectorAll('[data-schedule-time]').forEach(function (input, i) { input.value = times[i].value; });
            syncRow(row);
        });
    });

    var account = document.getElementById('account_enabled');
    var fields = document.querySelector('[data-account-fields]');
    account.addEventListener('change', function () { fields.classList.toggle('hd-dim', !account.checked); });
})();
</script>
@endsection
