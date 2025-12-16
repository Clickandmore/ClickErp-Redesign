@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.add_completed_contract")}}
@endsection

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('content') 

@if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE))

    <form id="completedContractForm" class="row g-3 contractForm" method="post" action="{{route("Contract.createCompleted")}}">
        @csrf
        <input type="hidden" name="contract_status" value="completed">

        <!-- معلومات العميل -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user me-2"></i>معلومات العميل</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="existing_client_id" class="form-label">اختر العميل <span class="text-danger">*</span></label>
                            <select name="existing_client_id" id="existing_client_id" class="form-select select2-ajax-client" style="width:100%" required>
                                <option value="" selected disabled>اختر عميل...</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- معلومات العاملة -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-user-tie me-2"></i>معلومات العاملة</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="maid_id" class="form-label">اختر العاملة <span class="text-danger">*</span></label>
                            <select name="maid_id" id="maid_id" class="form-select select2-ajax-maid" style="width:100%" required>
                                <option value="" selected disabled>اختر عاملة...</option>
                            </select>
                        </div>
                    </div>

                    <!-- تفاصيل العاملة المختارة -->
                    <div id="maid_details" class="row" style="display: none;">
                        <div class="col-md-12">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>تفاصيل العاملة المختارة</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <strong class="text-primary">الاسم:</strong>
                                            <span id="maid_name_display" class="ms-2"></span>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <strong class="text-primary">رقم الجواز:</strong>
                                            <span id="maid_passport_display" class="ms-2"></span>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <strong class="text-primary">الجنسية:</strong>
                                            <span id="maid_nationality_display" class="ms-2"></span>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <strong class="text-primary">العمر:</strong>
                                            <span id="maid_age_display" class="ms-2"></span>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <strong class="text-primary">الراتب:</strong>
                                            <span id="maid_salary_display" class="ms-2"></span>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <strong class="text-primary">سنوات الخبرة:</strong>
                                            <span id="maid_experience_display" class="ms-2"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- معلومات العقد -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-file-contract me-2"></i>معلومات العقد</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contract_date" class="form-label">تاريخ العقد <span class="text-danger">*</span></label>
                            <input type="date" name="contract_date" id="contract_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="arrival_date" class="form-label">تاريخ الوصول <span class="text-danger">*</span></label>
                            <input type="date" name="arrival_date" id="arrival_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="visa_number" class="form-label">رقم التأشيرة <span class="text-danger">*</span></label>
                            <input type="text" name="visa_number" id="visa_number" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="msaned_number" class="form-label">رقم عقد مساند</label>
                            <input type="text" name="msaned_number" id="msaned_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="client_national_num" class="form-label">رقم هوية العميل</label>
                            <input type="text" name="client_national_num" id="client_national_num" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="client_birthdate" class="form-label">تاريخ ميلاد العميل</label>
                            <input type="date" name="client_birthdate" id="client_birthdate" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- زر الحفظ -->
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <button class="btn btn-success btn-lg px-5" type="submit">
                        <i class="fas fa-check-circle me-2"></i>
                        {{helperTrans("ingaz.create_completed_contract")}}
                    </button>
                </div>
            </div>
        </div>
    </form>

@else
    @include('components.no_permissions')
@endif

@endsection

@section('js')
<script>
$(document).ready(function() {
    // تهيئة Select2
    $('.select2').select2({
        placeholder: '{{helperTrans("ingaz.choose")}}',
        allowClear: true
    });

    // Select2 AJAX للعملاء - بدون minimumInputLength
    $('#existing_client_id').select2({
        placeholder: '{{helperTrans("ingaz.choose")}}',
        allowClear: true,
        ajax: {
            url: '{{ route("Contract.searchClients") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term || ''
                };
            },
            processResults: function (data) {
                return {
                    results: data.results || []
                };
            },
            cache: true
        }
        // تم إزالة minimumInputLength
    });

    // Select2 AJAX للعاملة - بدون minimumInputLength
    $('#maid_id').select2({
        placeholder: '{{helperTrans("ingaz.choose")}}',
        allowClear: true,
        ajax: {
            url: '{{ route("Contract.searchMaids") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term || ''
                };
            },
            processResults: function (data) {
                return {
                    results: data.maids.map(function(maid) {
                        return {
                            id: maid.id,
                            text: maid.text,
                            maid: maid.maid
                        };
                    })
                };
            },
            cache: true
        }
        // تم إزالة minimumInputLength
    });

    // تحميل جميع العملاء عند فتح الصفحة
    $('#existing_client_id').on('select2:open', function() {
        if ($(this).find('option').length <= 1) {
            // تحميل جميع العملاء إذا لم يتم تحميلهم بعد
            $.ajax({
                url: '{{ route("Contract.searchClients") }}',
                dataType: 'json',
                data: { search: '' },
                success: function(data) {
                    var $select = $('#existing_client_id');
                    $select.empty();
                    $select.append('<option value="" selected disabled>{{helperTrans("ingaz.choose")}}</option>');

                    if (data.results && data.results.length > 0) {
                        data.results.forEach(function(client) {
                            $select.append(new Option(client.text, client.id, false, false));
                        });
                    }
                }
            });
        }
    });

    // استيراد بيانات العميل عند اختياره
    $('#existing_client_id').on('select2:select', function(e) {
        var client = e.params.data;
        if (client) {
            // استيراد رقم الهوية
            if (client.national_num) {
                $('#client_national_num').val(client.national_num);
            }

            // استيراد تاريخ الميلاد
            if (client.birthdate) {
                $('#client_birthdate').val(client.birthdate);
            }
        }
    });

    // تحميل جميع العمالة عند فتح الصفحة
    $('#maid_id').on('select2:open', function() {
        if ($(this).find('option').length <= 1) {
            // تحميل جميع العمالة إذا لم يتم تحميلهم بعد
            $.ajax({
                url: '{{ route("Contract.searchMaids") }}',
                dataType: 'json',
                data: { search: '' },
                success: function(data) {
                    var $select = $('#maid_id');
                    $select.empty();
                    $select.append('<option value="" selected disabled>{{helperTrans("ingaz.choose")}}</option>');

                    if (data.maids && data.maids.length > 0) {
                        data.maids.forEach(function(maid) {
                            var option = new Option(maid.text, maid.id, false, false);
                            option.maid = maid.maid; // حفظ بيانات العاملة
                            $select.append(option);
                        });
                    }
                }
            });
        }
    });

    // عرض تفاصيل العاملة عند اختيارها - إصلاح
    $('#maid_id').on('select2:select', function(e) {
        var maid = e.params.data.maid;
        console.log('Selected maid:', maid); // للتأكد من البيانات

        if (maid) {
            $('#maid_name_display').text(maid.name || 'غير محدد');
            $('#maid_passport_display').text(maid.passport_num || 'غير محدد');
            $('#maid_nationality_display').text(maid.nationality || 'غير محدد');
            $('#maid_age_display').text(maid.age || 'غير محدد');
            $('#maid_salary_display').text(maid.salary || 'غير محدد');
            $('#maid_experience_display').text(maid.experience_years || 'غير محدد');
            $('#maid_details').show();
        }
    });

    // عرض تفاصيل العاملة عند تغيير الاختيار (للخيارات المحملة مسبقاً)
    $('#maid_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var maid = selectedOption.data('maid') || selectedOption.attr('data-maid');

        if (maid && typeof maid === 'object') {
            $('#maid_name_display').text(maid.name || 'غير محدد');
            $('#maid_passport_display').text(maid.passport_num || 'غير محدد');
            $('#maid_nationality_display').text(maid.nationality || 'غير محدد');
            $('#maid_age_display').text(maid.age || 'غير محدد');
            $('#maid_salary_display').text(maid.salary || 'غير محدد');
            $('#maid_experience_display').text(maid.experience_years || 'غير محدد');
            $('#maid_details').show();
        } else {
            $('#maid_details').hide();
        }
    });

    // إخفاء تفاصيل العاملة عند مسح الاختيار
    $('#maid_id').on('select2:clear', function(e) {
        $('#maid_details').hide();
    });

    // معالجة إرسال النموذج
    $('#completedContractForm').on('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);
        let submitButton = $(this).find('button[type="submit"]');
        let originalText = submitButton.html();

        // إظهار loading
        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>جاري الإنشاء...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '{{helperTrans("ingaz.success")}}',
                        text: response.message + ' {{helperTrans("ingaz.redirecting_to_shelter")}}',
                        confirmButtonText: '{{helperTrans("ingaz.ok")}}'
                    }).then((result) => {
                        // التوجيه إلى صفحة إضافة الإيواء مع اختيار العقد الجديد
                        if (response.redirect_url) {
                            window.location.href = response.redirect_url;
                        } else {
                            window.location.href = '{{ route("RecruitmentContract.index") }}';
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '{{helperTrans("ingaz.error")}}',
                        text: response.message,
                        confirmButtonText: '{{helperTrans("ingaz.ok")}}'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = '{{helperTrans("ingaz.error_creating_contract")}}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: '{{helperTrans("ingaz.error")}}',
                    text: errorMessage,
                    confirmButtonText: '{{helperTrans("ingaz.ok")}}'
                });
            },
            complete: function() {
                submitButton.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endsection
