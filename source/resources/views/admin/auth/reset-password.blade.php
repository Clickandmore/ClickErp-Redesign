@extends('admin.auth.layout')

@section('auth-page-title', __('admin.change_password'))
@section('auth-heading', __('admin.change_password'))
@section('auth-subtitle', __('admin.new_pw_text'))

@section('auth-body')
    <form action="{{ route('admin.password.update') }}" method="POST" novalidate>
        @csrf
        <input type="hidden" name="email" value="{{ old('email', $email) }}">
        <input type="hidden" name="token" value="{{ old('token', $token) }}">

        <div class="form-group">
            <div class="input-field">
                <label for="reset-email">{{ __('auth.email') }}</label>
                <div class="input-shell">
                    <span class="input-icon">
                        <img src="{{ asset('admin/login') }}/imgs/email.svg" alt="email">
                    </span>
                    <input type="email" id="reset-email" value="{{ old('email', $email) }}" readonly>
                </div>
            </div>

            <div class="input-field">
                <label for="password">{{ __('admin.new_password') }}*</label>
                <div class="input-shell">
                    <span class="input-icon">
                        <img src="{{ asset('admin/login') }}/imgs/password.svg" alt="password">
                    </span>
                    <input type="password" id="password" name="password" placeholder="•••••••••••••••"
                        autocomplete="new-password" required>
                </div>
            </div>

            <div class="input-field">
                <label for="password_confirmation">{{ __('admin.confirm_new_password') }}*</label>
                <div class="input-shell">
                    <span class="input-icon">
                        <img src="{{ asset('admin/login') }}/imgs/password.svg" alt="password confirmation">
                    </span>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        placeholder="•••••••••••••••" autocomplete="new-password" required>
                </div>
            </div>
        </div>

        <button type="submit" class="primary-btn">{{ __('admin.save') }}</button>
    </form>
@endsection

@section('auth-footer')
    <a class="secondary-link" href="{{ route('admin.form.login') }}">{{ __('admin.sign_in') }}</a>
@endsection
