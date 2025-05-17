@extends('admin.layout.indexs.index')

@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dropify.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/select2.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/sweetalert2.min.css">
@endsection

@section('page-title')
    {{helperTrans("ingaz.maids")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CONTROL))
    <div class="card">
        <div class="card-body">
            <form class="row g-3 from-submit-global-maid" method="post" action="{{route("maids.store")}}" enctype="multipart/form-data" id="maidForm">
                @csrf
                <!-- الملفات -->
                <div class="col-6">
                    <label for="TextInput" class="form-label">{{helperTrans("ingaz.cv_file")}} <small class="text-muted">(اختياري)</small></label>
                    <input type="file" data-default-file="" name="cv_file" class="dropify">
                </div>
                <div class="col-6">
                    <label for="TextInput" class="form-label">صورة العامله   </label>
                    <input type="file" data-default-file="" name="image" class="dropify" required>
                </div>

                <!-- أنواع الوظائف -->
                <div class="col-4">
                    <label for="TextInput" class="form-label">{{helperTrans("ingaz.job_types_2")}} </label>
                    <select name="type_id" class="form-control" required>
                        <option value="">اختر نوع السيرة</option>
                        @foreach($types as $type)
                            <option value="{{$type->id}}" {{$type->title == 'طلب استقدام' ? 'selected' : ''}}>{{$type->title}}</option>
                        @endforeach
                    </select>
                </div>

                <!-- الجنسية -->
                <div class="col-4">
                    <label for="TextInput" class="form-label">{{helperTrans("ingaz.nationality")}} </label>
                    <select name="nationality_id" id="Pnationality_id" class="form-control" required>
                        <option value="">اختر </option>
                        @foreach($nationalities as $nationality)
                        <option value="{{$nationality->id}}"
                            data-price="{{$nationality->price}}"
                            data-salary="{{$nationality->monthly_salary}}"
                            data-service_transfer_cost="{{$nationality->service_transfer_cost}}"
                            data-rental_cost="{{$nationality->rental_cost}}">
                            {{$nationality->sub_title}}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- مكتب الاستقدام -->
                <div class="col-4 office-field">
                    <label for="TextInput" class="form-label">{{helperTrans("ingaz.recruitment office")}} <small class="text-muted">(اختياري)</small></label>
                    <select name="office_id" class="form-control">
                        <option value="">اختر مكتب الاستقدام</option>
                        {!! showSelectElement($offices , 'title') !!}
                    </select>
                </div>

                <!-- الوظائف -->
                <div class="col-4">
                    <label for="TextInput" class="form-label">{{helperTrans("ingaz.job_2")}} </label>
                    <select name="job_id[]" data-placeholder="{{helperTrans('ingaz.choose')}}"
                            class="form-control js-example-basic-multiple" multiple required>
                        @foreach($jobs as $job)
                            <option value="{{$job->id}}">{{$job->title}}</option>
                        @endforeach
                    </select>
                </div>

                <!-- اسم العاملة -->
                <div class="col-4">
                    <label for="TextInput" class="form-label">
                        {{helperTrans("ingaz.maid_name")}} ({{helperTrans('ingaz.en')}})
                    </label>
                    <input type="text" name="name:en" class="form-control" required>
                </div>

                <!-- السعر والراتب -->
                <div class="col-4">
                    <label for="TextInput" class="form-label price-label">
                        {{helperTrans("ingaz.price")}}
                    </label>
                    <input type="number" name="price" id="price" class="form-control">
                </div>
                <div class="col-4">
                    <label for="TextInput" class="form-label">
                        {{helperTrans("ingaz.salary")}}
                    </label>
                    <input type="number" name="salary" class="form-control">
                </div>

                <!-- البيانات الشخصية -->
                <div class="col-4">
                    <label for="TextInput" class="form-label">
                        تاريخ الميلاد <small class="text-muted">(اختياري)</small>
                    </label>
                    <input type="date" name="birth_date" id="birth_date" class="form-control">
                </div>

                <div class="col-4">
                    <label for="TextInput" class="form-label">
                        {{helperTrans("ingaz.age")}} <small class="text-muted">(اختياري)</small>
                    </label>
                    <input type="number" min="18" max="90" name="age" id="age" class="form-control" readonly>
                </div>

                <div class="col-4">
                    <label for="TextInput" class="form-label">
                        {{helperTrans("ingaz.height")}}
                    </label>
                    <input type="text" name="height" class="form-control" value="160" placeholder="الطول بالسنتيمتر">
                </div>

                <div class="col-4">
                    <label for="TextInput" class="form-label">
                        {{helperTrans("ingaz.weight")}}
                    </label>
                    <input type="text" name="weight" class="form-control" value="60" placeholder="الوزن بالكيلوجرام">
                </div>

                <div class="col-4">
                    <label for="TextInput" class="form-label">{{helperTrans("ingaz.religion")}}</label>
                    <select name="religion_id" class="form-control">
                        <option value="">غير محدد</option>
                        {!! showSelectElement($religions , 'title') !!}
                    </select>
                </div>

                <div class="col-4">
                    <label for="TextInput" class="form-label">{{helperTrans("ingaz.marital status")}} </label>
                    <select name="marital_status:ar" class="form-control" required>
                        <option value="عازبة">عازبة</option>
                        <option value="أعزب">أعزب</option>
                        <option value="متزوجة">متزوجة</option>
                        <option value="متزوج">متزوج</option>
                    </select>
                </div>

                <!-- رقم الجواز ورقم الهوية وتاريخ انتهاء الهوية -->
                <div class="col-4">
                    <label for="TextInput" class="form-label">
                        {{helperTrans("ingaz.passport_num")}}
                    </label>
                    <input type="text" name="passport_num" class="form-control" required>
                </div>

                <div class="col-4">
                    <label for="TextInput" class="form-label">
                        {{helperTrans("ingaz.id_number")}}
                    </label>
                    <input type="text" name="id_number" id="id_number" class="form-control" maxlength="10" pattern="\d{10}" required>
                    <small class="text-muted">يجب أن يتكون رقم الهوية من 10 أرقام</small>
                </div>

                <div class="col-4">
                    <label for="TextInput" class="form-label">
                        {{helperTrans("ingaz.id_expiry_date")}}
                    </label>
                    <input type="date" name="id_expiry_date" class="form-control" required>
                </div>

                <!-- المهارات -->
                <div class="col-4">
                    <label for="skills" class="form-label">{{helperTrans("ingaz.skills")}} <small class="text-muted">(اختياري)</small></label>
                    <select name="skills[]" class="form-control js-example-basic-multiple" multiple>
                        @foreach($skills as $skill)
                            <option value="{{$skill->id}}">{{$skill->title}}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-4">
                    <label for="TextInput" class="form-label">
                        {{helperTrans("ingaz.experience_years")}} <small class="text-muted">(اختياري)</small>
                    </label>
                    <select name="experience_years" class="form-control">
                        <option value="">اختر سنوات الخبرة</option>
                        @for($i = 1; $i <= 10; $i++)
                            <option value="{{$i}}">{{$i}} {{$i == 1 ? 'سنة' : 'سنوات'}}</option>
                        @endfor
                    </select>
                </div>

                <!-- اللغات والحالة الاجتماعية -->
                <div class="col-12">
                    <label for="TextInput" class="form-label">{{helperTrans("ingaz.languages")}} <small class="text-muted">(اختياري)</small></label>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="languages[arabic]" id="arabic" value="نعم">
                                <label class="form-check-label" for="arabic">
                                    اللغة العربية
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="languages[english]" id="english" value="نعم">
                                <label class="form-check-label" for="english">
                                    اللغة الإنجليزية
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="languages[amharic]" id="amharic" value="نعم">
                                <label class="form-check-label" for="amharic">
                                    اللغة الأمهرية
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="languages[mother]" id="mother" value="نعم">
                                <label class="form-check-label" for="mother">
                                    اللغة الأم
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
                    <a href="{{ route('maids.index') }}" class="btn btn-outline-secondary">{{__("buttons.cancel")}}</a>
                </div>
            </form>
        </div>
    </div>
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dropify.bundle.js"></script>
    <script src="{{asset("admin")}}/assets/js/bundle/select2.bundle.js"></script>
    <script src="{{asset("admin")}}/assets/js/bundle/sweetalert2.bundle.js"></script>
    <script>
        $(document).ready(function () {
            $('.dropify').dropify();
            $('.js-example-basic-multiple').select2();

            // Function to handle fields visibility based on job type
            function toggleFields() {
                var selectedType = $('select[name="type_id"] option:selected').text();
                console.log('Selected type:', selectedType);

                // Get all fields that need to be toggled
                var idFields = $('input[name="id_number"], input[name="id_expiry_date"]').closest('.col-4');
                var heightWeightFields = $('input[name="height"], input[name="weight"]').closest('.col-4');
                var maritalStatusField = $('select[name="marital_status:ar"]').closest('.col-4');
                var officeField = $('.office-field');
                var salaryField = $('input[name="salary"]').closest('.col-4');
                var jobField = $('select[name="job_id[]"]').closest('.col-4');
                var languagesField = $('.col-12:has(label:contains("اللغات"))');
                var priceLabel = $('.price-label');

                // Reset all fields to default state
                idFields.show();
                heightWeightFields.show();
                maritalStatusField.show();
                officeField.show();
                salaryField.show();
                jobField.show();
                languagesField.show();
                priceLabel.text('{{helperTrans("ingaz.price")}}');
                $('input[name="id_number"], input[name="id_expiry_date"]').attr('required', 'required');
                $('input[name="height"], input[name="weight"]').attr('required', 'required');
                $('select[name="marital_status:ar"]').attr('required', 'required');
                $('select[name="office_id"]').attr('required', 'required');
                $('input[name="salary"]').attr('required', 'required');
                $('select[name="job_id[]"]').attr('required', 'required');

                // Set price to 0 in all cases
                $('#price').val('0');

                // Handle specific cases
                if (selectedType === 'طلب استقدام') {
                    // Hide ID fields for طلب استقدام
                    idFields.hide();
                    $('input[name="id_number"], input[name="id_expiry_date"]').removeAttr('required');
                } else if (selectedType === 'إستلام فورى') {
                    console.log('Hiding fields for إستلام فورى');
                    // Hide multiple fields for إستلام فورى
                    idFields.hide();
                    heightWeightFields.hide();
                    maritalStatusField.hide();
                    officeField.hide();
                    priceLabel.text('تكلفة نقل الخدمات');

                    // Remove required attribute from hidden fields
                    $('input[name="id_number"], input[name="id_expiry_date"]').removeAttr('required');
                    $('input[name="height"], input[name="weight"]').removeAttr('required');
                    $('select[name="marital_status:ar"]').removeAttr('required');
                    $('select[name="office_id"]').removeAttr('required');

                    // Set service transfer cost from nationality if selected
                    var selectedNationality = $('#Pnationality_id option:selected');
                    if (selectedNationality.length) {
                        var serviceTransferCost = selectedNationality.data('service_transfer_cost');
                        if (serviceTransferCost) {
                            $('#price').val(serviceTransferCost);
                        }
                    }
                } else if (selectedType.trim() === 'ايجار عمالة' || selectedType.trim() === 'تأجير') {
                    console.log('Hiding fields for ايجار/تأجير');
                    // Hide fields for ايجار
                    heightWeightFields.hide();
                    maritalStatusField.hide();
                    salaryField.hide();
                    officeField.hide();
                    languagesField.hide();
                    idFields.hide();
                    priceLabel.text('تكلفه الايجار الشهري');

                    // Remove required attribute from hidden fields
                    $('input[name="height"], input[name="weight"]').removeAttr('required');
                    $('select[name="marital_status:ar"]').removeAttr('required');
                    $('input[name="salary"]').removeAttr('required');
                    $('select[name="office_id"]').removeAttr('required');
                    $('input[name="id_number"], input[name="id_expiry_date"]').removeAttr('required');

                    // Set rental cost from nationality if selected
                    var selectedNationality = $('#Pnationality_id option:selected');
                    if (selectedNationality.length) {
                        var rentalCost = selectedNationality.data('rental_cost');
                        if (rentalCost) {
                            $('#price').val(rentalCost);
                        }
                    }
                }
            }

            // Call on page load
            toggleFields();

            // Call when type changes
            $('select[name="type_id"]').change(function() {
                console.log('Type changed');
                toggleFields();
            });

            // التحقق من رقم الهوية
            $('#id_number').on('input', function() {
                var idNumber = $(this).val();
                // إزالة أي حروف غير رقمية
                idNumber = idNumber.replace(/[^0-9]/g, '');
                $(this).val(idNumber);
            });

            // حساب العمر تلقائياً عند تغيير تاريخ الميلاد
            $('#birth_date').change(function() {
                var birthDate = new Date($(this).val());
                var today = new Date();
                var age = today.getFullYear() - birthDate.getFullYear();
                var monthDiff = today.getMonth() - birthDate.getMonth();

                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }

                $('#age').val(age);
            });

            // Handle nationality change to filter offices and set price/salary
            $('#Pnationality_id').change(function() {
                var nationalityId = $(this).val();
                var selectedType = $('select[name="type_id"] option:selected').text().trim();

                if (nationalityId) {
                    $.ajax({
                        url: "{{ route('getOfficesByNationality') }}",
                        type: 'GET',
                        data: { nationality_id: nationalityId },
                        success: function(response) {
                            var officeSelect = $('select[name="office_id"]');
                            officeSelect.empty();
                            officeSelect.append('<option value="">اختر مكتب الاستقدام</option>');

                            response.forEach(function(office) {
                                officeSelect.append('<option value="' + office.id + '">' + office.title + '</option>');
                            });

                            // Set default price and salary based on nationality
                            var selectedOption = $('#Pnationality_id option:selected');
                            var salary = selectedOption.data('salary');
                            var rentalCost = selectedOption.data('rental_cost');
                            var serviceTransferCost = selectedOption.data('service_transfer_cost');

                            if (selectedType === 'ايجار عمالة' || selectedType === 'تأجير') {
                                // Set rental cost for rental types
                                if (rentalCost) {
                                    $('#price').val(rentalCost);
                                }
                            } else if (selectedType === 'إستلام فورى') {
                                // Set service transfer cost for immediate delivery
                                if (serviceTransferCost) {
                                    $('#price').val(serviceTransferCost);
                                }
                            } else {
                                // Set default price to 0 for other types
                                $('#price').val('0');
                            }

                            if (salary) {
                                $('input[name="salary"]').val(salary);
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: 'حدث خطأ أثناء جلب مكاتب الاستقدام',
                                confirmButtonText: 'حسناً'
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
                var selectedType = $('select[name="type_id"] option:selected').text().trim();

                // التحقق من الملفات
                var image = $('input[name="image"]').get(0).files.length;

                if (!image) {
                    errors.push('الرجاء اختيار صورة العاملة');
                    isValid = false;
                }

                // التحقق من رقم الهوية فقط إذا لم يكن نوع السيرة "طلب استقدام" أو "إستلام فورى" أو "ايجار عمالة" أو "تأجير"
                if (selectedType !== 'طلب استقدام' && selectedType !== 'إستلام فورى' && selectedType !== 'ايجار عمالة' && selectedType !== 'تأجير') {
                    var idNumber = $('#id_number').val();
                    if (!idNumber || idNumber.length !== 10 || !/^\d{10}$/.test(idNumber)) {
                        errors.push('يجب أن يتكون رقم الهوية من 10 أرقام');
                        isValid = false;
                    }
                }

                // التحقق من الحقول المطلوبة فقط إذا لم يكن نوع السيرة "إستلام فورى" أو "ايجار عمالة" أو "تأجير"
                if (selectedType !== 'إستلام فورى' && selectedType !== 'ايجار عمالة' && selectedType !== 'تأجير') {
                    var requiredFields = {
                        'nationality_id': 'الجنسية',
                        'office_id': 'مكتب التوظيف'
                    };

                    // التحقق من الحقول النصية والرقمية المطلوبة فقط
                    for (var field in requiredFields) {
                        var value = $('[name="' + field + '"]').val();
                        if (!value || value.trim() === '') {
                            errors.push(requiredFields[field] + ' مطلوب');
                            isValid = false;
                        }
                    }

                    // التحقق من الوظائف
                    var jobs = $('select[name="job_id[]"]').val();
                    if (!jobs || jobs.length === 0) {
                        errors.push('معرف الوظيفة مطلوب');
                        isValid = false;
                    }

                    var maritalStatus = $('select[name="marital_status:ar"]').val();
                    if (!maritalStatus || maritalStatus.trim() === '') {
                        errors.push('الحالة الاجتماعية مطلوبة');
                        isValid = false;
                    }
                }

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'الرجاء تصحيح الأخطاء التالية:\n' + errors.join('\n'),
                        confirmButtonText: 'حسناً'
                    });
                    return false;
                }

                // إرسال النموذج باستخدام AJAX
                var formData = new FormData(this);

                // إضافة قيم افتراضية للحقول الاختيارية
                if (!formData.get('cv_file')) {
                    formData.append('cv_file', '');
                }
                if (!formData.get('experience_years')) {
                    formData.append('experience_years', '0');
                }
                if (!formData.get('religion_id')) {
                    formData.append('religion_id', ''); // Default to غير محدد
                }
                if (!formData.get('age')) {
                    formData.append('age', '0');
                }
                if (!formData.get('skills[]')) {
                    formData.append('skills[]', '');
                }
                if (!formData.get('height')) {
                    formData.append('height', '0');
                }
                if (!formData.get('weight')) {
                    formData.append('weight', '0');
                }
                if (!formData.get('salary')) {
                    formData.append('salary', '0');
                }
                if (!formData.get('price')) {
                    formData.append('price', '0');
                }

                // إضافة type_id إذا لم يكن محدداً
                if (!formData.get('type_id')) {
                    var defaultType = $('select[name="type_id"] option:selected').val();
                    formData.append('type_id', defaultType);
                }

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.code === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'نجاح',
                                text: 'تم إضافة العاملة بنجاح',
                                confirmButtonText: 'حسناً'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = '{{ route("maids.index") }}';
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء إضافة العاملة',
                                confirmButtonText: 'حسناً'
                            });
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'حدث خطأ أثناء إضافة العاملة';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            errorMessage = '';

                            for (var field in errors) {
                                errorMessage += errors[field].join('\n') + '\n';
                            }
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: errorMessage,
                            confirmButtonText: 'حسناً'
                        });
                    }
                });
            });
        });
    </script>
@endsection

