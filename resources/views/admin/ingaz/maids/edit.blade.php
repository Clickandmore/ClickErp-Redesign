@extends('admin.layout.indexs.index')

@php
    $defaultShelterContract = $defaultShelterContract ?? (($obj->maid_accommodation_type ?? null) == 2 ? '1' : '0');
    $isRentalForm = $isRentalForm ?? false;
    $formRedirectRoute = $formRedirectRoute ?? route('maids.index');
    $selectedTypeId = (int) old('type_id', $obj->jobTypes->pluck('id')->first());
    $selectedTypeTitle = collect($types ?? [])->firstWhere('id', $selectedTypeId)->title ?? '';
    $normalizedSelectedTypeTitle = mb_strtolower(trim((string) $selectedTypeTitle));
    $normalizedSelectedTypeTitle = str_replace(['أ', 'إ', 'آ'], 'ا', $normalizedSelectedTypeTitle);
    $showArrivalDateField = $isRentalForm
        || str_contains($normalizedSelectedTypeTitle, 'ايجار')
        || str_contains($normalizedSelectedTypeTitle, 'تاجير')
        || str_contains($normalizedSelectedTypeTitle, 'rent');
    $defaultRentalFinanceItems = app(\App\Services\ingaz\RentalMaidFinanceService::class)->getFormItems();
    $storedRentalFinanceItems = $obj->rentalFinancialAdjustments()
        ->where('context', 'maid')
        ->with('account:id,code,name')
        ->get(['id', 'type', 'label', 'amount', 'account_id'])
        ->map(fn ($item) => [
            'id' => $item->id,
            'account_id' => $item->account_id,
            'account_code' => $item->account?->code,
            'type' => $item->type,
            'label' => $item->label,
            'amount' => $item->amount,
        ])
        ->values()
        ->all();
    $rentalFinanceItems = old('rental_finance_items', !empty($storedRentalFinanceItems) ? $storedRentalFinanceItems : $defaultRentalFinanceItems);
    $rentalFinanceItems = collect($rentalFinanceItems)->map(function ($item) {
        $account = !empty($item['account_id'])
            ? \Nami\Finance\Models\Account::query()->select(['id', 'code', 'name'])->find($item['account_id'])
            : null;

        return [
            'id' => $item['id'] ?? null,
            'account_id' => $item['account_id'] ?? null,
            'account_code' => $item['account_code'] ?? $account?->code,
            'type' => $item['type'] ?? 'expense',
            'label' => $account?->name ?? ($item['label'] ?? ''),
            'amount' => $item['amount'] ?? 0,
        ];
    })->values()->all();
@endphp

@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dropify.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/select2.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/sweetalert2.min.css">
@endsection

@section('page-title')
    {{helperTrans("ingaz.maids")}}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT))
        <div class="card">
            <div class="card-body">
                <form class="row g-3 from-submit-global-maid" method="post" action="{{route("maids.update",$obj->id)}}"
                      enctype="multipart/form-data" id="maidForm">
                    @csrf
                    @method('Put')

                    <!-- الملفات -->

                    <div class="col-6">
                        <label for="TextInput" class="form-label">صورة شخصية </label>
                        <input type="file" data-default-file="{{showFile($obj->image)}}" name="image" class="dropify" >
                    </div>
                    <div class="col-6">
                        <label for="TextInput" class="form-label">صورة كاملة </label>
                        <input type="file" data-default-file="{{showFile($obj->cv_file)}}" name="cv_file" class="dropify">
                    </div>

                    <!-- اسم العاملة -->
                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.maid_name")}} ({{helperTrans('ingaz.en')}})
                        </label>
                        <input type="text" name="name:en" class="form-control" required value="{{$obj->name ?? ''}}">
                    </div>

                    <!-- أنواع الوظائف -->
                    <div class="col-4">
                        <label for="TextInput" class="form-label">{{helperTrans("ingaz.job_types_2")}} </label>
                        <select name="type_id" id="type_id" class="form-control" required>
                            <option value="">اختر نوع السيرة</option>
                            @foreach($types as $type)
                                <option
                                        value="{{$type->id}}" {{ in_array($type->id, $obj->jobTypes->pluck('id')->toArray()) ? 'selected' : '' }}>{{$type->title}}</option>
                            @endforeach
                        </select>
                        <div class="text-muted mt-2 d-none">
                            سيتم تسكينها تلقائياً للإيواء لهذا النوع.
                        </div>
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
                                        data-service_transportation_cost="{{$nationality->service_transportation_cost}}"
                                        data-rental_cost="{{$nationality->rental_cost}}"
                                        {{$obj->nationality_id == $nationality->id ? 'selected' : ''}}>
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
                            {!! showSelectElement($offices , 'title', $obj->office_id) !!}
                        </select>
                    </div>

                    <!-- خيار تسكين للإيواء -->
                    <div class="col-4" id="shelter_option">
                        <label for="create_shelter_contract" class="form-label">تسكين للإيواء</label>
                        <select name="create_shelter_contract" id="create_shelter_contract" class="form-control shelter-question-select">
                            <option value="0">لا - لا تسكين</option>
                            <option value="1">نعم - تسكين للإيواء</option>
                        </select>
                        <small class="text-muted">سيتم إنشاء عقد إيواء تلقائياً باسم {{ documentSetting('document_company_name', 'شركة بلاتنيوم الشرقية') }}</small>
                    </div>

                    <!-- الوظائف -->
                    <div class="col-4">
                        <label for="TextInput" class="form-label">{{helperTrans("ingaz.job_2")}} </label>
                        <select name="job_id[]" data-placeholder="{{helperTrans('ingaz.choose')}}"
                                class="form-control js-example-basic-multiple" multiple required>
                            @foreach($jobs as $job)
                                <option
                                        value="{{$job->id}}" {{ in_array($job->id, $obj->jobTitles->pluck('id')->toArray()) ? 'selected' : '' }}>{{$job->title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <label for="request_type" class="form-label">{{ helperTrans('ingaz.request_type') }}</label>
                        @php
                            $requestType = old('request_type', \App\Models\ingaz\Maid::resolveRequestType($obj->request_type));
                        @endphp
                        <select name="request_type" id="request_type" class="form-control">
                            @foreach (\App\Models\ingaz\Maid::getRequestTypeOptions() as $value => $label)
                                <option value="{{ $value }}" {{ $requestType === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- السعر والراتب -->
                    <div class="col-4">
                        <label for="TextInput" class="form-label price-label">
                            {{helperTrans("ingaz.price")}}
                        </label>
                        <input type="number" name="price" id="price" class="form-control" value="{{$obj->price}}">
                    </div>
{{--                    <div class="col-4">--}}
{{--                        <label for="offer_price" class="form-label">{{ helperTrans('ingaz.offer_price_new') }}</label>--}}
{{--                        <input type="number" step="0.01" name="offer_price" id="offer_price" class="form-control"--}}
{{--                               value="{{ old('offer_price', $obj->offer_price) }}"--}}
{{--                               placeholder="{{ helperTrans('ingaz.offer_price_placeholder') }}">--}}
{{--                    </div>--}}
{{--                    <div class="col-4">--}}
{{--                        <label for="offer_duration" class="form-label">{{ helperTrans('ingaz.offer_duration') }}</label>--}}
{{--                        <input type="text" name="offer_duration" id="offer_duration" class="form-control"--}}
{{--                               value="{{ old('offer_duration', $obj->offer_duration) }}"--}}
{{--                               placeholder="{{ helperTrans('ingaz.offer_duration_placeholder') }}">--}}
{{--                    </div>--}}
{{--                    <div class="col-4">--}}
{{--                        <label for="offer_starts_at" class="form-label">{{ helperTrans('ingaz.offer_starts_at') }}</label>--}}
{{--                        <input type="date" name="offer_starts_at" id="offer_starts_at" class="form-control"--}}
{{--                               value="{{ old('offer_starts_at', optional($obj->offer_starts_at)->format('Y-m-d')) }}">--}}
{{--                    </div>--}}
{{--                    <div class="col-4">--}}
{{--                        <label for="offer_expires_at" class="form-label">{{ helperTrans('ingaz.offer_expires_at') }}</label>--}}
{{--                        <input type="date" name="offer_expires_at" id="offer_expires_at" class="form-control"--}}
{{--                               value="{{ old('offer_expires_at', optional($obj->offer_expires_at)->format('Y-m-d')) }}">--}}
{{--                    </div>--}}
{{--                    <div class="col-12">--}}
{{--                        <label for="offer_description" class="form-label">{{ helperTrans('ingaz.offer_description') }}</label>--}}
{{--                        <textarea name="offer_description" id="offer_description" class="form-control" rows="2"--}}
{{--                                  placeholder="{{ helperTrans('ingaz.offer_description_placeholder') }}">{{ old('offer_description', $obj->offer_description) }}</textarea>--}}
{{--                    </div>--}}
                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.salary")}}
                        </label>
                        <input type="number" name="salary" class="form-control" value="{{$obj->salary}}">
                    </div>

                    <!-- البيانات الشخصية -->
                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            تاريخ الميلاد
                        </label>
                        <input type="date" name="birth_date" id="birth_date" class="form-control"
                               value="{{$obj->birth_date}}">
                    </div>

                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.age")}}
                        </label>
                        <input type="number" min="18" max="90" name="age" id="age" class="form-control" readonly
                               value="{{$obj->age}}">
                    </div>

                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.height")}}
                        </label>
                        <input type="text" name="height" class="form-control" value="{{$obj->height ?? '160'}}"
                               placeholder="الطول بالسنتيمتر">
                    </div>

                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.weight")}}
                        </label>
                        <input type="text" name="weight" class="form-control" value="{{$obj->weight ?? '60'}}"
                               placeholder="الوزن بالكيلوجرام">
                    </div>

                    <div class="col-4">
                        <label for="TextInput" class="form-label">{{helperTrans("ingaz.religion")}}</label>
                        <select name="religion_id" class="form-control">
                            @foreach($religions as $religion)
                                <option
                                        value="{{$religion->id}}" {{$obj->religion_id == $religion->id ? 'selected' : ''}}>{{$religion->title}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-4">
                        <label for="TextInput" class="form-label">{{helperTrans("ingaz.marital status")}} </label>
                        <select name="marital_status:ar" class="form-control" required>
                            <option
                                    value="عازبة" {{(optional($obj->translate('ar'))->marital_status == 'عازبة') ? 'selected' : ''}}>
                                عازبة
                            </option>
                            <option
                                    value="أعزب" {{(optional($obj->translate('ar'))->marital_status == 'أعزب') ? 'selected' : ''}}>
                                أعزب
                            </option>
                            <option
                                    value="متزوجة" {{(optional($obj->translate('ar'))->marital_status == 'متزوجة') ? 'selected' : ''}}>
                                متزوجة
                            </option>
                            <option
                                    value="متزوج" {{(optional($obj->translate('ar'))->marital_status == 'متزوج') ? 'selected' : ''}}>
                                متزوج
                            </option>
                        </select>
                        <input type="hidden" name="marital_status:en" id="marital_status_en"
                               value="{{ optional($obj->translate('en'))->marital_status }}">
                    </div>
                    
                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.childrens_count")}}
                        </label>
                        <input type="number" name="childrens_count" class="form-control" min="0" step="1"
                               value="{{$obj->childrens_count}}">
                    </div>
                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.duration")}}
                        </label>
                        <input type="text" name="duration" class="form-control"
                               value="{{ old('duration', $obj->duration ?: '24 شهر') }}">
                    </div>
                    <div class="col-4" id="arrival_date_field" style="{{ $showArrivalDateField ? '' : 'display: none;' }}">
                        <label for="arrival_date" class="form-label">تاريخ وصول العاملة <small
                                class="text-muted">(اختياري)</small></label>
                        <input type="date" name="arrival_date" id="arrival_date" class="form-control"
                               value="{{ old('arrival_date', optional($obj->arrival_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.additional_notes")}}
                        </label>
                        <textarea type="text" name="additional_notes" class="form-control">{{$obj->additional_notes}}</textarea>
                    </div>

                    <!-- رقم الجواز ورقم الهوية وتاريخ انتهاء الهوية -->
                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.passport_num")}}
                        </label>
                        <input type="text" name="passport_num" id="passport_num" class="form-control" required
                               value="{{$obj->passport_num}}">
                        <div id="passport_num_feedback" class="invalid-feedback"></div>
                    </div>

                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.id_number")}}
                        </label>
                        <input type="text" name="id_number" id="id_number" class="form-control" maxlength="30"
                               value="{{$obj->id_number}}">
                        <small class="text-muted">يمكن إدخال أرقام أو حروف أو شرطة (-) بحد أقصى 30 خانة</small>
                    </div>

                    <div class="col-4">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.id_expiry_date")}}
                        </label>
                        <input type="date" name="id_expiry_date" class="form-control"
                               value="{{$obj->id_expiry_date}}">
                    </div>

                    <!-- المهارات -->
                    <div class="col-4">
                        <label for="skills" class="form-label">{{helperTrans("ingaz.skills")}} <small
                                    class="text-muted">(اختياري)</small></label>
                        <select name="skills[]" class="form-control js-example-basic-multiple" multiple>
                            @foreach($skills as $skill)
                                @php
                                    $maidSkills = [];
                                    if ($obj->skills) {
                                        if (is_array($obj->skills)) {
                                            $maidSkills = $obj->skills;
                                        } else {
                                            $maidSkills = json_decode($obj->skills, true) ?? [];
                                        }
                                    }
                                @endphp
                                <option
                                        value="{{$skill->id}}" {{ in_array($skill->id, $maidSkills) ? 'selected' : '' }}>{{$skill->title}}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- حالة الخبرة -->
                    <div class="col-4">
                        <label for="experience_status" class="form-label">
                            حالة الخبرة <small class="text-muted">(مطلوب)</small>
                        </label>
                        <select name="experience_status" id="experience_status" class="form-control" required>
                            <option value="">اختر حالة الخبرة</option>
                            <option
                                    value="has_experience" {{$obj->experience_status == 'has_experience' ? 'selected' : ''}}>
                                سبق لها العمل
                            </option>
                            <option
                                    value="no_experience" {{$obj->experience_status == 'no_experience' ? 'selected' : ''}}>
                                لم يسبق لها العمل
                            </option>
                        </select>
                    </div>

                    {{-- <div class="col-4" id="experience_years_container"
                         style="display: {{$obj->experience_status == 'has_experience' ? 'block' : 'none'}};">
                        <label for="TextInput" class="form-label">
                            {{helperTrans("ingaz.experience_years")}} <small class="text-muted">(مطلوب)</small>
                        </label>
                        <select name="experience_years" id="experience_years"
                                class="form-control" {{$obj->experience_status == 'has_experience' ? 'required' : ''}}>
                            <option value="">اختر سنوات الخبرة</option>
                            @for($i = 1; $i <= 10; $i++)
                                <option
                                        value="{{$i}}" {{$obj->experience_years == $i ? 'selected' : ''}}>{{$i}} {{$i == 1 ? 'سنة' : 'سنوات'}}</option>
                            @endfor
                        </select>
                    </div> --}}

                    <!-- اللغات والحالة الاجتماعية -->
                    <div class="col-12">
                        <label for="TextInput" class="form-label">{{helperTrans("ingaz.languages")}} <small
                                    class="text-muted">(اختياري)</small></label>
                        @php
                            $langs = is_array($obj->languages)
                                ? $obj->languages
                                : (json_decode($obj->languages, true) ?? []);
                            $percentages = is_array($obj->percentage)
                                ? $obj->percentage
                                : (json_decode($obj->percentage, true) ?? []);
                        @endphp

                        <div class="row">
                            @foreach (['arabic'=>'اللغة العربية',
                                       'english'=>'اللغة الإنجليزية',
                                       'amharic'=>'اللغة الأمهرية',
                                       'mother'=>'اللغة الأم'] as $key => $label)

                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input lang-check"
                                               type="checkbox"
                                               name="languages[{{ $key }}]"
                                               id="{{ $key }}"
                                               value="{{ $key }}"
                                                {{ in_array($key, $langs) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $key }}">{{ $label }}</label>
                                    </div>

                                    <div class="lang-extra mt-2 {{ in_array($key, $langs) ? '' : 'd-none' }}">
                                        @if (in_array($key, $langs))
                                            <input type="number" min="0" max="100" class="form-control" placeholder="النسبة %"
                                                name="percentage[{{ $key }}]" value="{{ $percentages[$key] ?? '' }}">
                                        @endif
                                    </div>
                                </div>

                            @endforeach
                        </div>
                </div>
                    <div class="col-12">
                        <label for="TextInput" class="form-label">رابط الفيديو <small class="text-muted">(اختياري)</small></label>
                        <input type="url" name="video_link" class="form-control" value="{{$obj->video_link}}" placeholder="https://...">
                    </div>
                <div class="col-12" id="experience_section" style="display: {{$obj->experience_status == 'has_experience' ? 'block' : 'none'}};">
                    <label for="TextInput" class="form-label">{{helperTrans("ingaz.expertise")}}</label>
                    <table class="table table-bordered" id="experience_table">
                        <thead>
                        <tr>
                            <th>{{ __("ingaz.country") }} </th>
                            <th>{{ __("ingaz.number_of_years") }}</th>
                            <th>{{ __("ingaz.job_title") }}</th>
                            <th style="width:150px">{{ __("ingaz.actions") }}</th>
                        </tr>
                        </thead>
                        <tbody id="json_array">
                        @forelse($obj->expertises as $index => $expertise)
                            <tr class="y-group">
                                <td>
                                    <select name="expertises[{{$index}}][country_id]" data-placeholder=""
                                            class="form-control select2" data-exp-required="true">
                                        <option value="">{{helperTrans('ingaz.choose')}}</option>
                                        @foreach($countries as $country)

                                            <option value="{{$country->id}}" {{$expertise->country_id == $country->id ? 'selected' : ''}}>{{$country->name_ar}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="experience-duration-wrapper">
                                        <div class="input-group">
                                            <input type="number" min="0" step="0.001" class="form-control exp-duration-value" placeholder="{{ helperTrans('ingaz.duration_value_placeholder') }}" data-exp-required="true">
                                            <select class="form-select exp-duration-unit" data-exp-required="true">
                                                <option value="years">{{ helperTrans('ingaz.years') }}</option>
                                                <option value="months">{{ helperTrans('ingaz.months') }}</option>
                                            </select>
                                        </div>
                                        <input type="hidden" name="expertises[{{$index}}][number_of_years]" class="exp-duration-years" value="{{$expertise->number_of_years}}" data-exp-required="true">
                                    </div>
                                </td>
                                <td>
                                    <select name="expertises[{{$index}}][job_title_id]" data-placeholder=""
                                            class="form-control select2" data-exp-required="true">
                                        <option value="">{{helperTrans('ingaz.choose')}}</option>
                                        @foreach($jobs as $job)
                                            <option value="{{$job->id}}" {{$expertise->job_title_id == $job->id ? 'selected' : ''}}>{{$job->title}}</option>
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
                        @empty
                            <tr class="y-group">
                                <td>
                                    <select name="expertises[0][country_id]" data-placeholder=""
                                            class="form-control select2" data-exp-required="true">
                                        <option value="">{{helperTrans('ingaz.choose')}}</option>
                                        @foreach($countries as $country)
                                            <option value="{{$country->id}}">{{$country->name_ar}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="experience-duration-wrapper">
                                        <div class="input-group">
                                            <input type="number" min="0" step="0.1" class="form-control exp-duration-value" placeholder="{{ helperTrans('ingaz.duration_value_placeholder') }}" data-exp-required="true">
                                            <select class="form-select exp-duration-unit" data-exp-required="true">
                                                <option value="years">{{ helperTrans('ingaz.years') }}</option>
                                                <option value="months">{{ helperTrans('ingaz.months') }}</option>
                                            </select>
                                        </div>
                                        <input type="hidden" name="expertises[0][number_of_years]" class="exp-duration-years" data-exp-required="true">
                                    </div>
                                </td>
                                <td>
                                    <select name="expertises[0][job_title_id]" data-placeholder=""
                                            class="form-control select2" data-exp-required="true">
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
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- اللغات والحالة الاجتماعية -->
{{--                <div class="col-12">--}}
{{--                        <label for="TextInput" class="form-label">{{helperTrans("ingaz.languages")}} <small--}}
{{--                                class="text-muted">(اختياري)</small></label>--}}
{{--                        @php--}}
{{--                            $langs = is_array($obj->languages)--}}
{{--                                ? $obj->languages--}}
{{--                                : (json_decode($obj->languages, true) ?? []);--}}
{{--                            $percentages = is_array($obj->percentage)--}}
{{--                                ? $obj->percentage--}}
{{--                                : (json_decode($obj->percentage, true) ?? []);--}}
{{--                        @endphp--}}

{{--                        <div class="row">--}}
{{--                            @foreach (['arabic'=>'اللغة العربية',--}}
{{--                                       'english'=>'اللغة الإنجليزية',--}}
{{--                                       'amharic'=>'اللغة الأمهرية',--}}
{{--                                       'mother'=>'اللغة الأم'] as $key => $label)--}}

{{--                                <div class="col-md-3">--}}
{{--                                    <div class="form-check">--}}
{{--                                        <input class="form-check-input lang-check"--}}
{{--                                               type="checkbox"--}}
{{--                                               name="languages[{{ $key }}]"--}}
{{--                                               id="{{ $key }}"--}}
{{--                                               value="{{ $key }}"--}}
{{--                                            {{ in_array($key, $langs) ? 'checked' : '' }}>--}}
{{--                                        <label class="form-check-label" for="{{ $key }}">{{ $label }}</label>--}}
{{--                                    </div>--}}

{{--                                    <div class="lang-extra mt-2"--}}
{{--                                         style="{{ in_array($key,$langs) ? '' : 'display:none;' }}">--}}
{{--                                        <input type="number" min="0" max="100"--}}
{{--                                               class="form-control"--}}
{{--                                               placeholder="النسبة %"--}}
{{--                                               name="percentage[{{ $key }}]"--}}
{{--                                               value="{{ $percentages[$key] ?? '' }}">--}}
{{--                                    </div>--}}
{{--                                </div>--}}

{{--                            @endforeach--}}
{{--                        </div>--}}
{{--                    </div>--}}
                    <div class="col-12" id="rental_finance_preview" style="display: none;">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="mb-3">بنود مالية التأجير (السيرة)</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 15%;">الحساب</th>
                                                <th>البند</th>
                                                <th style="width: 22%;">المبلغ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($rentalFinanceItems as $index => $item)
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark border">
                                                            {{ $item['account_code'] ?: '-' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        {{ $item['label'] ?? '' }}
                                                        @if (!empty($item['id']))
                                                            <input type="hidden" name="rental_finance_items[{{ $index }}][id]" value="{{ $item['id'] }}">
                                                        @endif
                                                        <input type="hidden" name="rental_finance_items[{{ $index }}][account_id]" value="{{ $item['account_id'] ?? '' }}">
                                                        <input type="hidden" name="rental_finance_items[{{ $index }}][type]" value="{{ $item['type'] ?? 'expense' }}">
                                                        <input type="hidden" name="rental_finance_items[{{ $index }}][label]" value="{{ $item['label'] ?? '' }}">
                                                    </td>
                                                    <td>
                                                        <input type="number" name="rental_finance_items[{{ $index }}][amount]"
                                                            class="form-control form-control-sm text-end" step="0.01"
                                                            min="0" value="{{ $item['amount'] ?? 0 }}">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted d-block mt-2">سيتم إنشاء هذه البنود تلقائياً ضمن مالية التأجير بعد حفظ السيرة.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
                        <a href="{{ $formRedirectRoute }}"
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

            const formatYearsValue = function (value, unit) {
                if (value === '' || value === null || typeof value === 'undefined') {
                    return '';
                }

                const numericValue = parseFloat(value);
                if (Number.isNaN(numericValue)) {
                    return '';
                }

                if (unit === 'months') {
                    return (numericValue / 12).toFixed(2);
                }

                return numericValue.toFixed(2);
            };

            window.syncExperienceRow = function ($row) {
                const $valueInput = $row.find('.exp-duration-value');
                const $unitSelect = $row.find('.exp-duration-unit');
                const $hiddenInput = $row.find('.exp-duration-years');

                const rawValue = $valueInput.val();
                const unit = $unitSelect.val();

                const formattedYears = formatYearsValue(rawValue, unit);
                $hiddenInput.val(formattedYears);
            };

            window.initializeExperienceRow = function ($row) {
                const $valueInput = $row.find('.exp-duration-value');
                const $unitSelect = $row.find('.exp-duration-unit');
                const $hiddenInput = $row.find('.exp-duration-years');

                const initialYears = parseFloat($hiddenInput.val());

                if (!Number.isNaN(initialYears) && initialYears > 0) {
                    if (initialYears < 1) {
                        $unitSelect.val('months');
                        const months = (initialYears * 12).toFixed(1);
                        $valueInput.val(parseFloat(months));
                    } else {
                        $unitSelect.val('years');
                        $valueInput.val(parseFloat(initialYears.toFixed(1)));
                    }
                } else {
                    $unitSelect.val('years');
                    $valueInput.val('');
                    $hiddenInput.val('');
                }

                window.syncExperienceRow($row);
            };

            window.attachExperienceHandlers = function (context) {
                const $scope = context ? $(context) : $('#experience_section');

                $scope.find('tr.y-group').each(function () {
                    const $row = $(this);

                    $row.find('.exp-duration-value').off('.exp').on('input.exp change.exp', function () {
                        window.syncExperienceRow($row);
                    });

                    $row.find('.exp-duration-unit').off('.exp').on('change.exp', function () {
                        window.syncExperienceRow($row);
                    });

                    window.initializeExperienceRow($row);
                });
            };

            // إعادة ترقيم أسماء الحقول بعد أي تغيير
            window.reindexRows = function () {
                $('#json_array tr.y-group').each(function (rowIndex) {
                    $(this).find('input, select, textarea').each(function () {
                        let name = $(this).attr('name') || '';
                        name = name.replace(/\[\d+\]/, '[' + rowIndex + ']');
                        $(this).attr('name', name);
                    });
                });
            }

            // إظهار/إخفاء أزرار الحذف بناءً على عدد الصفوف
            window.updateRemoveButtons = function () {
                const rowCount = $('#json_array tr.y-group').length;
                if (rowCount <= 1) {
                    $('#json_array .btnRemove').hide(); // اختفاء الزرار لو الصف واحد أو أقل
                } else {
                    $('#json_array .btnRemove').show(); // إظهار لو أكثر من صف
                }
            }

            window.reindexRows();
            window.updateRemoveButtons();
            window.attachExperienceHandlers();

            // إضافة صف (ينسخ الصف الحالي ويُفرغ الحقول)
            $(document).on('click', '.btnAdd', function () {
                const $currentRow = $(this).closest('tr');
                const $clone = $currentRow.clone();
                $clone.find('input, textarea').val('');
                $clone.find('span.select2').remove();
                $clone.find('select.select2').each(function () {
                    $(this)
                        .removeAttr('data-select2-id')
                        .removeClass('select2-hidden-accessible')
                        .prop('selectedIndex', 0);
                });
                $clone.find('.exp-duration-unit').val('years');
                $currentRow.after($clone);
                window.reindexRows();
                window.updateRemoveButtons();
                if (typeof window.initExperienceSelects === 'function') {
                    window.initExperienceSelects($clone);
                }
                window.attachExperienceHandlers($clone);
            });

            // حذف صف
            $(document).on('click', '.btnRemove', function () {
                const $rows = $('#json_array tr.y-group');
                if ($rows.length <= 1) {
                    return; // لن يحدث شيء؛ الزر مخفي فعلاً لكن هنا كحماية
                }
                const $row = $(this).closest('tr');
                if ($row.find('select.select2[data-select2-id]').length) {
                    $row.find('select.select2').select2('destroy');
                }
                $row.remove();
                window.reindexRows();
                window.updateRemoveButtons();
                window.attachExperienceHandlers();
            });

        });
    </script>
    <script>
        $(document).ready(function () {
            $('.dropify').dropify();
            $('.js-example-basic-multiple').select2();

            window.initExperienceSelects = function (context) {
                const $context = context ? $(context) : $('#experience_section');
                $context.find('select.form-control.select2').each(function () {
                    const $select = $(this);
                    if ($select.data('select2')) {
                        $select.select2('destroy');
                    }
                    $select.select2({
                        width: '100%',
                        dropdownParent: $('#experience_section')
                    });
                });
            };

            window.initExperienceSelects();
            window.attachExperienceHandlers();

            window.clearExperienceSection = function () {
                $('#json_array tr.y-group').find('input, textarea').val('');
                $('#json_array tr.y-group').find('select').each(function () {
                    $(this).val('').trigger('change');
                });
                $('#json_array tr.y-group').slice(1).remove();
                window.reindexRows();
                window.updateRemoveButtons();
                window.initExperienceSelects();
                window.attachExperienceHandlers();
            }

            window.toggleExperienceSection = function () {
                var status = $('#experience_status').val();
                var $section = $('#experience_section');
                var $fields = $section.find('[data-exp-required]');

                if (status === 'has_experience') {
                    $section.slideDown();
                    $fields.each(function () {
                        $(this).attr('required', 'required');
                    });
                    window.initExperienceSelects();
                    window.attachExperienceHandlers();
                } else {
                    $fields.each(function () {
                        $(this).removeAttr('required');
                    });
                    window.clearExperienceSection();
                    $section.slideUp();
                }
            }

            $('#experience_status').on('change', window.toggleExperienceSection);
            window.toggleExperienceSection();

            var maritalStatusEnField = $('#marital_status_en');
            var maritalStatusTranslations = {
                'عازبة': 'single',
                'أعزب': 'single',
                'متزوجة': 'married',
                'متزوج': 'married'
            };

            function updateMaritalStatusTranslation() {
                var selectedStatus = $('select[name="marital_status:ar"]').val();
                if (selectedStatus && maritalStatusTranslations[selectedStatus]) {
                    maritalStatusEnField.val(maritalStatusTranslations[selectedStatus]);
                } else {
                    maritalStatusEnField.val('');
                }
            }

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
                var shelterOption = $('#shelter_option');
                var shouldUpdateMaritalTranslation = true;

                // Reset all fields to default state
                idFields.show();
                heightWeightFields.show();
                maritalStatusField.show();
                officeField.show();
                salaryField.show();
                jobField.show();
                languagesField.show();
                shelterOption.hide(); // Hide shelter option by default
                $('#create_shelter_contract').val('0'); // Reset shelter option
                priceLabel.text('{{helperTrans("ingaz.price")}}');
                $('input[name="id_number"], input[name="id_expiry_date"]').attr('required', 'required');
                $('input[name="height"], input[name="weight"]').attr('required', 'required');
                $('select[name="marital_status:ar"]').attr('required', 'required');
                $('select[name="office_id"]').attr('required', 'required');
                $('input[name="salary"]').attr('required', 'required');
                $('select[name="job_id[]"]').attr('required', 'required');

                // Handle specific cases
                if (selectedType === 'استقدام') {
                    // Hide ID fields for استقدام
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
                    maritalStatusEnField.val('');
                    shouldUpdateMaritalTranslation = false;

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
                    languagesField.hide();
                    priceLabel.text('تكلفه الايجار الشهري');

                    // Show shelter option for rental maids
                    shelterOption.show();
                    maritalStatusEnField.val('');
                    shouldUpdateMaritalTranslation = false;

                    // Remove required attribute from hidden fields
                    $('input[name="height"], input[name="weight"]').removeAttr('required');
                    $('select[name="marital_status:ar"]').removeAttr('required');
                    $('input[name="salary"]').removeAttr('required');

                    // Set rental cost from nationality if selected
                    var selectedNationality = $('#Pnationality_id option:selected');
                    if (selectedNationality.length) {
                        var rentalCost = selectedNationality.data('rental_cost');
                        if (rentalCost) {
                            $('#price').val(rentalCost);
                        }
                    }
                }

                if (shouldUpdateMaritalTranslation) {
                    updateMaritalStatusTranslation();
                }
            }

            // Call on page load
            toggleFields();
            updateMaritalStatusTranslation();

            // Call when type changes
            $('select[name="type_id"]').change(function () {
                console.log('Type changed');
                toggleFields();
            });

            $('select[name="marital_status:ar"]').on('change', updateMaritalStatusTranslation);

            // التحقق من رقم الهوية
            $('#id_number').on('input', function () {
                var idNumber = $(this).val();
                // السماح فقط بالأحرف والأرقام والشرطة
                idNumber = idNumber.replace(/[^0-9a-zA-Z-]/g, '');
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
                            maid_id: '{{$obj->id}}', // Add maid ID for edit form
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
                                var selected = office.id == '{{$obj->office_id}}' ? 'selected' : '';
                                officeSelect.append('<option value="' + office.id + '" ' + selected + '>' + office.title + '</option>');
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
                }
            });

            // التحقق من النموذج قبل الإرسال
            const defaultShelterContract = @json($defaultShelterContract);
            const isRentalForm = {{ $isRentalForm ? 'true' : 'false' }};

            function hydrateShelterCopy() {
                var shelterOption = $('#shelter_option');
                var shelterSelect = $('#create_shelter_contract');
                var shelterLabel = shelterOption.find('label[for="create_shelter_contract"]');
                var shelterHint = shelterOption.find('small.text-muted').last();

                shelterLabel.addClass('shelter-question-label').text('تسكين للإيواء');
                shelterSelect.addClass('shelter-question-select');
                shelterSelect.find('option[value="0"]').text('لا - عاملة عادية');
                shelterSelect.find('option[value="1"]').text('نعم - تسكين للإيواء');

                if (!$('#shelter_auto_notice').length) {
                    $('<div>', {
                        id: 'shelter_auto_notice',
                        class: 'text-muted mt-2',
                        text: 'سيتم تسكينها تلقائياً للإيواء لهذا النوع.'
                    }).hide().insertAfter(shelterSelect);
                }

                if (shelterHint.length) {
                    shelterHint.text('سيتم إنشاء عقد إيواء تلقائياً باسم شركة أسس الانجاز');
                }

                if (defaultShelterContract !== '') {
                    shelterSelect.val(defaultShelterContract);
                }
            }

            function getTypeContext() {
                var selectedTypeOriginal = $('select[name="type_id"] option:selected').text() || '';
                var trimmedType = selectedTypeOriginal.trim();
                var normalizedArabicType = trimmedType
                    .replace(/[إأآ]/g, 'ا')
                    .replace(/\u0649/g, 'ي')
                    .replace(/\s+/g, ' ');
                var normalizedArabicLower = normalizedArabicType.toLowerCase();
                var selectedTypeLower = trimmedType.toLowerCase();

                return {
                    original: trimmedType,
                    normalizedArabicLower: normalizedArabicLower,
                    selectedTypeLower: selectedTypeLower,
                    isRental: normalizedArabicLower.includes('ايجار') ||
                        normalizedArabicLower.includes('تاجير') ||
                        selectedTypeLower.includes('rent'),
                    isInstantTransfer: normalizedArabicLower === 'استلام فوري' ||
                        selectedTypeLower.includes('instant'),
                    isRecruitment: normalizedArabicLower.includes('استقدام') ||
                        selectedTypeLower.includes('recruit')
                };
            }

            function applyPriceFromNationality(typeContext) {
                var selectedNationality = $('#Pnationality_id option:selected');
                var context = typeContext || getTypeContext();

                if (!selectedNationality.length) {
                    $('#price').val('0');
                    return;
                }

                var serviceTransferCost = parseFloat(selectedNationality.data('service_transportation_cost')) || 0;
                var rentalCost = parseFloat(selectedNationality.data('rental_cost')) || 0;
                var finalPrice = 0;

                if (context.isRental) {
                    finalPrice = rentalCost || 0;
                } else if (context.isInstantTransfer) {
                    finalPrice = serviceTransferCost || 0;
                }

                $('#price').val(finalPrice.toString());
            }

            function toggleShelterOption() {
                var shelterOption = $('#shelter_option');
                var shelterSelect = $('#create_shelter_contract');
                var shelterNotice = $('#shelter_auto_notice');
                var shelterLabel = $('.shelter-question-label');
                var shelterSelectField = $('.shelter-question-select');
                var isRentalType = isRentalForm || getTypeContext().isRental;

                if (isRentalType) {
                    shelterOption.show();
                    shelterSelect.val('1');
                    shelterSelect.prop('disabled', false);
                    shelterLabel.hide();
                    shelterSelectField.hide();
                    shelterNotice.show();
                } else {
                    shelterOption.hide();
                    shelterSelect.val('0');
                    shelterSelect.prop('disabled', true);
                    shelterLabel.show();
                    shelterSelectField.show();
                    shelterNotice.hide();
                }
            }

            function toggleFields() {
                var typeContext = getTypeContext();
                var idFields = $('input[name="id_number"], input[name="id_expiry_date"]').closest('.col-4');
                var heightWeightFields = $('input[name="height"], input[name="weight"]').closest('.col-4');
                var maritalStatusField = $('select[name="marital_status:ar"]').closest('.col-4');
                var officeField = $('.office-field');
                var salaryField = $('input[name="salary"]').closest('.col-4');
                var jobField = $('select[name="job_id[]"]').closest('.col-4');
                var languagesField = $('.lang-check').closest('.col-12');
                var priceLabel = $('.price-label');
                var rentalFinanceSection = $('#rental_finance_preview');
                var idInputs = $('input[name="id_number"], input[name="id_expiry_date"]');
                var heightWeightInputs = $('input[name="height"], input[name="weight"]');
                var maritalStatusSelect = $('select[name="marital_status:ar"]');
                var officeSelect = $('select[name="office_id"]');
                var salaryInput = $('input[name="salary"]');
                var jobSelect = $('select[name="job_id[]"]');
                var arrivalDateField = $('#arrival_date_field');
                var arrivalDateInput = $('#arrival_date');
                var shouldUpdateMaritalTranslation = true;

                idFields.show();
                heightWeightFields.show();
                maritalStatusField.show();
                officeField.show();
                salaryField.show();
                jobField.show();
                languagesField.show();
                arrivalDateField.hide();
                priceLabel.text('{{helperTrans("ingaz.price")}}');
                idInputs.attr('required', 'required');
                heightWeightInputs.attr('required', 'required');
                maritalStatusSelect.attr('required', 'required');
                officeSelect.attr('required', 'required');
                salaryInput.attr('required', 'required');
                jobSelect.attr('required', 'required');
                arrivalDateInput.prop('required', false);
                $('#price').val('0');

                if (typeContext.isRecruitment) {
                    idFields.hide();
                    idInputs.removeAttr('required');
                    arrivalDateInput.val('');
                    rentalFinanceSection.hide();
                } else if (typeContext.isInstantTransfer) {
                    idFields.hide();
                    heightWeightFields.hide();
                    maritalStatusField.hide();
                    officeField.hide();
                    priceLabel.text('تكلفة نقل الخدمات');
                    maritalStatusEnField.val('');
                    shouldUpdateMaritalTranslation = false;
                    idInputs.removeAttr('required');
                    heightWeightInputs.removeAttr('required');
                    maritalStatusSelect.removeAttr('required');
                    officeSelect.removeAttr('required');
                    arrivalDateInput.val('');
                    rentalFinanceSection.hide();
                } else if (typeContext.isRental) {
                    heightWeightFields.hide();
                    maritalStatusField.hide();
                    salaryField.hide();
                    languagesField.hide();
                    arrivalDateField.show();
                    priceLabel.text('تكلفه الايجار الشهري');
                    maritalStatusEnField.val('');
                    shouldUpdateMaritalTranslation = false;
                    heightWeightInputs.removeAttr('required');
                    maritalStatusSelect.removeAttr('required');
                    salaryInput.removeAttr('required');
                    rentalFinanceSection.show();
                } else {
                    arrivalDateInput.val('');
                    rentalFinanceSection.hide();
                }

                if (shouldUpdateMaritalTranslation) {
                    updateMaritalStatusTranslation();
                }

                applyPriceFromNationality(typeContext);
            }

            hydrateShelterCopy();
            toggleFields();
            toggleShelterOption();
            updateMaritalStatusTranslation();

            $('select[name="type_id"]').off('change').on('change', function () {
                toggleFields();
                toggleShelterOption();
            });

            $('#Pnationality_id').off('change').on('change', function () {
                var nationalityId = $(this).val();
                applyPriceFromNationality();

                if (nationalityId) {
                    $.ajax({
                        url: "{{ route('getOfficesByNationality') }}",
                        type: 'GET',
                        data: { nationality_id: nationalityId },
                        success: function (response) {
                            var officeSelect = $('select[name="office_id"]');
                            officeSelect.empty();
                            officeSelect.append('<option value="">Ø§Ø®ØªØ± Ù…ÙƒØªØ¨ Ø§Ù„Ø§Ø³ØªÙ‚Ø¯Ø§Ù…</option>');

                            officeSelect.find('option').first().text('اختر مكتب الاستقدام');

                            response.forEach(function (office) {
                                var selected = office.id == '{{$obj->office_id}}' ? 'selected' : '';
                                officeSelect.append('<option value="' + office.id + '" ' + selected + '>' + office.title + '</option>');
                            });
                        },
                        error: function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'Ø®Ø·Ø£',
                                text: 'Ø­Ø¯Ø« Ø®Ø·Ø£ Ø£Ø«Ù†Ø§Ø¡ Ø¬Ù„Ø¨ Ù…ÙƒØ§ØªØ¨ Ø§Ù„Ø§Ø³ØªÙ‚Ø¯Ø§Ù…',
                                confirmButtonText: 'Ø­Ø³Ù†Ø§Ù‹'
                            });
                        }
                    });
                } else {
                    $('#price').val('0');
                    $('input[name="salary"]').val('');
                }
            });

            $('#maidForm').on('submit', function (e) {
                e.preventDefault();

                var isValid = true;
                var errors = [];
                var typeContext = getTypeContext();

                // التحقق من الملفات
                var image = $('input[name="image"]').get(0).files.length;
                var hasExistingImage = '{{$obj->image}}' ? true : false;

                if (!image && !hasExistingImage) {
                    errors.push('الرجاء اختيار صورة العاملة');
                    isValid = false;
                }

                // التحقق من رقم الهوية وتاريخ الانتهاء لكل الأنواع التي تتطلبهما، ومنها التأجير
                if (!typeContext.isRecruitment && !typeContext.isInstantTransfer) {
                    var idNumber = $('#id_number').val().trim();
                    var idPattern = /^[0-9a-zA-Z-]{5,30}$/;
                    if (!idNumber || !idPattern.test(idNumber)) {
                        errors.push('يجب إدخال رقم هوية صالح (5-30 خانة من الأرقام أو الحروف أو الشرطة)');
                        isValid = false;
                    }

                    var idExpiryDate = $('input[name="id_expiry_date"]').val().trim();
                    if (!idExpiryDate) {
                        errors.push('تاريخ انتهاء الهوية مطلوب');
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

                // المكتب الخارجي مطلوب لكل الأنواع ما عدا الاستلام الفوري
                if (!typeContext.isInstantTransfer) {
                    var officeValue = $('[name="office_id"]').val();
                    if (!officeValue || officeValue.trim() === '') {
                        errors.push('المكتب الخارجي مطلوب');
                        isValid = false;
                    }
                }

                if (!typeContext.isInstantTransfer) {
                    // التحقق من الوظائف
                    var jobs = $('select[name="job_id[]"]').val();
                    if (!jobs || jobs.length === 0) {
                        errors.push('معرف الوظيفة مطلوب');
                        isValid = false;
                    }
                }

                if (!typeContext.isInstantTransfer && !typeContext.isRental) {
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

                if (typeof window.syncExperienceRow === 'function') {
                    $('#json_array tr.y-group').each(function () {
                        window.syncExperienceRow($(this));
                    });
                }

                // إرسال النموذج باستخدام AJAX
                var formData = new FormData(this);

                // إضافة قيم افتراضية للحقول الاختيارية
                if (!formData.get('cv_file')) {
                    formData.append('cv_file', '');
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

                if (formData.get('experience_status') !== 'has_experience') {
                    var keysToDelete = [];
                    formData.forEach(function (value, key) {
                        if (key.startsWith('expertises[')) {
                            keysToDelete.push(key);
                        }
                    });
                    keysToDelete.forEach(function (key) {
                        formData.delete(key);
                    });
                    formData.set('experience_status', formData.get('experience_status') || 'no_experience');
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
                    beforeSend: function() {
                        $('#maidForm button[type="submit"]').html('<div class="spinner-border text-light spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>');
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
                                text: 'تم تحديث العاملة بنجاح',
                                confirmButtonText: 'حسناً'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = '{{ $formRedirectRoute }}';
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء تحديث العاملة',
                                confirmButtonText: 'حسناً'
                            });
                        }
                    },
                    error: function (xhr) {
                        var errorMessage = 'حدث خطأ أثناء تحديث العاملة';

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
                    },
                    complete: function() {
                        $('#maidForm button[type="submit"]').html('{{ __('ingaz.create') }}');
                    }
                });
            });

            // Function to show/hide shelter option based on type on page load
            function initializeShelterOption() {
                if (typeof toggleShelterOption === 'function') {
                    toggleShelterOption();
                    return;
                }
                var selectedType = $('select[name="type_id"] option:selected').text().trim();
                var shelterOption = $('#shelter_option');

                if (selectedType === 'ايجار عمالة' || selectedType === 'تأجير') {
                    shelterOption.show();
                } else {
                    shelterOption.hide();
                    $('#create_shelter_contract').val('0'); // Reset to default
                }
            }

            // Initialize shelter option on page load
            initializeShelterOption();
        });
    </script>
    <script>
        $(function () {
            $('.lang-check').on('change', function () {
                const $wrapper = $(this).closest('.form-check').siblings('.lang-extra');
                const val = $(this).val();
                if ($(this).is(':checked')) {
                    $wrapper
                        .removeClass('d-none')
                        .html(
                            `<input type="number" class="form-control mt-2"
                name="percentage[${val}]"
                placeholder="{{ __("ingaz.Percentage") }}"
                min="0" max="100" />`
                        );
                } else {
                    $wrapper.addClass('d-none').empty();
                }
            });
        });
    </script>
@endsection
