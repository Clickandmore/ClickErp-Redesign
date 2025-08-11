@extends('admin.layout.indexs.index')

@section('page-title')
إضافة شكوى جديدة
@endsection

@section('links')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('complaints.index') }}">الشكاوى</a></li>
    <li class="breadcrumb-item active">إضافة شكوى</li>
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
</style>
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_CREATE))

<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>
            تسجيل شكوى جديدة
        </h4>
    </div>
    <div class="card-body">
        <form action="{{ route('complaints.store') }}" method="POST" enctype="multipart/form-data" id="complaintForm" class="from-submit-global">
            @csrf

            <!-- معلومات مقدم الشكوى -->
            <div class="section-header">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    معلومات مقدم الشكوى
                </h5>
            </div>
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label class="form-label">اسم مقدم الشكوى <span class="required-star">*</span></label>
                    <input type="text" name="complainant_name" class="form-control" required
                           placeholder="أدخل اسم مقدم الشكوى" value="{{ old('complainant_name') }}"
                           data-validation="required">
                    @error('complainant_name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">رقم الجوال <span class="required-star">*</span></label>
                    <input type="tel" name="complainant_phone" class="form-control" required
                           placeholder="05XXXXXXXX" pattern="05[0-9]{8}" value="{{ old('complainant_phone') }}"
                           data-validation="required">
                    @error('complainant_phone')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">العميل المسجل (اختياري)</label>
                    <select name="user_id" class="form-select select2">
                        <option value="">-- اختر عميل مسجل --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}
                                    data-name="{{ $user->name }}" data-phone="{{ $user->phone }}">
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
                    معلومات العاملة
                </h5>
            </div>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label">رقم جواز العاملة</label>
                    <input type="text" name="maid_passport" class="form-control"
                           placeholder="رقم الجواز" value="{{ old('maid_passport') }}">
                    @error('maid_passport')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">جنسية العاملة</label>
                    <select name="maid_nationality_id" class="form-select select2">
                        <option value="">-- اختر الجنسية --</option>
                        @foreach($nationalities as $nationality)
                            <option value="{{ $nationality->id }}" {{ old('maid_nationality_id') == $nationality->id ? 'selected' : '' }}>
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
                    تفاصيل الشكوى
                </h5>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">موضوع الشكوى <span class="required-star">*</span></label>
                    <input type="text" name="subject" class="form-control" required
                           placeholder="موضوع الشكوى" value="{{ old('subject') }}"
                           data-validation="required">
                    @error('subject')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">نوع الشكوى <span class="required-star">*</span></label>
                    <select name="complaint_type_id" class="form-select" required data-validation="required">
                        <option value="">-- اختر نوع الشكوى --</option>
                        @foreach($complaintTypes as $type)
                            <option value="{{ $type->id }}" {{ old('complaint_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('complaint_type_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">الأولوية <span class="required-star">*</span></label>
                    <select name="priority" class="form-select" required data-validation="required">
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>
                            <i class="fas fa-circle text-info"></i> منخفضة
                        </option>
                        <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>
                            <i class="fas fa-circle text-warning"></i> متوسطة
                        </option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>
                            <i class="fas fa-circle text-danger"></i> عالية
                        </option>
                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>
                            <i class="fas fa-exclamation-circle text-danger"></i> عاجلة
                        </option>
                    </select>
                    @error('priority')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-8 mb-3">
                    <label class="form-label">الموظف المختص</label>
                    <select name="assigned_to" class="form-select select2">
                        <option value="">-- اختر الموظف المختص --</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}" {{ old('assigned_to') == $admin->id ? 'selected' : '' }}>
                                {{ $admin->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_to')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">تفاصيل الشكوى <span class="required-star">*</span></label>
                    <textarea name="details" class="form-control" rows="5" required
                              placeholder="اكتب تفاصيل الشكوى هنا..." data-validation="required">{{ old('details') }}</textarea>
                    @error('details')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">المرفقات</label>
                    <input type="file" name="attachments[]" class="form-control" multiple
                           accept="image/*,application/pdf,.doc,.docx">
                    <small class="text-muted">يمكنك رفع عدة ملفات (صور، PDF، Word) - الحد الأقصى 5 ميجا لكل ملف</small>
                    @error('attachments')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="text-end mt-4">
                <a href="{{ route('complaints.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>حفظ الشكوى
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
    // تهيئة Select2
    $('.select2').select2({
        placeholder: 'اختر...',
        allowClear: true,
        dir: 'rtl',
        width: '100%',
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            },
            searching: function() {
                return "جاري البحث...";
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
        if (phone && !phone.match(/^05[0-9]{8}$/)) {
            toastr.warning('رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام');
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
});
</script>
@endsection
