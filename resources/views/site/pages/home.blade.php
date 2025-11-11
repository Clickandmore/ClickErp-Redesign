@extends('site.layouts.index')
@section('title')
    {{ __('ingaz.home') }}
@endsection
@section('content')
    <style>
        .hero-services {
            margin-top: -90px;
            padding: 40px 28px 32px;
            border-radius: 40px;
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.16), transparent 45%), #ffffff;
            box-shadow: 0 45px 90px rgba(15, 23, 42, 0.12);
            position: relative;
            z-index: 2;
        }

        .hero-services__content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero-services__header {
            text-align: center;
            margin-bottom: 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .hero-services__label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 18px;
            border-radius: 999px;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: rgba(79, 70, 229, 0.08);
            color: #312e81;
            margin: 0 auto;
        }

        .hero-services__label i {
            color: inherit;
            font-size: 0.9rem;
        }

        .hero-services__header h3 {
            margin: 14px 0 6px;
            font-size: clamp(1.35rem, 4vw, 2.1rem);
            color: #0f172a;
        }

        .hero-services__header p {
            color: #475569;
            max-width: 620px;
            margin: 0 auto;
        }

        .hero-services__cards {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            justify-content: center;
        }

        .hero-service-card {
            position: relative;
            border-radius: 26px;
            padding: 28px 24px 26px;
            background: linear-gradient(145deg, rgba(248, 250, 252, 0.92), rgba(255, 255, 255, 0.95));
            border: 1px solid rgba(226, 232, 240, 0.9);
            text-decoration: none;
            color: inherit;
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            width: 260px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            min-height: 320px;
        }

        .hero-service-card::after {
            content: '';
            position: absolute;
            inset: 16px;
            border-radius: 20px;
            border: 1px dashed rgba(148, 163, 184, 0.35);
            opacity: 0.7;
            pointer-events: none;
        }

        .hero-service-card:hover {
            transform: translateY(-8px);
            border-color: rgba(99, 102, 241, 0.35);
            box-shadow: 0 35px 70px rgba(79, 70, 229, 0.14);
        }

        .hero-service-card__icon {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.2);
        }

        .hero-service-card__badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            border-radius: 999px;
            font-size: 0.72rem;
            background: rgba(15, 23, 42, 0.04);
            color: #0f172a;
        }

        .hero-service-card__body {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .hero-service-card__body .text {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .hero-service-card__body h4 {
            margin: 0;
            font-size: 1.1rem;
            color: #0f172a;
        }

        .hero-service-card__body p {
            margin: 0;
            color: #5f6b7b;
            line-height: 1.7;
            min-height: 72px;
        }

        .hero-service-card__cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            font-weight: 600;
            margin-top: auto;
            font-size: 0.92rem;
            padding: 10px 20px;
            border-radius: 999px;
            background: linear-gradient(135deg, #005CA9, #005CA9);
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.25);
        }

        .hero-service-card__cta i {
            color: inherit;
        }

        .hero-service-card--emerald .hero-service-card__icon {
            background: linear-gradient(135deg, #10b981, #0f766e);
        }

        .hero-service-card--blue .hero-service-card__icon {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
        }

        .hero-service-card--orange .hero-service-card__icon {
            background: linear-gradient(135deg, #f97316, #ea580c);
        }

        .hero-service-card--violet .hero-service-card__icon {
            background: linear-gradient(135deg, #a855f7, #7c3aed);
        }

        @media (max-width: 767.98px) {
            .hero-services {
                padding: 32px 20px 24px;
                border-radius: 30px;
                margin-top: -50px;
            }

            .hero-services__cards {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }
        }
    </style>
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
                                بلاتينيوم الشرقية للاستقدام
                            </h4>
                            <h1 class="sliderTitle">
                                أكثر من 20 سنة من تقديم خدمات الاستقدام باحترافية عالية
                            </h1>
                        </div>
                    </div>
                    <!-- swiper-slide -->
                    <div class="swiper-slide" style="background-image: url({{ asset('site') }}/assets/images/s2.webp)">
                        <div class="info">
                            <h4 class="hint"> سهولة الاستخدام</h4>
                            <h1 class="sliderTitle">
                                ابدأ حجزك واتمم دفعك من خلال الموقع الالكتروني او التواصل معنا
                                بوقت وجيز وبخطوات مختصرة
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
        <div class="hero-services__content">
            <div class="hero-services__header">
                <span class="hero-services__label">
                    <i class="fa-solid fa-grid-2"></i>{{ __('ingaz.our_services') }}
                </span>
                <h3>خدمات شركة بلاتنيوم </h3>
                <p>{{ __('ingaz.recruitment_request_text') }}</p>
            </div>
            <div class="hero-services__cards">
                @foreach ($heroServices as $service)
                    <a href="{{ $service['url'] }}" class="hero-service-card hero-service-card--{{ $service['theme'] }}">
                        <div class="hero-service-card__icon">
                            <i class="{{ $service['icon'] }}"></i>
                        </div>
                        <div class="hero-service-card__body">
                            <span class="hero-service-card__badge">
                                <i class="fa-solid fa-circle fa-2xs"></i>{{ $service['badge'] }}
                            </span>
                            <div class="text">
                                <h4>{{ $service['title'] }}</h4>
                                <p>{{ $service['description'] }}</p>
                            </div>
                        </div>
                        <span class="hero-service-card__cta">
                            {{ __('ingaz.request now') }}
                            <i class="fa-solid fa-arrow-left-long"></i>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
        <!-- warn -->
        <div class="warning">
            {{ __('ingaz.warning') }} :
            @if (isset($settings))
                {{ app()->getLocale() == 'ar' ? $settings->note_ar : $settings->note_en }}
            @else
                {{ __('ingaz.warning_text') }}
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
                <div class="col-12 col-md-6 col-lg-4 p-3">
                    <div class="modern-card">
                        <div class="flag-side">
                            <div class="flag-bg"></div>
                            <div class="flag-wrapper">
                                <img src="{{ asset('site/assets/images/countries/1.png') }}" alt="علم أوغندا"
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
                                    <p>900 ريال</p>
                                </div>
                                <div class="info-card">
                                    <i class="fas fa-user-check"></i>
                                    <h5>الخبرة</h5>
                                    <p>لم يسبق لها العمل</p>
                                </div>
                                <div class="info-card">
                                    <i class="fas fa-flag"></i>
                                    <h5>دولة الاستقدام</h5>
                                    <p>أوغندا</p>
                                </div>
                            </div>
                            <a href="all-workers.html" class="modern-btn">
                                تصفح السير الذاتية <i class="fas fa-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4 p-3">
                    <div class="modern-card">
                        <div class="flag-side">
                            <div class="flag-bg"></div>
                            <div class="flag-wrapper">
                                <img src="{{ asset('site/assets/images/countries/2.png') }}" alt="علم كينيا"
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
                                <img src="{{ asset('site/assets/images/countries/3.jpeg') }}" alt="علم بنغلادش"
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
                                <img src="{{ asset('site/assets/images/countries/4.jpeg') }}" alt="علم الفلبين"
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
                                <img src="{{ asset('site/assets/images/countries/5.png') }}" alt="علم الهند"
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
                                <img src="{{ asset('site/assets/images/countries/6.png') }}" alt="علم موريتانيا"
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ================ services ================= -->
    <section class="services-sec" id="services">
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

                <!--  nav btns -->
                <div class="swiper-button-next custom-swiper-btn"></div>
                <div class="swiper-button-prev custom-swiper-btn"></div>
            </div>

            <!--  last card  -->
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
    <section class="steps" id="steps">
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
                        @if ($key == 0)
                            <div class="images">
                                <img src="{{ asset('site') }}/assets/images/mc.webp" data-aos="zoom-in"
                                    data-aos-delay="200">
                                <img src="{{ asset('site') }}/assets/images/visa.webp" data-aos="zoom-in"
                                    data-aos-delay="300">
                                <img src="{{ asset('site') }}/assets/images/mada.webp" data-aos="zoom-in"
                                    data-aos-delay="400">
                                <img src="{{ asset('site') }}/assets/images/tabby.png" data-aos="zoom-in"
                                    data-aos-delay="400">
                                <img src="{{ asset('site') }}/assets/images/tamara.webp" data-aos="zoom-in"
                                    data-aos-delay="400">
                            </div>
                        @endif
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
                        <div class="images">
                            <img src="{{ asset('site') }}/assets/images/mc.webp" data-aos="zoom-in"
                                data-aos-delay="200">
                            <img src="{{ asset('site') }}/assets/images/visa.webp" data-aos="zoom-in"
                                data-aos-delay="300">
                            <img src="{{ asset('site') }}/assets/images/mada.webp" data-aos="zoom-in"
                                data-aos-delay="400">
                            <img src="{{ asset('site') }}/assets/images/tabby.png" data-aos="zoom-in"
                                data-aos-delay="400">
                            <img src="{{ asset('site') }}/assets/images/tamara.webp" data-aos="zoom-in"
                                data-aos-delay="400">
                        </div>
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
                                    <p> تعريف بالراتب من جهة العمل أو كشف حساب البنك </p>
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
                                    <p> تعريف بالراتب من جهة العمل أو كشف حساب البنك </p>
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
    <section class="references" data-aos="fade-up">
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
                    },
                    error: function(xhr) {
                        console.error("خطأ في الطلب:", xhr);
                    }
                });


            });
        });
    </script>
@endsection
