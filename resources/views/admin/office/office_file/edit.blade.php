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

        .bootstrap-tagsinput {
            width: 100%;
            min-height: calc(1.5em + .75rem + 2px); /* ارتفاع شبيه بالـ form-control */
            padding: .375rem .75rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: .25rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .bootstrap-tagsinput .tag {
            margin-right: .25rem;
            margin-bottom: .25rem; /* 👈 ده الفاصل بين الصفوف */
            color: #fff;
            background-color: #0d6efd;
            padding: .25rem .5rem;
            border-radius: .2rem;
            font-size: 0.875rem;
        }


        .bootstrap-tagsinput input {
            border: none;
            outline: none;
            background: transparent;
            min-width: 120px;
            flex: 1;
            padding: .25rem 0;
        }


    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection
@section('page-title')
    {{helperTrans("ingaz.edit_office")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
    <div class="wizard-container" style="width: 100%">
        <form id="wizardForm" class="from-submit-global" action="{{ route('offices-file.update',$obj->id) }}"
              method="POST"
              enctype="multipart/form-data">
            @method('PUT')
            <div class="form-step active">
                <div class="form-row">
                    <div class="form-group">
                        <label>{{ helperTrans('ingaz.office') }} *</label>
                        <input type="text" class="form-control" value="{{ $office->title }}" readonly>
                        <input type="hidden" name="recruitment_office_id" value="{{ $office->id }}">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.office_file_name")}} *</label>
                        <input type="text" class="form-control" value="{{ $obj->office_file_name }}"
                               name="office_file_name" data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{ helperTrans("ingaz.job_title_for_file") }} *</label>
                        <select class="form-control" name="job_title_id" data-validation="required">
                            <option value="">{{ helperTrans("ingaz.choose") }}</option>
                            @foreach($jobTitles as $jobTitle)
                                <option
                                        value="{{ $jobTitle->id }}" {{$jobTitle->id == $obj->job_title_id ? 'selected' :''}}>{{ $jobTitle->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ helperTrans("ingaz.religion") }} *</label>
                        <select class="form-control" name="religion_id" data-validation="required">
                            <option value="">{{ helperTrans("ingaz.choose") }}</option>
                            @foreach($religions as $religion)
                                <option
                                        value="{{ $religion->id }}" {{$religion->id == $obj->religion_id ? 'selected' :''}}>{{ $religion->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ helperTrans("ingaz.marital_status") }} *</label>
                        <select class="form-control" name="marital_status" data-validation="required">
                            <option value="">{{ helperTrans("ingaz.choose") }}</option>
                            @foreach($marital_statuses as $marital_status)
                                <option
                                        value="{{ $marital_status->value }}" {{$marital_status->value == $obj->marital_status ? 'selected' :''}}>
                                    {{ \App\Enums\Office\MaritalStatusEnum::tryFrom($marital_status->value)->lang() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.contract_duration")}} *</label>
                        <input type="text" class="form-control" name="contract_duration"
                               value="{{ $obj->contract_duration }}"
                               data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.passport_validity_period")}} *</label>
                        <input type="text" class="form-control" name="passport_validity_period"
                               value="{{ $obj->passport_validity_period }}"
                               data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.salary")}} *</label>
                        <input type="text" class="form-control" name="salary" value="{{ $obj->salary }}"
                               data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{helperTrans("ingaz.recruitment_price")}} *</label>
                        <input type="text" class="form-control" name="recruitment_price"
                               value="{{ $obj->recruitment_price }}" data-validation="required">
                    </div>
                    <div class="form-group">
                        <label>{{ helperTrans("ingaz.languages") }} *</label>
                        <select class="form-control select2" name="languages[]" multiple data-validation="required">
                            @foreach($languages as $language)
                                <option value="{{ $language->value }}"
                                        @if(is_array($obj->languages) && in_array($language->value, $obj->languages)) selected @endif>
                                    {{ \App\Enums\Office\LanguagesTypesEnum::tryFrom($language->value)->lang() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>{{ helperTrans("ingaz.skills") }} *</label>
                        <select class="form-control select2" name="skillsIds[]" multiple data-validation="required">
                            @foreach($skills as $skill)
                                <option value="{{ $skill->id }}" {{in_array($skill->id , $obj->skills()->pluck('skills.id')->ToArray()) ? 'selected' : ''}}>{{ $skill->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ helperTrans('ingaz.passport_issuance_location') }} *</label>
                        <input type="text"
                               class="form-control"
                               name="passport_issuance_location[]"
                               data-validation="required"
                               data-role="tagsinput"
                               value="{{ is_array($obj->passport_issuance_location) ? implode(',', $obj->passport_issuance_location) : $obj->passport_issuance_location }}">
                    </div>

                    <div class="form-group">
                        <label>{{ helperTrans('ingaz.education') }} *</label>
                        <input type="text"
                               class="form-control"
                               name="education[]"
                               data-validation="required"
                               data-role="tagsinput"
                               value="{{ is_array($obj->education) ? implode(',', $obj->education) : $obj->education }}">
                    </div>
                </div>
                <div class="btns">
                    <button type="submit" class="btn btn-next">{{helperTrans("ingaz.save")}}</button>
                </div>
            </div>
        </form>
    </div>

@endsection
@section('js')
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.select2').select2({
                placeholder: "{{ helperTrans('ingaz.choose') }}",
                allowClear: true,
            });
        });
    </script>
    <script>
        $(function () {
            $('[data-role="tagsinput"]').tagsinput({
                trimValue: true,
                confirmKeys: [13, 44] // Enter أو Comma
            });
        });
    </script>
@endsection
