@extends('admin.auth.layout')

@section('auth-page-title', __('admin.forgot_pw'))
@section('auth-heading', __('admin.forgot_pw'))
@section('auth-subtitle', __('admin.provide_email'))

@section('auth-body')
    <form action="{{ route('admin.password.email') }}" method="POST" novalidate>
        @csrf
        <div class="form-group">
            <div class="input-field">
                <label for="email">{{ __('auth.email') }}*</label>
                <div class="input-shell">
                    <span class="input-icon">
                        <img src="{{ asset('admin/login') }}/imgs/email.svg" alt="email">
                    </span>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                        placeholder="{{ __('auth.placeholderEmail') }}" autocomplete="email" required>
                </div>
            </div>
        </div>

        <button type="submit" class="primary-btn">{{ __('admin.forgot_pw_btn') }}</button>
    </form>
@endsection

@section('auth-footer')
    <a class="secondary-link" href="{{ route('admin.form.login') }}">{{ __('admin.sign_in') }}</a>
@endsection
