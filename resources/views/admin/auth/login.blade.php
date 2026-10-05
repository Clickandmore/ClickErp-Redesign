@extends('admin.auth.layout')

@section('auth-page-title', __('auth.login'))
@section('auth-heading', __('auth.login'))
@section('auth-subtitle', app()->getLocale() === 'ar' ? 'سجل دخولك للوصول السريع إلى لوحة التحكم.' : 'Sign in for quick
    access to the dashboard.')

@section('auth-top-actions')
    <a href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL(app()->getLocale(), route('agent.login.form'), [], true) }}"
        class="agent-login-icon"
        title="{{ app()->getLocale() === 'ar' ? 'تسجيل دخول الوكلاء' : 'Agent Login' }}"
        aria-label="{{ app()->getLocale() === 'ar' ? 'تسجيل دخول الوكلاء' : 'Agent Login' }}">
        <i class="fa fa-user" aria-hidden="true"></i>
    </a>
@endsection

@section('auth-body')
    @if (\App\Support\SystemModuleManager::usesRedesign())
        <div class="auth-role-label">
            <i class="fa fa-shield" aria-hidden="true"></i>
            <span>{{ app()->getLocale() === 'ar' ? 'دخول موظفي الإدارة' : 'Staff administration login' }}</span>
        </div>
    @endif
    <form id="loginForm" action="{{ route('admin.login') }}" method="POST" novalidate>
        @csrf
        <div class="form-alert" role="alert" aria-live="assertive"></div>
        <div class="form-group">
            <div class="input-field">
                <label for="email">البريد الإلكتروني أو اسم المستخدم*</label>
                <div class="input-shell">
                    <span class="input-icon">
                        <img src="{{ asset('admin/login') }}/imgs/email.svg" alt="email">
                    </span>
                    <input type="text" id="email" name="login" value="{{ old('login') }}"
                        placeholder="{{ __('auth.email') }}" autocomplete="username" required>
                </div>
            </div>
            <div class="input-field">
                <label for="password">{{ __('auth.password') }}*</label>
                <div class="input-shell">
                    <span class="input-icon">
                        <img src="{{ asset('admin/login') }}/imgs/password.svg" alt="password">
                    </span>
                    <input type="password" id="password" name="password" class="password-text-input"
                        placeholder="{{ __('auth.password_placeholder') }}" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" id="passwordToggle"
                        aria-label="{{ app()->getLocale() === 'ar' ? 'إظهار كلمة المرور' : 'Show password' }}"
                        aria-pressed="false">
                        <i class="fa fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="form-meta">
            <label for="rememberme" class="checkbox-control">
                <input type="checkbox" name="rememberme" id="rememberme">
                <span>{{ __('auth.rememberme') }}</span>
            </label>
            <span class="meta-hint">
                {{ app()->getLocale() === 'ar' ? 'يبقى الدخول فعالاً على أجهزتك الموثوقة.' : 'Keep access active on your trusted devices.' }}
            </span>
        </div>
        <button type="submit" class="primary-btn">{{ __('auth.sign in') }}</button>
    </form>
@endsection

@section('auth-footer')
    <a class="secondary-link" href="{{ route('admin.password.request') }}">{{ __('admin.forgot_pw') }}</a>
@endsection

@push('auth-scripts')
    <script type="text/javascript">
        $(function() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');

            if (passwordInput && passwordToggle) {
                const eyeIcon = passwordToggle.querySelector('i');
                const showLabel = @json(app()->getLocale() === 'ar' ? 'إظهار كلمة المرور' : 'Show password');
                const hideLabel = @json(app()->getLocale() === 'ar' ? 'إخفاء كلمة المرور' : 'Hide password');

                passwordToggle.addEventListener('click', function() {
                    const isHidden = passwordInput.type === 'password';

                    passwordInput.type = isHidden ? 'text' : 'password';
                    passwordToggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                    passwordToggle.setAttribute('aria-label', isHidden ? hideLabel : showLabel);

                    if (eyeIcon) {
                        eyeIcon.className = isHidden ? 'fa fa-eye-slash' : 'fa fa-eye';
                    }
                });
            }

            $(document).on("submit", "#loginForm", function(e) {
                e.preventDefault();
                let formObj = $(this);
                formObj.find("[type='submit']").html("{{ __('auth.sign in') }}...");
                $.ajax({
                    url: formObj.attr('action'),
                    data: formObj.serialize(),
                    type: "POST",
                    dataType: 'json',
                    success: function(result) {
                        formObj.find("[type='submit']").html("{{ __('auth.sign in') }}");
                        $('.form-alert').html(result.message)
                        if (result.code === 200) {
                            successToster('{{ __('auth.done successfully') }}', result
                            .message);
                            window.location.href = result.data.url;
                        } else if (result.code === 401) {
                            warningToster('{{ __('auth.an error occurred') }}', result.message)
                        } else {
                            warningToster('{{ __('auth.an error occurred') }}', result.message)
                        }
                    },
                    error: function(errorObj) {
                        formObj.find("[type='submit']").html("{{ __('auth.sign in') }}");
                        if (errorObj.status === 500) {
                            errorToster('{{ __('auth.an error occurred') }}',
                                "{{ __('auth.code error') }}");
                        } else if (errorObj.status == 422) {
                            let errorsObj = $.parseJSON(errorObj.responseText);
                            let listObject = errorsObj.errors;
                            let testArr = [];
                            $.each(listObject, function(errorKey, errorArr) {
                                $.each(errorArr, function(key, value) {
                                    testArr.push(value);
                                });
                            });
                            validToster('{{ __('auth.an error occurred') }}', testArr)
                        }
                    }
                });
                return false;
            });
        });
    </script>
@endpush
