@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.edit Recruitment contract")}}
@endsection

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="{{asset("admin")}}/custom/status-badges.css">
@endsection

@section('content')

@if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT))

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        تعديل العقد رقم: {{ $contract->id }}
                    </h4>
                </div>
                <div class="card-body">
                    <form id="editContractForm" method="post" action="{{ route('RecruitmentContract.update', $contract->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- معلومات العميل -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user me-2"></i>
                                            معلومات العميل
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.client_name")}} <span class="text-danger">*</span></label>
                                                <input type="text" name="client_name" id="client_name" class="form-control" value="{{ $contract->user->name ?? '' }}" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.client_phone")}} <span class="text-danger">*</span></label>
                                                <input type="tel" name="client_phone" id="client_phone" class="form-control" value="{{ $contract->user->phone ?? '' }}" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.email")}}</label>
                                                <input type="email" name="client_email" id="client_email" class="form-control" value="{{ $contract->user->email ?? '' }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.nationality_id")}} <span class="text-danger">*</span></label>
                                                <select name="client_nationality_id" id="client_nationality_id" class="form-select" required>
                                                    <option value="">{{helperTrans('ingaz.choose')}}</option>
                                                    @foreach($countries as $country)
                                                        <option value="{{$country->id}}" {{ ($contract->user->nationality_id ?? '') == $country->id ? 'selected' : '' }}>
                                                            {{$country->name_ar}}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.national_num")}} <span class="text-danger">*</span></label>
                                                <input type="text" name="client_national_num" id="client_national_num" class="form-control" value="{{ $contract->user->national_num ?? '' }}" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.address")}}</label>
                                                <input type="text" name="client_address" id="client_address" class="form-control" value="{{ $contract->user->address ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- معلومات العاملة -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user-tie me-2"></i>
                                            معلومات العاملة
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">اسم العاملة</label>
                                                <input type="text" name="maid_name" id="maid_name" class="form-control" value="{{ $contract->maid->name ?? '' }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">رقم الجواز</label>
                                                <input type="text" name="maid_passport" id="maid_passport" class="form-control" value="{{ $contract->maid->passport_num ?? '' }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">الجنسية</label>
                                                <input type="text" name="maid_nationality" id="maid_nationality" class="form-control" value="{{ $contract->maid->nationality->title ?? '' }}" readonly>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">الديانة</label>
                                                <input type="text" name="maid_religion" id="maid_religion" class="form-control" value="{{ $contract->maid->religion->title ?? '' }}" readonly>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">المهنة</label>
                                                <input type="text" name="maid_job" id="maid_job" class="form-control" value="{{ $contract->maid->jobTitles->first()->title ?? '' }}" readonly>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">الراتب</label>
                                                <input type="number" name="maid_salary" id="maid_salary" class="form-control" value="{{ $contract->maid->salary ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- معلومات العقد -->
                            <div class="col-12 mb-4">
                                <div class="card">
                                    <div class="card-header bg-warning text-dark">
                                        <h5 class="mb-0">
                                            <i class="fas fa-file-contract me-2"></i>
                                            معلومات العقد
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.contract_date")}} <span class="text-danger">*</span></label>
                                                <input type="date" name="contract_date" id="contract_date" class="form-control" value="{{ $contract->info->contract_date ?? '' }}" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.msaned_number")}} <span class="text-danger">*</span></label>
                                                <input type="text" name="msaned_number" id="msaned_number" class="form-control" value="{{ $contract->info->msaned_number ?? '' }}" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.visa_number")}} <span class="text-danger">*</span></label>
                                                <input type="text" name="visa_number" id="visa_number" class="form-control" value="{{ $contract->info->visa_number ?? '' }}" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.tafwed_number")}}</label>
                                                <input type="text" name="tafwed_number" id="tafwed_number" class="form-control" value="{{ $contract->info->tafwed_number ?? '' }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.visa_milad_date")}}</label>
                                                <input type="date" name="visa_milad_date" id="visa_milad_date" class="form-control" value="{{ $contract->info->visa_milad_date ?? '' }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.visa_hijry_date")}}</label>
                                                <input type="date" name="visa_hijry_date" id="visa_hijry_date" class="form-control" value="{{ $contract->info->visa_hijry_date ?? '' }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">{{helperTrans("ingaz.contract_duration")}} <span class="text-danger">*</span></label>
                                                <select name="contract_duration" id="contract_duration" class="form-select" required>
                                                    <option value="1" {{ ($contract->info->contract_duration ?? '') == 1 ? 'selected' : '' }}>سنة واحدة</option>
                                                    <option value="2" {{ ($contract->info->contract_duration ?? '') == 2 ? 'selected' : '' }}>سنتان</option>
                                                    <option value="3" {{ ($contract->info->contract_duration ?? '') == 3 ? 'selected' : '' }}>3 سنوات</option>
                                                    <option value="4" {{ ($contract->info->contract_duration ?? '') == 4 ? 'selected' : '' }}>4 سنوات</option>
                                                    <option value="5" {{ ($contract->info->contract_duration ?? '') == 5 ? 'selected' : '' }}>5 سنوات</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">حالة العقد</label>
                                                <select name="status" id="status" class="form-select">
                                                    <option value="new" {{ $contract->status == 'new' ? 'selected' : '' }}>عقد جديد</option>
                                                    <option value="msaned" {{ $contract->status == 'msaned' ? 'selected' : '' }}>عقود مساند</option>
                                                    <option value="tafwed" {{ $contract->status == 'tafwed' ? 'selected' : '' }}>تفويض</option>
                                                    <option value="taffez" {{ $contract->status == 'taffez' ? 'selected' : '' }}>التفييز</option>
                                                    <option value="tazker" {{ $contract->status == 'tazker' ? 'selected' : '' }}>تذكرة</option>
                                                    <option value="under_warranty" {{ $contract->status == 'under_warranty' ? 'selected' : '' }}>تحت الضمان</option>
                                                    <option value="arrival" {{ $contract->status == 'arrival' ? 'selected' : '' }}>مرحلة الوصول</option>
                                                    <option value="sari" {{ $contract->status == 'sari' ? 'selected' : '' }}>عقد ساري</option>
                                                    <option value="closed" {{ $contract->status == 'closed' ? 'selected' : '' }}>عقد مغلق</option>
                                                    <option value="shelter" {{ $contract->status == 'shelter' ? 'selected' : '' }}>عقد إيواء</option>
                                                    <option value="back" {{ $contract->status == 'back' ? 'selected' : '' }}>مرتجع</option>
                                                    <option value="pending_maid_selection" {{ $contract->status == 'pending_maid_selection' ? 'selected' : '' }}>بانتظار اختيار العاملة</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- أزرار الحفظ -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <button type="submit" class="btn btn-primary btn-lg px-5 me-3">
                                            <i class="fas fa-save me-2"></i>
                                            {{__('buttons.save')}}
                                        </button>
                                        <a href="{{ route('RecruitmentContract.index') }}" class="btn btn-secondary btn-lg px-5">
                                            <i class="fas fa-arrow-right me-2"></i>
                                            {{__('buttons.back')}}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@else
    @include('components.no_permissions')
@endif

@endsection

@section('js')
<script>
$(document).ready(function() {
    // تهيئة Select2
    $('#client_nationality_id').select2({
        placeholder: 'اختر الجنسية...',
        allowClear: true,
        dir: 'rtl',
        width: '100%'
    });

    // معالجة تقديم النموذج
    $('#editContractForm').on('submit', function(e) {
        e.preventDefault();

        // التحقق من الحقول المطلوبة
        let isValid = true;
        $('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'يرجى ملء جميع الحقول المطلوبة',
                confirmButtonText: 'حسناً'
            });
            return false;
        }

        // إرسال النموذج
        let formData = new FormData(this);
        let submitButton = $(this).find('button[type="submit"]');

        submitButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i>جاري الحفظ...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: response.message || 'تم تحديث العقد بنجاح',
                        confirmButtonText: 'حسناً'
                    }).then((result) => {
                        window.location.href = "{{ route('RecruitmentContract.index') }}";
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: response.message || 'حدث خطأ أثناء تحديث العقد',
                        confirmButtonText: 'حسناً'
                    });
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'حدث خطأ أثناء تحديث العقد';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors).flat().join('\n');
                    }
                }

                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: errorMessage,
                    confirmButtonText: 'حسناً'
                });
            },
            complete: function() {
                submitButton.prop('disabled', false).html('<i class="fas fa-save me-2"></i>{{__("buttons.save")}}');
            }
        });
    });
});
</script>
@endsection
