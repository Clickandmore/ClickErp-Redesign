@extends('admin.layout.indexs.index')

@section('page-title')
    {{ helperTrans('ingaz.edit_client') }}
@endsection

@section('style')
    <style>
        .client-edit-page {
            max-width: 1180px;
            margin: 0 auto;
        }

        .client-edit-header,
        .client-form-card,
        .client-form-actions {
            border: 1px solid #e8edf3;
            border-radius: 8px;
            background: #fff;
        }

        .client-edit-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 22px 24px;
            margin-bottom: 16px;
        }

        .client-edit-header h4 {
            margin: 0;
            color: #202734;
            font-size: 22px;
            font-weight: 700;
        }

        .client-edit-header p {
            margin: 8px 0 0;
            color: #667085;
            font-size: 14px;
        }

        .client-form-card {
            margin-bottom: 16px;
            box-shadow: 0 10px 26px rgba(16, 24, 40, .06);
            overflow: hidden;
        }

        .client-form-card .card-body,
        .client-form-card .card-header {
            padding: 22px 24px;
        }

        .client-form-card .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #eef2f6;
            background: #fbfcfe;
        }

        .section-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            color: #202734;
            font-size: 16px;
            font-weight: 700;
        }

        .section-heading i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            color: #0f766e;
            background: #e6f6f3;
        }

        .optional-label,
        .required-label {
            display: inline-flex;
            align-items: center;
            height: 24px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .required-label {
            color: #b42318;
            background: #fff1f0;
            border: 1px solid #ffd6d1;
        }

        .optional-label {
            color: #475467;
            background: #f2f4f7;
            border: 1px solid #e4e7ec;
        }

        .form-label {
            color: #344054;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select,
        .input-group-text {
            min-height: 44px;
            border-color: #d9e0ea;
            border-radius: 8px;
        }

        .input-group .form-control {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .input-group-text {
            color: #344054;
            background: #f8fafc;
            font-weight: 700;
        }

        .select2-container--default .select2-selection--single {
            min-height: 44px;
            border-color: #d9e0ea;
            border-radius: 8px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
        }

        .client-form-actions {
            position: sticky;
            bottom: 12px;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 16px;
            box-shadow: 0 10px 26px rgba(16, 24, 40, .08);
        }

        @media (max-width: 767.98px) {
            .client-edit-header,
            .client-form-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .client-edit-header .btn,
            .client-form-actions .btn {
                width: 100%;
            }
        }
    </style>
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT))
        @php
            $hasSourcePhone = filled($client->source_phone);
            $phoneInput = (string) old('phone', $client->source_phone ?: $client->phone);
            $phoneDigits = $hasSourcePhone ? $phoneInput : preg_replace('/\D+/', '', $phoneInput);

            if (!$hasSourcePhone && str_starts_with($phoneDigits, '00966')) {
                $phoneDigits = substr($phoneDigits, 5);
            } elseif (!$hasSourcePhone && str_starts_with($phoneDigits, '966')) {
                $phoneDigits = substr($phoneDigits, 3);
            }

            if (!$hasSourcePhone && str_starts_with($phoneDigits, '05')) {
                $phoneDigits = substr($phoneDigits, 1);
            }

            $selectedNationalityId = old('nationality_id', $client->nationality_id);
            $selectedCity = old('city', $client->city);
        @endphp

        <div class="client-edit-page">
            <div class="client-edit-header">
                <div>
                    <h4>{{ helperTrans('ingaz.edit_client') }}</h4>
                    <p>{{ helperTrans('ingaz.client_edit_required_fields_hint') }}</p>
                </div>

                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i>
                    {{ helperTrans('ingaz.back') }}
                </a>
            </div>

            <form action="{{ route('clients.update', $client->id) }}" method="POST" class="from-submit-global">
                @csrf
                @method('PUT')
                <input type="hidden" name="phone_code" value="{{ $hasSourcePhone ? '' : '+966' }}">

                <div class="client-form-card">
                    <div class="card-body">
                        <div class="section-heading">
                            <i class="fas fa-user-edit"></i>
                            <span>{{ helperTrans('ingaz.basic_information') }}</span>
                            <span class="required-label">{{ helperTrans('ingaz.required') }}</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label for="name" class="form-label">{{ helperTrans('ingaz.name') }}</label>
                                <input type="text" id="name" name="name" class="form-control"
                                       value="{{ old('name', $client->name) }}" required autofocus>
                            </div>

                            <div class="col-lg-6">
                                <label for="phone" class="form-label">{{ helperTrans('ingaz.phone') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text" dir="ltr">{{ $hasSourcePhone ? 'المصدر' : '+966' }}</span>
                                    <input type="text" id="phone" name="phone" class="form-control" dir="ltr"
                                           value="{{ $phoneDigits ?: $phoneInput }}" required>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <label class="form-label d-block">{{ helperTrans('ingaz.client_type') }}</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input client-type-radio" type="radio" name="client_type"
                                               id="client_type_person" value="person" @checked(old('client_type', $client->client_type ?? 'person') === 'person')>
                                        <label class="form-check-label" for="client_type_person">{{ helperTrans('ingaz.person_client') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input client-type-radio" type="radio" name="client_type"
                                               id="client_type_company" value="company" @checked(old('client_type', $client->client_type ?? 'person') === 'company')>
                                        <label class="form-check-label" for="client_type_company">{{ helperTrans('ingaz.company') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="client-form-card">
                    <div class="card-header">
                        <div class="section-heading mb-0">
                            <i class="fas fa-id-card"></i>
                            <span>{{ helperTrans('ingaz.additional_information') }}</span>
                        </div>
                        <span class="optional-label">{{ helperTrans('ingaz.optional') }}</span>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label for="email" class="form-label">{{ helperTrans('ingaz.email') }}</label>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="{{ old('email', $client->email) }}">
                            </div>

                            <div class="col-lg-6">
                                <label for="nationalitySelect" class="form-label">{{ helperTrans('ingaz.nationality') }}</label>
                                <select name="nationality_id" id="nationalitySelect" class="form-control select2">
                                    <option value="">{{ helperTrans('ingaz.choose') }}</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" @selected((string) $selectedNationalityId === (string) $country->id)>
                                            {{ $country->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-6 person-client-field">
                                <label for="national_num" class="form-label">{{ helperTrans('ingaz.national_num') }}</label>
                                <input type="text" id="national_num" name="national_num" class="form-control"
                                       value="{{ old('national_num', $client->national_num) }}" required>
                            </div>

                            <div class="col-lg-6 company-client-field d-none">
                                <label for="tax_number" class="form-label">{{ helperTrans('ingaz.tax_number') }}</label>
                                <input type="text" id="tax_number" name="tax_number" class="form-control"
                                       value="{{ old('tax_number', $client->tax_number) }}">
                            </div>

                            <div class="col-lg-6 company-client-field d-none">
                                <label for="license_number" class="form-label">{{ helperTrans('ingaz.license_number') }}</label>
                                <input type="text" id="license_number" name="license_number" class="form-control"
                                       value="{{ old('license_number', $client->license_number) }}">
                            </div>

                            <div class="col-lg-6 person-client-field">
                                <label for="birthdate" class="form-label">{{ helperTrans('ingaz.birthdate') }}</label>
                                <input type="date" id="birthdate" name="birthdate" class="form-control"
                                       value="{{ old('birthdate', $client->birthdate) }}">
                            </div>

                            <div class="col-lg-6 person-client-field">
                                <label class="form-label d-block">{{ helperTrans('ingaz.marital_status') }}</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach(['single' => 'أعزب', 'married' => 'متزوج', 'divorced' => 'مطلق', 'widowed' => 'أرمل'] as $value => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="marital_status"
                                                   id="marital_status_{{ $value }}" value="{{ $value }}"
                                                   @checked(old('marital_status', $client->marital_status) === $value) required>
                                            <label class="form-check-label" for="marital_status_{{ $value }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <label for="phone2" class="form-label">{{ helperTrans('ingaz.phone2') }}</label>
                                <input type="text" id="phone2" name="phone2" class="form-control" dir="ltr"
                                       value="{{ old('phone2', $client->source_phone2 ?: $client->phone2) }}">
                            </div>

                            <div class="col-lg-6">
                                <label for="client_category_id" class="form-label">{{ __('finance::finance.client_category') }}</label>
                                <select name="client_category_id" id="client_category_id" class="form-select">
                                    {!! showSelectElement($client_categories, 'name', old('client_category_id', $client->client_category_id)) !!}
                                </select>
                            </div>

                            @if(($branchesEnabled ?? false) && ($canViewAllBranches ?? false))
                                <div class="col-lg-6">
                                    <label for="branch_id" class="form-label">{{ __('admin.branches') }}</label>
                                    <select name="branch_id" id="branch_id" class="form-select" required>
                                        <option value="">{{ helperTrans('ingaz.choose') }}</option>
                                        @foreach(($branches ?? collect()) as $branch)
                                            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $client->branch_id) === (string) $branch->id)>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif($branchesEnabled ?? false)
                                <input type="hidden" name="branch_id" value="{{ old('branch_id', $client->branch_id) }}">
                            @endif

                            <div class="col-lg-6">
                                <label for="address" class="form-label">{{ helperTrans('ingaz.address') }}</label>
                                <input type="text" id="address" name="address" class="form-control"
                                       value="{{ old('address', $client->address) }}">
                            </div>

                            <div class="col-lg-6">
                                <label for="citySelect" class="form-label">{{ helperTrans('ingaz.city') }}</label>
                                <select name="city" id="citySelect" class="form-control select2">
                                    <option value="">{{ helperTrans('ingaz.choose') }}</option>
                                    @foreach($cities as $city)
                                        <option value="{{ $city->name }}" data-country-id="{{ $city->country_id }}"
                                                @selected((string) $selectedCity === (string) $city->name)>
                                            {{ $city->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-3 col-md-6 person-client-field">
                                <label for="rooms_num" class="form-label">{{ helperTrans('ingaz.rooms_num') }}</label>
                                <input type="number" id="rooms_num" name="rooms_num" class="form-control" min="0"
                                       value="{{ old('rooms_num', $client->rooms_num) }}">
                            </div>

                            <div class="col-lg-3 col-md-6 person-client-field">
                                <label for="family_num" class="form-label">{{ helperTrans('ingaz.family_num') }}</label>
                                <input type="number" id="family_num" name="family_num" class="form-control" min="0"
                                       value="{{ old('family_num', $client->family_num) }}">
                            </div>

                            <div class="col-lg-3 col-md-6 person-client-field">
                                <label for="children" class="form-label">{{ helperTrans('ingaz.children') }}</label>
                                <input type="number" id="children" name="children" class="form-control" min="0"
                                       value="{{ old('children', $client->children) }}">
                            </div>

                            <div class="col-lg-3 col-md-6 person-client-field">
                                <label for="job" class="form-label">{{ helperTrans('ingaz.job') }}</label>
                                <input type="text" id="job" name="job" class="form-control"
                                       value="{{ old('job', $client->job) }}">
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label" for="full_name_en">الاسم الكامل بالإنجليزية</label>
                                <input type="text" id="full_name_en" name="full_name_en" class="form-control" dir="ltr" value="{{ old('full_name_en', $client->full_name_en) }}">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label" for="identity_type">نوع الهوية</label>
                                <input type="text" id="identity_type" name="identity_type" class="form-control" value="{{ old('identity_type', $client->identity_type) }}">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label" for="identity_date">تاريخ الهوية</label>
                                <input type="text" id="identity_date" name="identity_date" class="form-control" value="{{ old('identity_date', $client->identity_date) }}" placeholder="هجري أو ميلادي">
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label" for="workplace">جهة العمل</label>
                                <input type="text" id="workplace" name="workplace" class="form-control" value="{{ old('workplace', $client->workplace) }}">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label" for="po_box">صندوق البريد</label>
                                <input type="text" id="po_box" name="po_box" class="form-control" value="{{ old('po_box', $client->po_box) }}">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label" for="postal_code">الرقم البريدي</label>
                                <input type="text" id="postal_code" name="postal_code" class="form-control" value="{{ old('postal_code', $client->postal_code) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="notes">ملاحظات العميل</label>
                                <textarea id="notes" name="notes" class="form-control" rows="4">{{ old('notes', $client->notes) }}</textarea>
                            </div>

                            <div class="col-lg-6">
                                <label for="is_blocked" class="form-label">{{ helperTrans('ingaz.is_blocked') }}</label>
                                <select id="is_blocked" name="is_blocked" class="form-select form-control">
                                    <option value="0" @selected((string) old('is_blocked', $client->is_blocked) === '0')>
                                        {{ helperTrans('ingaz.active') }}
                                    </option>
                                    <option value="1" @selected((string) old('is_blocked', $client->is_blocked) === '1')>
                                        {{ helperTrans('ingaz.blocked') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="client-form-actions">
                    <a href="{{ route('clients.index') }}" class="btn btn-light">
                        {{ helperTrans('ingaz.cancel') }}
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>
                        {{ helperTrans('ingaz.save') }}
                    </button>
                </div>
            </form>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            const chooseText = "{{ helperTrans('ingaz.choose') }}";
            const $nationalitySelect = $('#nationalitySelect');
            const $citySelect = $('#citySelect');
            const selectedCity = @json($selectedCity ?? null);

            if ($nationalitySelect.length) {
                $nationalitySelect.select2({
                    placeholder: chooseText,
                    allowClear: true,
                    width: '100%'
                });
            }

            if ($('#client_category_id').length) {
                $('#client_category_id').select2({
                    placeholder: chooseText,
                    allowClear: true,
                    width: '100%'
                });
            }

            if ($('#branch_id').length) {
                $('#branch_id').select2({
                    placeholder: chooseText,
                    allowClear: true,
                    width: '100%'
                });
            }

            function toggleClientTypeFields() {
                const type = $('input[name="client_type"]:checked').val() || 'person';
                const isCompany = type === 'company';

                $('.person-client-field').toggleClass('d-none', isCompany)
                    .find('input, select, textarea').prop('disabled', isCompany);

                $('.company-client-field').toggleClass('d-none', !isCompany)
                    .find('input, select, textarea').prop('disabled', !isCompany);

                $('#national_num').prop('required', !isCompany);
                $('input[name="marital_status"]').prop('required', !isCompany);
                $('#tax_number, #license_number').prop('required', isCompany);
            }

            $('.client-type-radio').on('change', toggleClientTypeFields);
            toggleClientTypeFields();

            if ($citySelect.length) {
                const citiesData = $citySelect.find('option').not('[value=""]').map(function () {
                    const $option = $(this);
                    const countryId = $option.data('countryId');

                    return {
                        value: $option.val(),
                        text: $option.text(),
                        countryId: countryId !== undefined && countryId !== null ? String(countryId) : ''
                    };
                }).get();

                const filterCities = function (countryId) {
                    const targetCountry = countryId ? String(countryId) : '';

                    $citySelect.find('option').not('[value=""]').remove();

                    citiesData.forEach(function (city) {
                        if (!targetCountry || city.countryId === targetCountry) {
                            const option = new Option(city.text, city.value, false, city.value === selectedCity);
                            option.setAttribute('data-country-id', city.countryId);
                            $citySelect.append(option);
                        }
                    });
                };

                filterCities($nationalitySelect.val());

                $citySelect.select2({
                    placeholder: chooseText,
                    allowClear: true,
                    width: '100%'
                });

                if (selectedCity) {
                    $citySelect.val(selectedCity).trigger('change');
                } else {
                    $citySelect.val('').trigger('change');
                }

                $nationalitySelect.on('change', function () {
                    filterCities($(this).val());
                    $citySelect.val('').trigger('change');
                });
            }
        });
    </script>
@endsection
