<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0a4d91">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'بوابة السائق') | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/hourly-driver.css') }}?v={{ filemtime(public_path('css/hourly-driver.css')) }}">
</head>
<body class="@hasSection('portal') portal-page @else auth-page @endif">
@include('components.submission-guard')
@if(request()->routeIs('hourly-maid.*') && ($maidImpersonator = session('hourly_maid_impersonator')))
    <div role="status" style="background:#fef3c7;color:#92400e;padding:12px;text-align:center">
        أنت تتصفح بوابة العاملة بحساب <strong>{{ auth('hourly_maid')->user()?->name }}</strong> بصفتك المشرف {{ $maidImpersonator['name'] }}. أي إجراء هنا يُسجَّل باسم العاملة.
        <form method="POST" action="{{ route('hourly-maid.logout') }}" style="display:inline">
            @csrf
            <button type="submit">إنهاء والعودة للوحة</button>
        </form>
    </div>
@endif

@hasSection('portal')
    @if ($impersonator = session('hourly_driver_impersonator'))
        {{-- جلسة فتحها مشرف من جدول السائقين؛ الخروج يعيده إلى اللوحة --}}
        <div role="status" style="background:#fef3c7;color:#92400e;border-bottom:1px solid #fde68a;padding:10px 16px;text-align:center;font-size:14px;font-weight:600">
            أنت تتصفح بوابة السائق بحساب <strong>{{ auth('hourly_driver')->user()?->name }}</strong> بصفتك المشرف {{ $impersonator['name'] }}. أي إجراء هنا يُسجَّل باسم السائق.
            <form method="POST" action="{{ route('hourly-driver.logout') }}" style="display:inline;margin-inline-start:8px">
                @csrf
                <button type="submit" style="background:#92400e;color:#fff;border:0;border-radius:8px;padding:4px 12px;font:inherit;cursor:pointer">إنهاء والعودة للوحة</button>
            </form>
        </div>
    @endif
    <div class="page-shell">
        <header class="topbar">
            <a class="brand" href="{{ route('hourly-driver.home') }}" aria-label="العودة إلى لوحة السائق">
                <strong class="login-logo">{{ config('app.name') }}</strong>
                <span><strong>{{ config('app.name') }} — السائق</strong><small>بوابة سائقي الساعات</small></span>
            </a>
            <div class="top-actions">
                <a class="admin-shortcut" href="{{ route('admin.form.login') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 3 7v6c0 5 3.8 7.5 9 9 5.2-1.5 9-4 9-9V7l-9-4Z"/></svg>
                    <span>دخول لوحة الإدارة</span>
                </a>
                <form class="logout-form" method="POST" action="{{ route('hourly-driver.logout') }}">
                    @csrf
                    <button class="logout-btn" type="submit">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M21 19V5a2 2 0 0 0-2-2h-5a2 2 0 0 0-2 2v2m0 10v2a2 2 0 0 0 2 2h5a2 2 0 0 0 2-2Z"/></svg>
                        <span>تسجيل الخروج</span>
                    </button>
                </form>
            </div>
        </header>
        <main class="portal-content">@yield('content')</main>
        <footer class="portal-footer">{{ config('app.name') }} للخدمات &copy; {{ date('Y') }}</footer>
    </div>
@else
    <main class="auth-shell">@yield('content')</main>
@endif

@stack('scripts')
</body>
</html>
