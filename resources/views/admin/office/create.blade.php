@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
    <style>

        .wizard-container {
            background: #fff;
            border-radius: 0; /* ممكن تشيله عشان يملي الصفحة بدون زوايا */
            box-shadow: none; /* تشيله عشان ما يبقاش فيه ظل */
            padding: 30px;
            width: 100%;
            height: 100%;
            box-sizing: border-box; /* مهم عشان البادينج ما يأثرش على الارتفاع */
        }

        /* ----------- Progress Steps ----------- */
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }

        .steps::before {
            content: "";
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #dcdcdc;
            z-index: 0;
        }

        .step {
            text-align: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .step .circle {
            width: 40px;
            height: 40px;
            line-height: 38px;
            border-radius: 50%;
            border: 2px solid #066f63;
            background: #fff;
            margin: 0 auto 8px;
            color: #066f63;
            font-weight: bold;
            font-size: 18px;
        }

        .step.active .circle {
            background: #066f63;
            color: #fff;
        }

        .step p {
            font-size: 14px;
            margin: 0;
            color: #333;
        }

        /* ----------- Form Fields ----------- */
        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
            animation: fadeIn .5s ease-in-out;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        .btns {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-next {
            background: #066f63;
            color: #fff;
        }

        .btn-prev {
            background: #ccc;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .steps .step {
            cursor: pointer; /* يخلي المؤشر زي اليد */
            transition: background-color 0.3s, transform 0.2s;
        }

        .steps .step:hover {
            background-color: #f8f9fa; /* لون فاتح عند المرور */
            transform: scale(1.05);    /* تكبير بسيط */
            border-radius: 8px;        /* شكل الزرار */
        }

        .steps .step .circle {
            transition: background-color 0.3s, color 0.3s;
        }

        .steps .step:hover .circle {
            background-color: #066f63; /* أزرق */
            color: #fff;
        }

    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection
@section('page-title')
    {{helperTrans("ingaz.add_office")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
    <div class="wizard-container" style="width: 100%">
        <div class="steps">
            <div class="step active">
                <div class="circle">1</div>
                <p>{{helperTrans("ingaz.office_information")}}</p></div>
            <div class="step">
                <div class="circle">2</div>
                <p>{{helperTrans("ingaz.office_manager_information")}}</p></div>
            <div class="step">
                <div class="circle">3</div>
                <p>{{helperTrans("ingaz.bank_account_details")}}</p></div>
        </div>

        <form id="wizardForm" class="from-submit-global" action="{{ route('offices.store') }}" method="POST"
              enctype="multipart/form-data">
            <!-- Step 1 -->
            <div class="form-step active">
                <div class="form-row">
                    @foreach (config('translatable.locales') as $locale)
                        <div class="col-12">
                            <label for="TextInput" class="form-label">{{helperTrans("ingaz.title")}}
                                ({{helperTrans('ingaz.'.$locale)}}) </label>
                            <input type="text" name="title:{{ $locale }}" class="form-control"
                                   data-validation="required">
                        </div>
                    @endforeach
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.phone_office")}} *</label>
                        <input type="number" class="form-control" name="phone_office" data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.license_number")}} *</label>
                        <input type="number" class="form-control" name="license_number" data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{ helperTrans("ingaz.office_type") }} *</label>
                        <select class="form-control select2" name="job_title_id[]" multiple data-validation="required">
                            @foreach($jobTypes as $jobType)
                                <option value="{{ $jobType->id }}">{{ $jobType->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.nationality")}} *</label>
                        <select class="form-control" name="nationality_id" data-validation="required">
                            <option value="">{{helperTrans("ingaz.choose")}}</option>
                            @foreach($nationalities as $nationality)
                                <option value="{{$nationality->id}}">{{$nationality->title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.street")}} *</label>
                        <input type="text" class="form-control" name="street" data-validation="required">
                    </div>
                </div>
                <div class="btns">
                    <button type="button" class="btn btn-next">{{helperTrans("ingaz.next")}}</button>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="form-step">
                <div class="form-row">
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.name")}} *</label>
                        <input type="text" class="form-control" name="manager_name" data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.phone")}} *</label>
                        <input type="text" class="form-control" name="manager_phone" data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.email")}} *</label>
                        <input type="email" class="form-control" name="manager_email" data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.password")}} *</label>
                        <input type="password" class="form-control" name="password" data-validation="required">
                    </div>
                </div>
                <div class="btns">
                    <button type="button" class="btn btn-prev">{{helperTrans("ingaz.back")}}</button>
                    <button type="button" class="btn btn-next">{{helperTrans("ingaz.next")}}</button>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="form-step">
                <div class="form-row">
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.bank_address")}} *</label>
                        <input type="text" class="form-control" name="bank_address" data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.bank_account")}} *</label>
                        <input type="number" class="form-control" name="bank_account" data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.bank_account_name")}} *</label>
                        <input type="text" class="form-control" name="bank_account_name" data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.bank_swift_code")}} *</label>
                        <input type="text" class="form-control" name="bank_swift_code" data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.bank_iban")}} *</label>
                        <input type="number" class="form-control" name="bank_iban" data-validation="required">
                    </div>

                    <div class="form-group">
                        <label>{{helperTrans("ingaz.cost_of_dollar")}} *</label>
                        <input type="number" class="form-control" name="cost_of_dollar" data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.cost_of_other")}} *</label>
                        <input type="number" class="form-control" name="cost_of_other" data-validation="required">
                    </div>
                </div>
                <div class="btns">
                    <button type="button" class="btn btn-prev">{{helperTrans("ingaz.back")}}</button>
                    <button type="submit" class="btn btn-next">{{helperTrans("ingaz.save")}}</button>
                </div>
            </div>
        </form>
    </div>

@endsection
@section('js')

    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "title", orderable: true, searchable: true},
                {"data": "office_number", orderable: true, searchable: true},
                {"data": "license_number", orderable: true, searchable: true},
                {"data": "nationality_id", orderable: true, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("offices.index")}}", columns);
        });
    </script>
    <script>
        const steps = document.querySelectorAll(".form-step");
        const nextBtns = document.querySelectorAll(".btn-next");
        const prevBtns = document.querySelectorAll(".btn-prev");
        const progressCircles = document.querySelectorAll(".step");

        let currentStep = 0;

        function updateSteps() {
            steps.forEach((step, index) => {
                step.classList.toggle("active", index === currentStep);
            });
            progressCircles.forEach((circle, index) => {
                circle.classList.toggle("active", index <= currentStep);
            });
        }

        nextBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                if (currentStep < steps.length - 1) {
                    currentStep++;
                    updateSteps();
                }
            });
        });

        prevBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                if (currentStep > 0) {
                    currentStep--;
                    updateSteps();
                }
            });
        });

        // الجديد: التنقل بالضغط على عنوان التاب
        progressCircles.forEach((circle, index) => {
            circle.addEventListener("click", () => {
                currentStep = index;
                updateSteps();
            });
        });
    </script>

    <script>
        $(document).ready(function () {
            $('.select2').select2({
                placeholder: "{{ helperTrans('ingaz.choose') }}",
                allowClear: true ,
            });
        });
    </script>
@endsection
