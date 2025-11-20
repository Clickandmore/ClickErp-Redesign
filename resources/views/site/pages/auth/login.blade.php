@extends('site.layouts.index')
@section('title')
    {{ __('ingaz.login') }}
@endsection
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

@section('content')
    <section class="accountForm">
        <div class="imageBG">
            <img src="{{ asset('site') }}/assets/images/s3.webp" alt="" />
        </div>
        <div class="cardForm" data-aos="fade-up">
            <form id="form-login" action="{{ route('site.login.store') }}" method="post">
                @csrf
                <div class="loginLogo">
                    <img src="{{ asset('site') }}/assets/images/logo.svg" alt="img" class="img-fluid" />
                </div>
                <div class="formGroup">
                    <label for="phoneNum">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M15.045 0h-6.09A5.006 5.006 0 0 0 4 4.999v14c0 2.757 2.243 5 5 5h6c2.757 0 5-2.243 5-5V5a5.007 5.007 0 0 0-4.955-5ZM18 18.999c0 1.654-1.346 3-3 3H9c-1.654 0-3-1.346-3-3V5a3.006 3.006 0 0 1 2.411-2.942l.694 1.389A1 1 0 0 0 10 4h4c.379 0 .725-.214.895-.553l.694-1.389A3.005 3.005 0 0 1 18 5v14Zm-5 1h-2a1 1 0 0 1 0-2h2a1 1 0 0 1 0 2Z">
                                </path>
                            </svg>
                        </div>
                        {{ __('ingaz.phone') }}
                    </label>
                    <div class="inputDiv ltr">
                        <span> +966 </span>
                        <input type="tel" autocomplete="off" class="form-control" name="phone" id="phoneNum"
                            placeholder="5 000 0000" />
                    </div>
                </div>
                <div class="formGroup" style="position: relative;">
                    <label for="password">
                        <div class="icon">
                            <svg viewBox="0 0 24 24" width="20" height="20">
                                <path
                                    d="M19 8.424V7A7 7 0 0 0 5 7v1.424A5 5 0 0 0 2 13v6a5.006 5.006 0 0 0 5 5h10a5.006 5.006 0 0 0 5-5v-6a5 5 0 0 0-3-4.576ZM7 7a5 5 0 0 1 10 0v1H7Zm13 12a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3Z">
                                </path>
                                <path d="M12 14a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-1-1Z"></path>
                            </svg>
                        </div>
                        {{ __('ingaz.password') }}
                    </label>
                    <input type="password" autocomplete="new-password" class="form-control" id="password"
                        name="password" />
                    <span id="togglePassword" style="position: absolute; left: 10px; top: 38px; cursor: pointer;">
                        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20"
                            height="20">
                            <path fill="currentColor"
                                d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z" />
                        </svg>
                    </span>
                </div>

                <a href="{{ route('site.forget-password.index') }}"> {{ __('ingaz.password_forget') }} </a>
                {!! NoCaptcha::renderJs(app()->getLocale()) !!}
                {!! NoCaptcha::display() !!}
                @error('g-recaptcha-response')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <button type="submit" class="defaultBtn mt-2">
                    {{ __('ingaz.login') }}
                </button>
                <p class="text-center">
                    {{ __('ingaz.dont_have_account') }} <a href="{{ route('site.register.index') }}">
                        {{ __('ingaz.create_account') }}</a>
                </p>
            </form>
        </div>
    </section>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            $('#form-login').on('submit', function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let url = $(this).attr('action');
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $('#form-login button[type="submit"]').html('<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>');
                    },
                    success: function(response) {
                        if (!response.success) {
                            Toastify({
                                text: response.message,
                                duration: 4000,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "linear-gradient(to right, #e53935, #b71c1c)"
                            }).showToast();
                        } else {
                            Toastify({
                                text: response.message,
                                duration: 4000,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)"
                            }).showToast();

                            setTimeout(function() {
                                let target = response.result || '/'; // fallback
                                window.location.href = target;
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let res = xhr.responseJSON;
                            let errorMessages = '';

                            if (res.errors) {
                                for (let key in res.errors) {
                                    errorMessages +=
                                        `<p style="margin: 0 0 5px; color: red; text-align: center;">${res.errors[key][0]}</p>`;
                                }
                            } else if (res.message) {
                                // هنا بنعرض الرسالة القادمة من backend مثل incorrect_password
                                errorMessages =
                                    `<p style="margin: 0; color: red; text-align: center;">${res.message}</p>`;
                            }

                            Swal.fire({
                                icon: 'error',
                                title: '{{ __('ingaz.error') }}',
                                html: `<div>${errorMessages}</div>`,
                            });
                        } else {
                            Swal.fire('{{ __('ingaz.error') }}',
                                '{{ __('ingaz.error_from_server') }}', 'error');
                        }
                    },
                    complete: function() {
                        $('#form-login button[type="submit"]').html('{{ __('ingaz.login') }}');
                    }
                });
            })
        })
    </script>
    <script>
        const passwordInput = document.getElementById("password");
        const togglePassword = document.getElementById("togglePassword");
        const eyeIcon = document.getElementById("eyeIcon");

        togglePassword.addEventListener("click", function() {
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);

            // تغيير شكل الأيقونة حسب الحالة
            eyeIcon.innerHTML = type === "password" ?
                '<path fill="currentColor" d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/>' :
                '<path fill="currentColor" d="M2 5.27 3.28 4 20 20.72 18.73 22 15.94 19.21A10.6 10.6 0 0 1 12 20c-7 0-10-7-10-7a18.1 18.1 0 0 1 3.34-4.37L2 5.27zM12 8a4 4 0 0 1 4 4 3.9 3.9 0 0 1-.45 1.82l-1.5-1.5A1.9 1.9 0 0 0 14 12a2 2 0 0 0-2-2 1.9 1.9 0 0 0-.32.05l-1.5-1.5A3.9 3.9 0 0 1 12 8z"/>';
        });
    </script>
@endsection
