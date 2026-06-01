@php
$locale = \App::currentLocale();
$scaleDirection = ($locale == "ar")? "scale-right":"scale-left";
$user = getAuthUser();
$settings = $settings ?? null;
$appTimezone = config('app.timezone', 'Asia/Riyadh');
$headerNow = now()->timezone($appTimezone);
$storedQuickLinks = optional($settings)->admin_quick_links ?: [];
$quickLinks = collect($storedQuickLinks);

if ($quickLinks->isEmpty()) {
    $quickLinks = collect([
        [
            'title' => optional($settings)->header_link_1_title ?? 'مساند برو',
            'url' => optional($settings)->header_link_1_url ?? 'https://pros.musaned.com.sa/login',
            'icon' => 'fa-external-link',
            'is_visible' => true,
        ],
        [
            'title' => optional($settings)->header_link_2_title ?? 'مساند توثيق',
            'url' => optional($settings)->header_link_2_url ?? 'https://tawtheeq.musaned.com.sa/',
            'icon' => 'fa-file-text-o',
            'is_visible' => true,
        ],
        [
            'title' => optional($settings)->live_chat_title ?? 'اللايف شات',
            'url' => optional($settings)->live_chat_url ?? 'https://salesiq.zoho.sa/platinumeastern/liveview',
            'icon' => 'fa-comments',
            'is_visible' => true,
        ],
    ]);
}

$quickLinks = $quickLinks->filter(function ($link) {
    return filled($link['title'] ?? null) && filled($link['url'] ?? null) && ($link['is_visible'] ?? true);
})->values();
@endphp
@once
    <style>
        .page-header .navbar {
            gap: 0.7rem;
            align-items: center;
            flex-wrap: nowrap;
        }

        .cm-brand {
            min-height: 58px;
            min-width: auto;
            max-width: 320px;
            gap: 0.7rem;
        }

        .cm-header-logo {
            height: 52px;
            max-width: 150px;
            width: auto;
            object-fit: contain;
        }

        .cm-brand-text {
            line-height: 1.05;
        }

        .cm-brand-subtitle {
            font-size: 12px;
            font-weight: 700;
            color: #102033;
        }

        .cm-header-center {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: nowrap;
            min-width: 0;
        }

        .header-left {
            min-width: 0;
        }

        .cm-header-meta {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.45rem;
            flex: 0 0 auto;
            min-width: 0;
        }

        .cm-header-meta-card {
            min-width: auto;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.34rem 0.62rem 0.34rem 0.38rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: none;
            white-space: nowrap;
        }

        .cm-header-meta-icon {
            width: 28px;
            height: 28px;
            flex: 0 0 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.16) 0%, rgba(13, 110, 253, 0.06) 100%);
            color: #0d6efd;
            font-size: 0.8rem;
        }

        .cm-header-meta-body {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 0.4rem;
            min-width: 0;
        }

        .cm-header-meta-label {
            display: none;
        }

        .cm-header-meta-value {
            color: #0f172a;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
        }

        .cm-header-meta-subvalue {
            color: #475569;
            font-size: 0.72rem;
            margin-top: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cm-header-shortcuts {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.45rem;
            flex: 1 1 auto;
            min-height: auto;
            min-width: 0;
        }

        .cm-header-shortcuts-label {
            display: none;
        }

        .cm-header-shortcuts-list {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.4rem;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .cm-header-shortcuts-list::-webkit-scrollbar {
            display: none;
        }

        .cm-header-shortcut {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.42rem 0.72rem;
            border-radius: 10px;
            border: 1px solid rgba(13, 110, 253, 0.1);
            background: rgba(255, 255, 255, 0.92);
            color: #1f2937;
            font-size: 0.78rem;
            font-weight: 600;
            line-height: 1;
            text-decoration: none;
            box-shadow: none;
            white-space: nowrap;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .cm-header-shortcut:hover {
            color: #0d6efd;
            transform: translateY(-1px);
            box-shadow: none;
        }

        .cm-header-shortcut.is-primary {
            background: rgba(13, 110, 253, 0.12);
            border-color: rgba(13, 110, 253, 0.14);
            color: #0d6efd;
        }

        .cm-header-shortcut.is-primary:hover {
            color: #0d6efd;
        }

        .cm-header-shortcut i {
            font-size: 0.75rem;
        }

        .cm-header-action {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(255, 255, 255, 0.94);
            color: #0f172a;
            box-shadow: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, color 0.2s ease;
        }

        .cm-header-action:hover {
            color: #0d6efd;
            transform: translateY(-1px);
            box-shadow: none;
        }

        .cm-header-open-site {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.45rem 0.75rem;
            border-radius: 10px;
            text-decoration: none;
            background: #0f6b6e;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 700;
            box-shadow: none;
            white-space: nowrap;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .cm-header-open-site:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: none;
        }

        .cm-header-open-site i {
            font-size: 0.82rem;
        }

        .header-right {
            gap: 0.35rem;
        }

        @media (max-width: 1199.98px) {
            .page-header .navbar {
                gap: 0.55rem;
            }

            .cm-header-shortcut {
                padding: 0.4rem 0.62rem;
                font-size: 0.74rem;
            }
        }

        @media (max-width: 1550px) {
            .cm-header-meta-subvalue {
                display: none;
            }
        }

        @media (max-width: 1280px) {
            .cm-header-open-site span {
                display: none;
            }

            .cm-header-open-site {
                width: 38px;
                height: 38px;
                padding: 0;
                justify-content: center;
            }
        }

        @media (max-width: 991.98px) {
            .cm-brand {
                margin-inline-start: 0.65rem !important;
                margin-inline-end: 0 !important;
                max-width: 230px;
            }

            .cm-header-logo {
                height: 44px;
                max-width: 125px;
            }
        }
    </style>
@endonce
<header class="page-header sticky-top px-xl-4 px-sm-2 px-0 py-lg-2 py-1">
    <div class="container-fluid">
        <nav class="navbar">

            <div class="d-flex">
                <button type="button" class="btn btn-link d-none d-xl-block sidebar-mini-btn p-0 text-primary">
                    <span class="hamburger-icon">
                        <span class="line"></span>
                        <span class="line"></span>
                        <span class="line"></span>
                    </span>
                </button>
                <button type="button" class="btn btn-link d-block d-xl-none menu-toggle p-0 text-primary">
                    <span class="hamburger-icon">
                        <span class="line"></span>
                        <span class="line"></span>
                        <span class="line"></span>
                    </span>
                </button>
                @php
                    $headerDashboardName = isset($settings)
                        ? optional($settings->translate(app()->getLocale()))->dashboard_name
                        : null;
                    $headerLogo =
                        isset($settings) && !empty($settings->admin_logo)
                            ? showFile($settings->admin_logo)
                            : (isset($settings) && !empty($settings->logo_header)
                                ? showFile($settings->logo_header)
                                : asset('admin/assets/img/company_logo.jpeg'));
                @endphp
                <a href="{{ route('dashboard.index') }}" class="brand-icon cm-brand d-flex align-items-center mx-2 mx-sm-3">
                    <img class="cm-header-logo" src="{{ $headerLogo }}" alt="logo"
                        onerror="this.onerror=null;this.src='{{ asset('admin/assets/img/company_logo.jpeg') }}';">
                    <span class="cm-brand-text">
                        <small class="cm-brand-subtitle">{{ $headerDashboardName ?: helperTrans('ingaz.dashboard') }}</small>
                    </span>
                </a>
            </div>

            <div class="header-left flex-grow-1 d-none d-md-block">
                <div class="px-3 flex-fill">
                    <div class="cm-header-center">
                        <div class="cm-header-meta">
                            <div class="cm-header-meta-card">
                                <span class="cm-header-meta-icon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                <div class="cm-header-meta-body">
                                    <span class="cm-header-meta-label">{{ helperTrans('ingaz.time') }}</span>
                                    <strong class="cm-header-meta-value" id="cm-live-time">{{ $headerNow->format('H:i:s') }}</strong>
                                    <small class="cm-header-meta-subvalue" id="cm-live-date">{{ $headerNow->format('Y-m-d') }}</small>
                                </div>
                            </div>

                            <div class="cm-header-meta-card">
                                <span class="cm-header-meta-icon">
                                    <i class="fa fa-clock-o"></i>
                                </span>
                                <div class="cm-header-meta-body">
                                    <span class="cm-header-meta-label">{{ helperTrans('ingaz.duration') }}</span>
                                    <strong class="cm-header-meta-value" id="cm-session-duration">00:00</strong>
                                    <small class="cm-header-meta-subvalue">{{ helperTrans('ingaz.since_system_opened') }}</small>
                                </div>
                            </div>
                        </div>

                        @if ($quickLinks->isNotEmpty())
                            <div class="cm-header-shortcuts">
                                <p class="cm-header-shortcuts-label mb-0">{{ helperTrans('ingaz.quick_links') }}</p>
                                <div class="cm-header-shortcuts-list">
                                    @foreach ($quickLinks as $link)
                                        <a class="cm-header-shortcut"
                                           href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">
                                            <i class="fa {{ $link['icon'] ?? 'fa-external-link' }}"></i>
                                            <span>{{ $link['title'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <ul class="header-right justify-content-end d-flex align-items-center mb-0">
                <li>
                    <div class="dropdown morphing {{$scaleDirection}} notifications">
                        <a class="nav-link dropdown-toggle after-none cm-header-action" href="#" role="button" data-bs-toggle="dropdown" title="{{ helperTrans('ingaz.notifications') }}">

                            <i class="fa fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge" style="display: none;">
                                0
                            </span>
                        </a>
                        <div class="dropdown-menu shadow rounded-4 border-0 p-0 m-0" style="width: 300px; max-height: 400px; overflow-y: auto;">
                            <div class="card">
                                <div class="card-header p-3 d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0">{{ __('admin.layout.notifications') }}</h6>
                                    <button class="btn btn-sm btn-link mark-all-read">{{ __('admin.layout.mark_all_read') }}</button>
                                </div>
                                <div class="card-body">
                                    <div class="notification-list">
                                        <!-- Notifications will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="d-none d-xl-inline-block">
                    <a class="nav-link fullscreen cm-header-action" href="#{{--javascript:void(0);--}}"
                        onclick="toggleFullScreen(documentElement)" title="{{ __('admin.layout.fullscreen') }}">
                        <svg viewBox="0 0 16 16" width="18px" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M5.8279 10.172C5.73414 10.0783 5.60698 10.0256 5.4744 10.0256C5.34182 10.0256 5.21467 10.0783 5.1209 10.172L1.0249 14.268V11.5C1.0249 11.3674 0.972224 11.2402 0.878456 11.1464C0.784688 11.0527 0.657511 11 0.524902 11C0.392294 11 0.265117 11.0527 0.171349 11.1464C0.0775808 11.2402 0.0249023 11.3674 0.0249023 11.5V15.475C0.0249023 15.6076 0.0775808 15.7348 0.171349 15.8285C0.265117 15.9223 0.392294 15.975 0.524902 15.975H4.4999C4.63251 15.975 4.75969 15.9223 4.85346 15.8285C4.94722 15.7348 4.9999 15.6076 4.9999 15.475C4.9999 15.3424 4.94722 15.2152 4.85346 15.1214C4.75969 15.0277 4.63251 14.975 4.4999 14.975H1.7319L5.8279 10.879C5.92164 10.7852 5.9743 10.6581 5.9743 10.5255C5.9743 10.3929 5.92164 10.2658 5.8279 10.172ZM10.1719 10.172C10.2657 10.0783 10.3928 10.0256 10.5254 10.0256C10.658 10.0256 10.7851 10.0783 10.8789 10.172L14.9749 14.268V11.5C14.9749 11.3674 15.0276 11.2402 15.1213 11.1464C15.2151 11.0527 15.3423 11 15.4749 11C15.6075 11 15.7347 11.0527 15.8285 11.1464C15.9222 11.2402 15.9749 11.3674 15.9749 11.5V15.475C15.9749 15.6076 15.9222 15.7348 15.8285 15.8285C15.7347 15.9223 15.6075 15.975 15.4749 15.975H11.4999C11.3673 15.975 11.2401 15.9223 11.1463 15.8285C11.0526 15.7348 10.9999 15.6076 10.9999 15.475C10.9999 15.3424 11.0526 15.2152 11.1463 15.1214C11.2401 15.0277 11.3673 14.975 11.4999 14.975H14.2679L10.1719 10.879C10.0782 10.7852 10.0255 10.6581 10.0255 10.5255C10.0255 10.3929 10.0782 10.2658 10.1719 10.172ZM5.8279 5.82799C5.73414 5.92173 5.60698 5.97439 5.4744 5.97439C5.34182 5.97439 5.21467 5.92173 5.1209 5.82799L1.0249 1.73199V4.49999C1.0249 4.6326 0.972224 4.75978 0.878456 4.85355C0.784688 4.94732 0.657511 4.99999 0.524902 4.99999C0.392294 4.99999 0.265117 4.94732 0.171349 4.85355C0.0775808 4.75978 0.0249023 4.6326 0.0249023 4.49999V0.524994C0.0249023 0.392386 0.0775808 0.265209 0.171349 0.17144C0.265117 0.0776723 0.392294 0.0249939 0.524902 0.0249939H4.4999C4.63251 0.0249939 4.75969 0.0776723 4.85346 0.17144C4.94722 0.265209 4.9999 0.392386 4.9999 0.524994C4.9999 0.657602 4.94722 0.784779 4.85346 0.878547C4.75969 0.972315 4.63251 1.02499 4.4999 1.02499H1.7319L5.8279 5.12099C5.92164 5.21476 5.9743 5.34191 5.9743 5.47449C5.9743 5.60708 5.92164 5.73423 5.8279 5.82799Z" />
                            <path class="fill-secondary"
                                d="M10.5253 5.97439C10.3927 5.97439 10.2655 5.92173 10.1718 5.82799C10.078 5.73423 10.0254 5.60708 10.0254 5.47449C10.0254 5.34191 10.078 5.21476 10.1718 5.12099L14.2678 1.02499H11.4998C11.3672 1.02499 11.24 0.972315 11.1462 0.878547C11.0525 0.784779 10.9998 0.657602 10.9998 0.524994C10.9998 0.392386 11.0525 0.265209 11.1462 0.17144C11.24 0.0776723 11.3672 0.0249939 11.4998 0.0249939H15.4748C15.6074 0.0249939 15.7346 0.0776723 15.8283 0.17144C15.9221 0.265209 15.9748 0.392386 15.9748 0.524994V4.49999C15.9748 4.6326 15.9221 4.75978 15.8283 4.85355C15.7346 4.94732 15.6074 4.99999 15.4748 4.99999C15.3422 4.99999 15.215 4.94732 15.1212 4.85355C15.0275 4.75978 14.9748 4.6326 14.9748 4.49999V1.73199L10.8788 5.82799C10.785 5.92173 10.6579 5.97439 10.5253 5.97439Z" />
                        </svg>
                    </a>
                </li>

                <li class="d-none d-xl-inline-block">
                    <div class="dropdown morphing {{$scaleDirection}} Language mx-sm-2">
                        <a class="nav-link dropdown-toggle after-none cm-header-action" href="#" role="button" data-bs-toggle="dropdown" title="{{ __('admin.layout.language') }}">
                            <svg viewBox="0 0 16 16" width="18px" fill="currentColor"
                                xmlns="http://www.w3.org/2000/svg">
                                <path class="fill-secondary"
                                    d="M4.545 6.714 4.11 8H3l1.862-5h1.284L8 8H6.833l-.435-1.286H4.545zm1.634-.736L5.5 3.956h-.049l-.679 2.022H6.18z" />
                                <path
                                    d="M0 2a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v3h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-3H2a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H2zm7.138 9.995c.193.301.402.583.63.846-.748.575-1.673 1.001-2.768 1.292.178.217.451.635.555.867 1.125-.359 2.08-.844 2.886-1.494.777.665 1.739 1.165 2.93 1.472.133-.254.414-.673.629-.89-1.125-.253-2.057-.694-2.82-1.284.681-.747 1.222-1.651 1.621-2.757H14V8h-3v1.047h.765c-.318.844-.74 1.546-1.272 2.13a6.066 6.066 0 0 1-.415-.492 1.988 1.988 0 0 1-.94.31z" />
                            </svg>
                        </a>
                        <div class="dropdown-menu rounded-4 shadow border-0 p-0" data-bs-popper="none">
                          <div class="card">
                              {{--    <ul style="list-style: none;: 0;padding: 0;">
                                    @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                                        <li style="display: inline-block;-right: 10px;">
                                            <a style="color: #333333;font-size: 16px;text-decoration: none;padding: 5px 10px;border-radius: 5px;" rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
                                                {{ $properties['native'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>--}}
                                 <div class="list-group list-group-custom" style="width: 200px;">

                                     <a href="{{ LaravelLocalization::getLocalizedURL("en", null, [], true) }}" class="list-group-item"><span
                                            class="flag-icon flag-icon-us me-2"></span>US English</a>

                                    <a href="{{ LaravelLocalization::getLocalizedURL("ar", null, [], true) }}" class="list-group-item"><span
                                            class="flag-icon flag-icon-sa me-2"></span>{{ __('admin.layout.saudi_arabia') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="d-none d-sm-inline-block d-xl-none">
                    <a class="nav-link cm-header-action" href="#" data-bs-toggle="modal" data-bs-target="#MynotesModal" title="{{ __('admin.layout.notes') }}">
                        <svg viewBox="0 0 16 16" width="18px" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path class="fill-secondary"
                                d="M1.5 0A1.5 1.5 0 0 0 0 1.5V13a1 1 0 0 0 1 1V1.5a.5.5 0 0 1 .5-.5H14a1 1 0 0 0-1-1H1.5z" />
                            <path
                                d="M3.5 2A1.5 1.5 0 0 0 2 3.5v11A1.5 1.5 0 0 0 3.5 16h6.086a1.5 1.5 0 0 0 1.06-.44l4.915-4.914A1.5 1.5 0 0 0 16 9.586V3.5A1.5 1.5 0 0 0 14.5 2h-11zM3 3.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5V9h-4.5A1.5 1.5 0 0 0 9 10.5V15H3.5a.5.5 0 0 1-.5-.5v-11zm7 11.293V10.5a.5.5 0 0 1 .5-.5h4.293L10 14.793z" />
                        </svg>
                    </a>
                </li>

                <li class="d-none d-xl-inline-block">
                    <a class="cm-header-open-site" href="{{ publicSiteUrl() }}" target="_blank" rel="noopener noreferrer" title="{{ __('admin.layout.external_site_title') }}">
                        <i class="fa fa-globe"></i>
                        <span>{{ __('admin.layout.website') }}</span>
                    </a>
                </li>

                <li class="d-none d-sm-inline-block d-xl-none">
                    <a class="nav-link cm-header-action" href="{{ publicSiteUrl() }}" target="_blank" rel="noopener noreferrer" title="{{ __('admin.layout.external_site_title') }}">
                        <i class="fa fa-globe"></i>
                    </a>
                </li>

                <li class="d-none d-sm-inline-block d-xl-none">
                    <a class="nav-link cm-header-action" href="#" data-bs-toggle="modal" data-bs-target="#RecentChat" title="{{ __('admin.layout.chats') }}">
                        <svg viewBox="0 0 16 16" width="18px" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z" />
                            <path class="fill-secondary"
                                d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z" />
                        </svg>
                    </a>
                </li>

                <li>
                    <div class="dropdown morphing {{$scaleDirection}} user-profile mx-lg-3 mx-2">
                        <a class="nav-link dropdown-toggle rounded-circle after-none p-0" href="#" role="button"
                            data-bs-toggle="dropdown">
                            <img class="avatar img-thumbnail rounded-circle shadow"
                                src="{{ showFile(($user && $user->image) ? $user->image : '../admin/assets/img/profile_av.png') }}"
                                alt="">
                        </a>
                        <div class="dropdown-menu border-0 rounded-4 shadow p-0">
                            <div class="card border-0 w240">
                                <div class="card-body border-bottom d-flex">
                                    <img class="avatar rounded-circle"
                                        src="{{showFile(($user && $user->image) ? $user->image : '../admin/assets/img/profile_av.png') }}"
                                        alt="">
                                    <div class="flex-fill ms-3">
                                        <h6 class="card-title mb-0">{{$user ? $user->name : 'Guest'}}</h6>
                                        <span class="text-muted">
                                            {{--<a href="#" class="__cf_email__"
                                                data-cfemail="a8c9c4c4c1cdcfdac9dccddae8c4ddc6c786cbc7c5">--}}
                                                {{$user ? $user->phone : 'N/A'}}{{--</a>--}}
                                        </span>
                                    </div>
                                </div>
                                <div class="list-group m-2 mb-3">
                                    @if($user)
                                        <a class="list-group-item list-group-item-action border-0"
                                            href="{{route('profile.index')}}"><i class="w30 fa fa-user"></i>{{ __('admin.layout.profile') }}</a>
                                        {{-- @if (Auth::guard('admin')->user()->hasPermission('settings-read')) --}}
                                        <a class="list-group-item list-group-item-action border-0" href="{{route('settings.index')}}"><i
                                                class="w30 fa fa-gear"></i>{{ __('ingaz.settings') }}</a>
                                        {{-- @endif --}}
                                        {{-- @if(Auth::guard('admin')->user()->hasPermission('users-read')) --}}
                                        <a class="list-group-item list-group-item-action border-0" href="{{route('admins.index')}}"><i
                                                class="w30 fa fa-users"></i>{{ __('auth.users') }}</a>
                                        {{-- @endif --}}
                                        {{--<a class="list-group-item list-group-item-action border-0" href="#"><i
                                                class="w30 fa fa-tag"></i>Support Ticket</a>--}}
                                    @else
                                        <a class="list-group-item list-group-item-action border-0"
                                            href="{{route('admin.login')}}"><i class="w30 fa fa-sign-in"></i>{{ __('admin.layout.login') }}</a>
                                    @endif
                                </div>
                                @if($user)
                                    <a href="{{route('admin.logout')}}"
                                        class="btn bg-secondary text-light text-uppercase rounded-0">{{ __('admin.layout.logout') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </li>

                {{-- <li>
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#SettingsModal"
                        title="Settings">
                        <svg viewBox="0 0 16 16" width="18px" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path class="fill-secondary"
                                d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z">
                            </path>
                            <path
                                d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z">
                            </path>
                        </svg>
                    </a>
                </li> --}}
            </ul>
        </nav>
    </div>
</header>
@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const liveDate = document.getElementById('cm-live-date');
            const liveTime = document.getElementById('cm-live-time');
            const sessionDuration = document.getElementById('cm-session-duration');

            if (!liveDate || !liveTime || !sessionDuration) {
                return;
            }

            const timezone = @json($appTimezone);
            const locale = @json($locale === 'ar' ? 'ar-SA' : 'en-US');
            const storageKey = 'clickerp_admin_session_opened_at';
            const dateFormatter = new Intl.DateTimeFormat(locale, {
                timeZone: timezone,
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
            const timeFormatter = new Intl.DateTimeFormat(locale, {
                timeZone: timezone,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });

            let openedAt = sessionStorage.getItem(storageKey);
            if (!openedAt) {
                openedAt = String(Date.now());
                sessionStorage.setItem(storageKey, openedAt);
            }

            openedAt = parseInt(openedAt, 10);

            const pad = (value) => String(value).padStart(2, '0');

            const formatDuration = (milliseconds) => {
                const totalSeconds = Math.max(0, Math.floor(milliseconds / 1000));
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                if (hours > 0) {
                    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
                }

                return `${pad(minutes)}:${pad(seconds)}`;
            };

            const renderHeaderClock = () => {
                const now = new Date();
                liveDate.textContent = dateFormatter.format(now);
                liveTime.textContent = timeFormatter.format(now);
                sessionDuration.textContent = formatDuration(Date.now() - openedAt);
            };

            renderHeaderClock();
            window.setInterval(renderHeaderClock, 1000);
        });
    </script>
@endonce
