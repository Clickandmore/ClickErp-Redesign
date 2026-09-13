@extends('site.layouts.index')
@section('title')
    {{ __('ingaz.cv_details') }}
@endsection
@section('style')
    <style>
        .cv-profile-photo {
            width: 100%;
            height: 320px;
            object-fit: cover;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .cv-full-photo-wrapper {
            width: 100%;
            aspect-ratio: 3 / 4;
            max-height: 520px;
            overflow: hidden;
            border-radius: 28px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            background: #f7fafc;
        }

        .cv-full-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .full-photo-card {
            padding: 0;
            background: transparent;
            border: none;
        }

        .booking-modal-stepper {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        #cvBookingModal .modal-content {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 55px rgba(0, 0, 0, 0.16);
        }

        #cvBookingModal .modal-header {
            background: linear-gradient(180deg, var(--mainColor10) 0%, #ffffff 100%);
            border-bottom: 1px solid var(--mainColor20);
        }

        #cvBookingModal .modal-title {
            font-weight: 800;
            color: var(--mainColor);
        }

        .booking-hint {
            border: 1px dashed var(--mainColor40);
            background: var(--mainColor10);
            border-radius: 10px;
            color: var(--mainColor);
            font-size: 13px;
            padding: 9px 12px;
            margin-bottom: 14px;
        }

        .booking-step {
            flex: 1;
            min-width: 140px;
            border-radius: 10px;
            border: 1px solid #d9d9d9;
            background: #f7f7f7;
            color: #6b7280;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
        }

        .booking-step.is-active {
            border-color: var(--mainColor);
            background: var(--mainColor10);
            color: var(--mainColor);
        }

        .booking-step.is-done {
            border-color: #198754;
            background: #edf9f2;
            color: #198754;
        }

        .booking-auth-card {
            border: 1px solid #ececec;
            border-radius: 12px;
            padding: 14px;
            background: #ffffff;
        }

        .booking-auth-tabs {
            gap: 8px;
        }

        .booking-auth-tabs .nav-link {
            border: 1px solid #d9d9d9;
            border-radius: 10px;
            background: #f7f7f7;
            color: #545454;
            font-weight: 700;
            padding: 8px 14px;
        }

        .booking-auth-tabs .nav-link.active {
            background: var(--mainColor10);
            border-color: var(--mainColor40);
            color: var(--mainColor);
        }

        .booking-customer-choose {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .booking-customer-choose .customerOption {
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .booking-customer-choose .customerOption .btn {
            width: 100%;
            justify-content: flex-start;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 10px;
        }

        .booking-customer-choose .customerOption .btn img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .booking-customer-choose .customerOption .btn span {
            display: flex;
            flex-direction: column;
            gap: 2px;
            text-align: start;
        }

        .booking-customer-choose .customerOption .btn {
            transition: all 0.2s ease;
        }

        .booking-customer-choose .customerOption .btn.is-selected {
            border-color: var(--mainColor) !important;
            background: var(--mainColor10);
            position: relative;
        }

        .booking-customer-choose .customerOption .btn.is-selected::after {
            content: "✓";
            margin-inline-start: auto;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #198754;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
        }

        .booking-customer-choose .customerOption .customer-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .booking-customer-choose .customerOption .service-action {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid #d9d9d9;
            background: #fff;
            color: #4b5563;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .booking-customer-choose .customerOption .service-action:hover {
            transform: translateY(-1px);
            color: #fff;
        }

        .booking-customer-choose .customerOption .service-action.is-call:hover {
            background: #0d6efd;
            border-color: #0d6efd;
        }

        .booking-customer-choose .customerOption .service-action.is-whatsapp:hover {
            background: #25d366;
            border-color: #25d366;
        }

        .booking-customer-choose .customerOption .service-action.is-disabled {
            background: #f3f4f6;
            color: #9ca3af;
            pointer-events: none;
            border-color: #e5e7eb;
        }

        #cvBookingSubmitBtn {
            min-width: 170px;
            font-weight: 700;
        }

        #cvBookingPrimaryActionBtn {
            min-width: 210px;
            font-weight: 700;
        }

        .booking-modal-footer {
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            background: #fff;
            border-top: 1px solid var(--mainColor20);
        }

        .booking-footer-link {
            color: var(--mainColor);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
        }

        .booking-footer-link:hover {
            text-decoration: underline;
            color: var(--mainColor);
        }

        .booking-footer-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 8px;
            margin-inline-start: auto;
        }

        @media (max-width: 768px) {
            .cv-profile-photo {
                height: 220px;
            }

            .cv-full-photo-wrapper {
                aspect-ratio: 2 / 3;
                max-height: 380px;
            }

            .cv-full-photo {
                height: 100%;
            }

            .booking-customer-choose {
                grid-template-columns: 1fr;
            }

            #cvBookingPrimaryActionBtn,
            #cvBookingSubmitBtn {
                width: 100%;
            }

            .booking-modal-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .booking-footer-actions {
                margin-inline-start: 0;
                width: 100%;
            }
        }
    </style>
@endsection
@section('content')
    @php
        $todayDate = now()->toDateString();
        $offerStarts = optional($maid->offer_starts_at)->format('Y-m-d');
        $offerExpires = optional($maid->offer_expires_at)->format('Y-m-d');
        $offerPriceRaw = $maid->offer_price !== null ? (float) $maid->offer_price : null;
        $salaryRaw = $maid->resolved_salary !== null ? (float) $maid->resolved_salary : null;
        $servicePrice = $maid->resolvePriceForService($type);
        $priceRaw = $servicePrice !== null ? (float) $servicePrice : null;
        $priceDisplay = $priceRaw !== null ? formatMoney($priceRaw) : null;
        $salaryDisplay = $salaryRaw !== null ? formatMoney($salaryRaw) : null;
        $offerActive =
            $offerPriceRaw !== null &&
            (empty($offerStarts) || $offerStarts <= $todayDate) &&
            (empty($offerExpires) || $offerExpires >= $todayDate);
        $offerPriceDisplay = $offerPriceRaw !== null ? formatMoney($offerPriceRaw) : null;
        $cvPublicLink = route('site.all-workers.show', $maid->id) . ($type ? '?type=' . urlencode($type) : '');
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($cvPublicLink);
    @endphp
    <!-- cv page -->
    <div class="cvPage" id="cv-wrapper">
        <div class="shape1"></div>
        <div class="logo-bg">
            <img src="{{ asset('site') }}/assets/images/cv-fav.png" alt="" />
        </div>
        {{--        <div class="cv-side"> --}}
        {{--            <img src="{{ asset('site') }}/assets/images/cv-side.png" alt=""/> --}}
        {{--        </div> --}}
        <div class="cv-footer">
            <img src="{{ asset('site') }}/assets/images/cvFooter.png" alt="" />
        </div>
        <div class="header-logo">
            <div class="transparent-logo">
                <img src="{{ asset('site') }}/assets/images/cv-fav.png" alt="cv-logo" />
                <img src="{{ asset('site') }}/assets/images/cv-fav.png" alt="cv-logo" />
                <img src="{{ asset('site') }}/assets/images/cv-fav.png" alt="cv-logo" />
                <img src="{{ asset('site') }}/assets/images/cv-fav.png" alt="cv-logo" />
                <img src="{{ asset('site') }}/assets/images/cv-fav.png" alt="cv-logo" />
                <img src="{{ asset('site') }}/assets/images/cv-fav.png" alt="cv-logo" />
                <img src="{{ asset('site') }}/assets/images/cv-fav.png" alt="cv-logo" />
            </div>
            <div class="logo">
                <img src="{{ asset('site') }}/assets/images/cv-logo.png" alt="cv-logo" />
            </div>
        </div>
        <!-- col -->
        <div class="imageCol">
            <div class="image">
                @php
                    $profileImage = $maid->image ?: ($maid->cv_preview ?: $maid->cv_file);
                @endphp
                <img class="cv-profile-photo"
                    src="{{ $profileImage ? showFile($profileImage) : asset('site/assets/images/worker.webp') }}"
                    alt="" />
            </div>
            <div class="mainInfo">
                @if ($offerActive && $offerPriceDisplay)
                    <div class="sub">
                        <h5 class="title text-warning"><i class="fas fa-tags me-2"></i>{{ __('ingaz.offer_price_new') }}
                        </h5>
                        <p class="dis fw-bold text-warning">{{ $offerPriceDisplay }} {{ __('ingaz.SAR') }}</p>
                        @if ($priceDisplay && $offerPriceRaw < $priceRaw)
                            <p class="dis text-muted text-decoration-line-through">{{ $priceDisplay }}
                                {{ __('ingaz.SAR') }}</p>
                        @endif
                        @if ($maid->offer_description)
                            <p class="dis mt-2">{{ $maid->offer_description }}</p>
                        @endif
                    </div>
                @else
                    <div class="sub">
                        <h5 class="title">{{ __('ingaz.cost_with_tax') }} :</h5>
                        <p class="dis">{{ $priceDisplay ?? '--' }} {{ $priceDisplay ? __('ingaz.SAR') : '' }}</p>
                    </div>
                @endif
                <!-- languages -->
                <div class="languages">
                    <h5 class="title">{{ __('ingaz.languages') }} :</h5>
                    <div class="Percentages">
                        @php
                            $percentages = is_string($maid->percentage)
                                ? json_decode($maid->percentage, true)
                                : $maid->percentage;
                            $languageLevels = is_string($maid->language_levels)
                                ? json_decode($maid->language_levels, true)
                                : $maid->language_levels;
                            $languageLevels = is_array($languageLevels) ? $languageLevels : [];
                        @endphp
                        @if (isset($percentages))
                            @forelse ($percentages as $key => $percentage)
                                @if ($percentage)
                                    @php $languageLevel = $languageLevels[$key] ?? null; @endphp
                                    <div class="percentDiv">
                                        <div class="percent p{{ $percentage }}">
                                            <p class="percentNum">
                                                @if ($languageLevel)
                                                    {{ helperTrans('ingaz.language_level_' . $languageLevel) }}
                                                @else
                                                    {{ $percentage }} <span> % </span>
                                                @endif
                                            </p>
                                            <div class="slice">
                                                <div class="bar"></div>
                                                <div class="fill"></div>
                                            </div>
                                        </div>
                                        <h5>{{ __('ingaz.' . $key) }}</h5>
                                    </div>
                                @endif
                            @empty
                                <div>لا توجد لغات لعرضها.</div>
                            @endforelse
                        @endif
                    </div>

                    {{-- <div class="Percentages">
                        <div class="percentDiv">
                            <div class="percent p75">
                                <p class="percentNum">{{ $maid->percentage['arabic'] }}<span> % </span></p>
                                <div class="slice">
                                    <div class="bar"></div>
                                    <div class="fill"></div>
                                </div>
                            </div>
                            <h5>عربي</h5>
                        </div>
                        <div class="percentDiv">
                            <div class="percent p100">
                                <p class="percentNum">{{ $maid->percentage['arabic'] }} <span> % </span></p>
                                <div class="slice">
                                    <div class="bar"></div>
                                    <div class="fill"></div>
                                </div>
                            </div>
                            <h5>إنجليزي</h5>
                        </div>
                    </div> --}}
                </div>
                <div class="sub special-img full-photo-card">
                    @php
                        // Agent uploads use a dedicated CV-document field. Legacy
                        // agent submissions stored that document in cv_file, so it
                        // must never be rendered as the worker's full-body photo.
                        $galleryImage = $maid->submission_status
                            ? ($maid->image ?: $maid->cv_preview)
                            : ($maid->cv_file ?: ($maid->cv_preview ?: $maid->image));
                    @endphp
                    <div class="cv-full-photo-wrapper">
                        <img class="cv-full-photo"
                            src="{{ $galleryImage ? showFile($galleryImage) : asset('site/assets/images/worker.webp') }}"
                            alt="" />
                    </div>
                </div>
                <div class="sub special-img bar-code">
                    <a href="{{ $cvPublicLink }}" target="_blank" rel="noopener">
                        <img src="{{ $qrImage }}" alt="QR Code" width="200" height="200" />
                    </a>
                    <p class="dis ">{{ __('ingaz.scan_cv_qr') }}</p>
                </div>
            </div>
        </div>
        <!-- col -->
        <div class="informationCol">
            <div class="head">
                <div class="name">
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.name') }} :</p>
                        <h3 class="innerData">{{ $maid->name ?? '' }}</h3>
                    </div>
                    <div class="data">
                        <h5 class="innerTitle">{{ __('ingaz.job') }} :</h5>
                        <p class="innerData">
                            {{ $maid->jobTitles?->pluck('title')->join(' - ') ?? '' }}
                        </p>
                    </div>
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.passport_number') }} :</p>
                        <h6 class="innerData">{{ $maid->passport_num ?? '' }}</h6>
                    </div>
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.cv_number') }} :</p>
                        <div class="passportNumbers">
                            <span>{{ optional($maid->recruitmentOffice?->maids)->count() ?? 0 }}</span>
                            <span>/</span>
                            <span>{{ mb_substr(optional($maid->recruitmentOffice)->title ?? '', 0, 3) }}</span>
                        </div>
                    </div>
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.month_salary') }} :</p>
                        <h6 class="innerData">
                            {{ $salaryDisplay ? $salaryDisplay . ' ' . __('ingaz.SAR') : '--' }}
                        </h6>
                    </div>
                </div>
                <span class="logo">
                    <img src="{{ asset('site') }}/assets/images/fav.svg" alt="" />
                    <h4 class="experiance">
                        <svg width="24" height="24" viewBox="0 0 511.999 511.999">
                            <g>
                                <path
                                    d="m452.71 157.937-133.741-12.404L265.843 22.17c-3.72-8.638-15.967-8.638-19.686 0l-53.126 123.362L59.29 157.937c-9.365.868-13.149 12.516-6.084 18.723l100.908 88.646-29.531 131.029c-2.068 9.175 7.841 16.373 15.927 11.572L256 339.331l115.49 68.576c8.087 4.802 17.994-2.397 15.927-11.572l-29.532-131.029 100.909-88.646c7.065-6.207 3.28-17.855-6.084-18.723z"
                                    fill="#FFFFFF" data-original="#ffdc64"></path>
                                <path
                                    d="M119.278 17.923c6.818 9.47 26.062 50.14 37.064 73.842 1.73 3.726-2.945 7.092-5.93 4.269-18.987-17.952-51.452-49.104-58.27-58.575-5.395-7.493-3.694-17.941 3.8-23.336 7.493-5.395 17.941-3.693 23.336 3.8zM392.722 17.923c-6.818 9.47-26.062 50.14-37.064 73.842-1.73 3.726 2.945 7.092 5.93 4.269 18.987-17.952 51.451-49.105 58.27-58.575 5.395-7.493 3.694-17.941-3.8-23.336-7.493-5.395-17.941-3.693-23.336 3.8zM500.461 295.629c-11.094-3.618-55.689-9.595-81.612-12.875-4.075-.516-5.861 4.961-2.266 6.947 22.873 12.635 62.416 34.099 73.51 37.717 8.778 2.863 18.215-1.932 21.078-10.711 2.863-8.779-1.932-18.215-10.71-21.078zM11.539 295.629c11.094-3.618 55.689-9.595 81.612-12.875 4.075-.516 5.861 4.961 2.266 6.947-22.873 12.635-62.416 34.099-73.51 37.717-8.778 2.863-18.215-1.932-21.078-10.711s1.932-18.215 10.71-21.078zM239.794 484.31c0-11.669 8.145-55.919 13.065-81.582.773-4.034 6.534-4.034 7.307 0 4.92 25.663 13.065 69.913 13.065 81.582 0 9.233-7.485 16.718-16.718 16.718-9.234.001-16.719-7.485-16.719-16.718z"
                                    fill="#FFFFFF" data-original="#fff082"></path>
                                <path
                                    d="m285.161 67.03-19.319-44.86c-3.72-8.638-15.967-8.638-19.686 0L193.03 145.532 59.29 157.937c-9.365.868-13.149 12.516-6.084 18.723l100.908 88.646-29.531 131.029c-2.068 9.175 7.841 16.373 15.927 11.572l15.371-9.127c25.199-163.12 96.041-282.862 129.28-331.75z"
                                    fill="#E7E7E7" data-original="#ffc850"></path>
                            </g>
                        </svg>
                        {{ __('ingaz.Years of experience make a difference') }}
                    </h4>
                </span>
            </div>
            <div class="category">
                <h5 class="title"><span> {{ __('ingaz.personal_infromation') }} </span></h5>
                <div class="infoRow">
                    <!-- data -->
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.nationality') }}</p>
                        <h6 class="innerData">{{ $maid->nationality?->title ?? '-' }}</h6>
                    </div>
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.marital status') }}</p>
                        @php
                            $status = $maid->marital_status ?? '';
                            $statusEnum = \App\Enums\Office\MaritalStatusEnum::tryFrom($status);
                            $educationLabel =
                                \App\Enums\Office\EducationEnum::tryFrom($maid->education)?->lang() ?? $maid->education;
                        @endphp
                        <h6 class="innerData">{{ $statusEnum ? $statusEnum->lang() : $status }}</h6>
                    </div>
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.education') }}</p>
                        <h6 class="innerData">
                            {{ $educationLabel ?? __('ingaz.not_specified') }}
                        </h6>
                    </div>
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.religion') }}</p>
                        <h6 class="innerData">{{ $maid->religion?->title ?? '-' }}</h6>
                    </div>
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.age') }}</p>
                        <h6 class="innerData">{{ (int) $maid->age ?? '-' }}</h6>
                    </div>
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.experience_status') }}</p>
                        <h6 class="innerData">
                            {{ $maid->experience_status == 'no_experience' ? __('ingaz.no_experience') : __('ingaz.has_experience') }}
                        </h6>
                    </div>
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.height') }}</p>
                        <h6 class="innerData">{{ $maid->height }}</h6>
                    </div>
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.weight') }}</p>
                        <h6 class="innerData">{{ $maid->weight }}</h6>
                    </div>
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.childrens_count') }}</p>
                        <h6 class="innerData">{{ $maid->childrens_count }}</h6>
                    </div>
                    <div class="data">
                        <p class="innerTitle">{{ __('ingaz.duration') }}</p>
                        <h6 class="innerData">{{ $maid->duration ?? '-' }}</h6>
                    </div>
                </div>
            </div>
            <div class="category">
                <h5 class="title"><span> {{ __('ingaz.skills') }} </span></h5>
                <div class="passportData">
                    @php
                        $maidSkillIds = $maid->skills
                            ? (is_array($maid->skills)
                                ? $maid->skills
                                : json_decode($maid->skills, true))
                            : [];
                    @endphp

                    <table style="text-align: center">
                        @foreach ($skills->chunk(3) as $chunk)
                            <tr>
                                @foreach ($chunk as $skill)
                                    <td>{{ $skill->title }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach ($chunk as $skill)
                                    <td>
                                        @if (in_array($skill->id, $maidSkillIds))
                                            <svg width="24" height="24" viewBox="0 0 520 520">
                                                <g transform="matrix(1.13,0,0,1.13,-33.8,-33.8)">
                                                    <g>
                                                        <circle cx="208.52" cy="288.5" r="176.52" fill="#eeaa2c"
                                                            opacity=".2"></circle>
                                                        <path fill="#eeaa2c"
                                                            d="m210.516 424.937-2.239-3.815c-34.2-58.27-125.082-181.928-126-183.17l-1.311-1.781 30.963-30.6 98.012 68.439c61.711-80.079 119.283-135.081 156.837-167.2C407.859 71.675 434.6 55.5 434.87 55.345l.608-.364H488l-5.017 4.468C353.954 174.375 214.1 418.639 212.707 421.093z">
                                                        </path>
                                                    </g>
                                                </g>
                                            </svg>
                                        @else
                                            <svg width="24" height="24" viewBox="0 0 512 512">
                                                <circle cx="256" cy="256" r="240" fill="#B0B0B0"></circle>
                                                <path fill="#7F7F7F"
                                                    d="M495.941 260.673c-2.462 128.835-106.433 232.806-235.268 235.268-40.768-40.767-81.645-81.444-122.407-122.206-10.666-10.666-10.667-28.119-.001-38.786L217.214 256l-78.949-78.949c-10.666-10.666-10.666-28.119 0-38.785s28.119-10.667 38.786-.001L256 217.214l78.949-78.949c10.666-10.666 28.119-10.666 38.786 0 40.762 40.762 81.438 81.64 122.206 122.408z">
                                                </path>
                                                <path fill="#fff"
                                                    d="M138.265 373.735c10.666 10.666 28.12 10.666 38.786 0L256 294.786l78.949 78.949c10.666 10.666 28.119 10.666 38.785 0 10.666-10.666 10.666-28.119 0-38.786L294.786 256l78.949-78.949c10.666-10.666 10.666-28.119 0-38.785-10.666-10.666-28.119-10.666-38.786 0L256 217.214l-78.949-78.949c-10.666-10.666-28.119-10.666-38.785 0-10.666 10.666-10.666 28.119 0 38.785L217.214 256l-78.949 78.949c-10.666 10.666-10.666 28.12 0 38.786z">
                                                </path>
                                            </svg>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </table>

                </div>
            </div>
            <div class="category">
                <h5 class="title"><span> {{ __('ingaz.expertise') }} </span></h5>
                <div class="passportData">
                    <table>
                        <tr>
                            <td>{{ __('ingaz.country') }}</td>
                            <td>{{ __('ingaz.expertise_period') }}</td>
                            <td>{{ __('ingaz.expertise_duration') }}</td>
                            <td>{{ __('ingaz.job_title') }}</td>
                        </tr>
                        @forelse(optional($maid)->expertises ?? [] as $expertise)
                            <tr>
                                <td>
                                    {{ app()->getLocale() === 'ar'
                                        ? optional($expertise->country)->name_ar
                                        : optional($expertise->country)->name_en }}
                                </td>
                                <td>{{ $expertise->period_label ?? '--' }}</td>
                                <td>{{ $expertise->duration_label ?? '--' }}</td>
                                <td>{{ optional($expertise->jobTitle)->title }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center; vertical-align:middle;">
                                    {{ __('ingaz.no_expertise_for_this_maid') }}
                                </td>
                            </tr>
                        @endforelse

                    </table>
                </div>
            </div>
            <div class="category">
                <h5 class="title"><span> {{ __('ingaz.additional_notes') }} </span></h5>
                <div class="passportData">
                    {{ $maid->additional_notes ?? __('ingaz.no_additional_notes') }}
                </div>
            </div>
            <div class="category">
                <!-- <h5 class="title"><span> تواصل معنا </span></h5> -->
                <div class="passportData">
                    <!-- <div class="socialMedia">
                                                              <a href="tel:0506766333" target="_blank">
                                                                <svg width="24" height="24" viewBox="0 0 512 512">
                                                                  <g>
                                                                    <circle cx="256" cy="256" r="256" fill="#2196F3"></circle>
                                                                    <path
                                                                      d="M384 308.928c-27.616 0-53.952-6.016-78.24-17.888a16.267 16.267 0 0 0-12.256-.736 16.138 16.138 0 0 0-9.184 8.16l-11.52 23.84c-34.56-19.84-63.232-48.544-83.104-83.104l23.872-11.52c3.84-1.856 6.752-5.152 8.16-9.184 1.376-4.032 1.12-8.448-.736-12.256-11.904-24.256-17.92-50.592-17.92-78.24 0-8.832-7.168-16-16-16H128c-8.832 0-16 7.168-16 16 0 149.984 122.016 272 272 272 8.832 0 16-7.168 16-16v-59.072c0-8.832-7.168-16-16-16z"
                                                                      fill="#FAFAFA"
                                                                    ></path>
                                                                  </g>
                                                                </svg>
                                                                0506766333
                                                              </a>

                                                              <a href="https://wa.me/920003174" target="_blank">
                                                                <svg width="24" height="24" viewBox="0 0 176 176">
                                                                  <g>
                                                                    <circle cx="88" cy="88" r="88" fill="#29A71A"></circle>
                                                                    <g fill="#FFFFFF">
                                                                      <path
                                                                        d="M126.8 49.2a54.57 54.57 0 0 0-87.42 63.13l-5.79 28.11a2.08 2.08 0 0 0 .33 1.63 2.11 2.11 0 0 0 2.24.87l27.55-6.53A54.56 54.56 0 0 0 126.8 49.2zm-8.59 68.56a42.74 42.74 0 0 1-49.22 8l-3.84-1.9-16.89 4 .05-.21 3.5-17-1.88-3.71a42.72 42.72 0 0 1 7.86-49.59 42.73 42.73 0 0 1 60.42 0 2.28 2.28 0 0 0 .22.22 42.72 42.72 0 0 1-.22 60.19z"
                                                                      ></path>
                                                                      <path
                                                                        d="M116.71 105.29c-2.07 3.26-5.34 7.25-9.45 8.24-7.2 1.74-18.25.06-32-12.76l-.17-.15C63 89.41 59.86 80.08 60.62 72.68c.42-4.2 3.92-8 6.87-10.48a3.93 3.93 0 0 1 6.15 1.41l4.45 10a3.91 3.91 0 0 1-.49 4l-2.25 2.92a3.87 3.87 0 0 0-.35 4.32c1.26 2.21 4.28 5.46 7.63 8.47 3.76 3.4 7.93 6.51 10.57 7.57a3.82 3.82 0 0 0 4.19-.88l2.61-2.63a4 4 0 0 1 3.9-1l10.57 3a4 4 0 0 1 2.24 5.91z"
                                                                      ></path>
                                                                    </g>
                                                                  </g>
                                                                </svg>
                                                                920003174
                                                              </a>

                                                              <a
                                                                href="https://www.google.com/maps?q=طريق الأمير محمد بن فهد بن عبدالعزيز، الشاطئ الشرقي، الدمام 32413"
                                                                target="_blank"
                                                              >
                                                                <svg width="24" height="24" viewBox="0 0 512 512">
                                                                  <circle cx="256" cy="256" r="256" fill="#FF2147"></circle>
                                                                  <path
                                                                    fill="#FFFFFF"
                                                                    d="M256 116a111.31 111.31 0 0 0-111.31 111.31c0 40.08 47.3 109.64 80.23 153.23a39 39 0 0 0 62.16 0C320 337 367.31 267.39 367.31 227.31A111.31 111.31 0 0 0 256 116zm0 142.78a39.57 39.57 0 1 1 39.57-39.57A39.58 39.58 0 0 1 256 258.78z"
                                                                  ></path>
                                                                </svg>
                                                                طريق الأمير محمد بن فهد بن عبدالعزيز، الشاطئ الشرقي، الدمام
                                                                32413
                                                              </a>

                                                              <a
                                                                href="https://www.snapchat.com/add/platinum123"
                                                                target="_blank"
                                                              >
                                                                <svg width="24" height="24" viewBox="0 0 512 512">
                                                                  <circle cx="256" cy="256" r="256" fill="#FFC91B"></circle>
                                                                  <path
                                                                    fill="#FFFFFF"
                                                                    d="M416 332.102a22.225 22.225 0 0 1-9.251 17.833 39.71 39.71 0 0 1-11.287 5.818 141.913 141.913 0 0 1-19.433 4.625 45.365 45.365 0 0 1-1.396 6.895 24.177 24.177 0 0 1-13.818 16.233 31.052 31.052 0 0 1-12.073 2.065c-2.909 0-6.138-.233-9.484-.465-3.345-.233-7.302-.553-10.996-.553a32.008 32.008 0 0 0-12.916 2.065c-4.131 1.92-8.553 4.509-13.207 7.215-12.945 7.622-27.607 16.349-46.138 16.349s-33.193-8.727-46.138-16.262c-4.655-2.705-9.076-5.295-13.207-7.215a31.994 31.994 0 0 0-12.916-2.065c-3.695 0-7.389.262-10.996.553s-6.516.465-9.484.465a31.095 31.095 0 0 1-12.073-2.065 24.117 24.117 0 0 1-13.818-16.233 45.365 45.365 0 0 1-1.396-6.895 140.969 140.969 0 0 1-19.433-4.625 39.71 39.71 0 0 1-11.287-5.818 22.226 22.226 0 0 1-9.251-17.92 19.81 19.81 0 0 1 11.869-17.92 65.376 65.376 0 0 1 8.727-3.258c12.393-3.985 30.691-9.833 40.495-40.378l-14.865-5.818c-12.632-4.703-19.867-17.995-16.96-31.156a24.38 24.38 0 0 1 8.727-13.964 24.814 24.814 0 0 1 15.127-5.207 24.55 24.55 0 0 1 5.644.669l5.004 1.164c.233-5.818.349-11.956.349-18.24a94.873 94.873 0 0 1 7.651-37.149 97.221 97.221 0 0 1 50.967-51.229 93.098 93.098 0 0 1 37.265-7.651 93.88 93.88 0 0 1 37.004 7.651 97.748 97.748 0 0 1 51.055 51.055 94.517 94.517 0 0 1 7.68 37.149c0 6.284 0 12.393.349 18.24l5.004-1.164a24.466 24.466 0 0 1 20.771 4.538 24.38 24.38 0 0 1 8.873 13.964c3.005 13.219-4.249 26.619-16.96 31.331l-14.865 5.818c9.92 30.487 28.218 36.305 40.553 40.233a67 67 0 0 1 8.727 3.258A19.78 19.78 0 0 1 416 332.102z"
                                                                  ></path>
                                                                </svg>
                                                                platinum123
                                                              </a>
                                                            </div> -->
                    @if (isset($settings))
                        <div class="info_social">
                            <!-- <img src="assets/images/qr1.png" alt="" /> -->
                            <!-- <img src="assets/images/qr2.png" alt="" /> -->
                            <!-- <div class="experienceYears"><span> 20 </span> سنه خبرة</div> -->
                            <img style="margin-right: auto" src="{{ showFile($settings->experience_image) }}"
                                alt="" />
                        </div>
                    @else
                        <div class="info_social">
                            <!-- <img src="assets/images/qr1.png" alt="" /> -->
                            <!-- <img src="assets/images/qr2.png" alt="" /> -->
                            <!-- <div class="experienceYears"><span> 20 </span> سنه خبرة</div> -->
                            <img style="margin-right: auto" src="{{ asset('site') }}/assets/images/experience.svg"
                                alt="" />
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
    <div class="cv-actions-fixed">
        <button type="button" class="btn" id="donwload-cv">{{ __('ingaz.download_cv') }}</button>
        {{-- @dd($maid->jobTypes) --}}
        @if ($type == 'egar')
            <a href="{{ route('site.packages.show', $maid->id) }}"
                class="btn book">{{ __('ingaz.labor_rental_request') }}</a>
        @else
            <button type="button" class="btn book" id="cvBookingOpenBtn" data-bs-toggle="modal"
                data-bs-target="#cvBookingModal">
                {{ __('ingaz.book_cv') }}
            </button>
        @endif

        @if (!empty($maid->video_link) || (!empty($maid->video) && $maid->video !== '#'))
            @php
                $videoHref = !empty($maid->video_link) ? $maid->video_link : showFile($maid->video);
                $openAsIframe = !empty($maid->video_link);
            @endphp
            <a href="{{ $videoHref }}" class="btn video-btn" data-fancybox="video{{ $maid->id }}"
                @if ($openAsIframe) data-type="iframe" @endif title="مشاهدة الفيديو">
                <i class="fa-solid fa-video"></i>
            </a>
        @endif
    </div>

    @if ($type != 'egar')
        @php
            $servicesEmpty = empty($customerServices) || $customerServices->isEmpty();
        @endphp
        <div class="modal fade" id="cvBookingModal" tabindex="-1" aria-labelledby="cvBookingModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form id="cv-booking-form" action="{{ route('site.customer-service.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="maid_id" value="{{ $maid->id }}">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cvBookingModalLabel">{{ __('ingaz.book_cv') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="{{ __('ingaz.close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="booking-modal-stepper">
                                <div id="bookingStepAuth"
                                    class="booking-step {{ auth()->guard('web')->check() ? 'is-done' : 'is-active' }}">
                                    1. {{ __('ingaz.login') }} / {{ __('ingaz.create_account') }}
                                </div>
                                <div id="bookingStepService"
                                    class="booking-step {{ auth()->guard('web')->check() ? 'is-active' : '' }}">
                                    2. {{ __('ingaz.choose_customer_service') }}
                                </div>
                            </div>
                            <div id="bookingModalHint" class="booking-hint">
                                أكمل بيانات الحساب أولاً، ثم اختر ممثل خدمة العملاء لتأكيد الحجز.
                            </div>

                            @guest('web')
                                <div id="cvAuthSection">
                                    <div class="booking-auth-card mb-3">
                                        <ul class="nav nav-pills booking-auth-tabs mb-3">
                                            <li class="nav-item">
                                                <button type="button" class="nav-link active"
                                                    data-auth-tab="login">{{ __('ingaz.login') }}</button>
                                            </li>
                                            <li class="nav-item">
                                                <button type="button" class="nav-link"
                                                    data-auth-tab="register">{{ __('ingaz.create_account') }}</button>
                                            </li>
                                        </ul>

                                        <div id="bookingAuthLoginPane" class="booking-auth-pane">
                                            <p class="text-muted small mb-3">{{ __('ingaz.please_login_first') }}</p>
                                            <div class="row g-3 mb-2">
                                                <div class="col-md-6">
                                                    <label for="cvBookingPhone"
                                                        class="form-label">{{ __('ingaz.phone') }}</label>
                                                    <div class="input-group ltr">
                                                        <span class="input-group-text">+966</span>
                                                        <input type="tel" autocomplete="off" class="form-control"
                                                            id="cvBookingPhone" placeholder="5XXXXXXXX">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="cvBookingPassword"
                                                        class="form-label">{{ __('ingaz.password') }}</label>
                                                    <input type="password" autocomplete="off" class="form-control"
                                                        id="cvBookingPassword">
                                                </div>
                                            </div>
                                        </div>

                                        <div id="bookingAuthRegisterPane" class="booking-auth-pane d-none">
                                            <div class="row g-3 mb-2">
                                                <div class="col-md-12">
                                                    <label for="cvBookingRegisterName"
                                                        class="form-label">{{ __('ingaz.full_name') }}</label>
                                                    <input type="text" autocomplete="off" class="form-control"
                                                        id="cvBookingRegisterName"
                                                        placeholder="{{ __('ingaz.enter_full_name') }}">
                                                </div>
                                                <div class="col-md-12">
                                                    <label for="cvBookingRegisterPhone"
                                                        class="form-label">{{ __('ingaz.phone') }}</label>
                                                    <div class="input-group ltr">
                                                        <span class="input-group-text">+966</span>
                                                        <input type="tel" autocomplete="off" class="form-control"
                                                            id="cvBookingRegisterPhone" placeholder="5XXXXXXXX">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="cvBookingRegisterPassword"
                                                        class="form-label">{{ __('ingaz.password') }}</label>
                                                    <input type="password" autocomplete="off" class="form-control"
                                                        id="cvBookingRegisterPassword">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="cvBookingRegisterPasswordConfirmation"
                                                        class="form-label">{{ __('ingaz.confirm_password') }}</label>
                                                    <input type="password" autocomplete="off" class="form-control"
                                                        id="cvBookingRegisterPasswordConfirmation">
                                                </div>
                                            </div>
                                        </div>

                                        <div id="bookingAuthOtpPane" class="booking-auth-pane d-none">
                                            <p class="small mb-1">
                                                {{ __('ingaz.Please enter the verification code that was sent to you') }}
                                            </p>
                                            <p class="small fw-bold mb-3">
                                                <span id="bookingOtpPhonePreview"></span> +966
                                            </p>
                                            <div class="otp booking-otp mb-3">
                                                <input class="otp-input booking-otp-input" type="text" maxlength="1">
                                                <input class="otp-input booking-otp-input" type="text" maxlength="1">
                                                <input class="otp-input booking-otp-input" type="text" maxlength="1">
                                                <input class="otp-input booking-otp-input" type="text" maxlength="1">
                                                <input class="otp-input booking-otp-input" type="text" maxlength="1">
                                            </div>
                                        </div>

                                        <div id="cvBookingCaptchaWrap" class="mb-2 mt-3">
                                            {!! NoCaptcha::display() !!}
                                        </div>
                                        <div id="cvBookingLoginErrors"
                                            class="alert alert-danger d-none py-2 px-3 small mt-2"></div>
                                    </div>
                                </div>
                            @endguest

                            <div id="cvServiceSection" @if (auth()->guard('web')->guest()) style="display: none;" @endif>
                                <div class="alert alert-info small mb-3">
                                    {{ __('ingaz.customer_service_desc') }}
                                </div>

                                <h6 class="mb-2">{{ __('ingaz.choose_customer_service') }}</h6>
                                <div class="choose booking-customer-choose">
                                    @forelse ($customerServices ?? collect() as $customerService)
                                        @php
                                            $contactPhone = $customerService->contact_phone ?? null;
                                            $whatsappPhone = $customerService->whatsapp_phone ?? null;
                                            $telLink = $customerService->formatted_contact_phone ??
                                                ($contactPhone ? formatSaudiPhone($contactPhone) : null);
                                            $whatsappLink = $customerService->formatted_whatsapp_phone ??
                                                ($whatsappPhone ? formatSaudiPhone($whatsappPhone) : null);
                                        @endphp
                                        <div class="customerOption">
                                            <input type="radio" class="btn-check" value="{{ $customerService->id }}"
                                                name="customer" id="customer_{{ $customerService->id }}">
                                            <label class="btn btn-outline" for="customer_{{ $customerService->id }}">
                                                <img src="{{ asset('site') }}/assets/images/avater2.webp"
                                                    alt="{{ $customerService->name }}">
                                                <span>
                                                    {{ $customerService->name }}
                                                    <small class="d-block text-muted mt-1">اختر ممثل الخدمة ثم تواصل مباشرة</small>
                                                </span>
                                            </label>
                                            <div class="customer-actions">
                                                @if ($telLink)
                                                    <a href="tel:{{ $telLink }}" class="service-action is-call"
                                                        aria-label="اتصال بـ {{ $customerService->name }}"
                                                        title="اتصال بـ {{ $customerService->name }}">
                                                        <i class="fa-solid fa-phone"></i>
                                                    </a>
                                                @else
                                                    <span class="service-action is-disabled" aria-hidden="true"
                                                        title="رقم الاتصال غير متوفر">
                                                        <i class="fa-solid fa-phone"></i>
                                                    </span>
                                                @endif

                                                @if ($whatsappLink)
                                                    <a href="https://wa.me/{{ $whatsappLink }}"
                                                        class="service-action is-whatsapp" target="_blank" rel="noopener"
                                                        aria-label="واتساب {{ $customerService->name }}"
                                                        title="واتساب {{ $customerService->name }}">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </a>
                                                @else
                                                    <span class="service-action is-disabled" aria-hidden="true"
                                                        title="رقم الواتساب غير متوفر">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="alert alert-danger mb-0">لا يوجد مسوقون متاحون حاليا.</div>
                                    @endforelse
                                </div>
                                <p class="small text-muted mt-2 mb-0">اختر ممثل واحد وسيظهر زر "تأكيد حجز السيرة"
                                    تلقائيًا.</p>
                            </div>
                        </div>
                        <div class="modal-footer booking-modal-footer">
                            <a href="{{ route('site.forget-password.index') }}"
                                class="booking-footer-link d-none js-auth-forgot-redirect" id="cvModalForgotLink"
                                data-target="{{ route('site.forget-password.index') }}">
                                {{ __('ingaz.password_forget') }}
                            </a>
                            <div class="booking-footer-actions">
                                <button type="button" id="cvModalBackToRegisterBtn"
                                    class="btn btn-link p-0 small d-none">
                                    تعديل بيانات التسجيل
                                </button>
                                <button type="button" id="cvModalResendOtpBtn"
                                    class="btn btn-outline-secondary btn-sm d-none">
                                    {{ __('web.resend_code') }}
                                </button>
                                <button type="button" id="cvBookingPrimaryActionBtn" class="btn book">
                                    تسجيل الدخول والمتابعة
                                </button>
                                <button type="submit" id="cvBookingSubmitBtn" class="btn book d-none"
                                    @if ($servicesEmpty || auth()->guard('web')->guest()) disabled @endif>
                                    تأكيد حجز السيرة
                                </button>
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">{{ __('ingaz.cancel') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
@section('js')
    @if ($type != 'egar' && auth()->guard('web')->guest())
        {!! NoCaptcha::renderJs(app()->getLocale()) !!}
    @endif
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>

    <script>
        function wrapText(element, maxLength) {
            const text = element.innerText;
            if (text.length > maxLength) {
                element.innerText = text.slice(0, maxLength) + '...';
            }
        }

        document.querySelectorAll('.passportNumbers span').forEach(span => {
            wrapText(span, 20); // تحديد الحد الأقصى لعدد الحروف
        });
    </script>
    <script>
        $(document).ready(function() {
            const downloadBtn = document.getElementById('donwload-cv');
            const targetDiv = document.getElementById('cv-wrapper');

            if (!downloadBtn || !targetDiv) {
                return;
            }

            const waitForImages = () => {
                const images = targetDiv.querySelectorAll('img');
                if (!images.length) {
                    return Promise.resolve();
                }
                return Promise.all(Array.from(images).map((img) => {
                    if (img.complete) {
                        return Promise.resolve();
                    }
                    return new Promise((resolve) => {
                        img.addEventListener('load', resolve, {
                            once: true
                        });
                        img.addEventListener('error', resolve, {
                            once: true
                        });
                    });
                }));
            };

            downloadBtn.addEventListener('click', async function() {
                // Loader
                const originalHTML = downloadBtn.innerHTML;
                downloadBtn.classList.add('loading');
                downloadBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm" role="status"></span>';
                downloadBtn.setAttribute('disabled', 'disabled');

                try {
                    await waitForImages();
                    const canvas = await html2canvas(targetDiv, {
                        useCORS: true,
                        allowTaint: true,
                        scale: 2,
                        scrollY: -window.scrollY,
                    });
                    const imgData = canvas.toDataURL('image/jpeg', 0.95);
                    const {
                        jsPDF
                    } = window.jspdf;
                    const pdf = new jsPDF('p', 'pt', [canvas.width, canvas.height]);
                    pdf.addImage(imgData, 'JPEG', 0, 0, canvas.width, canvas.height);
                    pdf.save(`cv-{{ $maid->id }}.pdf`);
                } catch (error) {
                    console.error('Error generating CV PDF:', error);
                } finally {
                    downloadBtn.classList.remove('loading');
                    downloadBtn.innerHTML = originalHTML;
                    downloadBtn.removeAttribute('disabled');
                }
            });
        });
    </script>
    @if ($type != 'egar')
        <script>
            $(document).ready(function() {
                const bookingForm = $('#cv-booking-form');
                const modalElement = document.getElementById('cvBookingModal');
                const authSection = $('#cvAuthSection');
                const serviceSection = $('#cvServiceSection');
                const authTabs = $('.booking-auth-tabs .nav-link');
                const loginPane = $('#bookingAuthLoginPane');
                const registerPane = $('#bookingAuthRegisterPane');
                const otpPane = $('#bookingAuthOtpPane');
                const captchaWrap = $('#cvBookingCaptchaWrap');
                const primaryActionBtn = $('#cvBookingPrimaryActionBtn');
                const forgotLink = $('#cvModalForgotLink');
                const resendOtpBtn = $('#cvModalResendOtpBtn');
                const backToRegisterBtn = $('#cvModalBackToRegisterBtn');
                const submitBtn = $('#cvBookingSubmitBtn');
                const loginErrors = $('#cvBookingLoginErrors');
                const modalHint = $('#bookingModalHint');
                const stepAuth = $('#bookingStepAuth');
                const stepService = $('#bookingStepService');
                const serviceCards = $('.booking-customer-choose .customerOption .btn');
                const hasServices = bookingForm.find('input[name="customer"]').length > 0;
                const trackingMaidId = @json((string) $maid->id);
                const trackingServiceType = @json($type ?: 'estqdam');
                const resendDefaultText = resendOtpBtn.length ? resendOtpBtn.text().trim() : '';
                const resendWaitPrefix = "{{ app()->getLocale() === 'ar' ? 'إعادة الإرسال خلال' : 'Resend in' }}";
                const secondUnit = "{{ app()->getLocale() === 'ar' ? 'ث' : 's' }}";
                const loginActionText = 'تسجيل الدخول والمتابعة';
                const registerActionText = 'إنشاء الحساب وإرسال الكود';
                const verifyActionText = 'تأكيد الكود والمتابعة';
                let registerPhoneForOtp = '';
                let isUserAuthenticated = {{ auth()->guard('web')->check() ? 'true' : 'false' }};
                let resendTimer = null;

                if (!bookingForm.length) {
                    return;
                }

                function updateBookingTrackingUrl(extraParams = {}) {
                    const currentUrl = new URL(window.location.href);

                    currentUrl.searchParams.set('booking', '1');
                    currentUrl.searchParams.set('booking_step', extraParams.booking_step || 'opened');
                    currentUrl.searchParams.set('maid_id', trackingMaidId);
                    currentUrl.searchParams.set('service_type', trackingServiceType);
                    currentUrl.searchParams.set('tracking_source', 'cv_details');
                    currentUrl.searchParams.set('tracking_action', 'book_cv');

                    Object.keys(extraParams).forEach(function(key) {
                        const value = extraParams[key];

                        if (value === null || value === undefined || value === '') {
                            currentUrl.searchParams.delete(key);
                            return;
                        }

                        currentUrl.searchParams.set(key, value);
                    });

                    window.history.replaceState({}, '', currentUrl.toString());
                }

                function setBtnLoading($btn, loading, text) {
                    if (!$btn.length) {
                        return;
                    }
                    if (loading) {
                        $btn.prop('disabled', true);
                        $btn.data('original-text', $btn.html());
                        $btn.html(
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                        );
                        return;
                    }
                    $btn.prop('disabled', false);
                    $btn.html($btn.data('original-text') || text || $btn.html());
                }

                function clearError() {
                    loginErrors.addClass('d-none').empty();
                }

                function showError(message) {
                    loginErrors.removeClass('d-none').html(message);
                }

                function parseAjaxErrors(xhr) {
                    const res = xhr.responseJSON || {};
                    let errorMessages = [];

                    if (res.errors) {
                        Object.keys(res.errors).forEach(function(key) {
                            if (res.errors[key] && res.errors[key][0]) {
                                errorMessages.push(res.errors[key][0]);
                            }
                        });
                    } else if (res.message) {
                        errorMessages.push(res.message);
                    } else {
                        errorMessages.push("{{ __('ingaz.error_from_server') }}");
                    }

                    return errorMessages.join('<br>');
                }

                function updateHint(message) {
                    if (modalHint.length) {
                        modalHint.text(message);
                    }
                }

                function stopResendTimer() {
                    if (resendTimer) {
                        clearInterval(resendTimer);
                        resendTimer = null;
                    }
                }

                function startResendTimer(seconds = 30) {
                    if (!resendOtpBtn.length) {
                        return;
                    }

                    stopResendTimer();
                    let remaining = seconds;
                    resendOtpBtn.prop('disabled', true).text(`${resendWaitPrefix} ${remaining}${secondUnit}`);

                    resendTimer = setInterval(function() {
                        remaining -= 1;
                        if (remaining <= 0) {
                            stopResendTimer();
                            resendOtpBtn.prop('disabled', false).text(resendDefaultText);
                            return;
                        }
                        resendOtpBtn.text(`${resendWaitPrefix} ${remaining}${secondUnit}`);
                    }, 1000);
                }

                function refreshServiceCards() {
                    serviceCards.removeClass('is-selected');
                    bookingForm.find('input[name="customer"]:checked').each(function() {
                        $(this).next('label').addClass('is-selected');
                    });
                }

                function getCurrentAuthMode() {
                    if (isUserAuthenticated) {
                        return 'service';
                    }

                    if (!otpPane.hasClass('d-none')) {
                        return 'otp';
                    }

                    const activeTab = authTabs.filter('.active').data('auth-tab');
                    return activeTab === 'register' ? 'register' : 'login';
                }

                function setPrimaryAction(mode) {
                    if (!primaryActionBtn.length) {
                        return;
                    }

                    const modeTextMap = {
                        login: loginActionText,
                        register: registerActionText,
                        otp: verifyActionText
                    };
                    const text = modeTextMap[mode] || loginActionText;

                    primaryActionBtn.data('mode', mode);
                    primaryActionBtn.text(text);
                    primaryActionBtn.prop('disabled', false).removeClass('d-none');
                }

                function updateFooterActions() {
                    const selectedCustomer = bookingForm.find('input[name="customer"]:checked').val();

                    if (forgotLink.length) {
                        forgotLink.addClass('d-none');
                    }
                    if (resendOtpBtn.length) {
                        resendOtpBtn.addClass('d-none');
                    }
                    if (backToRegisterBtn.length) {
                        backToRegisterBtn.addClass('d-none');
                    }

                    if (isUserAuthenticated) {
                        if (primaryActionBtn.length) {
                            primaryActionBtn.addClass('d-none').prop('disabled', true);
                        }

                        if (hasServices && selectedCustomer) {
                            submitBtn.removeClass('d-none').prop('disabled', false);
                        } else {
                            submitBtn.addClass('d-none').prop('disabled', true);
                        }
                        return;
                    }

                    submitBtn.addClass('d-none').prop('disabled', true);

                    const mode = getCurrentAuthMode();
                    setPrimaryAction(mode);

                    if (mode === 'login' && forgotLink.length) {
                        forgotLink.removeClass('d-none');
                    }

                    if (mode === 'otp') {
                        if (resendOtpBtn.length) {
                            resendOtpBtn.removeClass('d-none');
                        }
                        if (backToRegisterBtn.length) {
                            backToRegisterBtn.removeClass('d-none');
                        }
                    }
                }

                function showAuthTab(tab) {
                    clearError();

                    if (!authSection.length) {
                        updateFooterActions();
                        return;
                    }

                    stopResendTimer();
                    if (resendOtpBtn.length) {
                        resendOtpBtn.prop('disabled', false).text(resendDefaultText);
                    }

                    authTabs.removeClass('active');
                    loginPane.addClass('d-none');
                    registerPane.addClass('d-none');
                    otpPane.addClass('d-none');
                    captchaWrap.show();

                    if (tab === 'register') {
                        authTabs.filter('[data-auth-tab="register"]').addClass('active');
                        registerPane.removeClass('d-none');
                        updateHint('أنشئ حسابًا جديدًا، ثم أدخل كود التحقق للمتابعة.');
                        updateFooterActions();
                        return;
                    }

                    authTabs.filter('[data-auth-tab="login"]').addClass('active');
                    loginPane.removeClass('d-none');
                    updateHint('سجّل الدخول أولًا ثم انتقل لاختيار ممثل خدمة العملاء.');
                    updateFooterActions();
                }

                function showOtpPane(phone) {
                    registerPhoneForOtp = phone;
                    clearError();

                    authTabs.removeClass('active').addClass('d-none');
                    loginPane.addClass('d-none');
                    registerPane.addClass('d-none');
                    otpPane.removeClass('d-none');
                    captchaWrap.hide();

                    $('#bookingOtpPhonePreview').text(phone);
                    otpPane.find('.booking-otp-input').val('');
                    otpPane.find('.booking-otp-input').first().focus();
                    startResendTimer(30);
                    updateHint('أدخل رمز التحقق المرسل إلى رقم جوالك لإكمال إنشاء الحساب.');
                    updateFooterActions();
                }

                function syncSteps() {
                    if (isUserAuthenticated) {
                        stepAuth.removeClass('is-active').addClass('is-done');
                        stepService.addClass('is-active').removeClass('is-done');

                        if (authSection.length) {
                            authSection.hide();
                        }
                        serviceSection.show();
                        if (hasServices) {
                            updateHint('اختر ممثل خدمة العملاء المناسب، وبعد الاختيار سيظهر زر "تأكيد حجز السيرة".');
                        } else {
                            updateHint('لا يوجد ممثلو خدمة عملاء متاحون حاليًا، حاول لاحقًا.');
                        }
                        updateFooterActions();
                        return;
                    }

                    stepAuth.addClass('is-active').removeClass('is-done');
                    stepService.removeClass('is-active is-done');

                    if (authSection.length) {
                        authSection.show();
                        authTabs.removeClass('d-none');
                    }
                    serviceSection.hide();
                    submitBtn.addClass('d-none').prop('disabled', true);
                    updateFooterActions();
                }

                function handleLoginAction() {
                    if (isUserAuthenticated) {
                        return;
                    }

                    clearError();

                    const phone = $('#cvBookingPhone').val().trim();
                    const password = $('#cvBookingPassword').val();
                    const captchaResponse = typeof grecaptcha !== 'undefined' ? grecaptcha.getResponse() : '';

                    if (!phone || !password) {
                        showError('يرجى إدخال رقم الجوال وكلمة المرور');
                        return;
                    }

                    if (!/^5\d{8}$/.test(phone)) {
                        showError("{{ __('web.phone_validation_text') }}");
                        return;
                    }

                    if (!captchaResponse) {
                        showError("{{ __('ingaz.captcha_required') }}");
                        return;
                    }

                    setBtnLoading(primaryActionBtn, true, loginActionText);

                    $.ajax({
                        url: "{{ route('site.login.store') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            phone: phone,
                            password: password,
                            'g-recaptcha-response': captchaResponse
                        },
                    }).done(function(response) {
                        if (!response.success) {
                            showError(response.message || "{{ __('ingaz.error_from_server') }}");
                            return;
                        }

                        isUserAuthenticated = true;
                        clearError();
                        syncSteps();
                        toastr.success(response.message || "{{ __('ingaz.login_successfully') }}");
                    }).fail(function(xhr) {
                        showError(parseAjaxErrors(xhr));
                    }).always(function() {
                        setBtnLoading(primaryActionBtn, false, loginActionText);

                        if (typeof grecaptcha !== 'undefined') {
                            grecaptcha.reset();
                        }
                    });
                }

                function handleRegisterAction() {
                    clearError();

                    const name = $('#cvBookingRegisterName').val().trim();
                    const phone = $('#cvBookingRegisterPhone').val().trim();
                    const password = $('#cvBookingRegisterPassword').val();
                    const passwordConfirmation = $('#cvBookingRegisterPasswordConfirmation').val();
                    const captchaResponse = typeof grecaptcha !== 'undefined' ? grecaptcha.getResponse() : '';

                    if (!name || !phone || !password || !passwordConfirmation) {
                        showError('يرجى استكمال جميع بيانات التسجيل');
                        return;
                    }

                    if (!/^5\d{8}$/.test(phone)) {
                        showError("{{ __('web.phone_validation_text') }}");
                        return;
                    }

                    if (password.length < 6) {
                        showError("{{ __('ingaz.password_min', ['min' => 6]) }}");
                        return;
                    }

                    if (password !== passwordConfirmation) {
                        showError("{{ __('ingaz.password_confirmed') }}");
                        return;
                    }

                    if (!captchaResponse) {
                        showError("{{ __('ingaz.captcha_required') }}");
                        return;
                    }

                    setBtnLoading(primaryActionBtn, true, registerActionText);

                    $.ajax({
                        url: "{{ route('site.register.store') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            name: name,
                            phone: phone,
                            password: password,
                            password_confirmation: passwordConfirmation,
                            'g-recaptcha-response': captchaResponse
                        },
                    }).done(function(response) {
                        showOtpPane(phone);
                        toastr.success(response.message || "{{ __('ingaz.register_successfully') }}");
                    }).fail(function(xhr) {
                        showError(parseAjaxErrors(xhr));
                    }).always(function() {
                        setBtnLoading(primaryActionBtn, false, registerActionText);
                        if (typeof grecaptcha !== 'undefined') {
                            grecaptcha.reset();
                        }
                    });
                }

                function handleVerifyOtpAction() {
                    clearError();
                    const code = otpPane.find('.booking-otp-input').map(function() {
                        return $(this).val().trim();
                    }).get().join('');

                    if (!registerPhoneForOtp || code.length !== 5) {
                        showError('يرجى إدخال رمز التحقق المكوّن من 5 أرقام');
                        return;
                    }

                    setBtnLoading(primaryActionBtn, true, verifyActionText);

                    $.ajax({
                        url: "{{ route('site.otp-register.store') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            phone: registerPhoneForOtp,
                            code: code
                        },
                    }).done(function(response) {
                        if (response.code === 422) {
                            showError(response.message || "{{ __('ingaz.code_is_wrong') }}");
                            return;
                        }

                        isUserAuthenticated = true;
                        clearError();
                        syncSteps();
                        toastr.success(response.message || "{{ __('ingaz.login_successfully') }}");
                    }).fail(function(xhr) {
                        showError(parseAjaxErrors(xhr));
                    }).always(function() {
                        setBtnLoading(primaryActionBtn, false, verifyActionText);
                    });
                }

                refreshServiceCards();
                syncSteps();

                bookingForm.on('change', 'input[name="customer"]', function() {
                    refreshServiceCards();
                    updateFooterActions();
                    updateBookingTrackingUrl({
                        booking_step: 'customer_selected',
                        customer_service_id: $(this).val()
                    });
                });

                $('#cvBookingOpenBtn').on('click', function() {
                    updateBookingTrackingUrl({
                        booking_step: isUserAuthenticated ? 'customer_service' : 'auth'
                    });
                });

                authTabs.on('click', function() {
                    showAuthTab($(this).data('auth-tab'));
                });

                if (primaryActionBtn.length) {
                    primaryActionBtn.on('click', function() {
                        const mode = primaryActionBtn.data('mode');

                        if (mode === 'register') {
                            handleRegisterAction();
                            return;
                        }

                        if (mode === 'otp') {
                            handleVerifyOtpAction();
                            return;
                        }

                        handleLoginAction();
                    });
                }

                otpPane.on('input', '.booking-otp-input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value.length === 1) {
                        $(this).next('.booking-otp-input').focus();
                    }
                });

                otpPane.on('keydown', '.booking-otp-input', function(e) {
                    if (e.key === 'Backspace' && !this.value) {
                        $(this).prev('.booking-otp-input').focus();
                    }
                });

                if (resendOtpBtn.length) {
                    resendOtpBtn.on('click', function() {
                        if (!registerPhoneForOtp) {
                            return;
                        }

                        if (resendOtpBtn.prop('disabled')) {
                            return;
                        }

                        const originalText = resendOtpBtn.text();
                        resendOtpBtn.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                        );
                        $.ajax({
                            url: "{{ route('site.resend-otp.store') }}",
                            type: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                phone: registerPhoneForOtp
                            },
                        }).done(function(response) {
                            toastr.success(response.message || response.code ||
                                "{{ __('web.resend_code_successfuly') }}");
                        }).fail(function(xhr) {
                            showError(parseAjaxErrors(xhr));
                        }).always(function() {
                            resendOtpBtn.html(originalText);
                            startResendTimer(30);
                        });
                    });
                }

                if (backToRegisterBtn.length) {
                    backToRegisterBtn.on('click', function() {
                        stopResendTimer();
                        if (resendOtpBtn.length) {
                            resendOtpBtn.prop('disabled', false).text(resendDefaultText);
                        }
                        authTabs.removeClass('d-none');
                        showAuthTab('register');
                    });
                }

                bookingForm.on('submit', function(e) {
                    const selectedCustomer = bookingForm.find('input[name="customer"]:checked').val();

                    if (!isUserAuthenticated) {
                        e.preventDefault();
                        toastr.error("{{ __('ingaz.please_login_first') }}");
                        return;
                    }

                    if (!selectedCustomer) {
                        e.preventDefault();
                        toastr.error("{{ __('ingaz.customer_is_required') }}");
                        return;
                    }

                    submitBtn.prop('disabled', true);
                    updateBookingTrackingUrl({
                        booking_step: 'submitted',
                        customer_service_id: selectedCustomer
                    });
                    submitBtn.html(
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                    );
                });

                $('.js-auth-forgot-redirect').on('click', function(e) {
                    e.preventDefault();
                    const target = $(this).data('target');
                    const redirectUrl = new URL(window.location.href);
                    redirectUrl.searchParams.set('booking_modal', '1');

                    $.ajax({
                        url: "{{ route('site.store.intended.url') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            redirect_url: redirectUrl.toString()
                        }
                    }).always(function() {
                        window.location.href = target;
                    });
                });

                const params = new URLSearchParams(window.location.search);
                if (params.get('booking_modal') === '1' && modalElement) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const bookingModal = new bootstrap.Modal(modalElement);
                        bookingModal.show();
                    } else if (typeof $('#cvBookingModal').modal === 'function') {
                        $('#cvBookingModal').modal('show');
                    }

                    params.delete('booking_modal');
                    const nextQuery = params.toString();
                    const cleanUrl = window.location.pathname + (nextQuery ? '?' + nextQuery : '') + window
                        .location.hash;
                    window.history.replaceState({}, '', cleanUrl);
                }

                if (!isUserAuthenticated && authSection.length) {
                    showAuthTab('login');
                }
            });
        </script>
    @endif
    <script>
        @if (Session::has('error'))
            toastr.error("{{ Session::get('error') }}");
        @endif

        @if (Session::has('success'))
            toastr.success("{{ Session::get('success') }}");
        @endif
    </script>
@endsection
