@extends('admin.layout.indexs.index')

@php
    $defaultTypeId = $defaultTypeId ?? null;
    $defaultShelterContract = $defaultShelterContract ?? '0';
    $defaultIsShow = isset($defaultIsShow) ? (bool) $defaultIsShow : true;
    $isRentalForm = $isRentalForm ?? false;
    $formRedirectRoute = $formRedirectRoute ?? route('maids.index');
    $requireIdFields = $requireIdFields ?? true;
@endphp

@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dropify.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/select2.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/sweetalert2.min.css">
    <style>
        .rapid-cv-shell {
            max-width: 760px;
            margin-inline: auto;
        }

        .rapid-cv-panel {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            padding: 18px;
        }

        .rapid-cv-step {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            font-weight: 700;
            color: #111827;
        }

        .rapid-cv-step span {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #0d6efd;
            color: #fff;
            flex: 0 0 30px;
        }
    </style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.maids') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CREATE))
        <div class="card rapid-cv-shell">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <h5 class="mb-1">{{ helperTrans('ingaz.add_short_cv') }}</h5>
                        <div class="text-muted small">{{ helperTrans('ingaz.short_cv_minimal_hint') }}</div>
                    </div>
                    <a href="{{ route('maids.create') }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-right me-1"></i>
                        {{ helperTrans('ingaz.add_full_cv') }}
                    </a>
                </div>
                <form class="row g-3 from-submit-global-maid" method="post" action="{{ route('maids.store_rapid_cv') }}"
                    enctype="multipart/form-data" id="maidForm">
                    @csrf
                    <input type="hidden" name="type_id" value="{{ $defaultTypeId }}">

                    <div class="col-12">
                        <div class="rapid-cv-panel">
                            <div class="rapid-cv-step"><span>1</span> بيانات السيرة المختصرة</div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="rapid_name" class="form-label">
                                        اسم العامل/ة ({{ helperTrans('ingaz.en') }})
                                    </label>
                                    <input type="text" name="name:en" id="rapid_name" class="form-control"
                                        placeholder="{{ helperTrans('ingaz.short_cv_optional_name_placeholder') }}">
                                    <small class="text-muted">{{ helperTrans('ingaz.short_cv_name_optional_hint') }}</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="gender" class="form-label">الجنس <span class="text-danger">*</span></label>
                                    <select name="gender" id="gender" class="form-control" required>
                                        <option value="female" selected>أنثى</option>
                                        <option value="male">ذكر</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="Pnationality_id" class="form-label">{{ helperTrans('ingaz.nationality') }} <span class="text-danger">*</span></label>
                                    <select name="nationality_id" id="Pnationality_id" class="form-control" required>
                                        <option value="">{{ helperTrans('ingaz.choose') }}</option>
                                        @foreach ($nationalities as $nationality)
                                            <option value="{{ $nationality->id }}" data-price="{{ $nationality->price }}"
                                                data-salary="{{ $nationality->monthly_salary }}"
                                                data-service_transportation_cost="{{ $nationality->service_transportation_cost }}"
                                                data-rental_cost="{{ $nationality->rental_cost }}">
                                                {{ $nationality->sub_title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="job_id" class="form-label">المهنة <span class="text-danger">*</span></label>
                                    <select name="job_id[]" id="job_id" class="form-control" required>
                                        <option value="">اختر المهنة</option>
                                        @foreach ($jobs as $job)
                                            <option value="{{ $job->id }}">{{ $job->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 office-field">
                                    <label for="office_id" class="form-label">{{ helperTrans('ingaz.recruitment office') }} <span class="text-danger">*</span></label>
                                    <select name="office_id" id="office_id" class="form-control" required>
                                        <option value="">{{ helperTrans('ingaz.choose_recruitment_office') }}</option>
                                        @foreach ($offices as $office)
                                            <option value="{{ $office->id }}">{{ $office->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="passport_num" class="form-label">
                                        {{ helperTrans('ingaz.passport_num') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="passport_num" id="passport_num" class="form-control" required
                                        autocomplete="off">
                                    <div id="passport_num_feedback" class="invalid-feedback"></div>
                                </div>

                                <div class="col-12">
                                    <label for="cv_file" class="form-label">{{ helperTrans('ingaz.passport_file') }}</label>
                                    <input type="file" data-default-file="" name="cv_file" id="cv_file" class="dropify"
                                        accept=".pdf,.jpg,.jpeg,.png">
                                    <small class="text-muted">{{ helperTrans('ingaz.passport_file_optional_hint') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-save me-1"></i>
                            {{ __('buttons.save') }}
                        </button>
                        <a href="{{ route('maids.create') }}"
                            class="btn btn-outline-secondary">{{ __('buttons.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dropify.bundle.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/select2.bundle.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/sweetalert2.bundle.js"></script>
    <script>
        $(document).ready(function() {
            const rapidCvTranslations = {
                passportRegisteredBefore: @json(helperTrans('ingaz.passport_registered_before')),
                passportAvailable: @json(helperTrans('ingaz.passport_available')),
                chooseRecruitmentOffice: @json(helperTrans('ingaz.choose_recruitment_office')),
                errorTitle: @json(helperTrans('ingaz.error_title')),
                officeFetchError: @json(helperTrans('ingaz.office_fetch_error')),
                ok: @json(helperTrans('ingaz.ok')),
                nationalityRequired: @json(helperTrans('ingaz.nationality_required')),
                genderRequired: 'يرجى اختيار الجنس',
                jobRequired: 'يرجى اختيار المهنة',
                passportRequired: @json(helperTrans('ingaz.passport_required')),
                officeRequired: @json(helperTrans('ingaz.external_office_required')),
                passportDuplicateValidation: @json(helperTrans('ingaz.passport_duplicate_validation')),
                correctErrorsPrefix: @json(helperTrans('ingaz.correct_errors_prefix')),
                loading: @json(helperTrans('ingaz.loading_text')),
                successTitle: @json(helperTrans('ingaz.success_title')),
                shortCvAddedSuccessfully: @json(helperTrans('ingaz.short_cv_added_successfully')),
                maidAddError: @json(helperTrans('ingaz.maid_add_error')),
                create: @json(__('ingaz.create')),
            };
            $('.dropify').dropify();
            $('#Pnationality_id, #office_id, #job_id').select2({
                width: '100%',
                dir: 'rtl'
            });

            function normalizeArabicText(text) {
                return (text || '')
                    .toString()
                    .replace(/[إأآ]/g, 'ا')
                    .replace(/\u0649/g, 'ي')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .toLowerCase();
            }

            function inferGenderFromJobText(text) {
                const normalized = normalizeArabicText(text);

                if (normalized.includes('سائق') || normalized.includes('driver')) {
                    return 'male';
                }

                if (
                    normalized.includes('عاملة') ||
                    normalized.includes('عامله') ||
                    normalized.includes('منزلية') ||
                    normalized.includes('منزليه') ||
                    normalized.includes('maid') ||
                    normalized.includes('domestic')
                ) {
                    return 'female';
                }

                return null;
            }

            $('#job_id').on('change', function() {
                const inferredGender = inferGenderFromJobText($('#job_id option:selected').text());
                if (inferredGender) {
                    $('#gender').val(inferredGender).trigger('change');
                }
            });

            // التحقق من رقم الجواز - فحص التكرار
            var passportCheckTimeout;
            $('#passport_num').on('input', function() {
                var passportNum = $(this).val().trim();
                var feedback = $('#passport_num_feedback');

                // Clear previous timeout
                clearTimeout(passportCheckTimeout);

                // Clear previous feedback
                $(this).removeClass('is-valid is-invalid');
                feedback.removeClass('valid-feedback invalid-feedback').text('');

                // If empty, don't check
                if (!passportNum) {
                    return;
                }

                // Set timeout to avoid too many requests
                passportCheckTimeout = setTimeout(function() {
                    $.ajax({
                        url: '{{ route('check.passport.unique') }}',
                        type: 'POST',
                        data: {
                            passport_num: passportNum,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.exists) {
                                $('#passport_num').addClass('is-invalid');
                                feedback.addClass('invalid-feedback').text(
                                    rapidCvTranslations.passportRegisteredBefore
                                );
                            } else {
                                $('#passport_num').addClass('is-valid');
                                feedback.addClass('valid-feedback').text(
                                    rapidCvTranslations.passportAvailable);
                            }
                        },
                        error: function() {
                            // On error, don't show validation state
                            $('#passport_num').removeClass('is-valid is-invalid');
                            feedback.removeClass('valid-feedback invalid-feedback')
                                .text('');
                        }
                    });
                }, 500); // Wait 500ms after user stops typing
            });

            // Handle nationality change to filter offices and set price/salary
            $('#Pnationality_id').change(function() {
                var nationalityId = $(this).val();
                // applyPriceFromNationality();

                if (nationalityId) {
                    $.ajax({
                        url: "{{ route('getOfficesByNationality') }}",
                        type: 'GET',
                        data: {
                            nationality_id: nationalityId
                        },
                        success: function(response) {
                            var officeSelect = $('select[name="office_id"]');
                            officeSelect.empty();
                            officeSelect.append(
                                `<option value="">${rapidCvTranslations.chooseRecruitmentOffice}</option>`);

                            response.forEach(function(office) {
                                officeSelect.append('<option value="' + office.id +
                                    '">' + office.title + '</option>');
                            });
                            officeSelect.trigger('change');

                            // Set default price and salary based on nationality
                            // Keep salary as provided by the admin; do not override automatically
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: rapidCvTranslations.errorTitle,
                                text: rapidCvTranslations.officeFetchError,
                                confirmButtonText: rapidCvTranslations.ok
                            });
                        }
                    });
                } else {
                    // Clear fields if no nationality is selected
                    $('#price').val('0');
                    $('input[name="salary"]').val('');
                }
            });

            // التحقق من النموذج قبل الإرسال
            $('#maidForm').on('submit', function(e) {
                e.preventDefault();

                var isValid = true;
                var errors = [];

                // التحقق من الحقول المطلوبة
                var requiredFields = {
                    'nationality_id': rapidCvTranslations.nationalityRequired
                };

                // التحقق من الجنسية دائماً
                var nationalityValue = $('[name="nationality_id"]').val();
                if (!nationalityValue || nationalityValue.trim() === '') {
                    errors.push(rapidCvTranslations.nationalityRequired);
                    isValid = false;
                }

                var genderValue = $('[name="gender"]').val();
                if (!genderValue || genderValue.trim() === '') {
                    errors.push(rapidCvTranslations.genderRequired);
                    isValid = false;
                }

                var jobValue = $('#job_id').val();
                if (!jobValue || jobValue.length === 0) {
                    errors.push(rapidCvTranslations.jobRequired);
                    isValid = false;
                }

                var officeValue = $('[name="office_id"]').val();
                if (!officeValue || officeValue.trim() === '') {
                    errors.push(rapidCvTranslations.officeRequired);
                    isValid = false;
                }

                // التحقق من رقم الجواز
                var passportNum = $('input[name="passport_num"]').val().trim();
                if (!passportNum) {
                    errors.push(rapidCvTranslations.passportRequired);
                    isValid = false;
                } else {
                    // Check if passport number is already invalid (from real-time validation)
                    if ($('input[name="passport_num"]').hasClass('is-invalid')) {
                        errors.push(
                            rapidCvTranslations.passportDuplicateValidation);
                        isValid = false;
                    }
                }

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: rapidCvTranslations.errorTitle,
                        text: rapidCvTranslations.correctErrorsPrefix + '\n' + errors.join('\n'),
                        confirmButtonText: rapidCvTranslations.ok
                    });
                    return false;
                }

                // إرسال النموذج باستخدام AJAX
                var formData = new FormData(this);

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function() {
                        $('#maidForm button[type="submit"]').html(
                            `<div class="spinner-border text-light spinner-border-sm" role="status"><span class="visually-hidden">${rapidCvTranslations.loading}</span></div>`
                            );
                    },
                    success: function(response) {
                        if (response.code == 203) {
                            var cvConvertShowRoute = "{{ route('cv-convert.show', ':id') }}";

                            successToster('{{ __('auth.done successfully') }}', response
                                .message);
                            var redirectUrl = cvConvertShowRoute.replace(':id', response.data
                                .id);
                            location.href = redirectUrl;
                        }
                        if (response.code === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: rapidCvTranslations.successTitle,
                                text: rapidCvTranslations.shortCvAddedSuccessfully,
                                confirmButtonText: rapidCvTranslations.ok
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href =
                                        '{{ route('maids.index') }}';
                                }

                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: rapidCvTranslations.errorTitle,
                                text: response.message || rapidCvTranslations.maidAddError,
                                confirmButtonText: rapidCvTranslations.ok
                            });
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = rapidCvTranslations.maidAddError;

                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            errorMessage = '';

                            for (var field in errors) {
                                errorMessage += errors[field].join('\n') + '\n';
                            }
                        }

                        Swal.fire({
                            icon: 'error',
                            title: rapidCvTranslations.errorTitle,
                            text: errorMessage,
                            confirmButtonText: rapidCvTranslations.ok
                        });
                    },
                    complete: function() {
                        $('#maidForm button[type="submit"]').html(rapidCvTranslations.create);
                    }
                });
            });
        });
    </script>
@endsection
