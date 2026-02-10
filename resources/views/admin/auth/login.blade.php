<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($developSettings) ? $developSettings->name : 'بلاتنيوم الشرقية للاستقدام' }} |
        {{ __('auth.login') }}</title>
    <link rel="stylesheet" href="{{ asset('admin/login') }}/css/style-dashboard.css?v=1">
    <link rel="stylesheet" href="{{ asset('admin/login') }}/css/fontawesome.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/vendor/toster/css/jquery.toast.css">
</head>

<body @if (app()->getLocale() == 'ar') dir="rtl" @endif>
    @php
        $loginBg = null;
        if (isset($settings) && !empty($settings->cover_image)) {
            $loginBg = showFile($settings->cover_image);
        } elseif (isset($settings) && !empty($settings->light_image)) {
            $loginBg = showFile($settings->light_image);
        } else {
            $loginBg = asset('site/assets/images/login_image.jpg');
        }
    @endphp
    <div class="auth-page" style="--login-bg-image: url('{{ $loginBg }}');">
        @php
            $formSubtitle =
                app()->getLocale() === 'ar'
                    ? 'سجل دخولك للوصول السريع إلى لوحة التحكم.'
                    : 'Sign in for quick access to the dashboard.';
        @endphp
        <div class="auth-frame">
            <div class="auth-card">
                <div class="brand-lockup">
                    <div class="brand-logo">
                        @if (isset($developSettings) && !empty($developSettings->logo_login))
                            <img class="logo" src="{{ showFile($developSettings->logo_login) }}" alt="">
                        @else
                            <img class="logo" src="{{ asset('site/assets/images/company_logo.png') }}" alt="">
                        @endif
                    </div>
                    {{-- <div class="brand-text">
                        <p class="eyebrow">
                            {{ isset($developSettings) ? $developSettings->name : 'بلاتنيوم الشرقية للاستقدام' }}
                        </p>
                        <p class="subtext">{{ __('auth.login') }}</p>
                    </div> --}}
                </div>
                <div class="form-heading">
                    <h1>{{ __('auth.login') }}</h1>
                    <p class="subtitle">{{ $formSubtitle }}</p>
                </div>
                <form id="loginForm" action="{{ route('admin.login') }}" method="POST" novalidate>
                    @csrf
                    <div class="form-alert" role="alert" aria-live="assertive"></div>
                    <div class="form-group">
                        <div class="input-field">
                            <label for="email">{{ __('auth.email') }}*</label>
                            <div class="input-shell">
                                <span class="input-icon">
                                    <img src="{{ asset('admin/login') }}/imgs/email.svg" alt="email">
                                </span>
                                <input type="email" id="email" name="login" value="{{ old('login') }}"
                                    placeholder="{{ __('auth.placeholderEmail') }}" autocomplete="username" required>
                            </div>
                        </div>
                        <div class="input-field">
                            <label for="password">{{ __('auth.password') }}*</label>
                            <div class="input-shell">
                                <span class="input-icon">
                                    <img src="{{ asset('admin/login') }}/imgs/password.svg" alt="password">
                                </span>
                                <input type="password" id="password" name="password" placeholder="•••••••••••••••"
                                    autocomplete="current-password" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-meta">
                        <label for="rememberme" class="checkbox-control">
                            <input type="checkbox" name="rememberme" id="rememberme">
                            <span>{{ __('auth.rememberme') }}</span>
                        </label>
                        <span
                            class="meta-hint">{{ app()->getLocale() === 'ar' ? 'يبقى الدخول فعالاً على أجهزتك الموثوقة.' : 'Keep access active on your trusted devices.' }}</span>
                    </div>
                    <button type="submit" class="primary-btn">{{ __('auth.sign in') }}</button>
                </form>
            </div>
        </div>
        <p class="footer">
            {{ __('auth.copyright') }} © {{ date('Y') }}
            <a href="{{ isset($developSettings) ? $developSettings->portfolio_url : 'https://clickandmore.sa' }}"
                target="_blank">{{ isset($developSettings) ? $developSettings->name : 'بلاتنيوم الشرقية للاستقدام' }}</a>
        </p>
    </div>
    <!-- orbit -->
    <script src="https://unpkg.com/bootstrap-show-password@1.2.1/dist/bootstrap-show-password.min.js"></script>
    <script>
        $(function() {
            $('#password').password()
        })
    </script>
    <script src="{{ asset('admin') }}/assets/js/plugins.js"></script>
    <script src="{{ asset('admin') }}/assets/js/theme.js"></script>
    <script src="{{ asset('admin') }}/assets/vendor/toster/js/jquery.toast.js"></script>
    <script src="{{ asset('admin') }}/assets/js/custom.js"></script>
    <script type="text/javascript">
        $(function() {

            console.clear()
            /*------------------------------------------
            --------------------------------------------
            Submit Event
            --------------------------------------------
            --------------------------------------------*/
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
                        console.log("success =  200")
                        formObj.find("[type='submit']").html("{{ __('auth.sign in') }}");
                        $('.form-alert').html(result.message)
                        if (result.code === 200) {
                            successToster('{{ __('auth.done successfully') }}', result
                                .message);
                            // console.log(result.data.url)
                            window.location.href = result.data.url;
                        } else if (result.code === 401) {
                            warningToster('{{ __('auth.an error occurred') }}', result.message)
                        } else {
                            warningToster('{{ __('auth.an error occurred') }}', result.message)
                        }
                    },
                    error: function(errorObj, errorText, errorThrown) {
                        console.log("error =  500")
                        formObj.find("[type='submit']").html("login");
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
                            // console.log(testArr)
                            validToster('{{ __('auth.an error occurred') }}', testArr)
                        }

                    }
                });
                return false;
            });

        });
    </script>
</body>

</html>
