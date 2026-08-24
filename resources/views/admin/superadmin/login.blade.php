<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>دخول لوحة Super Admin التطويرية</title>
    <style>
        :root {
            --bg: #eef3f5;
            --surface: #ffffff;
            --border: #d7e1e7;
            --text: #172033;
            --muted: #65758b;
            --accent: #0f766e;
            --accent-dark: #115e59;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --danger-text: #991b1b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .55), rgba(238, 243, 245, .9)),
                var(--bg);
            color: var(--text);
            font-family: Tahoma, Arial, sans-serif;
        }

        .login-shell {
            width: min(460px, calc(100% - 32px));
        }

        .login-brand {
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .login-mark {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: #0f766e;
            color: #fff;
            font-weight: 900;
            font-size: 1.1rem;
        }

        .login-brand h1 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 900;
        }

        .login-brand p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: .9rem;
        }

        .login-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 18px 45px rgba(23, 32, 51, 0.08);
            padding: 24px;
        }

        .field {
            display: grid;
            gap: 8px;
            margin-bottom: 16px;
        }

        .field span {
            font-size: .88rem;
            font-weight: 900;
            color: #263449;
        }

        .field input {
            width: 100%;
            min-height: 48px;
            border: 1px solid #c8d4dc;
            border-radius: 10px;
            padding: 0 14px;
            color: var(--text);
            font: inherit;
            background: #fff;
        }

        .field input:focus {
            outline: none;
            border-color: #58aaa3;
            box-shadow: 0 0 0 4px rgba(15, 118, 110, .10);
        }

        .login-submit {
            width: 100%;
            min-height: 48px;
            border: 0;
            border-radius: 10px;
            background: var(--accent);
            color: #fff;
            font-weight: 900;
            cursor: pointer;
        }

        .login-submit:hover {
            background: var(--accent-dark);
        }

        .error {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid var(--danger-border);
            background: var(--danger-bg);
            color: var(--danger-text);
            line-height: 1.7;
        }

        .success {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            line-height: 1.7;
        }

        .login-note {
            margin-top: 14px;
            color: var(--muted);
            font-size: .86rem;
            line-height: 1.8;
        }
    </style>
</head>
<body>
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
