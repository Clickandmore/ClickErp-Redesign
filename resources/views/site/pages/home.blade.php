@extends('site.layouts.index')
@section('title')
    {{ __('ingaz.home') }}
@endsection
<style>
    .hero-services {
        position: relative;
        z-index: 2;
        margin: -90px auto 0;
        width: min(100% - 24px, 1320px);
        padding: 36px 28px 26px;
        border-radius: 32px;
        --platinum-navy: #0f1b40;
        --platinum-blue: #1763a6;
        --platinum-cyan: #23b7d9;
        --platinum-slate: #7c83a6;
        --platinum-light: #f2f2f2;
        --platinum-ink: #0b1430;
        background:
            radial-gradient(120% 120% at 110% -10%, rgba(35, 183, 217, 0.2) 0%, rgba(255, 255, 255, 0) 60%),
            radial-gradient(120% 120% at -10% 110%, rgba(23, 99, 166, 0.18) 0%, rgba(255, 255, 255, 0) 60%),
            linear-gradient(180deg, #f8f9fc 0%, #ffffff 100%);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        gap: 24px;

        &::before {
            content: "";
            position: absolute;
            inset: 16px;
            border-radius: 26px;
            border: 1px dashed rgba(23, 99, 166, 0.25);
            pointer-events: none;
        }

        &::after {
            content: "";
            position: absolute;
            width: 280px;
            height: 280px;
            right: -140px;
            top: -140px;
            background: radial-gradient(circle, rgba(23, 99, 166, 0.25), rgba(255, 255, 255, 0) 70%);
            filter: blur(2px);
            animation: heroGlow 10s ease-in-out infinite;
            pointer-events: none;
        }

        .section-header {
            width: 100%;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 10px;
            margin: 0 auto;

            .kicker {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 6px 16px;
                border-radius: 999px;
                background: rgba(23, 99, 166, 0.12);
                color: var(--platinum-navy);
                font-size: 14px;
                font-weight: 600;
                letter-spacing: 0.2px;

                i {
                    font-size: 14px;
                }
            }

            .title {
                font-size: clamp(24px, 2.3vw + 18px, 40px);
                font-weight: 800;
                color: var(--platinum-navy);
                margin: 0;
                position: relative;
                display: inline-block;

                &::after {
                    content: "";
                    display: block;
                    width: 64px;
                    height: 4px;
                    background: var(--platinum-blue);
                    border-radius: 999px;
                    margin: 12px auto 0;
                    box-shadow: 0 8px 18px rgba(23, 99, 166, 0.35);
                }
            }

            .hint {
                color: var(--platinum-slate);
                margin: 0;
                font-size: 16px;
                line-height: 1.8;
                max-width: 720px;
            }
        }

        .services-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 22px;
            padding: 6px;
        }

        .service-card {
            --accent: var(--platinum-blue);
            --accent-soft: rgba(23, 99, 166, 0.18);
            position: relative;
            background-image:
                linear-gradient(180deg, rgba(255, 255, 255, 0.82) 0%, rgba(255, 255, 255, 0.86) 100%),
                radial-gradient(circle at 2px 2px, rgba(23, 99, 166, 0.05) 1.5px, transparent 0),
                var(--card-bg, none),
                var(--card-image, none);
            background-size: cover, 20px 20px, cover, cover;
            background-position: center, top left, center, center;
            border-radius: 22px;
            padding: 22px 20px 18px;
            text-align: right;
            transition: transform 0.4s ease, box-shadow 0.4s ease, border-color 0.4s ease;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-top: 4px solid var(--accent);
            display: grid;
            gap: 12px;
            align-content: start;
            height: 100%;
            overflow: hidden;
            isolation: isolate;
            animation: serviceFloat 7s ease-in-out infinite;
            animation-delay: var(--float-delay, 0s);

            &::before {
                content: "";
                position: absolute;
                inset: -60% -10% auto;
                height: 180px;
                background: radial-gradient(closest-side, var(--accent-soft) 0%, rgba(255, 255, 255, 0) 70%);
                opacity: 0.8;
                z-index: -1;
                animation: cardSheen 12s ease-in-out infinite;
            }

            &::after {
                content: "";
                position: absolute;
                inset: auto -40px -40px auto;
                width: 160px;
                height: 160px;
                background: radial-gradient(circle, var(--accent-soft), rgba(255, 255, 255, 0) 70%);
                opacity: 0.5;
                z-index: -1;
            }

            &:hover {
                transform: translateY(-10px);
                box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
                border-color: rgba(23, 99, 166, 0.25);
                animation-play-state: paused;
            }

            &:hover::before {
                animation-play-state: paused;
            }

            &.blue {
                --accent: var(--platinum-navy);
                --accent-soft: rgba(15, 27, 64, 0.2);
                --card-bg: radial-gradient(140px 140px at 15% 20%, rgba(15, 27, 64, 0.28), transparent 70%),
                    radial-gradient(200px 200px at 85% 10%, rgba(35, 183, 217, 0.18), transparent 70%);
            }

            &.green {
                --accent: var(--platinum-blue);
                --accent-soft: rgba(23, 99, 166, 0.2);
                --card-bg: radial-gradient(140px 140px at 20% 20%, rgba(23, 99, 166, 0.22), transparent 70%),
                    radial-gradient(200px 200px at 85% 10%, rgba(35, 183, 217, 0.18), transparent 70%);
            }

            &.purple {
                --accent: var(--platinum-cyan);
                --accent-soft: rgba(35, 183, 217, 0.22);
                --card-bg: radial-gradient(140px 140px at 20% 20%, rgba(35, 183, 217, 0.24), transparent 70%),
                    radial-gradient(200px 200px at 85% 15%, rgba(23, 99, 166, 0.16), transparent 70%);
            }

            &.orange {
                --accent: var(--platinum-slate);
                --accent-soft: rgba(124, 131, 166, 0.22);
                --card-bg: radial-gradient(140px 140px at 25% 25%, rgba(124, 131, 166, 0.25), transparent 70%),
                    radial-gradient(200px 200px at 90% 10%, rgba(23, 99, 166, 0.16), transparent 70%);
            }

            &.teal {
                --accent: var(--platinum-blue);
                --accent-soft: rgba(23, 99, 166, 0.18);
                --card-bg: radial-gradient(140px 140px at 15% 25%, rgba(23, 99, 166, 0.22), transparent 70%),
                    radial-gradient(200px 200px at 85% 20%, rgba(35, 183, 217, 0.2), transparent 70%);
            }

            &.red {
                --accent: var(--platinum-navy);
                --accent-soft: rgba(15, 27, 64, 0.2);
                --card-bg: radial-gradient(140px 140px at 15% 20%, rgba(15, 27, 64, 0.22), transparent 70%),
                    radial-gradient(200px 200px at 90% 10%, rgba(124, 131, 166, 0.2), transparent 70%);
            }

            &.gold {
                --accent: var(--platinum-cyan);
                --accent-soft: rgba(35, 183, 217, 0.22);
                --card-bg: radial-gradient(140px 140px at 20% 25%, rgba(35, 183, 217, 0.25), transparent 70%),
                    radial-gradient(200px 200px at 85% 10%, rgba(23, 99, 166, 0.2), transparent 70%);
            }

            .icon {
                font-size: 26px;
                height: 64px;
                width: 64px;
                border-radius: 18px;
                display: grid;
                place-items: center;
                margin-bottom: 4px;
                background: linear-gradient(145deg, rgba(255, 255, 255, 0.85), var(--accent-soft));
                color: var(--accent);
                box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06),
                    0 12px 20px rgba(15, 23, 42, 0.08);
                transition: transform 0.4s ease, box-shadow 0.4s ease;
            }

            &:hover .icon {
                transform: translateY(-4px) scale(1.04);
                box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.1),
                    0 16px 30px rgba(15, 23, 42, 0.14);
            }

            h4 {
                font-size: 19px;
                margin: 0;
                font-weight: 700;
                color: var(--platinum-navy);
            }

            p {
                font-size: 14px;
                color: var(--platinum-slate);
                margin: 0;
                line-height: 1.7;
                min-height: 42px;
            }

            ul {
                padding: 0;
                list-style: none;
                text-align: right;
                margin: 0;
                display: grid;
                gap: 8px;

                li {
                    font-size: 13px;
                    color: var(--platinum-ink);
                    display: flex;
                    align-items: center;
                    justify-content: flex-start;
                    gap: 8px;
                    background: rgba(23, 99, 166, 0.08);
                    padding: 6px 10px;
                    border-radius: 999px;

                    &::before {
                        content: "";
                        width: 10px;
                        height: 10px;
                        border-radius: 50%;
                        background-color: var(--accent);
                        box-shadow: 0 0 0 3px var(--accent-soft);
                        flex-shrink: 0;
                    }
                }
            }

            .service-actions {
                margin-top: auto;
                display: flex;
                justify-content: flex-start;
            }

            .service-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 18px;
                border-radius: 999px;
                background: var(--accent);
                color: #fff;
                font-size: 13px;
                font-weight: 600;
                text-decoration: none;
                box-shadow: 0 10px 20px rgba(15, 23, 42, 0.16);
                transition: transform 0.3s ease, box-shadow 0.3s ease;

                i {
                    font-size: 12px;
                }
            }

            .service-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 14px 24px rgba(15, 23, 42, 0.2);
                color: #fff;
            }

            .service-btn:focus-visible {
                outline: 2px solid rgba(15, 23, 42, 0.4);
                outline-offset: 3px;
            }
        }

        .service-alert {
            width: 100%;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 16px;
            align-items: center;
            background: linear-gradient(135deg, rgba(23, 99, 166, 0.12), #ffffff 70%);
            color: var(--platinum-navy);
            padding: 18px 20px;
            border-radius: 18px;
            border: 1px solid rgba(23, 99, 166, 0.25);
            box-shadow: 0 16px 30px rgba(15, 27, 64, 0.12);
            position: relative;
            overflow: hidden;
            text-align: right;

            &::after {
                content: "";
                position: absolute;
                width: 200px;
                height: 200px;
                left: -60px;
                bottom: -80px;
                background: radial-gradient(circle, rgba(35, 183, 217, 0.2), rgba(255, 255, 255, 0) 70%);
                pointer-events: none;
            }

            .alert-icon {
                width: 52px;
                height: 52px;
                border-radius: 16px;
                display: grid;
                place-items: center;
                background: #fff;
                color: var(--platinum-blue);
                box-shadow: inset 0 0 0 1px rgba(23, 99, 166, 0.25),
                    0 10px 18px rgba(15, 23, 42, 0.08);
                font-size: 20px;
            }

            .alert-title {
                display: block;
                font-weight: 700;
                font-size: 16px;
                margin-bottom: 4px;
            }

            p {
                margin: 0;
                color: var(--platinum-slate);
                font-size: 15px;
                line-height: 1.8;
            }
        }

        @media (max-width: 992px) {
            margin: -70px auto 0;
            padding: 28px 22px 22px;
        }

        @media (max-width: 576px) {
            margin: -50px auto 0;
            padding: 22px 18px;

            .section-header .title {
                font-size: 26px;
            }

            .service-alert {
                grid-template-columns: 1fr;
                text-align: right;
            }
        }
    }

    @keyframes heroGlow {

        0%,
        100% {
            transform: translateY(0);
            opacity: 0.7;
        }

        50% {
            transform: translateY(12px);
            opacity: 1;
        }
    }

    @keyframes serviceFloat {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-6px);
        }
    }

    @keyframes cardSheen {

        0%,
        100% {
            transform: translateY(0);
            opacity: 0.6;
        }

        50% {
            transform: translateY(10px);
            opacity: 1;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .hero-services {
            &::after {
                animation: none;
            }

            .service-card {
                animation: none;
                transition: none;
            }

            .service-card::before {
                animation: none;
            }
        }
    }

    .about-section {
        padding: 70px 0;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 90%);
    }

    .about-section .container {
        background: #fff;
        border-radius: 32px;
        padding: 40px 30px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
    }

    .about-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 36px;
        align-items: center;
    }

    .about-text .about-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 14px;
        background: rgba(91, 34, 130, 0.08);
        color: var(--mainColor);
        margin-bottom: 12px;
    }

    .about-text h2 {
        font-size: 32px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 16px;
        line-height: 1.4;
    }

    .about-text .about-description {
        color: #4b5563;
        line-height: 1.9;
        font-size: 16px;
    }

    .about-image {
        position: relative;
        padding: 18px;
        border-radius: 32px;
        background: linear-gradient(135deg, rgba(147, 197, 253, 0.25), rgba(167, 139, 250, 0.2));
    }

    .about-image::after {
        content: "";
        position: absolute;
        inset: 18px;
        border-radius: 24px;
        border: 1px dashed rgba(255, 255, 255, 0.5);
        pointer-events: none;
    }

    .about-image img {
        width: 100%;
        display: block;
        border-radius: 24px;
        position: relative;
        z-index: 1;
        object-fit: cover;
    }

    .about-cards {
        margin-top: 40px;
        margin-bottom: 32px;
    }

    @media (max-width: 768px) {
        .about-section .container {
            padding: 32px 20px;
        }

        .about-text h2 {
            font-size: 26px;
        }
    }
</style>

@section('content')
    @php
        $socials = [
            [
                'url' => $settings->twitter ?? null,
                'icon' => 'fab fa-twitter',
                'title' => 'تويتر',
                'class' => 'twitter',
            ],
            [
                'url' => $settings->facebook ?? null,
                'icon' => 'fab fa-facebook-f',
                'title' => 'فيسبوك',
                'class' => 'facebook',
            ],
            [
                'url' => $settings->instagram ?? null,
                'icon' => 'fab fa-instagram',
                'title' => 'انستجرام',
                'class' => 'instagram',
            ],
            [
                'url' => $settings->linkedin ?? null,
                'icon' => 'fab fa-linkedin-in',
                'title' => 'لينكدإن',
                'class' => 'linkedin',
            ],
            [
                'url' => $settings->youtube ?? null,
                'icon' => 'fab fa-youtube',
                'title' => 'يوتيوب',
                'class' => 'youtube',
            ],
            [
                'url' => $settings->tiktok ?? null,
                'icon' => 'fab fa-tiktok',
                'title' => 'تيكتوك',
                'class' => 'tiktok',
            ],
            [
                'url' => $settings->snapchat ?? null,
                'icon' => 'fab fa-snapchat-ghost',
                'title' => 'سناب شات',
                'class' => 'snapchat',
            ],
        ];

        $whatsAppNumber = $settings->whatsapp ?? null;
        if ($whatsAppNumber) {
            $cleanNumber = preg_replace('/\D+/', '', $whatsAppNumber);
            $waLink = $cleanNumber ? 'https://wa.me/' . $cleanNumber : $whatsAppNumber;
            $socials[] = ['url' => $waLink, 'icon' => 'fab fa-whatsapp', 'title' => 'واتساب', 'class' => 'whatsapp'];
        }

        $socials = array_filter($socials, fn($item) => !empty($item['url']));

        $localizedModelText = function ($model, string $attribute, string $fallback = '') {
            if (!$model) {
                return $fallback;
            }

            $locale = app()->getLocale();

            if (method_exists($model, 'translate')) {
                $translation = $model->translate($locale, false);
                $value = trim((string) data_get($translation, $attribute, ''));

                if ($value !== '') {
                    return $value;
                }

                if ($locale === 'ar') {
                    return $fallback;
                }
            }

            return trim((string) data_get($model, $attribute, $fallback));
        };

        $whatDoFallbacks = [
            'estqdam' => [
                'title' => 'الاستقدام',
                'description' => 'خدمة استقدام العمالة المنزلية والمهنية من الخارج للعمل داخل المملكة.',
            ],
            'egar' => [
                'title' => 'تأجير العمالة الشهري',
                'description' => 'خدمة تأجير العمالة للشهر الواحد مع ضمان مرونة وجودة.',
            ],
            'transfer' => [
                'title' => 'النقل الفوري للعمالة',
                'description' => 'خدمة نقل العمالة لمشاريعك بسرعة وكفاءة عالية.',
            ],
        ];
    @endphp
    @if (!empty($socials))
        <div class="sideSocial d-md-flex snipcss-W5Bnr">
            @foreach ($socials as $social)
                <a href="{{ $social['url'] }}" class="{{ $social['class'] ?? '' }}" target="_blank" rel="noopener"
                    title="{{ $social['title'] }}">
                    <i class="{{ $social['icon'] }}"></i>
                </a>
            @endforeach
        </div>
    @endif
    <!-- main slider -->
    <section class="mainSlider" data-aos="fade-up" data-aos-duration="900">
        <div class="swiper">
            <div class="swiper-wrapper">
                @forelse($sliders as $slider)
                    <div class="swiper-slide" style="background-image: url({{ showFile($slider->image) }})">
                        <div class="info">
                            <h4 class="hint">
                                {{ $localizedModelText($slider, 'title', documentSetting('website_name', config('app.name'))) }}
                            </h4>
                            <h1 class="sliderTitle">
                                {{ $localizedModelText($slider, 'description', __('web.project_desc')) }}
                            </h1>
                        </div>
                    </div>
                @empty
                    <!-- swiper-slide -->
                    <div class="swiper-slide" style="background-image: url({{ asset('site') }}/assets/images/s3.webp)">

                        <div class="info">
                            <h4 class="hint">
                                {{ documentSetting('website_name', config('app.name')) }}
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
        {{-- <div class="section-header">
            <div class="badge">
                <i class="fa-solid fa-briefcase" style="font-weight: bold;">
                    {{ __('web.our_services') }}
                </i>
            </div>
            <h2 class="title">{{ isset($whatDoSetting) ? $whatDoSetting->title : 'خدمات ' . documentSetting('website_name', config('app.name')) }}</h2>
            <p class="hint">{{ isset($whatDoSetting) ? $whatDoSetting->description : ' خدماتنا مرخصه من وزاره الموارد البشريه والتنميه الاجتماعية لضمان راحتك وراحه اسرتك' }}
            </p>
        </div> --}}
        <div class="section-header" data-aos="fade-up" data-aos-duration="1000">
            <span class="kicker">
                <i class="fa-solid fa-star"></i>
                {{ __('ingaz.musaned_services_subtitle') }}
            </span>
            <h1 class="title"> {{ __('web.our_services') }}</h1>
            <p class="hint">
                {{ isset($whatDoSetting) ? $whatDoSetting->description : ' خدماتنا مرخصه من وزاره الموارد البشريه والتنميه الاجتماعية لضمان راحتك وراحه اسرتك' }}
            </p>
        </div>
        <div class="services-container">
            @php
                $colors = ['green', 'blue', 'red', 'orange', 'purple', 'teal'];
                $serviceImages = [
                    'estqdam' => asset('site/assets/images/worker.webp'),
                    'egar' => asset('site/assets/images/service2.webp'),
                    'transfer' => asset('site/assets/images/passport-bg.webp'),
                ];
                $defaultServiceImage = asset('site/assets/images/bg5.webp');
                // $displayWhatDos = ($whatDos ?? collect())->take(3);
                $displayWhatDos = ($whatDos ?? collect())->take(4);

                $normalize = function ($value) {
                    $value = trim((string) $value);
                    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
                };

                $contains = function ($haystack, $needle) {
                    if ($needle === '') {
                        return false;
                    }
                    if (function_exists('mb_strpos')) {
                        return mb_strpos($haystack, $needle) !== false;
                    }
                    return strpos($haystack, $needle) !== false;
                };

                $preferredOrder = [
                    ['keywords' => ['السير الذاتية', 'سيرة ذاتية', 'cv', 'cvs']],
                    ['keywords' => ['عروض الاستقدام', 'عروض', 'offers', 'offer']],
                    ['keywords' => ['خدمة التأجير', 'التأجير', 'إيجار', 'إجار', 'rent', 'rental', 'egar']],
                    ['keywords' => ['مساند', 'musaned']],
                ];

                $orderedWhatDos = $displayWhatDos
                    ->sortBy(function ($item, $originalIndex) use ($preferredOrder, $normalize, $contains) {
                        $title = $normalize($item->title ?? '');
                        $type = $normalize($item->type ?? '');
                        $haystack = $title . ' ' . $type;

                        foreach ($preferredOrder as $index => $rule) {
                            foreach ($rule['keywords'] as $keyword) {
                                $keyword = $normalize($keyword);
                                if ($contains($haystack, $keyword)) {
                                    return $index;
                                }
                            }
                        }

                        return 999 + $originalIndex;
                    })
                    ->values();
            @endphp
            {{-- @dd($displayWhatDos) --}}
            @forelse($orderedWhatDos as $whatDo)
                @php
                    // $color = $colors[array_rand($colors)];
                    $color = '';
                    switch ($whatDo->type) {
                        case 'estqdam':
                            $color = 'green';
                            break;
                        case 'egar':
                            $color = 'purple';
                            break;
                        case 'transfer':
                            $color = 'blue';
                            break;
                        default:
                            $color = $colors[array_rand($colors)];
                    }
                    $cardImage = $serviceImages[$whatDo->type] ?? $defaultServiceImage;
                @endphp
                <div class="service-card {{ $color }}" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}"
                    style="--float-delay: {{ $loop->index * 0.2 }}s;">
                    <div class="icon {{ $color }}">
                        <i class="{{ $whatDo->icon }}"></i>
                    </div>
                    <h4>{{ $localizedModelText($whatDo, 'title', $whatDoFallbacks[$whatDo->type]['title'] ?? '') }}</h4>
                    <p>
                        {{ $localizedModelText($whatDo, 'description', $whatDoFallbacks[$whatDo->type]['description'] ?? '') }}
                    </p>
                    <ul>
                        @foreach ($whatDo->items as $item)
                            <li>{{ $localizedModelText($item, 'title') }}</li>
                        @endforeach
                    </ul>
                    <div class="service-actions">
                        <a href="{{ route('site.all-workers.index') }}?type={{ $whatDo->type }}" class="service-btn">
                            {{ __('web.request_service') }}
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="service-card green" data-aos="fade-up" data-aos-delay="0" style="--float-delay: 0s;">
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
                    <div class="service-actions">
                        <a href="{{ route('site.all-workers.index') }}?type=estqdam" class="service-btn">
                            {{ __('web.request_service') }}
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
                <div class="service-card purple" data-aos="fade-up" data-aos-delay="100" style="--float-delay: 0.2s;">
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
                    <div class="service-actions">
                        <a href="{{ route('site.all-workers.index') }}?type=egar" class="service-btn">
                            {{ __('web.request_service') }}
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
                <div class="service-card blue" data-aos="fade-up" data-aos-delay="200" style="--float-delay: 0.4s;">
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
                    <div class="service-actions">
                        <a href="{{ route('site.all-workers.index') }}?type=transfer" class="service-btn">
                            {{ __('web.request_service') }}
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
            @endforelse
            <div class="service-card gold" data-aos="fade-up" data-aos-delay="350" style="--float-delay: 0.6s;">
                <div class="icon gold">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <h4>صفحة الشركة في منصة مساند</h4>
                <p>
                    استعرض ملف الشركة الرسمي في منصة مساند وتفاصيل الاعتماد والخدمات المتاحة
                    بكل وضوح.
                </p>
                <ul>
                    <li>بيانات موثقة</li>
                    <li>تفاصيل الخدمات والعروض</li>
                    <li>سهولة الوصول للملف الرسمي</li>
                </ul>
                <div class="service-actions">
                    <a href="https://musaned.com.sa/ar/marketplace?page=0&params=%7B%22office_name%22:%22%D8%B4%D8%B1%D9%83%D8%A9+%D8%A8%D9%84%D8%A7%D8%AA%D9%8A%D9%86%D9%8A%D9%88%D9%85+%D8%A7%D9%84%D8%B4%D8%B1%D9%82%D9%8A%D8%A9+%D9%84%D9%84%D8%A7%D8%B3%D8%AA%D9%82%D8%AF%D8%A7%D9%85+%22%7D&officeNameSearch=%D8%B4%D8%B1%D9%83%D8%A9+%D8%A8%D9%84%D8%A7%D8%AA%D9%8A%D9%86%D9%8A%D9%88%D9%85+%D8%A7%D9%84%D8%B4%D8%B1%D9%82%D9%8A%D8%A9+%D9%84%D9%84%D8%A7%D8%B3%D8%AA%D9%82%D8%AF%D8%A7%D9%85+"
                        class="service-btn" target="_blank" rel="noopener">
                        {{ __('web.request_service') }}
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- التحذير -->
        <div class="service-alert" data-aos="fade-up" data-aos-delay="150" role="alert">
            <div class="alert-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="alert-content">
                <span class="alert-title">{{ __('ingaz.warning') }}</span>
                <p>
                    @if (isset($settings))
                        {{ app()->getLocale() == 'ar' ? $settings->note_ar : $settings->note_en }}
                    @else
                        {{ __('ingaz.warning_text') }}
                    @endif
                </p>
            </div>
        </div>
    </section>

    @php
        $aboutTitle = $localizedModelText($abouts ?? null, 'title', __('ingaz.about-us'));
        $aboutDescription = $localizedModelText($abouts ?? null, 'description', documentSetting('website_name', config('app.name')) . ' مرخصة من وزارة العمل والموارد البشرية لخدمات التوسط في استقدام العمالة الوافدة، وحرصاً منا على تقديم خدمات متميزة تم إطلاق منصة رقمية متكاملة لتبسيط عملية الاستقدام وجعلها أكثر كفاءة وسهولة لعملائنا بما يتوافق مع رؤية المملكة.');
        $aboutImage = !empty($abouts) && optional($abouts)->image ? showFile($abouts->image) : asset('site/assets/images/about.webp');
    @endphp
    <!-- about sec  -->
    <section class="about-section" id="about" data-aos="fade-up" data-aos-duration="900">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <span class="about-badge">
                        <i class="fa-solid fa-building"></i>
                        {{ __('web.aboutUs') }}
                    </span>
                    <h2>{{ $aboutTitle }}</h2>
                    <div class="about-description">
                        {!! $aboutDescription !!}
                    </div>
                </div>
                <div class="about-image">
                    <img src="{{ $aboutImage }}" alt="{{ $aboutTitle }}" />
                </div>
            </div>
            <div class="about-cards">
                @forelse($features as $feature)
                    <div class="service">
                        <i class="{{ $feature->image }}"></i>
                        <div>
                            <h4>{{ $localizedModelText($feature, 'title') }}</h4>
                            <p>{{ $localizedModelText($feature, 'description') }}</p>
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
    <section class="countries" data-aos="fade-up" data-aos-duration="900">
        <div class="container">
            {{-- <div class="headTitle text-center">
                <h2>دول الاستقدام</h2>
                <p>اختر الدولة التي ترغب بالاستقدام منها</p>
            </div> --}}
            <div class="section-header" data-aos="fade-up" data-aos-duration="1000">
                <h1 class="title"> دول الاستقدام</h1>
                <p class="hint">
                    اختر الدولة التي ترغب بالاستقدام منها
                </p>
            </div>
            <div class="row justify-content-center">
                @php
                    $formatNumber = function ($value) {
                        if ($value === null || $value === '') {
                            return '';
                        }
                        if (!is_numeric($value)) {
                            return $value;
                        }
                        $number = (float) $value;
                        if (abs($number - round($number)) < 0.00001) {
                            return number_format($number, 0, '.', '');
                        }
                        $formatted = number_format($number, 2, '.', '');
                        return rtrim(rtrim($formatted, '0'), '.');
                    };
                @endphp
                @forelse($nationalities as $nationalty)
                    <div class="col-12 col-md-6 col-lg-4 p-3" data-aos="fade-up"
                        data-aos-delay="{{ $loop->index * 100 }}">
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
                                <span class="label title">{{ $localizedModelText($nationalty, 'title', app()->getLocale() === 'ar' ? 'غير محدد' : $nationalty->display_name) }}</span>
                                <h2 class="price">{{ $formatNumber($nationalty->price) }} <small>ريال</small></h2>
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
                                        <p>{{ $formatNumber($nationalty->salary_with_no_experience) }} ريال</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-user-check"></i>
                                        <h5>راتب الخبرة</h5>
                                        <p>{{ $formatNumber($nationalty->salary_with_experience) }} ريال</p>
                                    </div>
                                    <div class="info-card">
                                        <i class="fas fa-flag"></i>
                                        <h5> تكلفة التاجير</h5>
                                        <p>{{ $formatNumber($nationalty->rental_cost) }} ريال</p>
                                    </div>
                                </div>
                                <a href="{{ route('site.all-workers.index') }}?type=estqdam&nationality_id={{ $nationalty->id }}"
                                    class="modern-btn">
                                    تصفح السير الذاتية <i class="fas fa-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 col-md-6 col-lg-4 p-3" data-aos="fade-up" data-aos-delay="0">
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
                                <h2 class="price">{{ $formatNumber(9500) }} <small>ريال</small></h2>
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
                                        <p>{{ $formatNumber(1000) }} ريال</p>
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
                    <div class="col-12 col-md-6 col-lg-4 p-3" data-aos="fade-up" data-aos-delay="100">
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
                                <h2 class="price">{{ $formatNumber(8000) }} <small>ريال</small></h2>
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
                                        <p>{{ $formatNumber(850) }} ريال</p>
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
                    <div class="col-12 col-md-6 col-lg-4 p-3" data-aos="fade-up" data-aos-delay="200">
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
                                <h2 class="price">{{ $formatNumber(12000) }} <small>ريال</small></h2>
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
                                        <p>{{ $formatNumber(1500) }} ريال</p>
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
                    <div class="col-12 col-md-6 col-lg-4 p-3" data-aos="fade-up" data-aos-delay="300">
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
                                <h2 class="price">{{ $formatNumber(9000) }} <small>ريال</small></h2>
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
                                        <p>{{ $formatNumber(1000) }} ريال</p>
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
    <section class="services-sec" style="height: fit-content" id="services" data-aos="fade-up"
        data-aos-duration="900">
        <div class="container">
            {{-- <div class="headTitle" data-aos="fade-up">
                <h2>{{ __('ingaz.our_services') }}</h2>
                <p>{{ __('ingaz.our_services_desc') }}</p>
            </div> --}}
            <div class="section-header" data-aos="fade-up" data-aos-duration="1000">
                <h1 class="title">{{ __('ingaz.our_services') }}</h1>
                <p class="hint">
                    {{ __('ingaz.our_services_desc') }}
                </p>
            </div>
            <!-- السلايدر -->
            <div class="services-slider swiper" data-aos="fade-up">
                <div class="swiper-wrapper">
                    @forelse($services as $service)
                        <div class="swiper-slide">
                            <a href="{{ $service->service_link ? $service->service_link : '#' }}"
                                class="service d-flex flex-column justify-content-center"
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
    <section class="statistics" id="statistics" data-aos="fade-up" data-aos-duration="900">
        <div class="container">
            <div class="section-header" data-aos="fade-up" data-aos-duration="1000">
                <h1 class="title">{{ __('ingaz.Statistics') }}</h1>
                <h6 class="hint">{{ __('ingaz.Statistics_text') }}</h6>
            </div>

            {{-- الإحصائيات الرئيسية --}}
            <div class="stats-container">
                {{--                @forelse($statics as $static) --}}
                {{--                    <div class="stat-card" data-aos="zoom-in" data-aos-delay="100"> --}}
                {{--                        <div class="icon-wrapper"> --}}
                {{--                            <i class="fa-solid fa-{{ $static->image }}"></i> --}}
                {{--                        </div> --}}
                {{--                        <span class="counter" data-target="{{ $static->counter_num }}">0</span> --}}
                {{--                        <h6>{{ $static->title }}</h6> --}}
                {{--                    </div> --}}
                {{--                @empty --}}
                <div class="stat-card" data-aos="zoom-in" data-aos-delay="100">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <span class="counter" data-target="{{ $counts['number_recruitment_requests'] }}">
                        {{ $counts['number_recruitment_requests'] }}
                    </span>
                    <h6>طلبات الاستقدام</h6>
                </div>

                <div class="stat-card" data-aos="zoom-in" data-aos-delay="200">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-file-contract"></i>
                    </div>
                    <span class="counter" data-target="{{ $counts['number_contracts_requests'] }}">
                        {{ $counts['number_contracts_requests'] }}
                    </span>
                    <h6>عقود تمت بنجاح</h6>
                </div>

                <div class="stat-card" data-aos="zoom-in" data-aos-delay="300">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-face-smile"></i>
                    </div>
                    <span class="counter" data-target="{{ $counts['clients'] }}">
                        {{ $counts['clients'] }}
                    </span>
                    <h6>عميل راضي عن الخدمة</h6>
                </div>

                {{--                @endforelse --}}
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
    <section class="steps" id="steps" style="position: relative" data-aos="fade-up" data-aos-duration="900">
        <div class="container">
            <!-- Section Title -->
            {{-- <div class="SectionTitle">
                <h2 class="title" data-aos="fade-up">{{ __('ingaz.Recruitment steps') }}</h2>
                <h6 class="hint" data-aos="fade-up" data-aos-delay="100">
                    {{ __('ingaz.Recruitment steps hint') }}
                </h6>
            </div> --}}
            <div class="section-header" data-aos="fade-up" data-aos-duration="1000">
                <h1 class="title">{{ __('ingaz.Recruitment steps') }}</h1>
                <p class="hint">
                    {{ __('ingaz.Recruitment steps hint') }}
                </p>
            </div>
            <!-- steps -->
            <div class="allSteps">
                @forelse($steps as $key=>$step)
                    <div class="step" data-aos="fade-up" data-aos-delay="100">
                        <div class="icon">
                            <i class="fa-thin fa-{{ $step->icon }}"></i>
                        </div>
                        <p>
                            <strong class="d-block">{{ $step->title }}</strong>
                            @if (filled($step->description))
                                <span>{{ $step->description }}</span>
                            @endif
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
            <div>
                <img width="40px" height="40px" src="{{ asset('site') }}/assets/images/mc.webp" data-aos="zoom-in"
                    data-aos-delay="200">
                <img width="40px" height="40px" src="{{ asset('site') }}/assets/images/visa.webp"
                    data-aos="zoom-in" data-aos-delay="300">
                <img width="40px" height="40px" src="{{ asset('site') }}/assets/images/mada.webp"
                    data-aos="zoom-in" data-aos-delay="400">
            </div>
            <div>
                <img width="40px" height="40px" src="{{ asset('site') }}/assets/images/tabby.png"
                    data-aos="zoom-in" data-aos-delay="400">
                <img width="40px" height="40px" src="{{ asset('site') }}/assets/images/tamara.webp"
                    data-aos="zoom-in" data-aos-delay="400">
            </div>
        </div>
    </section>

    <!-- ================ requirements ================= -->
    <section class="requirements" data-aos="fade-up" data-aos-duration="900">
        <div class="container">
            {{-- <div class="headTitle">
                <h2 data-aos="fade-up">{{ __('ingaz.Recruitment_requirements') }} </h2>
                <p data-aos="fade-up">
                    {{ __('ingaz.requirements_you_need') }}
                </p>
            </div> --}}
            <div class="section-header" data-aos="fade-up" data-aos-duration="1000">
                <h1 class="title">{{ __('ingaz.Recruitment_requirements') }}</h1>
                <p class="hint">
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
    <section class="video-section" id="promo-video" data-aos="fade-up" data-aos-duration="900">
        <div class="container">
            <h2 class="video-title" data-aos="fade-up" data-aos-duration="800">
                {{ __('ingaz.video_title') }}
            </h2>

            <div class="video-wrapper" data-aos="zoom-in" data-aos-duration="1000">
                <iframe src="{{ __('ingaz.video_link') }}" title="{{ __('ingaz.video_description') }}"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>
    </section>





    <!-- ================ contact Form ================= -->
    <section class="contactForm" data-aos="fade-up" data-aos-duration="900">
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
                    <section class="googleMap" data-aos="fade-up" data-aos-duration="900">
                        {{-- @dd($settings->map_link) --}}
                        @if ($settings->map_link)
                            <iframe src="{{ $settings->map_link }}" frameborder="0" style="border: 0"
                                allowfullscreen="" aria-hidden="false" tabindex="0"></iframe>
                        @else
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d57144.98454618672!2d50.12401500000001!3d26.469859!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e49fbc320200d1b%3A0x1abc87f1ffdb75d5!2z2KjZhNin2KrZitmG2YrZiNmFINin2YTYtNix2YLZitipINmE2YTYpdiz2KrZgtiv2KfZhQ!5e0!3m2!1sar!2ssa!4v1761469557614!5m2!1sar!2ssa"
                                frameborder="0" style="border: 0" allowfullscreen="" aria-hidden="false"
                                tabindex="0"></iframe>
                        @endif

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
        function initMap() {
            const mapElement = document.getElementById("map");
            if (!mapElement || typeof google === "undefined" || !google.maps) {
                return;
            }

            const defaultLocation = {
                lat: 30.0444,
                lng: 31.2357
            }; // القاهرة كمثال

            const map = new google.maps.Map(mapElement, {
                zoom: 10,
                center: defaultLocation,
            });

            const marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true
            });

            // عند تحريك العلامة (Marker)
            google.maps.event.addListener(marker, 'dragend', function(event) {
                const latInput = document.getElementById("latitude");
                const lngInput = document.getElementById("longitude");
                if (latInput) {
                    latInput.value = event.latLng.lat();
                }
                if (lngInput) {
                    lngInput.value = event.latLng.lng();
                }
            });
        }

        // تحميل الخريطة عند فتح الصفحة
        window.addEventListener('load', initMap);
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
