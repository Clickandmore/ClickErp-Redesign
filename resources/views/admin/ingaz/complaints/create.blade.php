@extends('admin.layout.indexs.index')

@section('page-title')
{{ __('ingaz.complaint_add_new') }}
@endsection

@section('links')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('ingaz.dashboard_home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('complaints.index') }}">{{ __('ingaz.complaints') }}</a></li>
    <li class="breadcrumb-item active">{{ __('ingaz.complaint_add_new') }}</li>
@endsection

@section('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px 10px 0 0;
        padding: 1.5rem;
    }
    .section-header {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1.5rem;
        border-left: 4px solid #667eea;
    }
    .section-header h5 {
        color: #495057;
        font-weight: 600;
    }
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    .required-star {
        color: #dc3545;
    }
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #5569d8 0%, #6a4190 100%);
    }
    .is-invalid + .select2-container .select2-selection,
    select.is-invalid + .select2-container .select2-selection {
        border-color: #dc3545 !important;
    }
    .select2-container--default.select2-invalid .select2-selection {
        border-color: #dc3545 !important;
    }
</style>
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_CREATE))

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ __('ingaz.complaint_register_new') }}
        </h4>
    </div>
    <div class="card-body">
        <form action="{{ route('complaints.store') }}" method="POST" enctype="multipart/form-data" id="complaintForm" class="from-submit-global" novalidate>
            @csrf

            <!-- معلومات مقدم الشكوى -->
            <div class="section-header">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    {{ __('ingaz.complainant_information') }}
                </h5>
            </div>
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('ingaz.complainant_name') }} <span class="required-star">*</span></label>
                    <input type="text" name="complainant_name" class="form-control {{ $errors->has('complainant_name') ? 'is-invalid' : '' }}" required
                           placeholder="{{ __('ingaz.complainant_name_placeholder') }}" value="{{ old('complainant_name') }}"
                           data-validation="required">
                    <span class="text-danger small d-block mt-1" data-field="complainant_name">{{ $errors->first('complainant_name') }}</span>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('ingaz.phone') }} <span class="required-star">*</span></label>
                    <input type="tel" name="complainant_phone" class="form-control {{ $errors->has('complainant_phone') ? 'is-invalid' : '' }}" required
                           placeholder="05XXXXXXXX | 5XXXXXXXX" pattern="^(05|5)[0-9]{8}$" value="{{ old('complainant_phone') }}"
                           data-validation="required">
                    <span class="text-danger small d-block mt-1" data-field="complainant_phone">{{ $errors->first('complainant_phone') }}</span>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('ingaz.registered_client_optional') }}</label>
                    <select name="user_id" class="form-select select2 {{ $errors->has('user_id') ? 'is-invalid' : '' }}">
                        <option value="">{{ __('ingaz.select_registered_client') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}
                                    data-name="{{ $user->name }}" data-phone="{{ $user->phone }}">
                                {{ $user->name }} - {{ $user->phone }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-danger small d-block mt-1" data-field="user_id">{{ $errors->first('user_id') }}</span>
                </div>
            </div>

            <!-- معلومات العاملة -->
            <div class="section-header">
                <h5 class="mb-0">
                    <i class="fas fa-female me-2"></i>
                    {{ __('ingaz.worker_information') }}
                </h5>
            </div>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('ingaz.worker_passport_number') }}</label>
                    <input type="text" name="maid_passport" class="form-control {{ $errors->has('maid_passport') ? 'is-invalid' : '' }}"
                           placeholder="{{ __('ingaz.passport_number_placeholder') }}" value="{{ old('maid_passport') }}">
                    <span class="text-danger small d-block mt-1" data-field="maid_passport">{{ $errors->first('maid_passport') }}</span>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('ingaz.worker_nationality') }}</label>
                    <select name="maid_nationality_id" class="form-select select2 {{ $errors->has('maid_nationality_id') ? 'is-invalid' : '' }}">
                        <option value="">{{ __('ingaz.select_nationality') }}</option>
                        @foreach($nationalities as $nationality)
                            <option value="{{ $nationality->id }}" {{ old('maid_nationality_id') == $nationality->id ? 'selected' : '' }}>
                                {{ $nationality->title }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-danger small d-block mt-1" data-field="maid_nationality_id">{{ $errors->first('maid_nationality_id') }}</span>
                </div>
            </div>

            <!-- تفاصيل الشكوى -->
            <div class="section-header">
                <h5 class="mb-0">
                    <i class="fas fa-file-alt me-2"></i>
                    {{ __('ingaz.complaint_details') }}
                </h5>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('ingaz.complaint_subject') }} <span class="required-star">*</span></label>
                    <input type="text" name="subject" class="form-control {{ $errors->has('subject') ? 'is-invalid' : '' }}" required
                           placeholder="{{ __('ingaz.complaint_subject_placeholder') }}" value="{{ old('subject') }}"
                           data-validation="required">
                    <span class="text-danger small d-block mt-1" data-field="subject">{{ $errors->first('subject') }}</span>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('ingaz.complaint_type') }} <span class="required-star">*</span></label>
                    <select name="complaint_type_id" class="form-select {{ $errors->has('complaint_type_id') ? 'is-invalid' : '' }}" required data-validation="required">
                        <option value="">{{ __('ingaz.select_complaint_type') }}</option>
                        @foreach($complaintTypes as $type)
                            <option value="{{ $type->id }}" {{ old('complaint_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-danger small d-block mt-1" data-field="complaint_type_id">{{ $errors->first('complaint_type_id') }}</span>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('ingaz.priority') }} <span class="required-star">*</span></label>
                    <select name="priority" class="form-select {{ $errors->has('priority') ? 'is-invalid' : '' }}" required data-validation="required">
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>
                            {{ __('ingaz.priority_low') }}
                        </option>
                        <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>
                            {{ __('ingaz.priority_medium') }}
                        </option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>
                            {{ __('ingaz.priority_high') }}
                        </option>
                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>
                            {{ __('ingaz.priority_urgent') }}
                        </option>
                    </select>
                    <span class="text-danger small d-block mt-1" data-field="priority">{{ $errors->first('priority') }}</span>
                </div>

                <div class="col-md-8 mb-3">
                    <label class="form-label">{{ __('ingaz.assigned_employee') }}</label>
                    <select name="assigned_to" class="form-select select2 {{ $errors->has('assigned_to') ? 'is-invalid' : '' }}">
                        <option value="">{{ __('ingaz.select_assigned_employee') }}</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}" {{ old('assigned_to') == $admin->id ? 'selected' : '' }}>
                                {{ $admin->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-danger small d-block mt-1" data-field="assigned_to">{{ $errors->first('assigned_to') }}</span>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">{{ __('ingaz.complaint_details') }} <span class="required-star">*</span></label>
                    <textarea name="details" class="form-control {{ $errors->has('details') ? 'is-invalid' : '' }}" rows="5" required
                              placeholder="{{ __('ingaz.complaint_details_placeholder') }}" data-validation="required">{{ old('details') }}</textarea>
                    <span class="text-danger small d-block mt-1" data-field="details">{{ $errors->first('details') }}</span>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">{{ __('ingaz.attachments') }}</label>
                    <input type="file" name="attachments[]" class="form-control {{ $errors->has('attachments') ? 'is-invalid' : '' }}" multiple
                           accept="image/*,application/pdf,.doc,.docx">
                    <small class="text-muted">{{ __('ingaz.attachments_hint') }}</small>
                    <span class="text-danger small d-block mt-1" data-field="attachments">{{ $errors->first('attachments') }}</span>
                </div>
            </div>

            <div class="text-end mt-4">
                <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>{{ __('buttons.cancel') }}
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>{{ __('ingaz.save_complaint') }}
                </button>
            </div>
        </form>
    </div>
</div>

@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    const complaintFormTranslations = {
        selectPlaceholder: @json(__('ingaz.select_placeholder')),
        noResults: @json(__('ingaz.no_results')),
        searching: @json(__('ingaz.searching')),
        phoneInvalid: @json(__('ingaz.complaint_phone_invalid')),
    };

    // تهيئة Select2
    $('.select2').select2({
        placeholder: complaintFormTranslations.selectPlaceholder,
        allowClear: true,
        dir: 'rtl',
        width: '100%',
        language: {
            noResults: function() {
                return complaintFormTranslations.noResults;
            },
            searching: function() {
                return complaintFormTranslations.searching;
            }
        }
    });

    // عند اختيار عميل مسجل، املأ البيانات تلقائياً
    $('select[name="user_id"]').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            var userName = selectedOption.data('name');
            var userPhone = selectedOption.data('phone');

            $('input[name="complainant_name"]').val(userName);
            $('input[name="complainant_phone"]').val(userPhone);
        }
    });

    // تحقق من رقم الجوال
    $('input[name="complainant_phone"]').on('blur', function() {
        var phone = $(this).val();
        if (phone && !phone.match(/^(05|5)[0-9]{8}$/)) {
            toastr.warning(complaintFormTranslations.phoneInvalid);
            $(this).focus();
        }
    });

    // تغيير لون الأولوية
    $('select[name="priority"]').on('change', function() {
        var priority = $(this).val();
        $(this).removeClass('text-info text-warning text-danger');

        switch(priority) {
            case 'low':
                $(this).addClass('text-info');
                break;
            case 'medium':
                $(this).addClass('text-warning');
                break;
            case 'high':
            case 'urgent':
                $(this).addClass('text-danger');
                break;
        }
    });

    function setFieldInvalid(fieldName, message) {
        var $input = $('#complaintForm [name="' + fieldName + '"]');
        $input.addClass('is-invalid');
        // select2: لون الحد على الـ container
        $input.next('.select2-container').find('.select2-selection').css('border-color', '#dc3545');
        $('#complaintForm [data-field="' + fieldName + '"]').text(message);
    }

    function clearFieldInvalid(fieldName) {
        var $input = $('#complaintForm [name="' + fieldName + '"]');
        $input.removeClass('is-invalid');
        $input.next('.select2-container').find('.select2-selection').css('border-color', '');
        $('#complaintForm [data-field="' + fieldName + '"]').text('');
    }

    // إزالة is-invalid عند الكتابة أو التغيير
    $('#complaintForm').on('input change', 'input, select, textarea', function() {
        clearFieldInvalid($(this).attr('name'));
    });

    // التحقق من الحقول المطلوبة قبل الإرسال
    var requiredFields = [
        { name: 'complainant_name',  label: 'اسم مقدم الشكوى' },
        { name: 'complainant_phone', label: 'رقم الجوال' },
        { name: 'subject',           label: 'موضوع الشكوى' },
        { name: 'complaint_type_id', label: 'نوع الشكوى' },
        { name: 'details',           label: 'تفاصيل الشكوى' },
        { name: 'priority',          label: 'الأولوية' },
    ];

    $('#complaintForm').on('submit', function(e) {
        var hasError = false;

        $.each(requiredFields, function(i, field) {
            var val = $('#complaintForm [name="' + field.name + '"]').val();
            if (!val || val.toString().trim() === '') {
                setFieldInvalid(field.name, 'حقل ' + field.label + ' مطلوب');
                hasError = true;
            }
        });

        // تحقق إضافي من صيغة رقم الجوال
        var phoneVal = $('input[name="complainant_phone"]').val();
        if (phoneVal && !phoneVal.match(/^(05|5)[0-9]{8}$/)) {
            setFieldInvalid('complainant_phone', 'صيغة رقم الجوال غير صحيحة (مثال: 0512345678)');
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var $firstInvalid = $('#complaintForm .is-invalid').first();
            if ($firstInvalid.length) {
                $('html, body').animate({ scrollTop: $firstInvalid.offset().top - 120 }, 400);
            }
        }
    });
});
</script>
@endsection
