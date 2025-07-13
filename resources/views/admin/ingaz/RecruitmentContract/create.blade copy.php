@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.add Recruitment contract")}}
@endsection

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    .contract-type-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        overflow: hidden;
    }
    .contract-type-card:hover {
        border-color: #007bff;
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }
    .contract-type-card.selected {
        border-color: #007bff;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        box-shadow: 0 4px 12px rgba(0,123,255,0.2);
    }
    .contract-type-card .card-body {
        text-align: center;
        padding: 2.5rem 1.5rem;
    }
    .contract-type-card .icon {
        font-size: 3.5rem;
        margin-bottom: 1.5rem;
        color: #007bff;
        transition: all 0.3s ease;
    }
    .contract-type-card:hover .icon {
        transform: scale(1.1);
    }
    .contract-type-card h5 {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #333;
    }
    .contract-type-card p {
        color: #666;
        font-size: 0.9rem;
        line-height: 1.4;
    }
    .section-container {
        display: none;
        animation: fadeIn 0.5s ease-in-out;
    }
    .section-container.active {
        display: block;
    }
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .card-header {
        border-radius: 8px 8px 0 0 !important;
    }
    .form-control:focus, .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }
    .btn-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,123,255,0.3);
    }
    .alert {
        border-radius: 8px;
        border: none;
    }
    .alert-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        color: #0c5460;
    }
    .btn.loading {
        opacity: 0.8;
        cursor: not-allowed;
    }
    .btn.loading:hover {
        transform: none !important;
        box-shadow: none !important;
    }
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    .shake {
        animation: shake 0.5s ease-in-out;
    }
</style>
@endsection

@section('content')

@if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE))

    <form id="maidForm" class="row g-3 contractForm" method="post" action="{{route("RecruitmentContract.store")}}">
        @csrf
        <input type="hidden" name="client_type" value="ex_client" id="client_type">
        <input type="hidden" name="contract_type" id="contract_type" value="existing_order">

        <!-- اختيار نوع العقد (تصغير المساحة) -->
        <div class="col-12 mb-3">
            <div class="card" style="box-shadow:none; border-width:1px;">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="fw-bold"><i class="fas fa-file-contract me-2"></i>نوع العقد:</span>
                        </div>
                        <div class="col-auto">
                            <div class="d-flex gap-2">
                                <div class="contract-type-card px-3 py-2 small" data-type="existing_order" style="min-width:120px;">
                                    <span><i class="fas fa-list-check me-1"></i>طلب مسبق</span>
                                </div>
                                <div class="contract-type-card px-3 py-2 small" data-type="new_contract" style="min-width:120px;">
                                    <span><i class="fas fa-user-plus me-1"></i>عقد جديد</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- قسم الطلب المسبق -->
        <div id="existing-order-section" class="section-container col-12 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-list-check me-2"></i>
                        اختيار الطلب المسبق
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">{{helperTrans("ingaz.select_order")}} <span class="text-danger">*</span></label>
                            <select name="order_id" id="order_id" class="form-select form-control select2" data-validation="required">
                                <option value="" disabled selected>{{helperTrans('ingaz.choose')}}</option>
                                @foreach($orders as $order)
                                    <option value="{{$order->id}}"
                                        data-client-name="{{$order->user->name ?? ''}}"
                                        data-client-phone="{{$order->user->phone ?? ''}}"
                                        data-client-email="{{$order->user->email ?? ''}}"
                                        data-client-nationality="{{$order->user->nationality_id ?? ''}}"
                                        data-client-national-num="{{$order->user->national_num ?? ''}}"
                                        data-client-birthdate="{{$order->user->birthdate ?? ''}}"
                                        data-client-phone2="{{$order->user->phone2 ?? ''}}"
                                        data-client-address="{{$order->user->address ?? ''}}"
                                        data-client-city="{{$order->user->city ?? ''}}"
                                        data-client-beds-num="{{$order->user->rooms_num ?? ''}}"
                                        data-client-family-num="{{$order->user->family_num ?? ''}}"
                                        data-client-children="{{$order->user->children ?? ''}}"
                                        data-client-job="{{$order->user->job ?? ''}}"
                                        data-maid-name="{{$order->maid->name ?? ''}}"
                                        data-maid-passport="{{$order->maid->passport_num ?? ''}}"
                                        data-client-nationality="{{$order->user->nationality_id ?? ''}}"
                                        data-maid-id="{{$order->maid->id ?? ''}}"
                                        data-maid-nationality="{{$order->maid->nationality_id ?? ''}}"
                                        data-maid-religion="{{$order->maid->religion_id ?? ''}}"
                                        data-maid-job="{{$order->maid->job_types && $order->maid->job_types->first() && $order->maid->job_types->first()->translations && $order->maid->job_types->first()->translations->first() ? $order->maid->job_types->first()->translations->first()->title : ''}}"
                                        data-maid-salary="{{$order->maid->salary ?? ''}}"
                                        data-maid-experience="{{$order->maid->experience_years ?? ''}}"
                                        data-maid-age="{{$order->maid->age ?? ''}}"
                                        @if(isset($orderId) && $orderId == $order->id) selected @endif>
                                        {{$order->user->name ?? ''}} - {{$order->user->phone ?? ''}} - {{$order->maid->name ?? ''}} ({{$order->maid->passport_num ?? ''}}) - {{$order->jobType->title ?? ''}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- بيانات العميل والعاملة المختارة من الطلب للمراجعة -->
                    <div id="selected-order-details" class="row mt-4" style="display: none;">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white py-2">
                                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>بيانات العميل المختار</h6>
                                </div>
                                <div class="card-body p-2">
                                    @include('admin.ingaz.contract.client-section')
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white py-2">
                                    <h6 class="mb-0"><i class="fas fa-user-tie me-2"></i>بيانات العاملة المختارة</h6>
                                </div>
                                <div class="card-body p-2">
                                    @include('admin.ingaz.contract.maid-section')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- قسم اختيار العميل والعاملة (موحد) - يظهر فقط في العقد الجديد -->
        <div id="client-maid-selection-section" class="section-container col-12 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        اختيار العميل والعاملة
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-6 mb-3">
                            <label for="existing_client_id" class="form-label">اختر عميل <span class="text-danger">*</span></label>
                            <select name="existing_client_id" id="existing_client_id" class="form-select select2" style="width:100%">
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
                            <a href="{{ route('clients.create') }}" target="_blank" class="btn btn-outline-primary mt-2" title="إضافة عميل جديد"><i class="fas fa-user-plus"></i> إضافة عميل جديد</a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="new_maid_id" class="form-label">اختر عاملة</label>
                            <select name="new_maid_id" id="new_maid_id" class="form-select select2" style="width:100%">
                                <option value="" selected>اختر عاملة...</option>
                                @foreach($all_available_maids as $maid)
                                    <option value="{{$maid->id}}"
                                        data-name="{{$maid->name}}"
                                        data-passport="{{$maid->passport_num}}"
                                        data-religion="{{$maid->religion ? $maid->religion->title : ''}}"
                                        data-job="{{$maid->job_types && $maid->job_types->first() ? $maid->job_types->first()->title : ''}}"
                                        data-nationality="{{$maid->nationality ? $maid->nationality->name_ar : ''}}"
                                        data-salary="{{$maid->salary}}"
                                        data-experience="{{$maid->experience_years}}"
                                        data-age="{{$maid->age}}">
                                        {{$maid->name}} - {{$maid->passport_num}} - {{$maid->nationality ? $maid->nationality->name_ar : ''}}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" id="no-maid-btn" class="btn btn-outline-warning mt-2 w-100"><i class="fas fa-user-slash me-1"></i> بدون عاملة حالياً (تأجيل الاختيار)</button>
                        </div>
                    </div>
                    <div id="no-maid-alert" style="display:none;">
                        <div class="alert alert-warning mt-2">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            تم اختيار "بدون عاملة حالياً". يمكنك إضافة عاملة لاحقاً من صفحة العقود.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- قسم العقد الجديد (مُعدل: اختيار العميل والعاملة في الأعلى) -->
        <div id="new-contract-section" class="section-container col-12 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        معلومات العقد الجديد
                    </h4>
                </div>
                <div class="card-body">
                    <!-- بيانات العميل والعاملة -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0"><i class="fas fa-id-card me-2"></i>بيانات العميل</h6>
                                </div>
                                <div class="card-body p-2">
                                    @include('admin.ingaz.contract.client-section')
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0"><i class="fas fa-id-badge me-2"></i>بيانات العاملة</h6>
                                </div>
                                <div class="card-body p-2">
                                    @include('admin.ingaz.contract.maid-section')
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
                    <h4 class="mb-0">
                        <i class="fas fa-file-contract me-2"></i>
                        معلومات العقد
                    </h4>
                </div>
                <div class="card-body">
                    @include('admin.ingaz.contract.contract-section')
                </div>
            </div>
        </div>

        <!-- زر الحفظ -->
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <button class="btn btn-primary btn-lg px-5" type="submit" id="submitBtn">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        {{__('buttons.save')}}
                    </button>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            تأكد من مراجعة جميع البيانات قبل الحفظ
                        </small>
                    </div>
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
    var indexRoute = "{{ route('RecruitmentContract.index') }}";
    var selectedOrder = @json($selectedOrder);
    var hasOrderId = {{ isset($orderId) ? 'true' : 'false' }};
</script>

<script>
    $(document).ready(function() {
        // إخفاء جميع الأقسام في البداية
        $('.section-container').hide();

        // تهيئة Select2 مع إضافة البحث


        // إظهار قسم الطلب المسبق افتراضياً إذا كان هناك orderId
        if (hasOrderId) {
            $('#existing-order-section').fadeIn(500);
            $('.contract-type-card[data-type="existing_order"]').addClass('selected');
            $('#contract_type').val('existing_order');
        } else {
            // إظهار قسم اختيار النوع
            $('.contract-type-card[data-type="existing_order"]').addClass('selected');
            $('#existing-order-section').fadeIn(500);
        }



        // اختيار نوع العقد (مُحدث للتصميم الجديد)
        $('.contract-type-card').click(function() {
            $('.contract-type-card').removeClass('selected');
            $(this).addClass('selected');

            var contractType = $(this).data('type');
            $('#contract_type').val(contractType);

            // إخفاء جميع الأقسام مع تأثير
            $('.section-container').fadeOut(300);

            // إظهار القسم المناسب مع تأثير
            setTimeout(function() {
                if (contractType === 'existing_order') {
                    $('#existing-order-section').fadeIn(300);
                    $('#client_type').val('ex_client');
                } else {
                    $('#client-maid-selection-section').fadeIn(300);
                    $('#new-contract-section').fadeIn(300);

                    // تهيئة مباشرة للعملاء في حالة العقد الجديد
                    setTimeout(function() {
                        if ($.fn.select2 && $('#existing_client_id').length) {
                            $('#existing_client_id').select2({
                                placeholder: 'اختر عميل...',
                                allowClear: true,
                                dir: 'rtl',
                                width: '100%',
                                language: {
                                    noResults: function() { return "لا توجد نتائج"; },
                                    searching: function() { return "جاري البحث..."; }
                                }
                            });
                        }
                    }, 100);

                    // إعادة تهيئة Select2 للعناصر الجديدة مع إضافة البحث
                    setTimeout(function() {
                        // إعادة تهيئة قائمة العملاء بشكل خاص
                        if ($.fn.select2 && $('#existing_client_id').length) {
                            $('#existing_client_id').select2('destroy');
                            $('#existing_client_id').select2({
                                placeholder: 'اختر عميل...',
                                allowClear: true,
                                dir: 'rtl',
                                width: '100%',
                                language: {
                                    noResults: function() { return "لا توجد نتائج"; },
                                    searching: function() { return "جاري البحث..."; }
                                }
                            });
                        }

                        // إعادة تهيئة قائمة العاملات بشكل خاص
                        if ($.fn.select2 && $('#new_maid_id').length) {
                            $('#new_maid_id').select2('destroy');
                            $('#new_maid_id').select2({
                                placeholder: 'اختر عاملة...',
                                allowClear: true,
                                dir: 'rtl',
                                width: '100%',
                                language: {
                                    noResults: function() { return "لا توجد نتائج"; },
                                    searching: function() { return "جاري البحث..."; }
                                }
                            });
                        }
                    }, 300);
                }
            }, 300);
        });

        // إخفاء خيارات نوع العميل إذا كان هناك orderId
        if (hasOrderId) {
            $('#new_client').parent().hide();
            $('#ex_client').parent().hide();
            $('#client_type').val('ex_client');

            // تعيين maid_id من الطلب المحدد
            if (selectedOrder && selectedOrder.maid) {
                $('#new_maid_id').val(selectedOrder.maid.id);
            }

            // قفل جميع حقول بيانات العميل والعاملة وجعلها للقراءة فقط
            $('#client_name, #client_phone, #client_email, #client_nationality_id, #client_national_num, #client_birthdate, #phone2, #address, #city, #beds_num, #family_num, #children, #job, #maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', true).css('background-color', '#e9ecef');

            // منع أي تغييرات على بيانات العميل والعاملة
            $('#client_name, #client_phone, #client_email, #client_nationality_id, #client_national_num, #client_birthdate, #phone2, #address, #city, #beds_num, #family_num, #children, #job, #maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').on('change keyup', function(e) {
                e.preventDefault();
                return false;
            });
        }

        // إذا كان لدينا طلب محدد، ملء حقول النموذج
        if (selectedOrder) {
            console.log('Selected order data:', selectedOrder);

            // تعيين الطلب في القائمة المنسدلة
            $('#order_id').val(selectedOrder.id).trigger('change');

            // إظهار تفاصيل الطلب المختار للمراجعة
            $('#selected-order-details').fadeIn(300);

            // ملء بيانات العميل
            if (selectedOrder.user) {
                $('#client_name').val(selectedOrder.user.name);
                $('#client_phone').val(selectedOrder.user.phone);
                $('#client_email').val(selectedOrder.user.email);
                $('#client_national_num').val(selectedOrder.user.national_num);
                $('#client_birthdate').val(selectedOrder.user.birthdate);
                $('#phone2').val(selectedOrder.user.phone2);
                $('#address').val(selectedOrder.user.address);
                $('#city').val(selectedOrder.user.city);
                $('#beds_num').val(selectedOrder.user.rooms_num);
                $('#family_num').val(selectedOrder.user.family_num);
                $('#children').val(selectedOrder.user.children);
                $('#job').val(selectedOrder.user.job);

                if (selectedOrder.user.nationality_id) {
                    $('#client_nationality_id').val(selectedOrder.user.nationality_id).trigger('change');
                }
            }

            // تعيين مدة العقد الافتراضية إلى 24 شهراً
            $('#contract_duration').val('2');

            // ملء بيانات الخادمة
            if (selectedOrder.maid) {
                // تعيين اسم الخادمة مباشرة من بيانات الطلب
                $('#maid_name').val(selectedOrder.maid.name);
                $('#maid_passport').val(selectedOrder.maid.passport_num);

                // جلب تفاصيل الخادمة للحصول على الأسماء/القيم الصحيحة
                if (selectedOrder.maid.id) {
                    $.ajax({
                        url: '/admin/maids/' + selectedOrder.maid.id + '/details',
                        type: 'GET',
                        success: function(data) {
                            console.log('Maid details response:', data);

                            // تعيين الدين
                            if (data.religion_title) {
                                $('#maid_religion').val(data.religion_title);
                            }

                            // التعامل مع نوع العمل - استخدام نوع العمل الفعلي من بيانات الخادمة
                            if (data.job_types && data.job_types.length > 0) {
                                $('#maid_job').val(data.job_types[0].title);
                            }

                            // التعامل مع الجنسية
                            if (data.nationality && data.nationality.title) {
                                $('#maid_nationality').val(data.nationality.title);
                            }

                            // تعيين الحقول الأخرى
                            $('#maid_salary').val(data.salary || '');
                            $('#experience_years').val(data.experience_years || '');
                            $('#age').val(data.age || '');

                            // إجبار تحديث Select2 إذا كان موجوداً
                            if ($.fn.select2) {
                                $('#maid_nationality').select2('destroy').select2();
                                $('#maid_religion').select2('destroy').select2();
                                $('#maid_job').select2('destroy').select2();
                            }
                        },
                        error: function(error) {
                            console.error('Error fetching maid details:', error);
                        }
                    });
                }
            }

            // تعيين نوع العميل إلى عميل موجود
            $('#ex_client').prop('checked', true).trigger('change');
        }

                        // تهيئة Select2 مع البحث دومًا
        function initializeSelect2() {
            if ($.fn.select2) {
                // تهيئة قائمة العملاء
                if ($('#existing_client_id').length && !$('#existing_client_id').hasClass('select2-hidden-accessible')) {
                    $('#existing_client_id').select2({
                        placeholder: 'اختر عميل...',
                        allowClear: true,
                        dir: 'rtl',
                        width: '100%',
                        language: {
                            noResults: function() { return "لا توجد نتائج"; },
                            searching: function() { return "جاري البحث..."; }
                        }
                    });
                }

                // تهيئة قائمة العاملات
                if ($('#new_maid_id').length && !$('#new_maid_id').hasClass('select2-hidden-accessible')) {
                    $('#new_maid_id').select2({
                        placeholder: 'اختر عاملة...',
                        allowClear: true,
                        dir: 'rtl',
                        width: '100%',
                        language: {
                            noResults: function() { return "لا توجد نتائج"; },
                            searching: function() { return "جاري البحث..."; }
                        }
                    });
                }

                // تهيئة قائمة الطلبات
                if ($('#order_id').length && !$('#order_id').hasClass('select2-hidden-accessible')) {
                    $('#order_id').select2({
                        placeholder: 'اختر طلب...',
                        allowClear: true,
                        dir: 'rtl',
                        width: '100%',
                        language: {
                            noResults: function() { return "لا توجد نتائج"; },
                            searching: function() { return "جاري البحث..."; }
                        }
                    });
                }
            }
        }

        // تهيئة Select2 عند تحميل الصفحة
        initializeSelect2();

        // تهيئة إضافية للعملاء بعد تحميل الصفحة
        setTimeout(function() {
            if ($.fn.select2 && $('#existing_client_id').length && !$('#existing_client_id').hasClass('select2-hidden-accessible')) {
                $('#existing_client_id').select2({
                    placeholder: 'اختر عميل...',
                    allowClear: true,
                    dir: 'rtl',
                    width: '100%',
                    language: {
                        noResults: function() { return "لا توجد نتائج"; },
                        searching: function() { return "جاري البحث..."; }
                    }
                });
            }
        }, 1000);

        // عند اختيار عميل: عبئ جميع الحقول الخاصة به
        $('#existing_client_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            if (selectedOption.val()) {
                $('#client_name').val(selectedOption.data('name'));
                $('#client_phone').val(selectedOption.data('phone'));
                $('#client_email').val(selectedOption.data('email'));
                $('#client_nationality_id').val(selectedOption.data('nationality')).trigger('change');
                $('#client_national_num').val(selectedOption.data('national-num'));
                $('#client_birthdate').val(selectedOption.data('birthdate'));
                $('#phone2').val(selectedOption.data('phone2'));
                $('#address').val(selectedOption.data('address'));
                $('#city').val(selectedOption.data('city'));
                $('#beds_num').val(selectedOption.data('beds-num'));
                $('#family_num').val(selectedOption.data('family-num'));
                $('#children').val(selectedOption.data('children'));
                $('#job').val(selectedOption.data('job'));
                // تعطيل الحقول بعد ملئها
                $('#client_name, #client_phone, #client_email, #client_nationality_id, #client_national_num, #client_birthdate, #phone2, #address, #city, #beds_num, #family_num, #children, #job').prop('disabled', true).css('background-color', '#e9ecef');
            } else {
                // إعادة تعيين الحقول عند إلغاء الاختيار
                $('#client_name, #client_phone, #client_email, #client_nationality_id, #client_national_num, #client_birthdate, #phone2, #address, #city, #beds_num, #family_num, #children, #job').val('');
                $('#client_name, #client_phone, #client_email, #client_nationality_id, #client_national_num, #client_birthdate, #phone2, #address, #city, #beds_num, #family_num, #children, #job').prop('disabled', false).css('background-color', '');
            }
        });

        // عند اختيار عاملة: عبئ جميع الحقول الخاصة بها
        $('#new_maid_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            if (selectedOption.val()) {
                $('#no-maid-alert').fadeOut(200);
                $('#maid_name').val(selectedOption.data('name'));
                $('#maid_passport').val(selectedOption.data('passport'));
                $('#maid_religion').val(selectedOption.data('religion'));
                $('#maid_job').val(selectedOption.data('job'));
                $('#maid_nationality').val(selectedOption.data('nationality'));
                $('#maid_salary').val(selectedOption.data('salary'));
                $('#experience_years').val(selectedOption.data('experience'));
                $('#age').val(selectedOption.data('age'));
                // تعطيل الحقول بعد ملئها
                $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', true).css('background-color', '#e9ecef');
            } else {
                // إعادة تعيين الحقول عند إلغاء الاختيار
                $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').val('');
                $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', false).css('background-color', '');
            }
        });

        // زر "بدون عاملة"
        $('#no-maid-btn').on('click', function() {
            $('#new_maid_id').val('').trigger('change');
            $('#no-maid-alert').fadeIn(300);
            // تعطيل حقول العاملة
            $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').val('').prop('disabled', true).css('background-color', '#e9ecef');
        });
        // إخفاء التنبيه عند اختيار عاملة
        $('#new_maid_id').on('change', function() {
            if ($(this).val()) {
                $('#no-maid-alert').fadeOut(200);
            }
        });

        // تهيئة Select2 لجميع القوائم المنسدلة في قسم بيانات العقد
        setTimeout(function() {
            if ($.fn.select2) {
                $('.select2').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            placeholder: 'اختر...',
                            allowClear: true,
                            dir: 'rtl',
                            width: '100%',
                            language: {
                                noResults: function() { return "لا توجد نتائج"; },
                                searching: function() { return "جاري البحث..."; }
                            }
                        });
                    }
                });
            }
        }, 500);

        // التعامل مع تغيير اختيار الطلب
        $('#order_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var maidId = selectedOption.data('maid-id');

            // تعيين نوع العميل إلى ex_client عند اختيار طلب
            $('#client_type').val('ex_client');

                            // تعيين maid_id من الطلب المحدد
                $('#new_maid_id').val(maidId);

            // مسح جميع الحقول أولاً
            $('#client_name, #client_phone, #client_email, #client_nationality_id, #client_national_num, #client_birthdate, #phone2, #address, #city, #beds_num, #family_num, #children, #job, #maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').val('');

            if (maidId) {
                // إظهار تفاصيل الطلب المختار للمراجعة
                $('#selected-order-details').fadeIn(300);

                // ملء حقول نموذج العميل
                $('#client_name').val(selectedOption.data('client-name'));
                $('#client_phone').val(selectedOption.data('client-phone'));
                $('#client_email').val(selectedOption.data('client-email'));
                $('#client_national_num').val(selectedOption.data('client-national-num'));
                $('#client_birthdate').val(selectedOption.data('client-birthdate'));
                $('#phone2').val(selectedOption.data('client-phone2'));
                $('#address').val(selectedOption.data('client-address'));
                $('#city').val(selectedOption.data('client-city'));
                $('#beds_num').val(selectedOption.data('client-beds-num'));
                $('#family_num').val(selectedOption.data('client-family-num'));
                $('#children').val(selectedOption.data('client-children'));
                $('#job').val(selectedOption.data('client-job'));

                if (selectedOption.data('client-nationality')) {
                    $('#client_nationality_id').val(selectedOption.data('client-nationality')).trigger('change');
                }

                // ملء حقول نموذج الخادمة
                $('#maid_name').val(selectedOption.data('maid-name'));
                $('#maid_passport').val(selectedOption.data('maid-passport'));

                // جلب تفاصيل الخادمة للحصول على الأسماء/القيم الصحيحة
                $.ajax({
                    url: '/admin/maids/' + maidId + '/details',
                    type: 'GET',
                    success: function(data) {
                        console.log('Maid details response:', data);

                        // تمكين الحقول مؤقتاً
                        $('#maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', false);

                        // تعيين الدين
                        console.log('Setting religion:', data.religion_title);
                        $('#maid_religion').val(data.religion_title || '');

                        // التعامل مع نوع العمل
                        var jobTypeTitle = '';
                        if (data.job_types && data.job_types.length > 0) {
                            jobTypeTitle = data.job_types[0].title || '';
                            console.log('Setting job type:', jobTypeTitle);
                        }
                        $('#maid_job').val(jobTypeTitle);

                        // التعامل مع الجنسية
                        var nationalityTitle = '';
                        if (data.nationality) {
                            nationalityTitle = data.nationality.title || '';
                            console.log('Setting nationality:', nationalityTitle);
                        }
                        $('#maid_nationality').val(nationalityTitle);

                        // تعيين الحقول الأخرى
                        console.log('Setting salary:', data.salary);
                        $('#maid_salary').val(data.salary || '');

                        console.log('Setting experience years:', data.experience_years);
                        $('#experience_years').val(data.experience_years || '');

                        console.log('Setting age:', data.age);
                        $('#age').val(data.age || '');

                        // إجبار تحديث Select2 إذا كان موجوداً
                        if ($.fn.select2) {
                            $('#maid_nationality').select2('destroy').select2();
                            $('#maid_religion').select2('destroy').select2();
                            $('#maid_job').select2('destroy').select2();
                        }

                        // تعطيل الحقول مرة أخرى بعد تعيين القيم
                        setTimeout(function() {
                            $('#maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age').prop('disabled', true);
                        }, 100);
                    },
                    error: function(error) {
                        console.error('Error fetching maid details:', error);
                    }
                });

                // جلب تفاصيل العميل للحصول على الجنسية
                if (selectedOption.data('client-nationality')) {
                    $.ajax({
                        url: '/admin/clients/' + selectedOption.val() + '/details',
                        type: 'GET',
                        success: function(data) {
                            if (data.nationality) {
                                $('#client_nationality_id').val(data.nationality.id).trigger('change');
                            }
                        },
                        error: function(error) {
                            console.error('Error fetching client details:', error);
                        }
                    });
                }
            } else {
                // إخفاء تفاصيل الطلب إذا لم يتم اختيار طلب
                $('#selected-order-details').fadeOut(300);
            }
        });

        // معالجة إرسال النموذج (مُحدث لدعم حالة "بدون عاملة")
        $('#maidForm').on('submit', function(e) {
            e.preventDefault();

            // مسح أخطاء التحقق السابقة
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let isValid = true;
            let firstInvalidField = null;

            // التحقق من الحقول المطلوبة
            $('[data-validation="required"]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('is-invalid');

                    // إضافة رسالة الخطأ
                    var fieldName = $(this).attr('name');
                    var errorMessage = '';

                    switch(fieldName) {
                        case 'contract_date':
                            errorMessage = 'يرجى ادخال تاريخ العقد';
                            break;
                        case 'msaned_number':
                            errorMessage = 'يرجى ادخال رقم مساند';
                            break;
                        case 'visa_number':
                            errorMessage = 'يرجى ادخال رقم التأشيرة';
                            break;
                        case 'contract_duration':
                            errorMessage = 'يرجى ادخال مدة العقد';
                            break;
                        case 'order_id':
                            errorMessage = 'يرجى اختيار الطلب';
                            break;
                        default:
                            errorMessage = 'هذا الحقل مطلوب';
                    }

                    $(this).after('<div class="invalid-feedback">' + errorMessage + '</div>');

                    // إضافة تأثير للخطأ
                    $(this).addClass('shake');
                    setTimeout(function() {
                        $(this).removeClass('shake');
                    }.bind(this), 500);

                    if (!firstInvalidField) {
                        firstInvalidField = $(this);
                    }
                }
            });

            // التحقق الإضافي من maid_id
            var contractType = $('#contract_type').val();
            if (contractType === 'existing_order') {
                if (!$('#order_id').val()) {
                    isValid = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'يرجى اختيار الطلب المسبق',
                        confirmButtonText: 'حسناً',
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOutUp'
                        }
                    });
                    return false;
                }
                // في حالة الطلب المسبق، نتحقق من أن الطلب يحتوي على عاملة
                var selectedOrder = $('#order_id').find('option:selected');
                var maidId = selectedOrder.data('maid-id');
                if (!maidId) {
                    isValid = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'الطلب المختار لا يحتوي على عاملة',
                        confirmButtonText: 'حسناً',
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOutUp'
                        }
                    });
                    return false;
                }
            } else {
                // التحقق من اختيار العميل في العقد الجديد
                if (!$('#existing_client_id').val()) {
                    isValid = false;
                    $('#existing_client_id').addClass('is-invalid');
                    $('#existing_client_id').after('<div class="invalid-feedback">يرجى اختيار العميل</div>');
                    $('#existing_client_id').addClass('shake');
                    setTimeout(function() {
                        $('#existing_client_id').removeClass('shake');
                    }, 500);
                    if (!firstInvalidField) {
                        firstInvalidField = $('#existing_client_id');
                    }
                }

                // التحقق من اختيار العاملة (يمكن أن تكون "بدون عاملة")
                var selectedMaidId = $('#new_maid_id').val();
                if (!selectedMaidId) {
                    isValid = false;
                    $('#new_maid_id').addClass('is-invalid');
                    $('#new_maid_id').after('<div class="invalid-feedback">يرجى اختيار عاملة أو "بدون عاملة"</div>');
                    $('#new_maid_id').addClass('shake');
                    setTimeout(function() {
                        $('#new_maid_id').removeClass('shake');
                    }, 500);
                    if (!firstInvalidField) {
                        firstInvalidField = $('#new_maid_id');
                    }
                }
                // لا نحتاج لتعيين maid_id لأن new_maid_id سيتم إرساله مباشرة
            }

            if (!isValid) {
                // التمرير إلى أول حقل غير صحيح
                if (firstInvalidField) {
                    $('html, body').animate({
                        scrollTop: firstInvalidField.offset().top - 100
                    }, 500);
                }
                return false;
            }

            // إذا نجح جميع التحققات، إرسال النموذج عبر AJAX
            let formData = new FormData(this);
            let url = $(this).attr('action');
            let submitButton = $(e.originalEvent.submitter);

            // تعطيل زر الإرسال وإظهار حالة التحميل
            submitButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i>جاري الحفظ...');

            // إضافة تأثير التحميل
            submitButton.addClass('loading');

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Server response:', response);
                    if (response.success) {
                        // إظهار رسالة النجاح
                        Swal.fire({
                            icon: 'success',
                            title: 'تم بنجاح',
                            text: response.message || 'تم إنشاء العقد بنجاح',
                            confirmButtonText: 'حسناً',
                            showClass: {
                                popup: 'animate__animated animate__fadeInDown'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__fadeOutUp'
                            }
                        }).then((result) => {
                            window.location.href = indexRoute;
                        });
                    } else {
                        // إظهار رسالة الخطأ
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: response.message || 'حدث خطأ أثناء إنشاء العقد',
                            confirmButtonText: 'حسناً',
                            showClass: {
                                popup: 'animate__animated animate__fadeInDown'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__fadeOutUp'
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });

                    let errorMessage = 'حدث خطأ أثناء إنشاء العقد';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON.errors) {
                            // معالجة أخطاء التحقق
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: errorMessage,
                        confirmButtonText: 'حسناً',
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOutUp'
                        }
                    });
                },
                complete: function() {
                    // إعادة تمكين زر الإرسال
                    submitButton.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-2"></i>حفظ');
                    submitButton.removeClass('loading');
                }
            });
        });
    });
</script>

@include('admin.ingaz.contract.js')
@endsection
