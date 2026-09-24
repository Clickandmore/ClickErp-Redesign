@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('admin.settings') }}
@endsection

@section('style')
    <style>
        .settings-shell .settings-nav {
            gap: 0.5rem;
        }

        .settings-shell .settings-nav .nav-link {
            border: 1px solid var(--bs-border-color, #e6e9f0);
            border-radius: 12px;
            background: var(--bs-light, #f8f9fa);
            color: var(--bs-body-color, #212529);
            padding: 0.75rem 1rem;
            text-align: start;
            transition: all 0.2s ease-in-out;
        }

        .settings-shell .settings-nav .nav-link:hover {
            background: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.08);
            color: var(--bs-body-color, #212529);
        }

        .settings-shell .settings-nav .nav-link.active {
            background: var(--bs-primary, #0d6efd);
            border-color: var(--bs-primary, #0d6efd);
            color: #fff;
            box-shadow: 0 10px 24px rgba(var(--bs-primary-rgb, 13, 110, 253), 0.2);
        }

        .settings-shell .settings-card {
            min-height: 320px;
        }

        @media (max-width: 991.98px) {
            .settings-shell .settings-nav {
                flex-direction: row !important;
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 0.5rem;
            }

            .settings-shell .settings-nav .nav-link {
                min-width: 180px;
                white-space: normal;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $activeSection = $activeSection ?? request()->route('section');
        if (!$activeSection && Route::currentRouteName() === 'dash.settings') {
            $activeSection = 'operations';
        }
        if (!$activeSection && Route::currentRouteName() === 'dash.settings.announcements') {
            $activeSection = 'announcements';
        }
        $activeSection = $activeSection ?: 'general';
    @endphp

    <div class="settings-shell">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-1">{{ __('admin.settings') }}</h4>
                <div class="text-muted small">{{ __('admin.settings_manage_sections_hint') }}</div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-3">
                <nav class="nav flex-lg-column nav-pills settings-nav" aria-label="Settings Sections">
                    <a class="nav-link {{ $activeSection === 'general' ? 'active' : '' }}" href="{{ route('settings.section', 'general') }}">
                        <span class="fw-semibold">{{ __('admin.settings_section_general') }}</span>
                    </a>
                    <a class="nav-link {{ $activeSection === 'social' ? 'active' : '' }}" href="{{ route('settings.section', 'social') }}">
                        <span class="fw-semibold">{{ __('admin.settings_section_social') }}</span>
                    </a>
                    <a class="nav-link {{ $activeSection === 'branding' ? 'active' : '' }}" href="{{ route('settings.section', 'branding') }}">
                        <span class="fw-semibold">{{ __('admin.settings_section_branding') }}</span>
                    </a>
                    <a class="nav-link {{ $activeSection === 'content' ? 'active' : '' }}" href="{{ route('settings.section', 'content') }}">
                        <span class="fw-semibold">{{ __('admin.settings_section_content') }}</span>
                    </a>
                    <a class="nav-link {{ $activeSection === 'policies' ? 'active' : '' }}" href="{{ route('settings.section', 'policies') }}">
                        <span class="fw-semibold">{{ __('admin.settings_section_policies') }}</span>
                    </a>
                    <a class="nav-link {{ $activeSection === 'authentication' ? 'active' : '' }}" href="{{ route('settings.section', 'authentication') }}">
                        <span class="fw-semibold">{{ __('admin.settings_section_external_auth') }}</span>
                    </a>
                    <a class="nav-link {{ $activeSection === 'ordering' ? 'active' : '' }}" href="{{ route('settings.section', 'ordering') }}">
                        <span class="fw-semibold">{{ __('admin.settings_section_external_order') }}</span>
                    </a>
                    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CV_TEMPLATE))
                        <a class="nav-link {{ $activeSection === 'cv-template' ? 'active' : '' }}"
                            href="{{ route('settings.cv-template.edit') }}">
                            <span class="fw-semibold">قالب السيرة الذاتية</span>
                        </a>
                    @endif
                    <a class="nav-link {{ $activeSection === 'seo' ? 'active' : '' }}" href="{{ route('settings.section', 'seo') }}">
                        <span class="fw-semibold">{{ __('admin.settings_section_seo') }}</span>
                    </a>
                    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
                    <a class="nav-link {{ $activeSection === 'zoho' ? 'active' : '' }}" href="{{ route('dash.settings.zoho') }}">
                        <span class="fw-semibold">إعدادات Zoho SalesIQ</span>
                    </a>
                    <a class="nav-link {{ $activeSection === 'operations' ? 'active' : '' }}" href="{{ route('dash.settings') }}">
                        <span class="fw-semibold">{{ __('admin.settings_section_additional') }}</span>
                    </a>
                    <a class="nav-link {{ $activeSection === 'announcements' ? 'active' : '' }}" href="{{ route('dash.settings.announcements') }}">
                        <span class="fw-semibold">{{ __('admin.settings_section_announcements') }}</span>
                    </a>
                    @endif
                </nav>
            </div>

            <div class="col-lg-9">
                <div class="card border-0 shadow-sm settings-card">
                    <div class="card-body">
                        @php
                            $sectionPermission = match ($activeSection) {
                                'cv-template' => \App\PermKeys::SETTINGS_CV_TEMPLATE,
                                default => \App\PermKeys::SETTINGS_VIEW,
                            };
                        @endphp
                        @if (@AdminGuardHasPerm($sectionPermission) || (\App\Support\WebsiteSettingsAccess::isWebsiteSection($activeSection) && @AdminGuardHasPerm(\App\PermKeys::WEBSITE_SETTINGS_CONTROL)))
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                <div>
                                    <h5 class="mb-1">@yield('settings-title')</h5>
                                    @hasSection('settings-description')
                                        <p class="text-muted small mb-0">@yield('settings-description')</p>
                                    @endif
                                </div>
                            </div>

                            @yield('settings-alerts')
                            @yield('settings-content')
                        @else
                            @include('components.no_permissions')
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
