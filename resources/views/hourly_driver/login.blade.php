@extends('hourly_driver.layout')
@section('title', 'تسجيل دخول السائق')
@section('content')
<section class="login-card" aria-labelledby="login-title">
    <div class="login-brand-panel">
        <div class="brand-glow" aria-hidden="true"></div>
        <strong class="login-logo">{{ config('app.name') }}</strong>
        <div class="login-brand-copy">
            <span class="eyebrow light">بوابة الفريق الميداني</span>
            <h2>كل مشاويرك<br>في مكان واحد</h2>
            <p>تابع جدولك اليومي، مواقع العملاء وحالة مهام التوصيل والاستلام بسهولة.</p>
        </div>
        <div class="brand-feature"><span class="feature-icon">✓</span><span>وصول آمن ومخصص للسائقين</span></div>
    </div>

    <div class="login-form-panel">
        <div class="mobile-brand">
            <strong class="login-logo">{{ config('app.name') }}</strong>
            <span>{{ config('app.name') }} — السائق</span>
        </div>
        <span class="eyebrow">مرحبًا بعودتك</span>
        <h1 id="login-title">تسجيل دخول السائق</h1>
        <p class="login-intro">أدخل بيانات حسابك للوصول إلى جدول الرحلات والمهام.</p>

        @if($errors->any())
            <div class="notice error" role="alert">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.8 2.1 18a2 2 0 0 0 1.7 3h16.4a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0Z"/></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('hourly-driver.authenticate') }}" class="login-form">
            @csrf
            <label for="login">اسم المستخدم أو رقم الجوال</label>
            <div class="input-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z"/></svg>
                <input id="login" name="login" value="{{ old('login') }}" required maxlength="255" autocomplete="username" inputmode="text" dir="ltr" placeholder="اسم المستخدم أو 05xxxxxxxx">
            </div>

            <label for="password">كلمة المرور</label>
            <div class="input-wrap password-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10V8a5 5 0 0 1 10 0v2m-11 0h12a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2Zm6 4v3"/></svg>
                <input type="password" id="password" name="password" required maxlength="72" autocomplete="current-password" dir="ltr" placeholder="أدخل كلمة المرور">
                <button class="password-toggle" type="button" aria-label="إظهار كلمة المرور" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                </button>
            </div>

            <button class="primary-btn" type="submit">
                <span>تسجيل الدخول</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m10 17-5-5 5-5m-5 5h14"/></svg>
            </button>
        </form>

        <a class="admin-login-link" href="{{ route('admin.form.login') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 3 7v6c0 5 3.8 7.5 9 9 5.2-1.5 9-4 9-9V7l-9-4Zm0 5v5m0 4h.01"/></svg>
            دخول لوحة الإدارة
        </a>
        <p class="help-text">تواجه مشكلة في الدخول؟ تواصل مع مسؤول التشغيل.</p>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.querySelector('.password-toggle')?.addEventListener('click', function () {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.setAttribute('aria-pressed', show ? 'true' : 'false');
        this.setAttribute('aria-label', show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
    });
</script>
@endpush
