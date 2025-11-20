@extends('site.layouts.index')
@section('title')
    {{ __('ingaz.home') }}
@endsection
<style>
    .hero-services {
        position: relative;
        z-index: 2;
        margin: -80px auto 0;
        background: #f9f9f9;
        width: min(100% - 24px, 1320px);
        padding: 12px;
        border-radius: 24px;
        box-shadow: var(--BigShadow);
        display: flex;
        gap: 12px;
        flex-wrap: wrap;

        .section-header {
            width: 100%;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            margin: 0 auto 20px;

            .badge {

                align-items: center;
                gap: 8px;
                background-color: var(--mainColor10);
                color: var(--mainColor);
                padding: 8px 18px;
                font-size: 14px;
                font-weight: 600;
                margin: 10px 0;
                border-radius: 30px;

                i {
                    color: var(--mainColor);
                    font-size: 16px;
                }
            }

            .section-title {
                font-size: 22px;
                font-weight: 700;
                color: black;
                margin-bottom: 10px;
            }

            p {
                color: #727272;
            }
        }

        .expertise {
            position: absolute;
            left: 50%;
            top: -120px;
            transform: translateX(-50%);
            width: 220px;
            aspect-ratio: 1;
            z-index: -1;
            background: #f9f9f9;
            border-radius: 50%;
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;

            img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 50%;
            }

            &::after {
                content: "";
                position: absolute;
                top: 55%;
                left: 0;
                width: 100%;
                height: 50%;
                background: #f9f9f9;
            }
        }

        .services-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 20px;

            a {
                color: black;
            }

            @media (max-width: 992px) {
                grid-template-columns: repeat(2, 1fr);
            }

            @media (max-width: 576px) {
                grid-template-columns: 1fr;
            }

            .service-card {
                background-color: var(--white);
                border-radius: 15px;
                padding: 25px;
                text-align: center;
                transition: all 0.3s ease;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100%;

                &:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
                }

                .icon {
                    font-size: 35px;
                    height: 60px;
                    width: 60px;
                    margin-bottom: 15px;
                    padding: 15px;
                    border-radius: 30%;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    margin-inline: auto;

                    &.orange {
                        background-color: #ffe7d3;
                        color: #ff8c00;
                    }

                    &.blue {
                        background-color: var(--mainColor10);
                        color: var(--mainColor);
                    }

                    &.purple {
                        background-color: #ead3ff;
                        color: #7b2cbf;
                    }

                    &.green {
                        background-color: #d3ffd8;
                        color: #28a745;
                    }
                }

                h4 {
                    font-size: 18px;
                    margin-bottom: 10px;
                    font-weight: bold;
                }

                p {
                    font-size: 14px;
                    color: #666;
                    margin-bottom: 10px;
                    min-height: 40px;
                }

                ul {
                    padding: 0;
                    list-style: none;
                    text-align: right;

                    li {
                        font-size: 13px;
                        color: #444;
                        margin-bottom: 8px;
                        display: flex;
                        align-items: center;
                        justify-content: flex-start;
                        gap: 8px;

                        &::before {
                            content: "✔";
                            font-size: 11px;
                            font-weight: bold;
                            color: #fff;
                            display: inline-flex;
                            justify-content: center;
                            align-items: center;
                            width: 18px;
                            height: 18px;
                            border-radius: 50%;
                        }
                    }
                }

                &.orange ul li::before {
                    background-color: #ff8c00;
                }

                &.blue ul li::before {
                    background-color: var(--mainColor);
                }

                &.purple ul li::before {
                    background-color: #7b2cbf;
                }

                &.green ul li::before {
                    background-color: #28a745;
                }
            }
        }


        .warning {
            width: 100%;
            background-color: #c10e0e;
            color: var(--white);
            text-align: center;
            padding: 12px;
            border-radius: 12px;
            margin-top: 10px;
            font-weight: 500;
        }
    }
</style>

@section('content')
    @php
        $socials = [
            ['url' => $settings->twitter ?? null, 'icon' => 'fab fa-twitter', 'title' => 'twitter page'],
            ['url' => $settings->facebook ?? null, 'icon' => 'fab fa-facebook-f', 'title' => 'facebook page'],
            ['url' => $settings->instagram ?? null, 'icon' => 'fab fa-instagram', 'title' => 'instagram page'],
            ['url' => $settings->linkedin ?? null, 'icon' => 'fab fa-linkedin-in', 'title' => 'linkedin page'],
            ['url' => $settings->youtube ?? null, 'icon' => 'fab fa-youtube', 'title' => 'youtube page'],
            ['url' => $settings->snapchat ?? null, 'icon' => 'fab fa-snapchat-ghost', 'title' => 'snapchat page'],
        ];

        $whatsAppNumber = $settings->whatsapp ?? null;
        if ($whatsAppNumber) {
            $cleanNumber = preg_replace('/\D+/', '', $whatsAppNumber);
            $waLink = $cleanNumber ? 'https://wa.me/' . $cleanNumber : $whatsAppNumber;
            $socials[] = ['url' => $waLink, 'icon' => 'fab fa-whatsapp', 'title' => 'whatsapp'];
        }

        $socials = array_filter($socials, fn($item) => !empty($item['url']));
    @endphp
    @if (!empty($socials))
        <div class="sideSocial d-md-flex snipcss-W5Bnr">
            @foreach ($socials as $social)
                <a href="{{ $social['url'] }}" target="_blank" rel="noopener" title="{{ $social['title'] }}">
                    <i class="{{ $social['icon'] }}"></i>
                </a>
            @endforeach
        </div>
    @endif
    <!-- main slider -->
    <section class="mainSlider">
        <div class="swiper">
            <div class="swiper-wrapper">
                @forelse($sliders as $slider)
                    <div class="swiper-slide" style="background-image: url({{ showFile($slider->image) }})">
                        <div class="info">
                            <h4 class="hint">
                                {{ $slider->title }}
                            </h4>
                            <h1 class="sliderTitle">
                                {{ $slider->description }}
                            </h1>
                        </div>
                    </div>
                @empty
                    <!-- swiper-slide -->
                    <div class="swiper-slide" style="background-image: url({{ asset('site') }}/assets/images/s3.webp)">

                        <div class="info">
                            <h4 class="hint">
                                {{ __('web.project_name') }}
                            </h4>
                            <h1 class="sliderTitle">
                                {{ __('web.project_desc') }}
                            </h1>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    @php
        $heroServices = [
            [
                'title' => __('ingaz.recruitment_request_title'),
                'description' => __('ingaz.recruitment_request_text'),
                'icon' => 'fa-solid fa-user-plus',
                'theme' => 'emerald',
                'badge' => __('ingaz.recruitment'),
                'url' => route('site.all-workers.index') . '?type=estqdam',
            ],
            [
                'title' => __('ingaz.labor_rental_request'),
                'description' => __('ingaz.Labor_transfer_text'),
                'icon' => 'fa-solid fa-clipboard-user',
                'theme' => 'blue',
                'badge' => __('ingaz.Labor_rental'),
                'url' => route('site.all-workers.index') . '?type=egar',
            ],
            [
                'title' => __('ingaz.transfer_services'),
                'description' => __('ingaz.transfer_service_text'),
                'icon' => 'fa-solid fa-arrow-right-arrow-left',
                'theme' => 'orange',
                'badge' => __('ingaz.Labor_transfer_services'),
                'url' => route('site.all-workers.index') . '?type=transfer',
            ],
            [
                'title' => __('ingaz.Labor_transfer_request'),
                'description' => __('ingaz.instant_receipt_desc'),
                'icon' => 'fa-solid fa-bolt-lightning',
                'theme' => 'violet',
                'badge' => __('ingaz.Labor_transfer_request'),
                'url' => route('site.all-workers.index') . '?type=transfer&instant=1',
            ],
        ];
    @endphp
    <section class="hero-services" data-aos="fade-up">
        <div class="section-header">
            <div class="badge">
                <i class="fa-solid fa-briefcase" style="font-weight: bold;">
                    {{ __('web.our_services') }}
                </i>

            </div>
            <h2 class="section-title">خدمات شركه بلاتينيوم</h2>
            <p> خدماتنا مرخصه من وزاره الموارد البشريه والتنميه الاجتماعية لضمان راحتك وراحه اسرتك</p>
        </div>
        {{--        <div class="expertise"> --}}
        {{--            @if (isset($settings) && isset($settings->experience_image)) --}}
        {{--                <img src="{{showFile($settings->experience_image)}}" alt="خبرة اكثر من 20 سنه"/> --}}
        {{--            @else --}}
        {{--                <img src="{{asset('site')}}/assets/images/experience.jpg" alt="خبرة اكثر من 20 سنه"/> --}}
        {{--            @endif --}}
        {{--        </div> --}}
        <div class="services-container">
            <a href="{{ route('site.all-workers.index') }}?type=estqdam">
                <div class="service-card green">
                    <div class="icon green">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <h4>الاستقدام</h4>
                    <p>
                        خدمة استقدام العمالة المنزلية والمهنية من الخارج للعمل داخل
                        المملكة.
                    </p>
                    <ul>
                        <li>سهولة الاستقدام</li>
                        <li>الجودة</li>
                        <li>نظام تتبع دقيق</li>
                    </ul>
                </div>
            </a>
            <a href="{{ route('site.all-workers.index') }}?type=egar">
                <div class="service-card purple">
                    <div class="icon purple">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <h4>تأجير العمالة الشهري</h4>
                    <p>خدمة تأجير العمالة للشهر الواحد مع ضمان مرونة وجودة.</p>
                    <ul>
                        <li>مرونة عالية</li>
                        <li>جودة مضمونة</li>
                        <li>تسليم سريع</li>
                    </ul>
                </div>
            </a>
            <a href="{{ route('site.all-workers.index') }}?type=transfer">
                <div class="service-card blue">
                    <div class="icon blue">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <h4>النقل الفوري للعمالة</h4>
                    <p>خدمة نقل العمالة لمشاريعك بسرعة وكفاءة عالية.</p>
                    <ul>
                        <li>النقل السريع</li>
                        <li>التسليم الفوري</li>
                        <li>الجودة</li>
                    </ul>
                </div>
            </a>
            <a href="{{ route('site.all-workers.index') }}?type=transfer">
                <div class="service-card orange">
                    <div class="icon orange">
                        <i class="fa-solid fa-stopwatch"></i>
                    </div>
                    <h4>التسليم الفوري للعمالة</h4>
                    <p>استلم فوري لعمالتك لتلبية احتياجاتك الفورية بسرعة وجودة.</p>
                    <ul>
                        <li>التسليم السريع</li>
                        <li>جودة العمالة</li>
                        <li>سهولة الإجراءات</li>
                    </ul>
                </div>
            </a>
        </div>
        <!-- التحذير -->
        <div class="warning">
            {{ __('ingaz.warning') }} :
            @if (isset($settings))
                {{ app()->getLocale() == 'ar' ? $settings->note_ar : $settings->note_en }}
            @else
                {{ 'ingaz.warning_text' }}
            @endif
        </div>
    </section>

    <!-- about sec  -->
    <section class="about-section" id="about">
        <div class="container">
            <!-- About Content -->
            <div class="about-content">
                <div class="about-text">
                    <h2>
                        {{ __('ingaz.about-us') }}
                    </h2>
                    <p>
                        {{ __('ingaz.about_description') }}
                    </p>
                </div>

                <div class="about-image">
                    <img src="{{ asset('site/assets/images/about.webp') }}" alt="{{ __('ingaz.about_us') }}" />
                </div>
            </div>
            <!-- About Cards -->
            <div class="about-cards">
                @forelse($features as $feature)
                    <div class="service">
                        <i class="{{ $feature->image }}"></i>
                        <div>
                            <h4>{{ $feature->title }}</h4>
                            <p>{{ $feature->description ?? '' }}</p>
                        </div>
                    </div>
                @empty
                    <div class="service">
                        <i class="fa-solid fa-headset"></i>
                        <div>
                            <h4>{{ __('ingaz.customer_service_title') }}</h4>
                            <p>{{ __('ingaz.customer_service_text') }}</p>
                        </div>
                    </div>

                    <div class="service">
                        <i class="fa-solid fa-id-card"></i>
                        <div>
                            <h4>{{ __('ingaz.hr_license_title') }}</h4>
                            <p>{{ __('ingaz.hr_license_text') }}</p>
                        </div>
                    </div>

                    <div class="service">
                        <i class="fa-solid fa-shield-check"></i>
                        <div>
                            <h4>{{ __('ingaz.musaned_title') }}</h4>
                            <p>{{ __('ingaz.musaned_text') }}</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="countries">
        <div class="container">
            <div class="headTitle text-center">
                <h2>دول الاستقدام</h2>
                <p>اختر الدولة التي ترغب بالاستقدام منها</p>
            </div>
            <div class="row justify-content-center">
                @forelse($nationalities as $nationalty)
                    <div class="col-12 col-md-6 col-lg-4 p-3">
                        <div class="modern-card">
                            <div class="flag-side">
                                <div class="flag-bg"></div>
                                <div class="flag-wrapper">
                                    <img src="{{ showFile($nationalty->logo) }}" alt="{{ $nationalty->title }}"
                                        class="flag-img" />
                                    <span class="pulse-ring"></span>
                                </div>
                            </div>
                            <div class="cost-highlight">
                                <span class="label">{{ $nationalty->title }}</span>
                                <h2 class="price">{{ $nationalty->price }} <small>ريال</small></h2>
                                <span class="label">تكلفة الاستقدام شامل الضريبة</span>
                            </div>
                            <div class="info-side">
                                <div class="info-grid">
                                    <div class="info-card">
                                        <i class="fas fa-clock"></i>
                                        <h5>المدة المتوقعة</h5>
                                        <p>{{ $nationalty->period }}</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-hand-holding-usd"></i>
                                        <h5>الراتب يبدء من</h5>
                                        <p>{{ $nationalty->salary_with_no_experience }} ريال</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-user-check"></i>
                                        <h5>راتب الخبرة</h5>
                                        <p>{{ $nationalty->salary_with_experience }}ريال</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-flag"></i>
                                        <h5> تكلفة التاجير</h5>
                                        <p>{{ $nationalty->rental_cost }}ريال</p>
                                    </div>
                                </div>
                                <a href="{{ route('site.all-workers.index') }}?nationality_id={{ $nationalty->id }}"
                                    class="modern-btn">
                                    تصفح السير الذاتية <i class="fas fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 col-md-6 col-lg-4 p-3">
                        <div class="modern-card">
                            <div class="flag-side">
                                <div class="flag-bg"></div>
                                <div class="flag-wrapper">
                                    <img src="{{ asset('site') }}/assets/images/countries/2.png" alt="علم كينيا"
                                        class="flag-img" />
                                    <span class="pulse-ring"></span>
                                </div>
                            </div>

                            <div class="cost-highlight">
                                <span class="label">تكلفة الاستقدام شامل الضريبة</span>
                                <h2 class="price">9500 <small>ريال</small></h2>
                            </div>

                            <div class="info-side">
                                <div class="info-grid">
                                    <div class="info-card">
                                        <i class="fas fa-clock"></i>
                                        <h5>المدة المتوقعة</h5>
                                        <p>60 يوم</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-hand-holding-usd"></i>
                                        <h5>الراتب</h5>
                                        <p>1000 ريال</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-user-check"></i>
                                        <h5>الخبرة</h5>
                                        <p>سبق لها العمل</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-flag"></i>
                                        <h5>دولة الاستقدام</h5>
                                        <p>كينيا</p>
                                    </div>
                                </div>

                                <a href="all-workers.html" class="modern-btn">
                                    تصفح السير الذاتية <i class="fas fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Bangladesh -->
                    <div class="col-12 col-md-6 col-lg-4 p-3">
                        <div class="modern-card">
                            <div class="flag-side">
                                <div class="flag-bg"></div>
                                <div class="flag-wrapper">
                                    <img src="{{ asset('site') }}/assets/images/countries/3.jpeg" alt="علم بنغلادش"
                                        class="flag-img" />
                                    <span class="pulse-ring"></span>
                                </div>
                            </div>

                            <div class="cost-highlight">
                                <span class="label">تكلفة الاستقدام شامل الضريبة</span>
                                <h2 class="price">8000 <small>ريال</small></h2>
                            </div>

                            <div class="info-side">
                                <div class="info-grid">
                                    <div class="info-card">
                                        <i class="fas fa-clock"></i>
                                        <h5>المدة المتوقعة</h5>
                                        <p>60 يوم</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-hand-holding-usd"></i>
                                        <h5>الراتب</h5>
                                        <p>850 ريال</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-user-check"></i>
                                        <h5>الخبرة</h5>
                                        <p>لم يسبق لها العمل</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-flag"></i>
                                        <h5>دولة الاستقدام</h5>
                                        <p>بنغلادش</p>
                                    </div>
                                </div>

                                <a href="all-workers.html" class="modern-btn">
                                    تصفح السير الذاتية <i class="fas fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Philippines -->
                    <div class="col-12 col-md-6 col-lg-4 p-3">
                        <div class="modern-card">
                            <div class="flag-side">
                                <div class="flag-bg"></div>
                                <div class="flag-wrapper">
                                    <img src="{{ asset('site') }}/assets/images/countries/4.jpeg" alt="علم الفلبين"
                                        class="flag-img" />
                                    <span class="pulse-ring"></span>
                                </div>
                            </div>

                            <div class="cost-highlight">
                                <span class="label">تكلفة الاستقدام شامل الضريبة</span>
                                <h2 class="price">12000 <small>ريال</small></h2>
                            </div>

                            <div class="info-side">
                                <div class="info-grid">
                                    <div class="info-card">
                                        <i class="fas fa-clock"></i>
                                        <h5>المدة المتوقعة</h5>
                                        <p>60 يوم</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-hand-holding-usd"></i>
                                        <h5>الراتب</h5>
                                        <p>1500 ريال</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-user-check"></i>
                                        <h5>الخبرة</h5>
                                        <p>سبق لها العمل</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-flag"></i>
                                        <h5>دولة الاستقدام</h5>
                                        <p>الفلبين</p>
                                    </div>
                                </div>

                                <a href="all-workers.html" class="modern-btn">
                                    تصفح السير الذاتية <i class="fas fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- India -->
                    <div class="col-12 col-md-6 col-lg-4 p-3">
                        <div class="modern-card">
                            <div class="flag-side">
                                <div class="flag-bg"></div>
                                <div class="flag-wrapper">
                                    <img src="{{ asset('site') }}/assets/images/countries/5.png" alt="علم الهند"
                                        class="flag-img" />
                                    <span class="pulse-ring"></span>
                                </div>
                            </div>

                            <div class="cost-highlight">
                                <span class="label">تكلفة الاستقدام شامل الضريبة</span>
                                <h2 class="price">9000 <small>ريال</small></h2>
                            </div>

                            <div class="info-side">
                                <div class="info-grid">
                                    <div class="info-card">
                                        <i class="fas fa-clock"></i>
                                        <h5>المدة المتوقعة</h5>
                                        <p>60 يوم</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-hand-holding-usd"></i>
                                        <h5>الراتب</h5>
                                        <p>1000 ريال</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-user-check"></i>
                                        <h5>الخبرة</h5>
                                        <p>لم يسبق لها العمل</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-flag"></i>
                                        <h5>دولة الاستقدام</h5>
                                        <p>الهند</p>
                                    </div>
                                </div>

                                <a href="all-workers.html" class="modern-btn">
                                    تصفح السير الذاتية <i class="fas fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Mauritania -->
                    <div class="col-12 col-md-6 col-lg-4 p-3">
                        <div class="modern-card">
                            <div class="flag-side">
                                <div class="flag-bg"></div>
                                <div class="flag-wrapper">
                                    <img src="{{ asset('site') }}/assets/images/countries/6.png" alt="علم موريتانيا"
                                        class="flag-img" />
                                    <span class="pulse-ring"></span>
                                </div>
                            </div>

                            <div class="cost-highlight">
                                <span class="label">تكلفة الاستقدام شامل الضريبة</span>
                                <h2 class="price">8500 <small>ريال</small></h2>
                            </div>

                            <div class="info-side">
                                <div class="info-grid">
                                    <div class="info-card">
                                        <i class="fas fa-clock"></i>
                                        <h5>المدة المتوقعة</h5>
                                        <p>60 يوم</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-hand-holding-usd"></i>
                                        <h5>الراتب</h5>
                                        <p>950 ريال</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-user-check"></i>
                                        <h5>الخبرة</h5>
                                        <p>سبق لها العمل</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-flag"></i>
                                        <h5>دولة الاستقدام</h5>
                                        <p>موريتانيا</p>
                                    </div>
                                </div>

                                <a href="all-workers.html" class="modern-btn">
                                    تصفح السير الذاتية <i class="fas fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Djibouti -->
                    <div class="col-12 col-md-6 col-lg-4 p-3">
                        <div class="modern-card">
                            <div class="flag-side">
                                <div class="flag-bg"></div>
                                <div class="flag-wrapper">
                                    <img src="{{ asset('site') }}/assets/images/countries/7.png" alt="علم جيبوتي"
                                        class="flag-img" />
                                    <span class="pulse-ring"></span>
                                </div>
                            </div>

                            <div class="cost-highlight">
                                <span class="label">تكلفة الاستقدام شامل الضريبة</span>
                                <h2 class="price">8800 <small>ريال</small></h2>
                            </div>

                            <div class="info-side">
                                <div class="info-grid">
                                    <div class="info-card">
                                        <i class="fas fa-clock"></i>
                                        <h5>المدة المتوقعة</h5>
                                        <p>60 يوم</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-hand-holding-usd"></i>
                                        <h5>الراتب</h5>
                                        <p>950 ريال</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-user-check"></i>
                                        <h5>الخبرة</h5>
                                        <p>سبق لها العمل</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-flag"></i>
                                        <h5>دولة الاستقدام</h5>
                                        <p>جيبوتي</p>
                                    </div>
                                </div>

                                <a href="all-workers.html" class="modern-btn">
                                    تصفح السير الذاتية <i class="fas fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
    <!-- ================ services ================= -->
    <section class="services-sec" style="height: fit-content" id="services">
        <div class="container">
            <div class="headTitle" data-aos="fade-up">
                <h2>{{ __('ingaz.our_services') }}</h2>
                <p>{{ __('ingaz.our_services_desc') }}</p>
            </div>
            <!-- السلايدر -->
            <div class="services-slider swiper" data-aos="fade-up">
                <div class="swiper-wrapper">
                    @forelse($services as $service)
                        <div class="swiper-slide">
                            <a href="#" class="service d-flex flex-column justify-content-center"
                                style="background: url('{{ showFile($service->image) }}') no-repeat center/cover;">
                                <i class="fa-solid fa-passport"></i>
                                <h3 class="serviceTitle">{{ $service->title }}</h3>
                                <h6 class="serviceP">
                                    {{ Str::limit($service->description, 100) }}
                                </h6>
                            </a>
                        </div>
                    @empty
                        <div class="swiper-slide">
                            <a href="#" class="service d-flex flex-column justify-content-center"
                                style="background: url('{{ asset('site/assets/images/passport-bg.webp') }}') no-repeat center/cover;">
                                <i class="fa-solid fa-passport"></i>
                                <h3 class="serviceTitle">إصدار التأشيرة</h3>
                                <h6 class="serviceP">
                                    طلب اصدار تاشيرة العمالة المنزلية الخاصة بك في برنامج مساند
                                </h6>
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="#" class="service d-flex flex-column justify-content-center"
                                style="background: url('{{ asset('site/assets/images/files.webp') }}') no-repeat center/cover;">
                                <i class="fa-solid fa-users-gear"></i>
                                <h3 class="serviceTitle">اختيار العمالة</h3>
                                <h6 class="serviceP">
                                    اختيار السيره الذاتيه للعماله المنزليه عبر البحث في برنامج مساند
                                </h6>
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="#" class="service d-flex flex-column justify-content-center"
                                style="background: url('{{ asset('site/assets/images/bg-plane.webp') }}') no-repeat center/cover;">
                                <i class="fa-solid fa-plane-arrival"></i>
                                <h3 class="serviceTitle">وصول العمالة</h3>
                                <h6 class="serviceP">
                                    وصول العمالة المنزلية من المطار المحلي الى المكتب
                                </h6>
                            </a>
                        </div>
                        <div class="swiper-slide">
                            <a href="#" class="service d-flex flex-column justify-content-center"
                                style="background: url('{{ asset('site/assets/images/hands.webp') }}') no-repeat center/cover;">
                                <i class="fa-solid fa-file-contract"></i>
                                <h3 class="serviceTitle">تجديد عقد عاملة</h3>
                                <h6 class="serviceP">
                                    خدمة تجديد عقد العاملة المنزلية بشكل إلكتروني عبر منصة مساند
                                </h6>
                            </a>
                        </div>
                    @endforelse
                </div>
                <div class="swiper-button-next custom-swiper-btn"></div>
                <div class="swiper-button-prev custom-swiper-btn"></div>
            </div>
            <div class="wide-card mt-4" data-aos="fade-up">
                <a href="#" class="service d-flex flex-column justify-content-center"
                    style="background: url('{{ asset('site/assets/images/f3.webp') }}') no-repeat center/cover;">
                    <i class="fa-solid fa-users-gear"></i>
                    <h3 class="serviceTitle">طلب العمالة المهنية</h3>
                    <h6 class="serviceP">
                        نحن قادرون على جلب أفضل أنواع العمالة المهنية من مختلف التخصصات
                        لتلبية احتياجاتك بأعلى جودة وكفاءة
                    </h6>
                </a>
            </div>
        </div>
    </section>
    <!-- statistics sec  -->
    <section class="statistics" id="statistics">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="1000">
                <h1 class="title">{{ __('ingaz.Statistics') }}</h1>
                <h6 class="hint">{{ __('ingaz.Statistics_text') }}</h6>
            </div>

            {{-- الإحصائيات الرئيسية --}}
            <div class="stats-container">
                @forelse($statics as $static)
                    <div class="stat-card" data-aos="zoom-in" data-aos-delay="100">
                        <div class="icon-wrapper">
                            <i class="fa-solid fa-{{ $static->image }}"></i>
                        </div>
                        <span class="counter" data-target="{{ $static->counter_num }}">0</span>
                        <h6>{{ $static->title }}</h6>
                    </div>
                @empty
                    <div class="stat-card" data-aos="zoom-in" data-aos-delay="100">
                        <div class="icon-wrapper">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <span class="counter" data-target="794">0</span>
                        <h6>عملاؤنا</h6>
                    </div>

                    <div class="stat-card" data-aos="zoom-in" data-aos-delay="200">
                        <div class="icon-wrapper">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <span class="counter" data-target="812">0</span>
                        <h6>زوارنا</h6>
                    </div>

                    <div class="stat-card" data-aos="zoom-in" data-aos-delay="300">
                        <div class="icon-wrapper">
                            <i class="fa-solid fa-headset"></i>
                        </div>
                        <span class="counter" data-target="793">0</span>
                        <h6>خدمة العملاء</h6>
                    </div>

                    <div class="stat-card" data-aos="zoom-in" data-aos-delay="400">
                        <div class="icon-wrapper">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <span class="counter" data-target="710">0</span>
                        <h6>عامل وعاملة</h6>
                    </div>
                @endforelse
            </div>

            {{-- الإحصائيات الإضافية --}}
            <div class="extra-stats" data-aos="fade-up" data-aos-delay="500">
                <div class="extra-item">
                    <div class="title-row">
                        <i class="fa-solid fa-star"></i>
                        <h5>نسبة رضا العملاء:</h5>
                    </div>
                    <p><span>94%</span> من عملاؤنا راضيين عن الخدمة</p>
                </div>

                <div class="extra-item">
                    <div class="title-row">
                        <i class="fa-solid fa-stopwatch"></i>
                        <h5>إنجاز الطلبات في الوقت المحدد:</h5>
                    </div>
                    <p><span>98%</span> من الطلبات نُفذت في الفترة المحددة</p>
                </div>

                <div class="extra-item">
                    <div class="title-row">
                        <i class="fa-solid fa-user-shield"></i>
                        <h5>دعم ما بعد الوصول:</h5>
                    </div>
                    <p><span>100%</span> نسبة المتابعة والدعم بعد الوصول</p>
                </div>
            </div>
        </div>
    </section>

    <!-- steps sec  -->
    <section class="steps" id="steps" style="position: relative">
        <div class="container">
            <!-- Section Title -->
            <div class="SectionTitle">
                <h2 class="title" data-aos="fade-up">{{ __('ingaz.Recruitment steps') }}</h2>
                <h6 class="hint" data-aos="fade-up" data-aos-delay="100">
                    {{ __('ingaz.Recruitment steps hint') }}
                </h6>
            </div>
            <!-- steps -->
            <div class="allSteps">
                @forelse($steps as $key=>$step)
                    <div class="step" data-aos="fade-up" data-aos-delay="100">
                        <div class="icon">
                            <i class="fa-thin fa-{{ $step->icon }}"></i>
                        </div>
                        <p>
                            {{ $step->title }}
                        </p>
                        {{--                        @if ($key == 0) --}}
                        {{--                           --}}
                        {{--                        @endif --}}
                    </div>
                    <div class="images" style="position: absolute ;bottom: 0 ; right: 0">
                        <img src="{{ asset('site') }}/assets/images/mc.webp" data-aos="zoom-in" data-aos-delay="200">
                        <img src="{{ asset('site') }}/assets/images/visa.webp" data-aos="zoom-in" data-aos-delay="300">
                        <img src="{{ asset('site') }}/assets/images/mada.webp" data-aos="zoom-in" data-aos-delay="400">
                        <img src="{{ asset('site') }}/assets/images/tabby.png" data-aos="zoom-in" data-aos-delay="400">
                        <img src="{{ asset('site') }}/assets/images/tamara.webp" data-aos="zoom-in"
                            data-aos-delay="400">
                    </div>
                @empty
                    <div class="step" data-aos="fade-up" data-aos-delay="100">
                        <div class="icon">
                            <i class="fa-thin fa-wallet"></i>
                        </div>
                        <p>
                            سداد رسوم تاشيرة العمالة المنزلية الخاصة بك عبر الخدمات الحكومية
                            في البنك او عن طريق القنوات التالية
                        </p>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="200">
                        <div class="icon">
                            <i class="fa-thin fa-passport"></i>
                        </div>
                        <p>طلب اصدار تاشيرة العمالة المنزلية الخاصة بك في برنامج مساند</p>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="300">
                        <div class="icon">
                            <i class="fa-thin fa-id-card"></i>
                        </div>
                        <p>
                            اختيار السيرة الذاتية للعمالة المنزلية عبر البحث في برنامج مساند
                            عن طريق المهنة/ الجنسية
                        </p>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="400">
                        <div class="icon">
                            <i class="fa-thin fa-wallet"></i>
                        </div>
                        <p>
                            دفع مبلغ اختياري وتوثيق طلب استقدام العمالة المنزلية المحددة بعد
                            اختيار السيرة الذاتية
                        </p>

                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="500">
                        <div class="icon">
                            <i class="fa-thin fa-plane"></i>
                        </div>
                        <p>وصول العمالة المنزلية من المطار المحلي الى المكتب</p>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="600">
                        <div class="icon">
                            <i class="fa-thin fa-handshake"></i>
                        </div>
                        <p>يتم استلام العمالة الخاصة بك في المكتب</p>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="700">
                        <div class="icon">
                            <i class="fa-thin fa-shield-check"></i>
                        </div>
                        <p>نضمن لك العمالة لمدة 3 شهور</p>
                    </div>
                @endforelse
            </div>
        </div>
        <div class="images">
            {{--             style="position: absolute ;bottom: 0 ; right: 0 ;width: 100px ;height: 100px; display: flex; object-fit:cover;"> --}}
            <div>
                <img width="40px" height="40px" src="{{ asset('site') }}/assets/images/mc.webp" data-aos="zoom-in"
                    data-aos-delay="200">
                <img width="40px" height="40px" src="{{ asset('site') }}/assets/images/visa.webp" data-aos="zoom-in"
                    data-aos-delay="300">
                <img width="40px" height="40px" src="{{ asset('site') }}/assets/images/mada.webp" data-aos="zoom-in"
                    data-aos-delay="400">
            </div>
            <div>
                <img width="40px" height="40px" src="{{ asset('site') }}/assets/images/tabby.png" data-aos="zoom-in"
                    data-aos-delay="400">
                <img width="40px" height="40px" src="{{ asset('site') }}/assets/images/tamara.webp"
                    data-aos="zoom-in" data-aos-delay="400">
            </div>
        </div>
    </section>

    <!-- ================ requirements ================= -->
    <section class="requirements">
        <div class="container">
            <div class="headTitle">
                <h2 data-aos="fade-up">{{ __('ingaz.Recruitment_requirements') }} </h2>
                <p data-aos="fade-up">
                    {{ __('ingaz.requirements_you_need') }}
                </p>
            </div>
            <div class="row">
                <div class="col-md-6 p-2">
                    <div class="requirement">
                        <div class="head" data-aos="fade-up">
                            <h4>{{ __('ingaz.Documents_domestic') }}</h4>
                        </div>
                        @forelse($Domestic as $domestic)
                            <ul>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="{{ $domestic->image }}"></i>
                                    </div>
                                    <p> {{ $domestic->title }}</p>
                                </li>
                            </ul>
                        @empty
                            <ul>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-address-card"></i>
                                    </div>
                                    <p> صورة الهوية الوطنية او الاقامة للمقيمين </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-headset"></i>
                                    </div>
                                    <p> خدمة عملاء متميزة </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-building-columns"></i>
                                    </div>
                                    <p> تعريف بالراتب يبدء من من جهة العمل أو كشف حساب البنك </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-passport"></i>
                                    </div>
                                    <p> التأشيرة الأصل </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-calendar-lines-pen"></i>
                                    </div>
                                    <p> توقيع عقد إستقدام </p>
                                </li>
                            </ul>
                        @endforelse
                    </div>
                </div>

                <div class="col-md-6 p-2">
                    <div class="requirement">
                        <div class="head" data-aos="fade-up">
                            <h4>{{ __('ingaz.Documents_visa') }} </h4>
                            <p>
                                {{ __('ingaz.Please_visit_the_Domestic_Workers_Program_Support_website') }}
                                <a href="{{ route('site.musaned.index') }}"> من هنا </a>
                            </p>
                        </div>
                        @forelse($Visa as $visa)
                            <ul>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="{{ $visa->image }}"></i>
                                    </div>
                                    <p> {{ $visa->title }} </p>
                                </li>
                            </ul>
                        @empty
                            <ul>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-address-card"></i>
                                    </div>
                                    <p> صورة الهوية الوطنية او الاقامة للمقيمين </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-headset"></i>
                                    </div>
                                    <p> خدمة عملاء متميزة </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-building-columns"></i>
                                    </div>
                                    <p> تعريف بالراتب يبدء من من جهة العمل أو كشف حساب البنك </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-passport"></i>
                                    </div>
                                    <p> التأشيرة الأصل </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-calendar-lines-pen"></i>
                                    </div>
                                    <p> توقيع عقد إستقدام </p>
                                </li>
                            </ul>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ================ musaned-platform ================= -->
    <section class="video-section" id="promo-video">
        <div class="container">
            <h2 class="video-title" data-aos="fade-up" data-aos-duration="800">
                {{ __('ingaz.video_title') }}
            </h2>

            <div class="video-wrapper" data-aos="zoom-in" data-aos-duration="1000">
                <iframe src="{{ __('ingaz.video_link') }}" title="{{ __('ingaz.video_description') }}" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>
    </section>





    <!-- ================ contact Form ================= -->
    <section class="contactForm">
        <div class="container">
            <div class="row">
                <div class="col-md-6 p-2 mb-5 mb-md-0 wow fadeInUp">
                    <div class="headTitle text-start">
                        <h2> {{ __('ingaz.contact-us') }}</h2>
                        <p>
                            {{ __('ingaz.request your mid') }}
                        </p>
                    </div>
                    <form action="" id="contact-form" class="row needs-validation" novalidate>
                        @csrf

                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                        <div class="col-md-12 p-2">
                            <label class="form-label">
                                <i class="fas fa-user me-2"></i> {{ __('ingaz.full name') }}*
                            </label>
                            <input type="text" id="name" name="name" class="form-control" required />
                            <div class="invalid-feedback"> {{ __('ingaz.this field is required') }}</div>
                            <div class="valid-feedback">{{ __('ingaz.good') }}</div>
                        </div>
                        <div class="col-md-6 p-2">
                            <label class="form-label"><i class="fas fa-phone-alt me-2"></i> {{ __('ingaz.phone') }}*
                            </label>
                            <input type="tel" id="phone" name="phone" id="phoneNum" class="form-control"
                                required />
                            <div class="invalid-feedback"> {{ __('ingaz.this field is required') }} </div>
                        </div>
                        <div class="col-md-6 p-2">
                            <label class="form-label">
                                <i class="fa-solid fa-comment-lines me-2"></i> {{ __('ingaz.subject') }}
                            </label>
                            <input type="text" id="subject" name="subject" class="form-control" />
                        </div>
                        <div class="col-md-12 p-2">
                            <label class="form-label">
                                <i class="fas fa-feather-alt me-2"></i>{{ __('ingaz.Write your message') }}*
                            </label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            <div class="invalid-feedback"> {{ __('ingaz.this field is required') }} </div>
                        </div>
                        {!! NoCaptcha::renderJs() !!}
                        {!! NoCaptcha::display() !!}

                        @error('g-recaptcha-response')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror

                        <div class="col-md-12 text-center p-2">
                            <button type="submit" class="defaultBtn">
                                {{ __('ingaz.send') }} <i class="fas fa-paper-plane ms-2"></i>
                                <span></span>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 p-2 wow fadeInUp">
                    <section class="googleMap">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d57144.98454618672!2d50.12401500000001!3d26.469859!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e49fbc320200d1b%3A0x1abc87f1ffdb75d5!2z2KjZhNin2KrZitmG2YrZiNmFINin2YTYtNix2YLZitipINmE2YTYpdiz2KrZgtiv2KfZhQ!5e0!3m2!1sar!2ssa!4v1761469557614!5m2!1sar!2ssa"
                            frameborder="0" style="border: 0" allowfullscreen="" aria-hidden="false"
                            tabindex="0"></iframe>
                    </section>
                </div>
            </div>
        </div>
    </section>
    <!-- ================ references ================= -->
    <section class="references" style="height: fit-content" data-aos="fade-up">
        <div class="container">
            <div class="swiper referencesSlider">
                <div class="swiper-wrapper">
                    @forelse($clients as $client)
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{ showFile($client->image) ?? asset('site/assets/images/musand.svg') }}"
                                    alt=""
                                    onerror="this.onerror=null;this.src='{{ asset('site/assets/images/musand.svg') }}';">
                            </div>
                        </div>
                    @empty
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">

                                <img src="{{ asset('site/assets/images/Ministry-of-Foreign-Affairs-01.svg') }}"
                                    alt=""
                                    onerror="this.onerror=null;this.src='{{ asset('site/assets/images/musand.svg') }}';">

                            </div>
                        </div>
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{ asset('site/assets/images/Ministry-of-Interior-01.svg') }}" alt=""
                                    onerror="this.onerror=null;this.src='{{ asset('site/assets/images/musand.svg') }}';">
                            </div>
                        </div>
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{ asset('site/assets/images/Ministry-of-Labor-and-Social-Development.svg') }}"
                                    alt=""
                                    onerror="this.onerror=null;this.src='{{ asset('site/assets/images/musand.svg') }}';">

                            </div>
                        </div>
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{ asset('site/assets/images/musand.svg') }}" alt="">
                            </div>
                        </div>
                    @endforelse
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>
@endsection
@section('js')
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
    <script>
        let map, marker;

        function initMap() {
            const defaultLocation = {
                lat: 30.0444,
                lng: 31.2357
            }; // القاهرة كمثال

            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 10,
                center: defaultLocation,
            });

            marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true
            });

            // عند تحريك العلامة (Marker)
            google.maps.event.addListener(marker, 'dragend', function(event) {
                document.getElementById("latitude").value = event.latLng.lat();
                document.getElementById("longitude").value = event.latLng.lng();
            });
        }

        // تحميل الخريطة عند فتح الصفحة
        window.onload = initMap;
    </script>

    <script>
        $(document).ready(function() {
            $('#contact-form').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: "{{ route('site.contact-us.store') }}",
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: $('#name').val(),
                        phone: $('#phone').val(),
                        subject: $('#subject').val(),
                        message: $('#message').val(),
                        latitude: $('#latitude').val(),
                        longitude: $('#longitude').val(),
                        'g-recaptcha-response': grecaptcha.getResponse()

                    },
                    success: function(response) {

                        $('#contact-form')[0].reset();
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __('ingaz.success') }}',
                            text: '{{ __('ingaz.success') }}',
                            confirmButtonText: '{{ __('ingaz.ok') }}'
                        });
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    },
                    error: function(xhr) {
                        console.error("خطأ في الطلب:", xhr);
                    }
                });


            });
        });
    </script>
@endsection
