@extends('admin.layout.indexs.index')

@section('page-title')
{{ __('ingaz.complaint_edit') }}
@endsection

@section('links')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">{{ __('ingaz.dashboard_home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('complaints.index') }}">{{ __('ingaz.complaints') }}</a></li>
    <li class="breadcrumb-item active">{{ __('ingaz.complaint_edit') }}</li>
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
    .current-attachment {
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
        margin-bottom: 10px;
    }
    .current-attachment a {
        color: #667eea;
        text-decoration: none;
    }
    .current-attachment a:hover {
        text-decoration: underline;
    }
    .status-info {
        padding: 15px;
        background: #f0f8ff;
        border-radius: 8px;
        border-left: 4px solid #17a2b8;
        margin-bottom: 20px;
    }
</style>
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_EDIT))

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">
            <i class="fas fa-edit me-2"></i>
            {{ __('ingaz.complaint_edit_number', ['number' => str_pad($complaint->id, 6, '0', STR_PAD_LEFT)]) }}
        </h4>
    </div>
    <div class="card-body">
        <!-- معلومات الحالة الحالية -->
        <div class="status-info">
            <div class="row">
                <div class="col-md-3">
                    <strong>{{ __('ingaz.current_status') }}:</strong>
                    @php
                        $statusLabels = [
                            'pending' => __('ingaz.complaint_status_pending'),
                            'in_progress' => __('ingaz.complaint_status_in_progress'),
                            'resolved' => __('ingaz.complaint_status_resolved'),
                            'closed' => __('ingaz.complaint_status_closed'),
                            'rejected' => __('ingaz.complaint_status_rejected')
                        ];
                    @endphp
                    <span class="badge bg-info">{{ $statusLabels[$complaint->status] ?? $complaint->status }}</span>
                </div>
                <div class="col-md-3">
                    <strong>{{ __('ingaz.created_at_label') }}:</strong>
                    {{ $complaint->created_at->format('Y-m-d H:i') }}
                </div>
                <div class="col-md-3">
                    <strong>{{ __('ingaz.last_updated_at') }}:</strong>
                    {{ $complaint->updated_at->format('Y-m-d H:i') }}
                </div>
                <div class="col-md-3">
                    <strong>{{ __('ingaz.assigned_employee') }}:</strong>
                    {{ $complaint->assignedTo ? $complaint->assignedTo->name : __('ingaz.not_specified') }}
                </div>
            </div>
        </div>

        <form action="{{ route('complaints.update', $complaint->id) }}" method="POST" enctype="multipart/form-data" id="complaintEditForm" class="from-submit-global">
            @csrf
            @method('PUT')

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
                    <input type="text" name="complainant_name" class="form-control" required
                           placeholder="{{ __('ingaz.complainant_name_placeholder') }}" value="{{ old('complainant_name', $complaint->complainant_name) }}"
                           data-validation="required">
                    @error('complainant_name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('ingaz.phone') }} <span class="required-star">*</span></label>
                    <input type="tel" name="complainant_phone" class="form-control" required
                           placeholder="05XXXXXXXX" pattern="05[0-9]{8}" value="{{ old('complainant_phone', $complaint->complainant_phone) }}"
                           data-validation="required">
                    @error('complainant_phone')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('ingaz.registered_client_optional') }}</label>
                    <select name="user_id" class="form-select select2">
                        <option value="">{{ __('ingaz.select_registered_client') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                    {{ old('user_id', $complaint->user_id) == $user->id ? 'selected' : '' }}
                                    data-name="{{ $user->name }}"
                                    data-phone="{{ $user->phone }}">
                                {{ $user->name }} - {{ $user->phone }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
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
                    <input type="text" name="maid_passport" class="form-control"
                           placeholder="{{ __('ingaz.passport_number_placeholder') }}" value="{{ old('maid_passport', $complaint->maid_passport) }}">
                    @error('maid_passport')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('ingaz.worker_nationality') }}</label>
                    <select name="maid_nationality_id" class="form-select select2">
                        <option value="">{{ __('ingaz.select_nationality') }}</option>
                        @foreach($nationalities as $nationality)
                            <option value="{{ $nationality->id }}"
                                    {{ old('maid_nationality_id', $complaint->maid_nationality_id) == $nationality->id ? 'selected' : '' }}>
                                {{ $nationality->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('maid_nationality_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
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
                    <input type="text" name="subject" class="form-control" required
                           placeholder="{{ __('ingaz.complaint_subject_placeholder') }}" value="{{ old('subject', $complaint->subject) }}"
                           data-validation="required">
                    @error('subject')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('ingaz.complaint_type') }} <span class="required-star">*</span></label>
                    <select name="complaint_type_id" class="form-select" required data-validation="required">
                        <option value="">{{ __('ingaz.select_complaint_type') }}</option>
                        @foreach($complaintTypes as $type)
                            <option value="{{ $type->id }}"
                                    {{ old('complaint_type_id', $complaint->complaint_type_id) == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('complaint_type_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('ingaz.priority') }} <span class="required-star">*</span></label>
                    <select name="priority" class="form-select" required data-validation="required">
                        <option value="low" {{ old('priority', $complaint->priority) == 'low' ? 'selected' : '' }}>
                            {{ __('ingaz.priority_low') }}
                        </option>
                        <option value="medium" {{ old('priority', $complaint->priority) == 'medium' ? 'selected' : '' }}>
                            {{ __('ingaz.priority_medium') }}
                        </option>
                        <option value="high" {{ old('priority', $complaint->priority) == 'high' ? 'selected' : '' }}>
                            {{ __('ingaz.priority_high') }}
                        </option>
                        <option value="urgent" {{ old('priority', $complaint->priority) == 'urgent' ? 'selected' : '' }}>
                            {{ __('ingaz.priority_urgent') }}
                        </option>
                    </select>
                    @error('priority')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('ingaz.status') }}</label>
                    <select name="status" class="form-select">
                        <option value="pending" {{ old('status', $complaint->status) == 'pending' ? 'selected' : '' }}>
                            {{ __('ingaz.complaint_status_pending') }}
                        </option>
                        <option value="in_progress" {{ old('status', $complaint->status) == 'in_progress' ? 'selected' : '' }}>
                            {{ __('ingaz.complaint_status_in_progress') }}
                        </option>
                        <option value="resolved" {{ old('status', $complaint->status) == 'resolved' ? 'selected' : '' }}>
                            {{ __('ingaz.complaint_status_resolved') }}
                        </option>
                        <option value="closed" {{ old('status', $complaint->status) == 'closed' ? 'selected' : '' }}>
                            {{ __('ingaz.complaint_status_closed') }}
                        </option>
                        <option value="rejected" {{ old('status', $complaint->status) == 'rejected' ? 'selected' : '' }}>
                            {{ __('ingaz.complaint_status_rejected') }}
                        </option>
                    </select>
                    @error('status')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('ingaz.assigned_employee') }}</label>
                    <select name="assigned_to" class="form-select select2">
                        <option value="">{{ __('ingaz.select_assigned_employee') }}</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}"
                                    {{ old('assigned_to', $complaint->assigned_to) == $admin->id ? 'selected' : '' }}>
                                {{ $admin->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_to')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">{{ __('ingaz.complaint_details') }} <span class="required-star">*</span></label>
                    <textarea name="details" class="form-control" rows="5" required
                              placeholder="{{ __('ingaz.complaint_details_placeholder') }}" data-validation="required">{{ old('details', $complaint->details) }}</textarea>
                    @error('details')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">{{ __('ingaz.current_attachments') }}</label>
                    @if($complaint->attachments && is_array($complaint->attachments))
                        <div class="current-attachments">
                            @foreach($complaint->attachments as $attachment)
                                <div class="current-attachment">
                                    <i class="fas fa-paperclip me-2"></i>
                                    <a href="{{ Storage::url($attachment) }}" target="_blank">
                                        {{ basename($attachment) }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">{{ __('ingaz.no_attachments') }}</p>
                    @endif
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">{{ __('ingaz.add_new_attachments') }}</label>
                    <input type="file" name="attachments[]" class="form-control" multiple
                           accept="image/*,application/pdf,.doc,.docx">
                    <small class="text-muted">{{ __('ingaz.attachments_hint') }}</small>
                    @error('attachments')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="text-end mt-4">
                <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>{{ __('buttons.cancel') }}
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>{{ __('ingaz.save_changes') }}
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
    const complaintEditTranslations = {
        selectPlaceholder: @json(__('ingaz.select_placeholder')),
        noResults: @json(__('ingaz.no_results')),
        searching: @json(__('ingaz.searching')),
        updateFromClientConfirm: @json(__('ingaz.update_complainant_from_client_confirm')),
        phoneInvalid: @json(__('ingaz.complaint_phone_invalid')),
    };

    // تهيئة Select2
    $('.select2').select2({
        placeholder: complaintEditTranslations.selectPlaceholder,
        allowClear: true,
        dir: 'rtl',
        width: '100%',
        language: {
            noResults: function() {
                return complaintEditTranslations.noResults;
            },
            searching: function() {
                return complaintEditTranslations.searching;
            }
        }
    });

    // عند اختيار عميل مسجل، املأ البيانات تلقائياً
    $('select[name="user_id"]').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            var userName = selectedOption.data('name');
            var userPhone = selectedOption.data('phone');

            // اسأل المستخدم إذا كان يريد تحديث البيانات
            if (confirm(complaintEditTranslations.updateFromClientConfirm)) {
                $('input[name="complainant_name"]').val(userName);
                $('input[name="complainant_phone"]').val(userPhone);
            }
        }
    });

    // تحقق من رقم الجوال
    $('input[name="complainant_phone"]').on('blur', function() {
        var phone = $(this).val();
        if (phone && !phone.match(/^05[0-9]{8}$/)) {
            if (typeof toastr !== 'undefined') {
                toastr.warning(complaintEditTranslations.phoneInvalid);
            } else {
                alert(complaintEditTranslations.phoneInvalid);
            }
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
    }).trigger('change');

    // تغيير لون الحالة
    $('select[name="status"]').on('change', function() {
        var status = $(this).val();
        $(this).removeClass('text-info text-warning text-success text-danger');

        switch(status) {
            case 'pending':
                $(this).addClass('text-warning');
                break;
            case 'in_progress':
                $(this).addClass('text-info');
                break;
            case 'resolved':
                $(this).addClass('text-success');
                break;
            case 'closed':
            case 'rejected':
                $(this).addClass('text-danger');
                break;
        }
    }).trigger('change');
});
</script>
@endsection
