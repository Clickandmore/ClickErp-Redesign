@extends('admin.layout.indexs.index')
@section('page-title')
    إضافة عقد تأجير جديد
@endsection

@section('style')
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/> --}}
    {{-- <link rel="stylesheet" href="{{ asset('admin/custom/admin-tables.css') }}"> --}}
@endsection

@section('content')

    @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE))

        <form id="rentalContractForm" method="post" action="{{ route('RentContract.store') }}">
            @csrf
            <input type="hidden" name="contract_type" value="new_contract">
            <input type="hidden" name="client_type" value="new_client">

            <div class="card mb-4">
                <div class="contract-header">
                    <div class="text-center">
                        <h4 class="mb-2">
                            <i class="fas fa-handshake me-2"></i>
                            إنشاء عقد تأجير جديد
                        </h4>
                        <p class="mb-0 opacity-75">قم بتعبئة البيانات المطلوبة لإنشاء عقد تأجير احترافي</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-6 mb-3">
                            <label for="new_existing_client_id" class="form-label">اختر عميل <span
                                    class="text-danger">*</span></label>
                            <select name="new_existing_client_id" id="new_existing_client_id"
                                    class="form-select select2" style="width:100%">
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
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="maid_id" class="form-label">اختر عاملة <span
                                    class="text-danger">*</span></label>
                            <select name="maid_id" id="maid_id" class="form-select select2" style="width:100%">
                                <option value="" selected>اختر عاملة...</option>
                                @if(isset($availableRentalMaids))
                                    @foreach($availableRentalMaids as $maid)
                                        <option value="{{$maid->id}}"
                                                data-name="{{$maid->name}}"
                                                data-passport="{{$maid->passport_num}}"
                                                data-religion="{{$maid->religion ? $maid->religion->title : ''}}"
                                                data-job="{{$maid->jobTitles && $maid->jobTitles->first() ? $maid->jobTitles->first()->title : ''}}"
                                                data-nationality="{{$maid->nationality ? $maid->nationality->sub_title : ''}}"
                                                data-salary="{{$maid->salary}}"
                                                data-experience="{{$maid->experience_years}}"
                                                data-age="{{$maid->age}}">
                                            {{$maid->name}} - {{$maid->passport_num}}
                                            - {{$maid->nationality ? $maid->nationality->sub_title : ''}}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <a href="{{ route('clients.create') }}" target="_blank" class="btn btn-outline-primary mt-2"
                               title="إضافة عميل جديد"><i class="fas fa-user-plus"></i> إضافة عميل جديد</a>
                        </div>
                    </div>
                    <!-- بيانات العميل والعاملة (تظهر بعد الاختيار) -->
                    <div class="col-md-12">
                        <div class="form-section">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <h5 class="section-title">معلومات العميل</h5>
                                    <p class="section-subtitle">البيانات الشخصية والمهنية</p>
                                </div>
                            </div>
                            <div class="card-body p-2">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">اسم العميل <span class="text-danger">*</span></label>
                                        <input type="text" id="new_client_name" name="new_client_name"
                                               class="form-control" data-validation="required">
                                        <div class="invalid-feedback" id="err_new_client_name"></div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">رقم الجوال <span class="text-danger">*</span></label>
                                        <input type="tel" id="new_client_phone" name="new_client_phone"
                                               class="form-control" data-validation="required" dir="rtl">
                                        <div class="invalid-feedback" id="err_new_client_phone"></div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="client_nationality_id"
                                               class="form-label">{{helperTrans("ingaz.nationality_id")}} <span
                                                class="text-danger">*</span></label>
                                        <select id="client_nationality_id" name="client_nationality_id"
                                                class="form-select form-control select2" data-validation="required">
                                            <option value="">{{helperTrans('ingaz.choose')}}</option>
                                            @foreach($countries as $country)
                                                <option
                                                    value="{{$country->id}}" {{ $country->code === 'SA' ? 'selected' : '' }}>{{$country->name_ar}}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="err_client_nationality_id"></div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">رقم الهوية <span class="text-danger">*</span></label>
                                        <input type="text" id="new_client_national_num" name="new_client_national_num"
                                               class="form-control" data-validation="required">
                                        <div class="invalid-feedback" id="err_new_client_national_num"></div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">المدينة/العنوان <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="new_client_city" name="new_client_city"
                                               class="form-control" data-validation="required">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">البريد الإلكتروني <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="email" id="new_client_email" name="new_client_email"
                                               class="form-control">
                                        <div class="invalid-feedback" id="err_new_client_email"></div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">تاريخ الميلاد <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="date" id="new_client_birthdate" name="new_client_birthdate"
                                               class="form-control" max="{{ date('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">رقم جوال إضافي <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="tel" id="new_client_phone2" name="new_client_phone2"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">العنوان التفصيلي <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="text" id="new_client_address" name="new_client_address"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">عدد الغرف <small class="text-muted">(اختياري)</small></label>
                                        <input type="text" id="new_client_beds_num" name="new_client_beds_num"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">عدد أفراد الأسرة <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="text" id="new_client_family_num" name="new_client_family_num"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">عدد الأطفال <small class="text-muted">(اختياري - تحت
                                                سن 12)</small></label>
                                        <input type="number" id="new_client_children" name="new_client_children"
                                               class="form-control" max="50">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">المهنة <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="text" id="new_client_job" name="new_client_job"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">الدخل الشهري <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="number" id="monthly_income" name="monthly_income"
                                               class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-section">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="fas fa-female"></i>
                                </div>
                                <div>
                                    <h5 class="section-title">بيانات العاملة</h5>
                                    <p class="section-subtitle">البيانات الشخصية والمهنية</p>
                                </div>
                            </div>
                            <div class="card-body p-2" id="maid-section-card">
                                <div class="row">
                                    <div class="col-6 mb-2">
                                        <label class="form-label">الاسم</label>
                                        <input type="text" id="maid_name" name="maid_name" class="form-control">
                                        <div class="invalid-feedback" id="err_maid_name"></div>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="form-label">رقم الجواز</label>
                                        <input type="text" id="maid_passport" name="maid_passport" class="form-control">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="form-label">الديانة</label>
                                        <input type="text" id="maid_religion" name="maid_religion" class="form-control">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="form-label">المهنة</label>
                                        <input type="text" id="maid_job" name="maid_job" class="form-control">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="form-label">الجنسية</label>
                                        <input type="text" id="maid_nationality" name="maid_nationality"
                                               class="form-control">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="form-label">الراتب</label>
                                        <input type="text" id="maid_salary" name="maid_salary" class="form-control">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="form-label">سنوات الخبرة</label>
                                        <input type="text" id="maid_experience_years" name="maid_experience_years"
                                               class="form-control">
                                    </div>
                                    <div class="col-6 mb-2">
                                        <label class="form-label">العمر</label>
                                        <input type="text" id="maid_age" name="maid_age" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- بيانات العقد -->
                    <div class="col-12 mt-4">
                        <div class="form-section">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="fas fa-file-contract"></i>
                                </div>
                                <div>
                                    <h5 class="section-title">بيانات العقد</h5>
                                    <p class="section-subtitle">البيانات الفنية والزمنية</p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label">تاريخ العقد <span class="text-danger">*</span></label>
                                        <input type="date" id="contract_date" name="contract_date" class="form-control"
                                               value="{{ date('Y-m-d') }}" data-validation="required">
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">التكلفة الشهرية <span
                                                class="text-danger">*</span></label>
                                        <input type="number" id="monthly_cost" name="monthly_cost" class="form-control"
                                               step="0.01" data-validation="required">
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">مدة العقد بالشهور <span
                                                class="text-danger">*</span></label>
                                        <input type="number" id="contract_duration_months"
                                               name="contract_duration_months" class="form-control"
                                               min="1" data-validation="required">
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">مدة العقد بالأيام</label>
                                        <input type="number" id="contract_duration_days" name="contract_duration_days"
                                               class="form-control" readonly>
                                        <small class="text-muted">(يتم حسابها تلقائياً)</small>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">تكلفة العقد الإجمالية</label>
                                        <input type="number" id="total_contract_value" name="total_contract_value"
                                               class="form-control" readonly>
                                        <small class="text-muted">(يتم حسابها تلقائياً)</small>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">ساعات العمل اليومية</label>
                                        <input type="number" id="working_hours" name="working_hours"
                                               class="form-control" value="8" min="1" max="24">
                                        <small class="text-muted">(افتراضي: 8 ساعات)</small>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">الإنجاز من <span class="text-danger">*</span></label>
                                        <input type="date" id="ingaz_from" name="ingaz_from" class="form-control"
                                               value="{{ date('Y-m-d') }}" data-validation="required">
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">الإنجاز إلى</label>
                                        <input type="date" id="ingaz_to" name="ingaz_to" class="form-control" readonly>
                                        <small class="text-muted">(يتم حسابها تلقائياً)</small>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">المسوق <span class="text-danger">*</span></label>
                                        <select name="marketer_id" id="marketer_id" class="form-select"
                                                data-validation="required">
                                            <option value="">اختر المسوق</option>
                                            @php
                                                $currentAdmin = auth('admin')->user();
                                                $admins = \App\Models\auth\Admin::whereNull('deleted_at')->orderBy('name')->get();
                                            @endphp
                                            @foreach($admins as $admin)
                                                <option value="{{ $admin->id }}"
                                                        @if($currentAdmin && $currentAdmin->id == $admin->id) selected @endif>
                                                    {{ $admin->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="err_marketer_id"></div>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">نوع التأجير <small
                                                class="text-muted">(اختياري)</small></label>
                                        <select name="rental_type" id="rental_type" class="form-select">
                                            <option value="">اختر نوع التأجير</option>
                                            <option value="daily">يومي</option>
                                            <option value="weekly">أسبوعي</option>
                                            <option value="monthly">شهري</option>
                                            <option value="yearly">سنوي</option>
                                        </select>
                                    </div>

                                    {{-- <div class="col-6">
                                        <label class="form-label">نقل الكفالة <small class="text-muted">(اختياري)</small></label>
                                        <select name="sponsorship_transfer" id="sponsorship_transfer" class="form-select">
                                            <option value="">اختر</option>
                                            <option value="yes">نعم</option>
                                            <option value="no">لا</option>
                                        </select>
                                    </div> --}}

                                    <div class="col-6">
                                        <label class="form-label">الراتب الشهري <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="number" id="monthly_salary" name="monthly_salary"
                                               class="form-control" step="0.01">
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">الأجر بالساعة <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="number" id="hourly_rate" name="hourly_rate" class="form-control"
                                               step="0.01">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- زر الحفظ -->
            <div class="col-12 mt-3">
                <div class="card">
                    <div class="card-body text-center">
                        <button class="btn btn-primary btn-lg px-5" type="submit" id="rentalContractSubmitBtn">
                            <i class="fa-solid fa-floppy-disk me-2"></i>
                            حفظ عقد التأجير
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            // تهيئة Select2
            $('.select2').select2({
                placeholder: 'اختر...',
                allowClear: true,
                dir: 'rtl',
                width: '100%'
            });

            // إذا كان هناك طلب محدد، اختر العميل والعاملة تلقائياً
            @if(isset($selectedOrder) && $selectedOrder)
            console.log('Selected order found:', {
                id: '{{ $selectedOrder->id }}',
                user_id: '{{ $selectedOrder->user_id }}',
                maid_id: '{{ $selectedOrder->maid_id }}',
                user_name: '{{ $selectedOrder->user ? $selectedOrder->user->name : "No user" }}',
                maid_name: '{{ $selectedOrder->maid ? $selectedOrder->maid->name : "No maid" }}'
            });

            // انتظار تحميل Select2
            setTimeout(function () {
                // اختيار العميل
                $('#new_existing_client_id').val('{{ $selectedOrder->user_id }}').trigger('change');

                // اختيار العاملة
                @if($selectedOrder->maid_id)
                $('#maid_id').val('{{ $selectedOrder->maid_id }}').trigger('change');
                @endif

                // إضافة order_id مخفي
                $('form').append('<input type="hidden" name="order_id" value="{{ $selectedOrder->id }}">');

                // إظهار رسالة تأكيد
                toastr.info('تم تحميل بيانات الطلب تلقائياً');
            }, 1000);
            @else
            console.log('No selected order found');
            @endif

            // معالج تغيير العميل المحدد
            $('#new_existing_client_id').on('change', function () {
                const selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    // تعبئة بيانات العميل
                    $('#new_client_name').val(selectedOption.data('name') || '');
                    $('#new_client_phone').val(selectedOption.data('phone') || '');
                    $('#new_client_email').val(selectedOption.data('email') || '');
                    $('#client_nationality_id').val(selectedOption.data('nationality') || '').trigger('change');
                    $('#new_client_national_num').val(selectedOption.data('national-num') || '');
                    $('#new_client_birthdate').val(selectedOption.data('birthdate') || '');
                    $('#new_client_phone2').val(selectedOption.data('phone2') || '');
                    $('#new_client_address').val(selectedOption.data('address') || '');
                    $('#new_client_city').val(selectedOption.data('city') || '');
                    $('#new_client_beds_num').val(selectedOption.data('beds-num') || '');
                    $('#new_client_family_num').val(selectedOption.data('family-num') || '');
                    $('#new_client_children').val(selectedOption.data('children') || '');
                    $('#new_client_job').val(selectedOption.data('job') || '');
                }
            });

            // معالج تغيير العاملة المحددة
            $('#maid_id').on('change', function () {
                const selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    // تعبئة بيانات العاملة
                    $('#maid_name').val(selectedOption.data('name') || '');
                    $('#maid_passport').val(selectedOption.data('passport') || '');
                    $('#maid_religion').val(selectedOption.data('religion') || '');
                    $('#maid_job').val(selectedOption.data('job') || '');
                    $('#maid_nationality').val(selectedOption.data('nationality') || '');
                    $('#maid_salary').val(selectedOption.data('salary') || '');
                    $('#maid_experience_years').val(selectedOption.data('experience') || '');
                    $('#maid_age').val(selectedOption.data('age') || '');
                }
            });

            // حساب مدة العقد بالأيام عند تغيير الشهور
            $('#contract_duration_months').on('input', function () {
                const months = parseInt($(this).val()) || 0;
                const days = months * 30; // تقريبي
                $('#contract_duration_days').val(days);
                calculateIngazTo();
                calculateContractValue();
            });

            // حساب تاريخ الإنجاز إلى
            function calculateIngazTo() {
                const ingazFrom = $('#ingaz_from').val();
                const days = parseInt($('#contract_duration_days').val()) || 0;

                if (ingazFrom && days > 0) {
                    const fromDate = new Date(ingazFrom);
                    const toDate = new Date(fromDate.getTime() + (days * 24 * 60 * 60 * 1000));
                    $('#ingaz_to').val(toDate.toISOString().split('T')[0]);
                }
            }

            // حساب تكلفة العقد الإجمالية
            function calculateContractValue() {
                const monthlyCost = parseFloat($('#monthly_cost').val()) || 0;
                const months = parseInt($('#contract_duration_months').val()) || 0;
                const totalValue = monthlyCost * months;
                $('#total_contract_value').val(totalValue.toFixed(2));
            }

            // ربط الأحداث
            $('#ingaz_from').on('change', calculateIngazTo);
            $('#monthly_cost, #contract_duration_months').on('input', calculateContractValue);

            // معالج إرسال النموذج
            $('#rentalContractForm').on('submit', function (e) {
                e.preventDefault();

                // التحقق من البيانات الأساسية
                if (!$('#new_existing_client_id').val() && !$('#new_client_name').val()) {
                    Swal.fire('خطأ', 'يرجى اختيار عميل أو تعبئة اسم العميل', 'error');
                    return;
                }

                if (!$('#maid_id').val()) {
                    Swal.fire('خطأ', 'يرجى اختيار عاملة', 'error');
                    return;
                }

                // التحقق من الحقول المطلوبة
                let isValid = true;
                let firstError = '';

                $('[data-validation="required"]').each(function () {
                    if (!$(this).val()) {
                        $(this).addClass('is-invalid');
                        isValid = false;
                        if (!firstError) {
                            const label = $(this).closest('.col-6, .col-md-6').find('label').text().replace('*', '').trim();
                            firstError = 'يرجى تعبئة حقل: ' + label;
                        }
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                // التحقق من رقم الجوال
                const phone = $('#new_client_phone').val();
                if (phone && !/^(05|5)[0-9]{8}$/.test(phone)) {
                    $('#new_client_phone').addClass('is-invalid');
                    Swal.fire('خطأ', 'رقم الجوال يجب أن يبدأ بـ 05 ويكون 10 أرقام', 'error');
                    return;
                }

                // التحقق من رقم الهوية
                const nationalNum = $('#new_client_national_num').val();
                if (nationalNum && !/^[12][0-9]{9}$/.test(nationalNum)) {
                    $('#new_client_national_num').addClass('is-invalid');
                    Swal.fire('خطأ', 'رقم الهوية يجب أن يبدأ بـ 1 أو 2 ويكون 10 أرقام', 'error');
                    return;
                }

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'بيانات ناقصة',
                        text: firstError,
                        confirmButtonText: 'حسناً'
                    });
                    return;
                }

                // تأكيد الحفظ
                Swal.fire({
                    title: 'تأكيد الحفظ',
                    text: 'هل أنت متأكد من حفظ عقد التأجير؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'نعم، احفظ',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // إرسال النموذج عبر AJAX
                        $('#rentalContractSubmitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...');

                        var formData = new FormData(this);

                        $.ajax({
                            url: $(this).attr('action'),
                            method: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                $('#rentalContractSubmitBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>حفظ العقد');

                                if (response.success) {
                                    Swal.fire({
                                        title: 'تم بنجاح!',
                                        text: response.message,
                                        icon: 'success',
                                        showCancelButton: true,
                                        confirmButtonText: 'طباعة العقد',
                                        cancelButtonText: 'الذهاب للقائمة',
                                        confirmButtonColor: '#2794eb',
                                        cancelButtonColor: '#6c757d'
                                    }).then((result) => {
                                        if (result.isConfirmed && response.print_url) {
                                            // فتح صفحة الطباعة في نافذة جديدة
                                            window.open(response.print_url, '_blank');
                                            // ثم الانتقال للقائمة
                                            setTimeout(() => {
                                                window.location.href = response.redirect_url;
                                            }, 1000);
                                        } else {
                                            window.location.href = response.redirect_url;
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'خطأ!',
                                        text: response.message || 'حدث خطأ غير متوقع',
                                        icon: 'error',
                                        confirmButtonText: 'حسناً'
                                    });
                                }
                            },
                            error: function (xhr) {
                                $('#rentalContractSubmitBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>حفظ العقد');

                                var errorMessage = 'حدث خطأ أثناء الحفظ';

                                if (xhr.responseJSON) {
                                    if (xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    } else if (xhr.responseJSON.errors) {
                                        var errors = xhr.responseJSON.errors;
                                        errorMessage = Object.values(errors).flat().join('\n');
                                    }
                                }

                                Swal.fire({
                                    title: 'خطأ!',
                                    text: errorMessage,
                                    icon: 'error',
                                    confirmButtonText: 'حسناً'
                                });
                            }
                        });
                    }
                });
            });

            // إعادة حساب القيم عند التحميل
            setTimeout(function () {
                calculateIngazTo();
                calculateContractValue();
            }, 500);
        });
    </script>
@endsection
