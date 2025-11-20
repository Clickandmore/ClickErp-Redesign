@extends('site.layouts.index')
@section('title')
    {{ __('ingaz.cv_details') }}
@endsection
@push('styles')
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
        }
    </style>
@endpush
@section('content')
    @php
        $todayDate = now()->toDateString();
        $offerStarts = optional($maid->offer_starts_at)->format('Y-m-d');
        $offerExpires = optional($maid->offer_expires_at)->format('Y-m-d');
        $offerPriceRaw = $maid->offer_price !== null ? (float) $maid->offer_price : null;
        $salaryRaw = $maid->resolved_salary !== null ? (float) $maid->resolved_salary : null;
        $priceRaw = $maid->resolved_price !== null ? (float) $maid->resolved_price : null;
        $priceDisplay = $priceRaw !== null ? number_format($priceRaw, 2) : null;
        $salaryDisplay = $salaryRaw !== null ? number_format($salaryRaw, 2) : null;
        $offerActive =
            $offerPriceRaw !== null &&
            (empty($offerStarts) || $offerStarts <= $todayDate) &&
            (empty($offerExpires) || $offerExpires >= $todayDate);
        $offerPriceDisplay = $offerPriceRaw !== null ? number_format($offerPriceRaw, 2) : null;
        $cvPublicLink = route('site.all-workers.show', $maid->id);
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
                        @endphp
                        @if (isset($percentages))
                            @forelse ($percentages as $key => $percentage)
                                @if ($percentage)
                                    <div class="percentDiv">
                                        <div class="percent p{{ $percentage }}">
                                            <p class="percentNum">{{ $percentage }} <span> % </span></p>
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
                        $galleryImage = $maid->cv_file ?: ($maid->cv_preview ?: $maid->image);
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
                            {{ $educationLabel }}
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
                        @foreach ($skills->chunk(4) as $chunk)
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
                            <td>{{ __('ingaz.number_of_years') }}</td>
                            <td>{{ __('ingaz.job_title') }}</td>
                        </tr>
                        @forelse(optional($maid)->expertises ?? [] as $expertise)
                            <tr>
                                <td>
                                    {{ app()->getLocale() === 'ar'
                                        ? optional($expertise->country)->name_ar
                                        : optional($expertise->country)->name_en }}
                                </td>
                                <td>{{ $expertise->number_of_years }}</td>
                                <td>{{ optional($expertise->jobTitle)->title }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align:center; vertical-align:middle;">
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
        <form action="{{ route('site.customer-service.store') }}" method="POST" style="margin-top: 0 !important ">
            @csrf
            <input type="hidden" name="maid_id" value="{{ $maid->id }}">
            <button type="submit" class="btn book">{{ __('ingaz.book_cv') }}</button>
        </form>
        @if (!empty($maid->video) && $maid->video !== '#')
            <a data-fancybox="video2" href="{{ showFile($maid->video) }}" type="button" class="btn video-btn"
                title="مشاهدة الفيديو">
                <i class="fa-solid fa-video"></i>
                <!-- <span>مشاهدة الفيديو</span> -->
            </a>
        @endif
    </div>
@endsection
@section('js')
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
    <script>
        @if (Session::has('error'))
            toastr.error("{{ Session::get('error') }}");
        @endif

        @if (Session::has('success'))
            toastr.success("{{ Session::get('success') }}");
        @endif
    </script>
@endsection
