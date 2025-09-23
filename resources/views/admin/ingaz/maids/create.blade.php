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
    @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CREATE))
        <div class="card">
            <div class="card-body">
                <form class="row g-3 from-submit-global-maid" method="post" action="{{route("maids.store")}}"
                      enctype="multipart/form-data" id="maidForm">
                    @csrf
                    <!-- الملفات -->
                    <div class="col-6">
                        <label for="TextInput" class="form-label">{{helperTrans("ingaz.cv_file")}} </label>
                        <input type="file" data-default-file="" name="cv_file" class="dropify">
                    </div>
                    <div class="col-6">
                        <label for="TextInput" class="form-label">صورة العامله </label>
                        <input type="file" data-default-file="" name="image" class="dropify" required>
                    </div>

                    <!-- أنواع الوظائف -->
                    <div class="col-4">
                        <label for="TextInput" class="form-label">{{helperTrans("ingaz.job_types_2")}} </label>
                        <select name="type_id" id="type_id" class="form-control" required>
                            <option value="">اختر نوع السيرة</option>
                            @foreach($types as $type)
                                <option
                                    value="{{$type->id}}" {{$type->title == 'استقدام' ? 'selected' : ''}}>{{$type->title}}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- خيار تسكين للإيواء -->
                    <div class="col-4" id="shelter_option" style="display: none;">
                        <label for="create_shelter_contract" class="form-label">تسكين للإيواء</label>
                        <select name="create_shelter_contract" id="create_shelter_contract" class="form-control">
                            <option value="0">لا - عاملة عادية</option>
                            <option value="1">نعم - تسكين للإيواء</option>
                        </select>
                        <small class="text-muted">سيتم إنشاء عقد إيواء تلقائياً باسم شركة أسس الانجاز</small>
                    </div>

                    <!-- الجنسية -->
                    <div class="col-4">
                        <label for="TextInput" class="form-label">{{helperTrans("ingaz.nationality")}} </label>
                        <select name="nationality_id" id="Pnationality_id" class="form-control" required>
                            <option value="">اختر</option>
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
                        <label for="TextInput" class="form-label">{{helperTrans("ingaz.recruitment office")}} </label>
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
                            تاريخ الميلاد
                        </label>
                        <input type="date" name="birth_date" id="birth_date" class="form-control" required>
                    </div>

                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.age")}}
                        </label>
                        <input type="number" min="18" max="90" name="age" id="age" class="form-control" readonly>
                    </div>

                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.height")}}
                        </label>
                        <input type="text" name="height" class="form-control" value="160"
                               placeholder="الطول بالسنتيمتر">
                    </div>

                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.weight")}}
                        </label>
                        <input type="text" name="weight" class="form-control" value="60"
                               placeholder="الوزن بالكيلوجرام">
                    </div>

                    <div class="col-4">
                        <label for="TextInput" class="form-label">{{helperTrans("ingaz.religion")}}</label>
                        <select name="religion_id" class="form-control">
                            @foreach($religions as $religion)
                                <option
                                    value="{{$religion->id}}" {{$religion->id == 4 ? 'selected' : ''}}>{{$religion->title}}</option>
                            @endforeach
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
                        <input type="text" name="passport_num" id="passport_num" class="form-control" required>
                        <div id="passport_num_feedback" class="invalid-feedback"></div>
                    </div>

                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.id_number")}}
                        </label>
                        <input type="text" name="id_number" id="id_number" class="form-control" maxlength="10"
                               pattern="\d{10}" required>
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
                        <label for="skills" class="form-label">{{helperTrans("ingaz.skills")}} <small
                                class="text-muted">(اختياري)</small></label>
                        <select name="skills[]" class="form-control js-example-basic-multiple" multiple>
                            @foreach($skills as $skill)
                                <option value="{{$skill->id}}">{{$skill->title}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-4">
                        <label for="experience_status" class="form-label">
                            حالة الخبرة <small class="text-muted">(مطلوب)</small>
                        </label>
                        <select name="experience_status" id="experience_status" class="form-control" required>
                            <option value="">اختر حالة الخبرة</option>
                            <option value="has_experience">سبق لها العمل</option>
                            <option value="no_experience">لم يسبق لها العمل</option>
                        </select>
                    </div>

                    <div class="col-4" id="experience_years_container">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.experience_years")}} <small class="text-muted"></small>
                        </label>
                        <select name="experience_years" id="experience_years" class="form-control">
                            <option value="">اختر سنوات الخبرة</option>
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{$i}}">{{$i}} {{$i == 1 ? 'سنة' : 'سنوات'}}</option>
                            @endfor
                        </select>
                    </div>

                    <!-- اللغات والحالة الاجتماعية -->
                    <div class="col-12">
                        <label for="TextInput" class="form-label">{{helperTrans("ingaz.languages")}} <small
                                class="text-muted">(اختياري)</small></label>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="languages[arabic]" id="arabic"
                                           value="نعم">
                                    <label class="form-check-label" for="arabic">
                                        اللغة العربية
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="languages[english]"
                                           id="english" value="نعم">
                                    <label class="form-check-label" for="english">
                                        اللغة الإنجليزية
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="languages[amharic]"
                                           id="amharic" value="نعم">
                                    <label class="form-check-label" for="amharic">
                                        اللغة الأمهرية
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="languages[mother]" id="mother"
                                           value="نعم">
                                    <label class="form-check-label" for="mother">
                                        اللغة الأم
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <label for="TextInput" class="form-label">{{helperTrans("ingaz.expertise")}} </label>
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>{{ __("ingaz.country") }} </th>
                            <th>{{ __("ingaz.number_of_years") }}</th>
                            <th>{{ __("ingaz.job_title") }}</th>
                            <th style="width:150px">{{ __("ingaz.actions") }}</th>
                        </tr>
                        </thead>
                        <tbody id="json_array">
                        <tr class="y-group">
                            <td>
                                <select name="expertises[0][country_id]" data-placeholder="" class="form-control select2"
                                        required>
                                    <option value="">{{helperTrans('ingaz.choose')}}</option>
                                    @foreach($countries as $country)
                                        <option value="{{$country->id}}">{{$country->name_ar}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="expertises[0][number_of_years]" class="form-control" required>
                            </td>
                            <td>
                                <select name="expertises[0][job_title_id]" data-placeholder="" class="form-control select2"
                                        required>
                                    <option value="">{{helperTrans('ingaz.choose')}}</option>
                                    @foreach($jobs as $job)
                                        <option value="{{$job->id}}">{{$job->title}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-link p-0 me-2 btnAdd" title="إضافة">
                                    <i class="fa fa-plus-circle text-success fs-5"></i>
                                </button>
                                <button type="button" class="btn btn-link p-0 btnRemove" title="حذف">
                                    <i class="fa fa-trash text-danger fs-5"></i>
                                </button>
                            </td>

                        </tr>
                        </tbody>
                    </table>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
                        <a href="{{ route('maids.index') }}"
                           class="btn btn-outline-secondary">{{__("buttons.cancel")}}</a>
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
        $(function () {

            // إعادة ترقيم أسماء الحقول بعد أي تغيير
            function reindexRows() {
                $('#json_array tr.y-group').each(function (rowIndex) {
                    $(this).find('input, select, textarea').each(function () {
                        let name = $(this).attr('name') || '';
                        name = name.replace(/\[\d+\]/, '[' + rowIndex + ']');
                        $(this).attr('name', name);
                    });
                });
            }

            // إظهار/إخفاء أزرار الحذف بناءً على عدد الصفوف
            function updateRemoveButtons() {
                const rowCount = $('#json_array tr.y-group').length;
                if (rowCount <= 1) {
                    $('#json_array .btnRemove').hide(); // اختفاء الزرار لو الصف واحد أو أقل
                } else {
                    $('#json_array .btnRemove').show(); // إظهار لو أكثر من صف
                }
            }

            // مبدئياً
            reindexRows();
            updateRemoveButtons();

            // إضافة صف (ينسخ الصف الحالي ويُفرغ الحقول)
            $(document).on('click', '.btnAdd', function () {
                const $currentRow = $(this).closest('tr');
                const $clone = $currentRow.clone();
                $clone.find('input, textarea').val('');
                $clone.find('select').prop('selectedIndex', 0);
                $currentRow.after($clone);
                reindexRows();
                updateRemoveButtons();
            });

            // حذف صف
            $(document).on('click', '.btnRemove', function () {
                const $rows = $('#json_array tr.y-group');
                if ($rows.length <= 1) {
                    return; // لن يحدث شيء؛ الزر مخفي فعلاً لكن هنا كحماية
                }
                $(this).closest('tr').remove();
                reindexRows();
                updateRemoveButtons();
            });

        });
    </script>
    <script>
        $(document).ready(function () {
            $('#json_array').multifield({
                section: '.y-group',
                btnAdd: '#btnAdd-2',
                btnRemove: '.btnRemove'
            });
            $('.dropify').dropify();
            $('.js-example-basic-multiple').select2();

            // Function to handle fields visibility based on job type
            // هذه الدالة تتحكم في إظهار/إخفاء الحقول بناءً على نوع السيرة المختار
            // استقدام: عاملة جديدة من الخارج
            // إستلام فورى: نقل عمالة موجودة من موقع إلى آخر
            // ايجار عمالة: تأجير عمالة لفترة محددة
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
                if (selectedType === 'استقدام') {
                    // Hide ID fields for استقدام
                    idFields.hide();
                    $('input[name="id_number"], input[name="id_expiry_date"]').removeAttr('required');
                } else if (selectedType === 'إستلام فورى') {
                    console.log('Hiding fields for إستلام فورى');
                    // إستلام فورى: خدمة نقل العمالة من موقع إلى آخر بدون استقدام جديد
                    // يتم إخفاء الحقول غير المطلوبة لهذا النوع من الخدمة
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
            $('select[name="type_id"]').change(function () {
                console.log('Type changed');
                toggleFields();
                toggleShelterOption();
            });

            // Function to show/hide shelter option based on type
            function toggleShelterOption() {
                var selectedType = $('select[name="type_id"] option:selected').text().trim();
                var shelterOption = $('#shelter_option');

                if (selectedType && (selectedType.includes('إيجار') || selectedType.includes('ايجار') ||
                    selectedType.includes('تأجير') || selectedType.toLowerCase().includes('rent'))) {
                    shelterOption.show();
                } else {
                    shelterOption.hide();
                    $('#create_shelter_contract').val('0'); // Reset to default
                }
            }

            // Call on page load
            toggleShelterOption();

            // التحقق من رقم الهوية
            $('#id_number').on('input', function () {
                var idNumber = $(this).val();
                // إزالة أي حروف غير رقمية
                idNumber = idNumber.replace(/[^0-9]/g, '');
                $(this).val(idNumber);
            });

            // التحقق من رقم الجواز - فحص التكرار
            var passportCheckTimeout;
            $('#passport_num').on('input', function () {
                var passportNum = $(this).val().trim();
                var feedback = $('#passport_num_feedback');

                // Clear previous timeout
                clearTimeout(passportCheckTimeout);

                // Clear previous feedback
                $(this).removeClass('is-valid is-invalid');
                feedback.removeClass('valid-feedback invalid-feedback').text('');

                // If empty, don't check
                if (!passportNum) {
                    return;
                }

                // Set timeout to avoid too many requests
                passportCheckTimeout = setTimeout(function () {
                    $.ajax({
                        url: '{{ route("check.passport.unique") }}',
                        type: 'POST',
                        data: {
                            passport_num: passportNum,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            if (response.exists) {
                                $('#passport_num').addClass('is-invalid');
                                feedback.addClass('invalid-feedback').text('رقم الجواز مسجل مسبقاً في النظام، يرجى التحقق من الرقم أو اختيار رقم آخر');
                            } else {
                                $('#passport_num').addClass('is-valid');
                                feedback.addClass('valid-feedback').text('رقم الجواز متاح');
                            }
                        },
                        error: function () {
                            // On error, don't show validation state
                            $('#passport_num').removeClass('is-valid is-invalid');
                            feedback.removeClass('valid-feedback invalid-feedback').text('');
                        }
                    });
                }, 500); // Wait 500ms after user stops typing
            });

            // حساب العمر تلقائياً عند تغيير تاريخ الميلاد
            $('#birth_date').change(function () {
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
            $('#Pnationality_id').change(function () {
                var nationalityId = $(this).val();
                var selectedType = $('select[name="type_id"] option:selected').text().trim();

                if (nationalityId) {
                    $.ajax({
                        url: "{{ route('getOfficesByNationality') }}",
                        type: 'GET',
                        data: {nationality_id: nationalityId},
                        success: function (response) {
                            var officeSelect = $('select[name="office_id"]');
                            officeSelect.empty();
                            officeSelect.append('<option value="">اختر مكتب الاستقدام</option>');

                            response.forEach(function (office) {
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
                                // إستلام فورى: خدمة نقل العمالة من موقع إلى آخر
                                // يتم تعيين تكلفة نقل الخدمات تلقائياً
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
                        error: function (xhr) {
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
            $('#maidForm').on('submit', function (e) {
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

                // التحقق من رقم الهوية فقط إذا لم يكن نوع السيرة "استقدام" أو "إستلام فورى" أو "ايجار عمالة" أو "تأجير"
                if (selectedType !== 'استقدام' && selectedType !== 'إستلام فورى' && selectedType !== 'ايجار عمالة' && selectedType !== 'تأجير') {
                    var idNumber = $('#id_number').val();
                    if (!idNumber || idNumber.length !== 10 || !/^\d{10}$/.test(idNumber)) {
                        errors.push('يجب أن يتكون رقم الهوية من 10 أرقام');
                        isValid = false;
                    }
                }

                // التحقق من الحقول المطلوبة
                var requiredFields = {
                    'nationality_id': 'الجنسية'
                };

                // التحقق من الجنسية دائماً
                var nationalityValue = $('[name="nationality_id"]').val();
                if (!nationalityValue || nationalityValue.trim() === '') {
                    errors.push('الجنسية مطلوبة');
                    isValid = false;
                }

                // التحقق من رقم الجواز
                var passportNum = $('input[name="passport_num"]').val().trim();
                if (!passportNum) {
                    errors.push('رقم الجواز مطلوب');
                    isValid = false;
                } else {
                    // Check if passport number is already invalid (from real-time validation)
                    if ($('input[name="passport_num"]').hasClass('is-invalid')) {
                        errors.push('رقم الجواز مسجل مسبقاً في النظام، يرجى التحقق من الرقم أو اختيار رقم آخر');
                        isValid = false;
                    }
                }

                // التحقق من الحقول المطلوبة فقط إذا لم يكن نوع السيرة "إستلام فورى" أو "ايجار عمالة" أو "تأجير"
                if (selectedType !== 'إستلام فورى' && selectedType !== 'ايجار عمالة' && selectedType !== 'تأجير') {
                    var additionalRequiredFields = {
                        'office_id': 'مكتب التوظيف'
                    };

                    // التحقق من الحقول الإضافية المطلوبة
                    for (var field in additionalRequiredFields) {
                        var value = $('[name="' + field + '"]').val();
                        if (!value || value.trim() === '') {
                            errors.push(additionalRequiredFields[field] + ' مطلوب');
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
                    success: function (response) {
                        if (response.code == 203) {
                            var cvConvertShowRoute = "{{ route('cv-convert.show', ':id') }}";

                            successToster('{{__("auth.done successfully")}}', response.message);
                            var redirectUrl = cvConvertShowRoute.replace(':id', response.data.id);
                            location.href = redirectUrl;
                        }
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
                    error: function (xhr) {
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

