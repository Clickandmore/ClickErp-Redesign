<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $locale = app()->getLocale();
        $settingsName = isset($settings) ? optional($settings->translate($locale))->website_name : null;
        $dashboardName = isset($settings) ? optional($settings->translate($locale))->dashboard_name : null;
        $brandName = $dashboardName ?: ($settingsName ?: (isset($developSettings) ? $developSettings->name : config('app.name', 'Platinum')));
        $footerName = 'Click ERP';
        $footerUrl = 'https://clickerp.com.sa';

        $resolveLoginBackground = static function (?string $storedPath): ?string {
            if (empty($storedPath)) {
                return null;
            }

            $storage = app(\App\Services\ManagedFileStorage::class);

            if ($storage->isR2Path($storedPath)) {
                return $storage->exists($storedPath) ? $storage->url($storedPath) : null;
            }

            $r2Pointer = $storage->existingR2Pointer($storedPath);
            if ($r2Pointer !== null) {
                return $storage->url($r2Pointer);
            }

            return \Illuminate\Support\Facades\Storage::disk('public')->exists($storedPath)
                ? asset('storage/' . ltrim($storedPath, '/'))
                : null;
        };

        $loginBg = null;
        foreach ([
            $settings->admin_login_background ?? null,
            $settings->cover_image ?? null,
            $settings->light_image ?? null,
        ] as $backgroundPath) {
            $loginBg = $resolveLoginBackground($backgroundPath);

            if ($loginBg !== null) {
                break;
            }
        }

        $loginBg ??= asset('site/assets/images/login_image.jpg');

        $loginLogo =
            isset($settings) && !empty($settings->admin_logo)
                ? showFile($settings->admin_logo)
                : (isset($settings) && !empty($settings->logo_header)
                    ? showFile($settings->logo_header)
                    : (isset($developSettings) && !empty($developSettings->logo_login)
                        ? showFile($developSettings->logo_login)
                        : asset('site/assets/images/company_logo.png')));

        $footerLogo = asset('site/assets/images/fav.png');
    @endphp
    <title>{{ $brandName }} | @yield('auth-page-title', __('auth.login'))</title>
    <link rel="stylesheet" href="{{ asset('admin/login') }}/css/style-dashboard.css?v=5">
    <link rel="stylesheet" href="{{ asset('admin/login') }}/css/fontawesome.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/vendor/toster/css/jquery.toast.css">
    @stack('auth-styles')
</head>

<body @if (app()->getLocale() == 'ar') dir="rtl" @endif>
    <div class="auth-page" style="--login-bg-image: url('{{ $loginBg }}');">
        <div class="auth-frame">
            <div class="auth-card">
                @php
                    $supportedLocales = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getSupportedLocales();
                    $currentLocale = app()->getLocale();
                @endphp

                @if (count($supportedLocales) > 1 || View::hasSection('auth-top-actions'))
                    <div class="auth-top-actions">
                        @hasSection('auth-top-actions')
                            @yield('auth-top-actions')
                        @endif

                        @if (count($supportedLocales) > 1)
                            <nav class="language-switcher"
                                aria-label="{{ $currentLocale === 'ar' ? 'تغيير اللغة' : 'Change language' }}">
                                @foreach ($supportedLocales as $localeCode => $properties)
                                    <a href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}"
                                        class="{{ $currentLocale === $localeCode ? 'active' : '' }}"
                                        hreflang="{{ $localeCode }}">
                                        {{ strtoupper($localeCode) }}
                                    </a>
                                @endforeach
                            </nav>
                        @endif
                    </div>
                @endif

                <div class="brand-lockup">
                    <div class="brand-logo">
                        <img class="logo" src="{{ $loginLogo }}" alt="logo"
                            onerror="this.onerror=null;this.src='{{ asset('site/assets/images/company_logo.png') }}';">
                    </div>
                </div>

                <div class="form-heading">
                    <h1>@yield('auth-heading')</h1>
                    @hasSection('auth-subtitle')
                        <p class="subtitle">@yield('auth-subtitle')</p>
                    @endif
                </div>

                @if (session('status'))
                    <div class="form-status form-status-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="form-status form-status-error">{{ $errors->first() }}</div>
                @endif

                @yield('auth-body')

                @hasSection('auth-footer')
                    <div class="auth-links">
                        @yield('auth-footer')
                    </div>
                @endif
            </div>
        </div>

        <div class="footer">
            <span class="footer-copy">جميع الحقوق محفوظة لـ</span>
            <a href="{{ $footerUrl }}" target="_blank" rel="noopener" class="footer-brand" title="{{ $footerName }}">
                <img src="{{ $footerLogo }}" alt="{{ $footerName }}"
                    style="width:24px!important;height:24px!important;max-width:24px!important;max-height:24px!important;object-fit:contain!important;"
                    onerror="this.onerror=null;this.style.display='none';">
                <span>{{ $footerName }}</span>
            </a>
            <span class="footer-copy">© {{ date('Y') }}</span>
        </div>
    </div>

    <script src="{{ asset('admin') }}/assets/js/plugins.js"></script>
    <script src="{{ asset('admin') }}/assets/js/theme.js"></script>
    <script src="{{ asset('admin') }}/assets/vendor/toster/js/jquery.toast.js"></script>
    <script src="{{ asset('admin') }}/assets/js/custom.js"></script>
    @stack('auth-scripts')
</body>

</html>
