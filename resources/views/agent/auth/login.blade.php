@extends('admin.auth.layout')

@section('auth-page-title', 'تسجيل دخول الوكيل')
@section('auth-heading', 'لوحة الوكيل الخارجي')
@section('auth-subtitle', 'تسجيل دخول المكاتب الخارجية')

@section('auth-body')
    <form action="{{ route('agent.login') }}" method="POST" novalidate>
        @csrf
        <div class="form-group">
            <div class="input-field">
                <label for="login">البريد الإلكتروني أو رقم الهاتف*</label>
                <div class="input-shell">
                    <span class="input-icon">
                        <img src="{{ asset('admin/login') }}/imgs/email.svg" alt="login">
                    </span>
                    <input type="text" id="login" name="login" value="{{ old('login') }}"
                        placeholder="agent@example.com" autocomplete="username" required>
                </div>
            </div>

            <div class="input-field">
                <label for="password">كلمة المرور*</label>
                <div class="input-shell">
                    <span class="input-icon">
                        <img src="{{ asset('admin/login') }}/imgs/password.svg" alt="password">
                    </span>
                    <input type="password" id="password" name="password" class="password-text-input"
                        placeholder="********" autocomplete="current-password" required>
                </div>
            </div>
        </div>

        <div class="form-meta">
            <label for="remember" class="checkbox-control">
                <input type="checkbox" name="remember" id="remember" value="1">
                <span>{{ __('auth.rememberme') }}</span>
            </label>
        </div>

        <button type="submit" class="primary-btn">دخول</button>
    </form>
@endsection

@section('auth-footer')
    <a class="secondary-link" href="{{ route('admin.form.login') }}">دخول الإدارة</a>
@endsection
