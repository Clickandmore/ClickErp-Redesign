<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('admin.superadmin.partials.head', ['pageTitle' => 'دخول لوحة Super Admin التطويرية'])
</head>
<body class="sa-login">
<main class="login-shell">
    <div class="login-brand">
        <div class="login-mark">SA</div>
        <div>
            <h1>لوحة Super Admin التطويرية</h1>
            <p>دخول مستقل عن حسابات الموظفين ولوحة الإدارة الحالية</p>
        </div>
    </div>

    <form class="login-panel" method="POST" action="{{ route('superadmin.login.post') }}">
        @csrf

        @if (isset($errors) && $errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        @if (session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        <label class="field">
            <span>اسم الدخول</span>
            <input type="text" name="username" value="{{ old('username') }}" autocomplete="username" required autofocus>
        </label>

        <label class="field">
            <span>كلمة المرور</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>

        <button class="login-submit" type="submit">دخول اللوحة</button>

        <div class="login-note">
            تستخدم هذه اللوحة لأعمال التطوير والتحقق الفني، وتعمل بجلسة منفصلة عن جلسة الإدارة العادية.
        </div>
    </form>
</main>
</body>
</html>
