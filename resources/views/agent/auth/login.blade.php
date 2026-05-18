@extends('admin.auth.layout')

@php
    $authLocale = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocale() ?: app()->getLocale();
@endphp

@section('auth-page-title', $authLocale === 'ar' ? 'تسجيل دخول الوكلاء' : 'Agent Login')
@section('auth-heading', $authLocale === 'ar' ? 'لوحة الوكلاء الخارجيين' : 'External Agent Dashboard')
@section('auth-subtitle', $authLocale === 'ar' ? 'سجل دخولك إلى مساحة عمل المكتب الخارجي' : 'Sign in to the external office workspace')

@section('auth-body')
    <form
        action="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL(app()->getLocale(), route('agent.login'), [], true) }}"
        method="POST" novalidate>
        @csrf
        <div class="form-group">
            <div class="input-field">
                <label for="login">{{ $authLocale === 'ar' ? 'البريد الإلكتروني أو رقم الجوال' : 'Email or phone number' }}*</label>
                <div class="input-shell">
                    <span class="input-icon">
                        <img src="{{ asset('admin/login') }}/imgs/email.svg" alt="login">
                    </span>
                    <input type="text" id="login" name="login" value="{{ old('login') }}"
                        placeholder="agent@example.com" autocomplete="username" required>
                </div>
            </div>

            <div class="input-field">
                <label for="password">{{ $authLocale === 'ar' ? 'كلمة المرور' : 'Password' }}*</label>
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

        <button type="submit" class="primary-btn">{{ $authLocale === 'ar' ? 'تسجيل الدخول' : 'Sign In' }}</button>
    </form>
@endsection

@section('auth-footer')
    <a class="secondary-link"
        href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL(app()->getLocale(), route('admin.form.login'), [], true) }}">
        {{ $authLocale === 'ar' ? 'تسجيل دخول الإدارة' : 'Admin Login' }}
    </a>
@endsection
