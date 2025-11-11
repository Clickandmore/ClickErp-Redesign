@extends('site.layouts.index')
@section('title')
    {{ __('ingaz.register') }}
@endsection
@section('content')
    <section class="accountForm">
        <div class="imageBG">
            <img src="{{ asset('site') }}/assets/images/s3.webp" alt=""/>
        </div>
        <div class="cardForm" data-aos="fade-up">
            <form id="form-register" action="{{ route('site.register.store') }}" method="post">
                @csrf
                <div class="loginLogo">
                    <img src="{{ asset('site') }}/assets/images/logo.svg" alt="img" class="img-fluid"/>
                </div>
                <div class="formGroup">
                    <label for="userName">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M12 12a6 6 0 1 0-6-6 6.006 6.006 0 0 0 6 6Zm0-10a4 4 0 1 1-4 4 4 4 0 0 1 4-4ZM12 14a9.01 9.01 0 0 0-9 9 1 1 0 0 0 2 0 7 7 0 0 1 14 0 1 1 0 0 0 2 0 9.01 9.01 0 0 0-9-9Z">
                                </path>
                            </svg>
                        </div>
                        {{ __('ingaz.full_name') }}
                    </label>
                    <input type="text" class="form-control" autocomplete="new-name" name="name" id="userName"
                           placeholder=" {{__('ingaz.enter_full_name')}} "/>
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
                        {{ __('ingaz.phone_number') }}
                    </label>
                    <div class="inputDiv ltr">
                        <span> +966 </span>
                        <input type="tel" class="form-control" autocomplete="new-phone" name="phone" id="phoneNum"
                               placeholder="05 000 0000"/>
                    </div>
                </div>
                {{-- <div class="formGroup">
                    <label for="email">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M19 1H5a5.006 5.006 0 0 0-5 5v12a5.006 5.006 0 0 0 5 5h14a5.006 5.006 0 0 0 5-5V6a5.006 5.006 0 0 0-5-5ZM5 3h14a3 3 0 0 1 2.78 1.887l-7.658 7.659a3.007 3.007 0 0 1-4.244 0L2.22 4.887A3 3 0 0 1 5 3Zm14 18H5a3 3 0 0 1-3-3V7.5l6.464 6.46a5.007 5.007 0 0 0 7.072 0L22 7.5V18a3 3 0 0 1-3 3Z"
                                ></path>
                            </svg>
                        </div>
                        {{__('ingaz.email')}}
                    </label>
                    <input type="email" class="form-control" name="email" id="email"
                           placeholder="{{__('ingaz.enter_email')}}"/>
                </div> --}}
                <div class="formGroup">
                    <label for="password">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M19 8.424V7A7 7 0 0 0 5 7v1.424A5 5 0 0 0 2 13v6a5.006 5.006 0 0 0 5 5h10a5.006 5.006 0 0 0 5-5v-6a5 5 0 0 0-3-4.576ZM7 7a5 5 0 0 1 10 0v1H7Zm13 12a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3Z">
                                </path>
                                <path d="M12 14a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-1-1Z"></path>
                            </svg>
                        </div>
                        {{ __('ingaz.password') }}
                    </label>

                    <div class="password-wrapper" style="position: relative;">
                        <input type="password" class="form-control" autocomplete="new-password"
                               name="password" id="password"
                               placeholder="{{ __('ingaz.enter_password') }}"
                               style="padding-inline-end: 40px;"/>
                        <button type="button" class="toggle-password" data-target="password" style="background:none;border:none;position:absolute;top:50%;transform:translateY(-50%);padding:0;cursor:pointer;">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                                <!-- العين المفتوحة -->
                                <path fill="currentColor"
                                      d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="formGroup">
                    <label for="confirmPassword">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M19 8.424V7A7 7 0 0 0 5 7v1.424A5 5 0 0 0 2 13v6a5.006 5.006 0 0 0 5 5h10a5.006 5.006 0 0 0 5-5v-6a5 5 0 0 0-3-4.576ZM7 7a5 5 0 0 1 10 0v1H7Zm13 12a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3Z">
                                </path>
                                <path d="M12 14a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-1-1Z"></path>
                            </svg>
                        </div>
                        {{ __('ingaz.confirm_password') }}
                    </label>

                    <div class="password-wrapper" style="position: relative;">
                        <input type="password" class="form-control" autocomplete="new-password"
                               id="confirmPassword" name="password_confirmation"
                               placeholder="{{ __('ingaz.enter_password') }}"
                               style="padding-inline-end: 40px;"/>
                        <button type="button" class="toggle-password" data-target="confirmPassword"
                                style="background:none;border:none;position:absolute;top:50%;transform:translateY(-50%);padding:0;cursor:pointer;">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                                <!-- العين المفتوحة -->
                                <path fill="currentColor"
                                      d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/>
                            </svg>
                        </button>
                    </div>
                </div>


                {!! NoCaptcha::renderJs(app()->getLocale()) !!}
                {!! NoCaptcha::display() !!}

                @error('g-recaptcha-response')
                <span class="text-danger">{{ $message }}</span>
                @enderror
                <button type="submit" class="defaultBtn mt-2">{{ __('ingaz.create') }}</button>
                <p class="text-center w-100 mt-3">
                    {{ __('ingaz.have_account') }}
                    <a href="{{ route('site.login.index') }}"> {{ __('ingaz.login') }} </a>
                </p>
            </form>
        </div>
    </section>
@endsection
@section('js')
    <script>
        $(document).ready(function () {
            $('#form-register').on('submit', function (e) {
                e.preventDefault();
                let formData = new FormData(this);
                let url = $(this).attr('action');
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        Toastify({
                            text: response.message,
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                            stopOnFocus: true
                        }).showToast();
                        setTimeout(function () {
                            window.location.href = response.data.redirect_url;
                        }, 1000);
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessages = '';
                            for (let key in errors) {
                                errorMessages +=
                                    `<p style="margin: 0 0 5px; color: red; text-align: center;">${errors[key][0]}</p>`;
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
                    }

                });
            })
        })
    </script>
    <script>
        document.querySelectorAll('.toggle-password').forEach(btn => {
            const langDir = document.documentElement.getAttribute('dir') || 'ltr';
            const input = document.getElementById(btn.dataset.target);
            const svg = btn.querySelector('svg');

            // ضبط مكان العين حسب اللغة
            if (langDir === 'rtl') {
                btn.style.left = '10px';
                btn.style.right = 'auto';
            } else {
                btn.style.right = '10px';
                btn.style.left = 'auto';
            }

            btn.addEventListener('click', function () {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';

                // تغيير الأيقونة بين مفتوحة ومقفولة
                svg.innerHTML = isPassword
                    ? '<path fill="currentColor" d="M2 5.27L3.28 4 20 20.72 18.73 22l-2.35-2.35A10.91 10.91 0 0 1 12 19c-5 0-8.27-3.11-10-7a11.58 11.58 0 0 1 3.26-4.36L2 5.27zM12 8a4 4 0 0 1 4 4 3.9 3.9 0 0 1-.45 1.82l-1.5-1.5A1.9 1.9 0 0 0 14 12a2 2 0 0 0-2-2 1.9 1.9 0 0 0-.32.05l-1.5-1.5A3.9 3.9 0 0 1 12 8z"/>'
                    : '<path fill="currentColor" d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/>';
            });
        });
    </script>
@endsection
