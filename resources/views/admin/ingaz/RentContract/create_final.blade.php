@extends('admin.layout.indexs.index')
@section('page-title')
    {{helperTrans("ingaz.add Rent contract")}}
@endsection

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('admin/custom/admin-tables.css') }}">
@endsection

@section('up-card')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    <i class="fas fa-file-contract me-2"></i>
                    إضافة عقد تأجير جديد
                </h4>
            </div>
            <div class="card-body">
                <form id="rentalContractForm" action="{{ route('RentContract.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Client Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-user me-2"></i>
                                بيانات العميل
                            </h5>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    اختر عميل موجود
                                </label>
                                <select name="new_existing_client_id" id="new_existing_client_id" class="form-select select2" style="width:100%" required>
                                    <option value="" selected disabled>اختر عميل...</option>
                                    @if(isset($existing_clients))
                                        @foreach($existing_clients as $client)
                                            <option value="{{$client->id}}"
                                                data-name="{{$client->name}}"
                                                data-phone="{{$client->phone}}"
                                                data-email="{{$client->email}}"
                                                data-nationality="{{$client->nationality_id}}"
                                                data-national-num="{{$client->national_num}}"
                                                data-birthdate="{{$client->birthdate}}"
                                                data-phone2="{{$client->phone2}}"
                                                data-address="{{$client->address}}"
                                                data-city="{{$client->city}}"
                                                data-beds-num="{{$client->rooms_num}}"
                                                data-family-num="{{$client->family_num}}"
                                                data-children="{{$client->children}}"
                                                data-job="{{$client->job}}">
                                                {{$client->name}} - {{$client->phone}} - {{$client->national_num}}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <a href="{{ route('clients.create') }}" target="_blank" class="btn btn-outline-primary mt-2" title="إضافة عميل جديد">
                                    <i class="fas fa-user-plus"></i> إضافة عميل جديد
                                </a>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    اسم العميل
                                </label>
                                <input type="text" class="form-control" id="client_name" name="client_name" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    رقم الجوال
                                </label>
                                <input type="text" class="form-control" id="client_phone" name="client_phone" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    رقم الهوية
                                </label>
                                <input type="text" class="form-control" id="client_national_num" name="client_national_num" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    الجنسية
                                </label>
                                <select class="form-select select2" id="client_nationality_id" name="client_nationality_id" required>
                                    <option value="">اختر الجنسية</option>
                                    @foreach($nationalities as $nationality)
                                        <option value="{{$nationality->id}}">
                                            {{ $nationality->translations->first()->title ?? $nationality->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="client_email" name="client_email">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    تاريخ الميلاد
                                </label>
                                <input type="date" class="form-control" id="client_birthdate" name="client_birthdate" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    المهنة
                                </label>
                                <input type="text" class="form-control" id="profession" name="profession" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">الدخل الشهري</label>
                                <input type="number" class="form-control" id="monthly_income" name="monthly_income" step="0.01">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">العنوان</label>
                                <input type="text" class="form-control" id="client_address" name="client_address">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">المدينة</label>
                                <input type="text" class="form-control" id="client_city" name="client_city">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">عدد أفراد الأسرة</label>
                                <input type="number" class="form-control" id="family_num" name="family_num">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">عدد الأطفال</label>
                                <input type="number" class="form-control" id="children" name="children">
                            </div>
                        </div>
                    </div>

                    <!-- Maid Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-female me-2"></i>
                                بيانات العاملة
                            </h5>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    اختر عاملة
                                </label>
                                <select name="maid_id" id="maid_id" class="form-select select2" required>
                                    <option value="" selected disabled>اختر عاملة...</option>
                                    @if(isset($availableRentalMaids))
                                        @foreach($availableRentalMaids as $maid)
                                            <option value="{{$maid->id}}"
                                                data-name="{{$maid->name}}"
                                                data-passport="{{$maid->passport_num}}"
                                                data-nationality="{{$maid->nationality->title ?? ''}}"
                                                data-age="{{$maid->age}}"
                                                data-experience="{{$maid->experience_years}}"
                                                data-salary="{{$maid->salary}}"
                                                data-photo="{{$maid->photo}}">
                                                {{$maid->name}} - {{$maid->passport_num}} - {{$maid->nationality->title ?? ''}}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Contract Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-file-contract me-2"></i>
                                بيانات العقد
                            </h5>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    تاريخ العقد
                                </label>
                                <input type="date" class="form-control" id="contract_date" name="contract_date" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">عدد ساعات العمل</label>
                                <input type="number" class="form-control" id="working_hours" name="working_hours" step="0.5">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">أجرة الساعة</label>
                                <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" step="0.01">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">الراتب الشهري</label>
                                <input type="number" class="form-control" id="monthly_salary" name="monthly_salary" step="0.01">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">مبلغ نقل الكفالة</label>
                                <input type="number" class="form-control" id="sponsorship_transfer" name="sponsorship_transfer" step="0.01">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    التكلفة الشهرية
                                </label>
                                <input type="number" class="form-control" id="monthly_cost" name="monthly_cost" step="0.01" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">مدة العقد (بالشهور)</label>
                                <input type="number" class="form-control" id="contract_duration_months" name="contract_duration_months" step="0.1">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">مدة العقد (بالأيام)</label>
                                <input type="number" class="form-control" id="contract_duration_days" name="contract_duration_days">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">قيمة العقد الإجمالية</label>
                                <input type="number" class="form-control" id="contract_value" name="contract_value" step="0.01" readonly>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">تاريخ بداية الإنجاز</label>
                                <input type="date" class="form-control" id="ingaz_start_date" name="ingaz_start_date">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">تاريخ نهاية الإنجاز</label>
                                <input type="date" class="form-control" id="ingaz_end_date" name="ingaz_end_date">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    المسوق
                                </label>
                                <select class="form-select select2" id="marketer_id" name="marketer_id" required>
                                    <option value="">اختر المسوق</option>
                                    @foreach($marketers as $marketer)
                                        <option value="{{$marketer->id}}" {{ auth()->id() == $marketer->id ? 'selected' : '' }}>
                                            {{$marketer->name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row">
                        <div class="col-12 text-center">
                            <a href="{{ route('RentContract.index') }}" class="btn btn-secondary me-2">
                                <i class="fas fa-times me-2"></i>
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <i class="fas fa-save me-2"></i>
                                حفظ عقد التأجير
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // تهيئة Select2
    $('.select2').select2({
        placeholder: 'اختر...',
        allowClear: true,
        dir: 'rtl',
        width: '100%',
        language: {
            noResults: function() { return "لا توجد نتائج"; },
            searching: function() { return "جاري البحث..."; }
        }
    });

    // معالج تغيير العميل
    $('#new_existing_client_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');

        if (selectedOption.val()) {
            $('#client_name').val(selectedOption.data('name') || '');
            $('#client_phone').val(selectedOption.data('phone') || '');
            $('#client_email').val(selectedOption.data('email') || '');
            $('#client_national_num').val(selectedOption.data('national-num') || '');
            $('#client_birthdate').val(selectedOption.data('birthdate') || '');
            $('#client_address').val(selectedOption.data('address') || '');
            $('#client_city').val(selectedOption.data('city') || '');
            $('#family_num').val(selectedOption.data('family-num') || '');
            $('#children').val(selectedOption.data('children') || '');
            $('#profession').val(selectedOption.data('job') || '');

            var nationalityId = selectedOption.data('nationality');
            if (nationalityId) {
                $('#client_nationality_id').val(nationalityId).trigger('change');
            }
        } else {
            // مسح البيانات
            $('#client_name, #client_phone, #client_email, #client_national_num, #client_birthdate, #client_address, #client_city, #family_num, #children, #profession').val('');
            $('#client_nationality_id').val('').trigger('change');
        }
    });

    // حساب قيمة العقد
    function calculateContractValue() {
        var monthlyCost = parseFloat($('#monthly_cost').val()) || 0;
        var durationMonths = parseFloat($('#contract_duration_months').val()) || 0;
        var totalValue = monthlyCost * durationMonths;
        $('#contract_value').val(totalValue.toFixed(2));
    }

    $('#monthly_cost, #contract_duration_months').on('input', calculateContractValue);

    // معالج إرسال النموذج
    $('#rentalContractForm').on('submit', function(e) {
        e.preventDefault();

        // التحقق من البيانات الأساسية
        if (!$('#new_existing_client_id').val() && !$('#client_name').val()) {
            Swal.fire('خطأ', 'يرجى اختيار عميل', 'error');
            return;
        }

        if (!$('#maid_id').val()) {
            Swal.fire('خطأ', 'يرجى اختيار عاملة', 'error');
            return;
        }

        // إرسال النموذج
        const submitBtn = $('#submit-btn');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم بنجاح',
                    text: 'تم إنشاء عقد التأجير بنجاح'
                }).then(() => {
                    window.location.href = "{{ route('RentContract.index') }}";
                });
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء حفظ العقد';

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors)[0][0];
                }

                Swal.fire('خطأ', errorMessage, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endsection
