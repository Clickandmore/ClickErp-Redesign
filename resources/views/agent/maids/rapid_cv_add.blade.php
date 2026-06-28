@extends('agent.layout')

@php
    $isAr = app()->getLocale() === 'ar';
    $agentOffice = $offices->first();
@endphp

@section('page-title', $isAr ? 'إضافة سيرة مختصرة' : 'Add Short CV')

@push('style')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dropify.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/select2.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/sweetalert2.min.css">
    <style>
        :root {
            --mc-primary: #0f766e;
            --mc-primary-dark: #115e59;
            --mc-accent: #2563eb;
            --mc-ink: #0f172a;
            --mc-muted: #64748b;
            --mc-line: #e2e8f0;
            --mc-bg-soft: #f8fafc;
            --mc-danger: #dc2626;
            --mc-danger-soft: #fef2f2;
        }

        /* Hero */
        .mc-hero {
            position: relative;
            border-radius: 18px;
            padding: 22px 24px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 55%, #b45309 100%);
            color: #fff;
            box-shadow: 0 18px 38px -18px rgba(245, 158, 11, .65);
            overflow: hidden;
            margin-bottom: 18px;
        }
        .mc-hero::after { content: ""; position: absolute; inset: auto -60px -80px auto; width: 240px; height: 240px; border-radius: 50%; background: radial-gradient(circle, rgba(255, 255, 255, .18), transparent 70%); }
        .mc-hero__row { display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap; position: relative; z-index: 1; }
        .mc-hero__title { display: flex; align-items: center; gap: 14px; }
        .mc-hero__icon { width: 52px; height: 52px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, .14); backdrop-filter: blur(8px); font-size: 22px; }
        .mc-hero h2 { margin: 0; font-size: 22px; font-weight: 800; }
        .mc-hero p { margin: 4px 0 0; color: rgba(255, 255, 255, .82); font-size: 13px; }

        .mc-hero__back {
            border: 1px solid rgba(255, 255, 255, .35);
            background: rgba(255, 255, 255, .12);
            color: #fff;
            border-radius: 10px;
            padding: 8px 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .mc-hero__back:hover { background: rgba(255, 255, 255, .22); color: #fff; }

        /* Section */
        .mc-section { background: #fff; border: 1px solid var(--mc-line); border-radius: 16px; padding: 18px; box-shadow: 0 4px 14px -10px rgba(15, 23, 42, .08); max-width: 820px; margin: 0 auto; }
        .mc-section__head { display: flex; align-items: center; gap: 12px; padding-bottom: 12px; margin-bottom: 16px; border-bottom: 1px dashed var(--mc-line); }
        .mc-section__title { display: flex; align-items: center; gap: 10px; font-weight: 800; color: var(--mc-ink); font-size: 15px; margin: 0; }
        .mc-section__icon { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: rgba(245, 158, 11, .12); color: #d97706; font-size: 15px; }

        /* Steps badge */
        .mc-step { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 14px; font-weight: 700; color: var(--mc-ink); font-size: 14px; }
        .mc-step span { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f59e0b, #b45309); color: #fff; font-size: 13px; flex: 0 0 28px; }

        /* Fields */
        .mc-field { margin-bottom: 4px; }
        .mc-field-label { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: var(--mc-ink); }
        .mc-required { color: var(--mc-danger); font-weight: 800; }
        .mc-optional { color: var(--mc-muted); font-weight: 400; font-size: 11px; }
        .form-control, .form-select { min-height: 44px; border-radius: 10px; border-color: var(--mc-line); box-shadow: none !important; font-size: 14px; transition: border-color .15s ease, box-shadow .15s ease; }
        .form-control:focus, .form-select:focus { border-color: #d97706; box-shadow: 0 0 0 3px rgba(217, 119, 6, .15) !important; }

        /* Validation */
        .form-control.is-invalid, .form-select.is-invalid, .select2-container--default.is-invalid .select2-selection {
            border-color: var(--mc-danger) !important;
            background-color: var(--mc-danger-soft) !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, .12) !important;
        }
        .select2-container--default.is-invalid .select2-selection--single,
        .select2-container--default.is-invalid .select2-selection--multiple {
            border-color: var(--mc-danger) !important;
            background-color: var(--mc-danger-soft) !important;
        }
        .mc-error { display: none; margin-top: 6px; font-size: 12px; color: var(--mc-danger); font-weight: 600; align-items: center; gap: 6px; }
        .mc-error.is-visible { display: flex; }
        .mc-error::before { content: "\26A0"; }

        /* Select2 */
        .select2-container--default .select2-selection--single, .select2-container--default .select2-selection--multiple { min-height: 44px; border-radius: 10px; border-color: var(--mc-line); }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 42px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 42px; }

        /* Locked field */
        .mc-locked-field { display: flex; align-items: center; gap: 10px; min-height: 44px; padding: 0 14px; border-radius: 10px; border: 1.5px dashed var(--mc-line); background: linear-gradient(135deg, rgba(15, 118, 110, .06), rgba(37, 99, 235, .04)); color: var(--mc-ink); font-weight: 600; font-size: 14px; }
        .mc-locked-icon { width: 30px; height: 30px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: rgba(15, 118, 110, .12); color: var(--mc-primary); font-size: 13px; flex-shrink: 0; }

        /* Actions */
        .mc-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 18px; padding-top: 14px; border-top: 1px dashed var(--mc-line); }
        .mc-btn { min-height: 44px; padding: 0 22px; border-radius: 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; border: 1.5px solid transparent; transition: transform .15s ease, background .2s ease, box-shadow .2s ease; }
        .mc-btn-primary { background: linear-gradient(135deg, #f59e0b, #b45309); color: #fff; box-shadow: 0 12px 22px -14px rgba(245, 158, 11, .7); }
        .mc-btn-primary:hover { color: #fff; transform: translateY(-1px); }
        .mc-btn-secondary { background: #fff; border-color: var(--mc-line); color: var(--mc-ink); }
        .mc-btn-secondary:hover { background: var(--mc-bg-soft); color: var(--mc-ink); }

        /* Dropify */
        .dropify-wrapper { border-radius: 12px !important; min-height: 140px; }

        @keyframes mc-shake { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-4px)} 40%{transform:translateX(4px)} 60%{transform:translateX(-2px)} 80%{transform:translateX(2px)} }
        .mc-shake { animation: mc-shake .3s ease; }
    </style>
@endpush

@section('content')
    <form method="POST" action="{{ route('agent.maids.store_rapid_cv') }}" enctype="multipart/form-data" id="rapidForm" novalidate>
        @csrf
        <input type="hidden" name="type_id" value="{{ $defaultTypeId }}">

        {{-- Hero --}}
        <div class="mc-hero">
            <div class="mc-hero__row">
                <div class="mc-hero__title">
                    <span class="mc-hero__icon"><i class="fa fa-bolt"></i></span>
                    <div>
                        <h2>{{ $isAr ? 'إضافة سيرة مختصرة' : 'Add Short CV' }}</h2>
                        <p>{{ $isAr ? 'احفظ السيرة بأقل البيانات الضرورية، يمكنك إكمال الباقي لاحقاً.' : 'Save the CV with minimal required data — fill the rest later.' }}</p>
                    </div>
                </div>
                <a href="{{ route('agent.maids.create') }}" class="mc-hero__back">
                    <i class="fa fa-arrow-right-arrow-left"></i>
                    <span>{{ $isAr ? 'سيرة كاملة' : 'Full CV' }}</span>
                </a>
            </div>
        </div>

        <div class="mc-section">
            <div class="mc-section__head">
                <h3 class="mc-section__title">
                    <span class="mc-section__icon"><i class="fa fa-id-card"></i></span>
                    {{ $isAr ? 'بيانات السيرة المختصرة' : 'Short CV Data' }}
                </h3>
            </div>

            <div class="mc-step"><span>1</span>{{ $isAr ? 'البيانات الأساسية' : 'Basic Data' }}</div>

            <div class="row g-3">
                <div class="col-12">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'اسم العامل/ة' : 'Worker Name' }}<span class="mc-optional">({{ $isAr ? 'اختياري' : 'optional' }})</span></label>
                        <input type="text" name="name:en" id="rapid_name" class="form-control" value="{{ old('name:en') }}"
                            placeholder="{{ $isAr ? 'يمكنك تركه فارغاً' : 'You can leave it empty' }}">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mc-field">
                        @php $oldGender = old('gender', 'female'); @endphp
                        <label class="mc-field-label">{{ $isAr ? 'الجنس' : 'Gender' }}<span class="mc-required">*</span></label>
                        <select name="gender" id="gender" data-label="{{ $isAr ? 'الجنس' : 'Gender' }}" class="form-control" required>
                            <option value="female" {{ $oldGender === 'female' ? 'selected' : '' }}>{{ $isAr ? 'أنثى' : 'Female' }}</option>
                            <option value="male" {{ $oldGender === 'male' ? 'selected' : '' }}>{{ $isAr ? 'ذكر' : 'Male' }}</option>
                        </select>
                        <div class="mc-error" data-error-for="gender">{{ $isAr ? 'يرجى اختيار الجنس' : 'Please select gender' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'الجنسية' : 'Nationality' }}<span class="mc-required">*</span></label>
                        <select name="nationality_id" id="Pnationality_id" data-label="{{ $isAr ? 'الجنسية' : 'Nationality' }}" class="form-control" required>
                            <option value="">{{ $isAr ? 'اختر' : 'Choose' }}</option>
                            @foreach ($nationalities as $nationality)
                                <option value="{{ $nationality->id }}" {{ (int) old('nationality_id') === (int) $nationality->id ? 'selected' : '' }}>{{ $nationality->sub_title ?: $nationality->title }}</option>
                            @endforeach
                        </select>
                        <div class="mc-error" data-error-for="nationality_id">{{ $isAr ? 'يرجى اختيار الجنسية' : 'Please choose nationality' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mc-field">
                        @php $oldJobIds = array_map('intval', (array) old('job_id', [])); @endphp
                        <label class="mc-field-label">{{ $isAr ? 'المهنة' : 'Job' }}<span class="mc-required">*</span></label>
                        <select name="job_id[]" id="job_id" data-label="{{ $isAr ? 'المهنة' : 'Job' }}" class="form-control" required>
                            <option value="">{{ $isAr ? 'اختر المهنة' : 'Choose Job' }}</option>
                            @foreach ($jobs as $job)
                                <option value="{{ $job->id }}" {{ in_array((int) $job->id, $oldJobIds, true) ? 'selected' : '' }}>{{ $job->title }}</option>
                            @endforeach
                        </select>
                        <div class="mc-error" data-error-for="job_id">{{ $isAr ? 'يرجى اختيار المهنة' : 'Please choose job' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mc-field">
                        <label class="mc-field-label">
                            {{ $isAr ? 'مكتب الاستقدام' : 'Recruitment Office' }}
                            <i class="fa fa-lock text-muted ms-1"></i>
                        </label>
                        @if ($agentOffice)
                            <div class="mc-locked-field">
                                <span class="mc-locked-icon"><i class="fa fa-building"></i></span>
                                <span>{{ $agentOffice->title }}</span>
                            </div>
                            <input type="hidden" name="office_id" value="{{ $agentOffice->id }}">
                        @else
                            <div class="mc-locked-field" style="background: var(--mc-danger-soft); border-color: rgba(220, 38, 38, .35); color: var(--mc-danger);">
                                <span class="mc-locked-icon" style="background: rgba(220, 38, 38, .12); color: var(--mc-danger);">
                                    <i class="fa fa-triangle-exclamation"></i>
                                </span>
                                <span>{{ $isAr ? 'لا يوجد مكتب مرتبط بحسابك' : 'No office linked to your account' }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-12">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'رقم الجواز' : 'Passport Number' }}<span class="mc-required">*</span></label>
                        <input type="text" name="passport_num" id="passport_num" data-label="{{ $isAr ? 'رقم الجواز' : 'Passport Number' }}" class="form-control" value="{{ old('passport_num') }}" required autocomplete="off">
                        <div class="mc-error" data-error-for="passport_num">{{ $isAr ? 'يرجى إدخال رقم الجواز' : 'Please enter passport number' }}</div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="mc-field">
                        <label class="mc-field-label">{{ $isAr ? 'ملف الجواز' : 'Passport File' }}<span class="mc-optional">({{ $isAr ? 'اختياري' : 'optional' }})</span></label>
                        <input type="file" data-default-file="" name="cv_file" id="cv_file" class="dropify" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
            </div>

            <div class="mc-actions">
                <a href="{{ route('agent.maids.index') }}" class="mc-btn mc-btn-secondary">
                    <i class="fa fa-xmark"></i>
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </a>
                <button class="mc-btn mc-btn-primary" type="submit" id="rapid_submit_btn">
                    <i class="fa fa-bolt"></i>
                    <span class="btn-label">{{ $isAr ? 'حفظ سيرة مختصرة' : 'Save Short CV' }}</span>
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/dropify.bundle.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/select2.bundle.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/sweetalert2.bundle.js"></script>
    <script>
        $(function () {
            const T = {
                ok: @json($isAr ? 'موافق' : 'OK'),
                errorTitle: @json($isAr ? 'بيانات ناقصة' : 'Missing data'),
                fixErrors: @json($isAr ? 'يرجى تصحيح الحقول المظللة بالأحمر' : 'Please fix the highlighted fields'),
                saving: @json($isAr ? 'جاري الحفظ...' : 'Saving...'),
                save: @json($isAr ? 'حفظ سيرة مختصرة' : 'Save Short CV'),
            };

            $('.dropify').dropify();
            $('#Pnationality_id, #job_id, #gender').select2({ width: '100%', dir: @json($isAr ? 'rtl' : 'ltr') });

            // Clear errors on change
            function clearFieldError($f) {
                $f.removeClass('is-invalid');
                $f.next('.select2-container').removeClass('is-invalid');
                var name = $f.attr('name');
                if (name) {
                    var $e = $('[data-error-for="' + name + '"]').first();
                    if ($e.length) $e.removeClass('is-visible');
                }
            }
            $(document).on('input change', '.form-control, .form-select', function () { clearFieldError($(this)); });

            // Form submit validation
            $('#rapidForm').on('submit', function (e) {
                var firstInvalid = null;
                $('.form-control, .form-select').removeClass('is-invalid');
                $('.select2-container').removeClass('is-invalid');
                $('.mc-error').removeClass('is-visible');

                $(this).find('[required]').each(function () {
                    var $f = $(this);
                    var v = $f.val();
                    if (v === null || v === '' || (Array.isArray(v) && v.length === 0)) {
                        $f.addClass('is-invalid');
                        $f.next('.select2-container').addClass('is-invalid');
                        var name = $f.attr('name');
                        $('[data-error-for="' + name + '"]').addClass('is-visible');
                        if (!firstInvalid) firstInvalid = $f;
                    }
                });

                if (firstInvalid) {
                    e.preventDefault();
                    var $target = firstInvalid.closest('.mc-field, .mc-section');
                    if ($target.length) {
                        $target.addClass('mc-shake');
                        setTimeout(function () { $target.removeClass('mc-shake'); }, 350);
                        $('html, body').animate({ scrollTop: Math.max(0, $target.offset().top - 100) }, 350);
                    }
                    Swal.fire({
                        icon: 'error',
                        title: T.errorTitle,
                        text: T.fixErrors,
                        confirmButtonText: T.ok,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                    return false;
                }

                var $btn = $('#rapid_submit_btn');
                $btn.prop('disabled', true);
                $btn.find('.btn-label').text(T.saving);
            });
        });
    </script>
@endpush
