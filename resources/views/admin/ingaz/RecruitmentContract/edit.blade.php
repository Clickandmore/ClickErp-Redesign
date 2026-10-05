@extends('admin.layout.indexs.index')
@section('page-title')
    {{ helperTrans('ingaz.edit Recruitment contract') }}
@endsection

@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/custom/status-badges.css">
    <style>
        .contract-page-intro {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.25rem;
            border: 1px solid #dce6f2;
            border-radius: 24px;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 55%, #f9fcff 100%);
            box-shadow: 0 18px 40px rgba(28, 76, 135, 0.08);
        }

        .contract-page-intro__title {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            color: #183b63;
        }

        .contract-page-intro__text {
            margin: .35rem 0 0;
            color: #5f6f82;
        }

        .contract-page-intro__badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .7rem 1rem;
            border-radius: 999px;
            background: rgba(31, 122, 140, 0.12);
            color: #0f5e6d;
            font-weight: 700;
            white-space: nowrap;
        }

        .contract-page-content {
            padding: 1.25rem;
            border-radius: 28px;
            background: linear-gradient(180deg, #f7fbff 0%, #ffffff 100%);
            border: 1px solid #edf2f8;
        }

        .contract-page-content .card {
            border: 1px solid #e2eaf3;
            border-radius: 22px;
            box-shadow: 0 14px 34px rgba(17, 38, 63, 0.06);
            overflow: hidden;
        }

        .contract-page-content .card-body {
            padding: 1.35rem;
        }

        .contract-page-content .form-section {
            width: 100%;
            padding: 1.25rem;
            border: 1px solid #dde8f4;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 12px 26px rgba(27, 55, 88, 0.05);
        }

        .contract-page-content .section-header {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            margin-bottom: 1.15rem;
            padding-bottom: .9rem;
            border-bottom: 1px solid #e8eef6;
        }

        .contract-page-content .section-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, #1f6fb2 0%, #2d8cbc 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            box-shadow: 0 10px 18px rgba(31, 111, 178, 0.16);
        }

        .contract-page-content .section-title {
            margin: 0 0 .2rem;
            font-size: 1.05rem;
            font-weight: 800;
            color: #233d59;
        }

        .contract-page-content .section-subtitle {
            margin: 0;
            color: #728196;
            font-size: .88rem;
        }

        .contract-page-content .form-label {
            font-weight: 700;
            color: #36506b;
            margin-bottom: .45rem;
        }

        .contract-page-content .form-control,
        .contract-page-content .form-select {
            min-height: 46px;
            border-radius: 14px;
            border-color: #d5e0eb;
            box-shadow: none;
        }

        .contract-page-content .form-control:focus,
        .contract-page-content .form-select:focus {
            border-color: #77a8d8;
            box-shadow: 0 0 0 0.22rem rgba(52, 118, 181, 0.12);
        }

        .contract-page-content textarea.form-control {
            min-height: 110px;
        }

        .contract-page-content .client-readonly-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: .85rem;
        }

        .contract-page-content .client-readonly-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-width: 0;
            padding: .85rem 1rem;
            border: 1px solid #e3ebf5;
            border-radius: 14px;
            background: #f7fafd;
        }

        .contract-page-content .client-readonly-item > div {
            min-width: 0;
        }

        .contract-page-content .client-readonly-icon {
            width: 36px;
            height: 36px;
            border-radius: 11px;
            background: #e6f0fa;
            color: #1f6fb2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .contract-page-content .client-readonly-label {
            display: block;
            font-size: .8rem;
            color: #728196;
            margin-bottom: .15rem;
        }

        .contract-page-content .client-readonly-value {
            display: block;
            font-weight: 700;
            color: #233d59;
            overflow-wrap: anywhere;
            text-align: right;
        }

        .contract-page-content .client-readonly-note {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            margin-top: 1rem;
            padding: .75rem 1rem;
            border-radius: 12px;
            background: #fff8e6;
            border: 1px solid #f3e2b3;
            color: #7a5b12;
            font-size: .86rem;
            line-height: 1.7;
        }

        .contract-page-content .client-readonly-note i {
            margin-top: .3rem;
        }

        .contract-city-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            align-items: center;
            margin-top: .75rem;
        }

        .contract-submit-card .card-body {
            padding: 1rem;
        }

        @media (max-width: 991.98px) {
            .contract-page-intro {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT))
        @php
            $client = $contract->user;
            $maid = $contract->maid;
            $info = $contract->info;
            // يُعرض مسوق العقد المحفوظ فقط؛ العقود القديمة بلا مسوق تُترك فارغة ليختارها المحرِّر صراحة.
            $selectedMarketerId = old('marketer_id', $contract->marketer?->id ?: $contract->order?->resolveResponsibleAdmin()?->id);
            $displayMarketerName = $contract->marketer?->name ?: $contract->order?->resolveResponsibleAdmin()?->name ?: $currentAdmin?->name ?: 'المستخدم الحالي';
        @endphp

        @if ($errors->any())
            <div class="alert alert-danger" dir="rtl">
                <strong>يرجى مراجعة البيانات التالية:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="contract-page-intro">
            <div>
                <h3 class="contract-page-intro__title">تعديل عقد استقدام رقم: {{ $contract->id }}</h3>
                <p class="contract-page-intro__text">نفس تقسيم شاشة الإضافة مع عرض البيانات المحفوظة للعميل والعاملة والعقد.</p>
            </div>
            <div class="contract-page-intro__badge">
                <i class="fas fa-file-pen"></i>
                <span>{{ getContractStatus($contract->status) }}</span>
            </div>
        </div>

        <div class="contract-page-content">
            <form id="editContractForm" method="post" action="{{ route('RecruitmentContract.update', $contract->id) }}">
                @csrf
                @method('PUT')

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="form-section">
                                    <div class="section-header">
                                        <div class="section-icon"><i class="fas fa-user"></i></div>
                                        <div>
                                            <h5 class="section-title">معلومات العميل</h5>
                                            <p class="section-subtitle">بيانات العميل المرتبط بالعقد</p>
                                        </div>
                                    </div>
                                    <div class="client-readonly-grid">
                                        <div class="client-readonly-item">
                                            <span class="client-readonly-icon"><i class="fas fa-user"></i></span>
                                            <div>
                                                <span class="client-readonly-label">اسم العميل</span>
                                                <span class="client-readonly-value">{{ $client?->name ?: '—' }}</span>
                                            </div>
                                        </div>
                                        <div class="client-readonly-item">
                                            <span class="client-readonly-icon"><i class="fas fa-phone"></i></span>
                                            <div>
                                                <span class="client-readonly-label">الجوال</span>
                                                <span class="client-readonly-value" dir="ltr">{{ $client?->phone ?: '—' }}</span>
                                            </div>
                                        </div>
                                        <div class="client-readonly-item">
                                            <span class="client-readonly-icon"><i class="fas fa-id-card"></i></span>
                                            <div>
                                                <span class="client-readonly-label">{{ $client ? $client->documentNumberLabel() : 'رقم الهوية' }}</span>
                                                <span class="client-readonly-value" dir="ltr">{{ $client?->documentNumber() ?: '—' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="client-readonly-note">
                                        <i class="fas fa-lock"></i>
                                        <div>
                                            <strong>بيانات العميل للعرض فقط.</strong>
                                            تصحيح بياناته يتم من شاشة العملاء ويؤثر على جميع معاملاته،
                                            ولتغيير العميل استخدم زر «تغيير العميل» في تفاصيل العقد؛ يتاح فقط قبل وجود معاملات مالية مرتبطة بالعقد.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-section">
                                    <div class="section-header">
                                        <div class="section-icon"><i class="fas fa-female"></i></div>
                                        <div>
                                            <h5 class="section-title">بيانات العامل/ة</h5>
                                            <p class="section-subtitle">بيانات العامل/ة المرتبطة بالعقد</p>
                                        </div>
                                    </div>

                                    @if ($maid)
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">{{ helperTrans('ingaz.maid_name') }}</label>
                                                <input type="text" id="maid_name" name="maid_name" class="form-control" value="{{ old('maid_name', $maid->name) }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ helperTrans('ingaz.maid_passport') }}</label>
                                                <input type="text" id="maid_passport" name="maid_passport" class="form-control" value="{{ old('maid_passport', $maid->passport_num) }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ helperTrans('ingaz.nationality') }}</label>
                                                <input type="text" class="form-control" value="{{ $maid->nationality?->sub_title }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ helperTrans('ingaz.religion') }}</label>
                                                <input type="text" class="form-control" value="{{ $maid->religion?->title }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ helperTrans('ingaz.maid_job') }}</label>
                                                <input type="text" class="form-control" value="{{ $maid->jobTitles?->first()?->title }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ helperTrans('ingaz.salary') }}</label>
                                                <input type="text" id="maid_salary" name="maid_salary" class="form-control" value="{{ old('maid_salary', $maid->salary) }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ helperTrans('ingaz.experience_years') }}</label>
                                                <input type="text" class="form-control" value="{{ $maid->experience_years }}" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ helperTrans('ingaz.age') }}</label>
                                                <input type="text" class="form-control" value="{{ $maid->age }}" readonly>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-user-slash me-1"></i>
                                            لا يوجد عامل/ة مرتبطة بهذا العقد حالياً. تظهر بالأسفل المتطلبات المحفوظة عند إنشاء العقد بدون عامل/ة.
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">الجنسية المطلوبة للعامل/ة</label>
                                                <select name="required_maid_nationality" id="required_maid_nationality" class="form-select select2">
                                                    <option value="">اختر الجنسية المطلوبة...</option>
                                                    @foreach ($maidNationalities as $nationality)
                                                        <option value="{{ $nationality->id }}" @selected((string) old('required_maid_nationality', $contract->required_maid_nationality_id) === (string) $nationality->id)>
                                                            {{ $nationality->sub_title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">الديانة المطلوبة للعامل/ة</label>
                                                <select name="required_maid_religion" id="required_maid_religion" class="form-select select2">
                                                    <option value="">اختر الديانة المطلوبة...</option>
                                                    @foreach ($maidReligions as $religion)
                                                        <option value="{{ $religion->id }}" @selected((string) old('required_maid_religion', $contract->required_maid_religion_id) === (string) $religion->id)>
                                                            {{ $religion->title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">الحد الأدنى للعمر</label>
                                                <input type="number" name="required_maid_age_min" id="required_maid_age_min" class="form-control" min="18" max="80" value="{{ old('required_maid_age_min', $contract->required_maid_age_min) }}">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">الحد الأقصى للعمر</label>
                                                <input type="number" name="required_maid_age_max" id="required_maid_age_max" class="form-control" min="18" max="80" value="{{ old('required_maid_age_max', $contract->required_maid_age_max) }}">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">الحد الأدنى للخبرة (سنوات)</label>
                                                <input type="number" name="required_maid_experience_min" id="required_maid_experience_min" class="form-control" min="0" max="50" value="{{ old('required_maid_experience_min', $contract->required_maid_experience_min) }}">
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">المهنة المطلوبة</label>
                                                <select name="required_maid_job_title" id="required_maid_job_title" class="form-select select2">
                                                    <option value="">اختر المهنة المطلوبة...</option>
                                                    @foreach ($maidJobTitles as $jobTitle)
                                                        <option value="{{ $jobTitle->id }}" @selected((string) old('required_maid_job_title', $contract->required_maid_job_title_id) === (string) $jobTitle->id)>
                                                            {{ $jobTitle->title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label">ملاحظات إضافية</label>
                                                <textarea name="required_maid_notes" id="required_maid_notes" class="form-control" rows="3">{{ old('required_maid_notes', $contract->required_maid_notes) }}</textarea>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-section">
                                    <div class="section-header">
                                        <div class="section-icon"><i class="fas fa-file-contract"></i></div>
                                        <div>
                                            <h5 class="section-title">بيانات العقد</h5>
                                            <p class="section-subtitle">البيانات الفنية والزمنية</p>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">{{ helperTrans('ingaz.contract date') }} <span class="text-danger">*</span></label>
                                            <input type="date" id="contract_date" name="contract_date" class="form-control" value="{{ old('contract_date', $info?->contract_date ?? $contract->contract_date) }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ helperTrans('ingaz.msaned number') }} <span class="text-danger">*</span></label>
                                            <input type="text" id="msaned_number" name="msaned_number" class="form-control" value="{{ old('msaned_number', $info?->msaned_number) }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ helperTrans('ingaz.tafwed number') }} <small class="text-muted">(اختياري)</small></label>
                                            <input type="text" id="tafwed_number" name="tafwed_number" class="form-control" value="{{ old('tafwed_number', $info?->tafwed_number) }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ helperTrans('ingaz.visa number') }} <span class="text-danger">*</span></label>
                                            <input type="text" id="visa_number" name="visa_number" class="form-control" value="{{ old('visa_number', $info?->visa_number) }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ helperTrans('ingaz.visa_date_is') }} <small class="text-muted">(اختياري)</small></label>
                                            <input type="date" id="visa_milad_date" name="visa_milad_date" class="form-control" value="{{ old('visa_milad_date', $info?->visa_milad_date) }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ helperTrans('ingaz.visa_hijry_date') }} <small class="text-muted">(اختياري)</small></label>
                                            <input type="date" id="visa_hijry_date" name="visa_hijry_date" class="form-control" value="{{ old('visa_hijry_date', $info?->visa_hijry_date) }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">{{ helperTrans('ingaz.contract duration') }} <span class="text-danger">*</span></label>
                                            <input type="number" id="contract_duration" name="contract_duration" class="form-control" value="{{ old('contract_duration', $info?->contract_duration ?? 2) }}" min="1" required>
                                            <small class="text-muted">(مدة التعاقد بالسنوات)</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">المسوق <span class="text-danger">*</span></label>
                                            @if (!empty($canSelectAnyMarketer))
                                                <select name="marketer_id" id="marketer_id" class="form-select select2" data-placeholder="اختر المسوق" required>
                                                    <option value="">اختر المسوق...</option>
                                                    @foreach ($marketers ?? collect() as $marketer)
                                                        {{-- @selected من Laravel 9 ولا يُترجم هنا (Laravel 8) --}}
                                                        <option value="{{ $marketer->id }}" {{ (string) $selectedMarketerId === (string) $marketer->id ? 'selected' : '' }}>
                                                            {{ $marketer->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="text" class="form-control" value="{{ $displayMarketerName }}" readonly>
                                                <input type="hidden" name="marketer_id" id="marketer_id" value="{{ $selectedMarketerId }}">
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">حالة العقد</label>
                                            <input type="text" class="form-control" value="{{ getContractStatus($contract->status) }}" readonly>
                                            <input type="hidden" name="status" id="status" value="{{ $contract->status }}">
                                            <small class="text-muted">تعديل الحالة يتم من شاشة قائمة العقود فقط.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="card contract-submit-card">
                                    <div class="card-body text-center">
                                        <button type="submit" class="btn btn-primary btn-lg px-5 me-3">
                                            <i class="fa-solid fa-floppy-disk me-2"></i>
                                            {{ __('buttons.save') }}
                                        </button>
                                        <a href="{{ route('RecruitmentContract.index') }}" class="btn btn-secondary btn-lg px-5">
                                            <i class="fas fa-arrow-right me-2"></i>
                                            {{ __('buttons.back') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    dir: 'rtl',
                    width: '100%'
                });
            }

            $('#editContractForm').on('submit', function(e) {
                e.preventDefault();

                var $form = $(this);
                var $submitButton = $form.find('button[type="submit"]');
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.ajax-validation-feedback').remove();
                $submitButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i>جاري الحفظ...');

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: response.message || 'تم تحديث العقد بنجاح',
                                confirmButtonText: 'حسناً'
                            }).then(function() {
                                window.location.href = "{{ route('RecruitmentContract.index') }}";
                            });
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: response.message || 'حدث خطأ أثناء تحديث العقد',
                            confirmButtonText: 'حسناً'
                        });
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON || {};
                        var errors = response.errors || {};
                        var messages = [];
                        var firstField = null;

                        $.each(errors, function(field, fieldMessages) {
                            var message = $.isArray(fieldMessages) ? fieldMessages[0] : fieldMessages;
                            var $field = $form.find('[name="' + field + '"]').first();
                            messages.push(message);

                            if ($field.length) {
                                $field.addClass('is-invalid');
                                var $feedback = $('<div class="invalid-feedback d-block ajax-validation-feedback"></div>').text(message);
                                if ($field.hasClass('select2-hidden-accessible')) {
                                    $field.next('.select2-container').after($feedback);
                                } else {
                                    $field.after($feedback);
                                }
                                firstField = firstField || $field;
                            }
                        });

                        if (firstField) {
                            $('html, body').animate({ scrollTop: firstField.offset().top - 120 }, 350);
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'يرجى مراجعة البيانات',
                            html: messages.length ? '<div class="text-start" dir="rtl">' + messages.map(function(message) {
                                return '<div class="mb-1">- ' + $('<div>').text(message).html() + '</div>';
                            }).join('') + '</div>' : (response.message || 'حدث خطأ أثناء تحديث العقد'),
                            confirmButtonText: 'حسناً'
                        });
                    },
                    complete: function() {
                        $submitButton.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-2"></i>{{ __('buttons.save') }}');
                    }
                });
            });
        });
    </script>
@endsection
