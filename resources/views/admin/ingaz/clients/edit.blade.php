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
            $phoneInput = (string) old('phone', $client->phone);
            $phoneDigits = preg_replace('/\D+/', '', $phoneInput);

            if (str_starts_with($phoneDigits, '00966')) {
                $phoneDigits = substr($phoneDigits, 5);
            } elseif (str_starts_with($phoneDigits, '966')) {
                $phoneDigits = substr($phoneDigits, 3);
            }

            if (str_starts_with($phoneDigits, '05')) {
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
                <input type="hidden" name="phone_code" value="+966">

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
                                    <span class="input-group-text" dir="ltr">+966</span>
                                    <input type="text" id="phone" name="phone" class="form-control" dir="ltr"
                                           value="{{ $phoneDigits ?: $phoneInput }}" required>
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

                            <div class="col-lg-6">
                                <label for="national_num" class="form-label">{{ helperTrans('ingaz.national_num') }}</label>
                                <input type="text" id="national_num" name="national_num" class="form-control"
                                       value="{{ old('national_num', $client->national_num) }}" required>
                            </div>

                            <div class="col-lg-6">
                                <label for="birthdate" class="form-label">{{ helperTrans('ingaz.birthdate') }}</label>
                                <input type="date" id="birthdate" name="birthdate" class="form-control"
                                       value="{{ old('birthdate', $client->birthdate) }}">
                            </div>

                            <div class="col-lg-6">
                                <label for="phone2" class="form-label">{{ helperTrans('ingaz.phone2') }}</label>
                                <input type="text" id="phone2" name="phone2" class="form-control" dir="ltr"
                                       value="{{ old('phone2', $client->phone2) }}">
                            </div>

                            <div class="col-lg-6">
                                <label for="client_category_id" class="form-label">{{ __('finance::finance.client_category') }}</label>
                                <select name="client_category_id" id="client_category_id" class="form-select">
                                    {!! showSelectElement($client_categories, 'name', old('client_category_id', $client->client_category_id)) !!}
                                </select>
                            </div>

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

                            <div class="col-lg-3 col-md-6">
                                <label for="rooms_num" class="form-label">{{ helperTrans('ingaz.rooms_num') }}</label>
                                <input type="number" id="rooms_num" name="rooms_num" class="form-control" min="0"
                                       value="{{ old('rooms_num', $client->rooms_num) }}">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label for="family_num" class="form-label">{{ helperTrans('ingaz.family_num') }}</label>
                                <input type="number" id="family_num" name="family_num" class="form-control" min="0"
                                       value="{{ old('family_num', $client->family_num) }}">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label for="children" class="form-label">{{ helperTrans('ingaz.children') }}</label>
                                <input type="number" id="children" name="children" class="form-control" min="0"
                                       value="{{ old('children', $client->children) }}">
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <label for="job" class="form-label">{{ helperTrans('ingaz.job') }}</label>
                                <input type="text" id="job" name="job" class="form-control"
                                       value="{{ old('job', $client->job) }}">
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
