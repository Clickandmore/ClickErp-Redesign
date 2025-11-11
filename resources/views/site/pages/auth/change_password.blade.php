@extends('site.layouts.index')
@section('title')
    {{ __('ingaz.change_password') }}
@endsection
@section('content')
    <section class="accountForm">
        <div class="imageBG">
            <img src="{{ asset('site') }}/assets/images/s3.webp" alt="" />
        </div>
        <div class="cardForm" data-aos="fade-up">
            <form id="change-password-form" method="post" action="{{ route('site.change-password.store') }}">
                @csrf
                <div class="loginLogo">
                    {{--                    <button class="back" type="button" onclick="window.history.back()"> --}}
                    {{--                        رجوع --}}
                    {{--                    </button> --}}
                    <img src="{{ asset('site') }}/assets/images/logo.svg" alt="img" class="img-fluid" />
                </div>
                <h3 class="text-center fw-bold text-uppercase">
                    {{ __('ingaz.change_password') }}
                </h3>
                <p class="text-center mb-3">
                    {{ __('ingaz.Your password should be strong and easy to remember') }}
                </p>
                <div class="formGroup">
                    <label for="password">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M19 8.424V7A7 7 0 0 0 5 7v1.424A5 5 0 0 0 2 13v6a5.006 5.006 0 0 0 5 5h10a5.006 5.006 0 0 0 5-5v-6a5 5 0 0 0-3-4.576ZM7 7a5 5 0 0 1 10 0v1H7Zm13 12a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3Z"></path>
                                <path d="M12 14a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-1-1Z"></path>
                            </svg>
                        </div>
                        {{ __('ingaz.password') }}
                    </label>
                    <div class="password-wrapper" style="position: relative;">
                        <input type="password" class="form-control" id="password" name="password" style="padding-inline-end:40px;" />
                        <button type="button" class="toggle-password" data-target="password"
                                style="background:none;border:none;position:absolute;top:50%;transform:translateY(-50%);padding:0;cursor:pointer;">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                                <path fill="currentColor"
                                      d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="formGroup">
                    <label for="password_confirmation">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M19 8.424V7A7 7 0 0 0 5 7v1.424A5 5 0 0 0 2 13v6a5.006 5.006 0 0 0 5 5h10a5.006 5.006 0 0 0 5-5v-6a5 5 0 0 0-3-4.576ZM7 7a5 5 0 0 1 10 0v1H7Zm13 12a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3Z"></path>
                                <path d="M12 14a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-1-1Z"></path>
                            </svg>
                        </div>
                        {{ __('ingaz.confirm_password') }}
                    </label>
                    <div class="password-wrapper" style="position: relative;">
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" style="padding-inline-end:40px;" />
                        <button type="button" class="toggle-password" data-target="password_confirmation"
                                style="background:none;border:none;position:absolute;top:50%;transform:translateY(-50%);padding:0;cursor:pointer;">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
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
                <input type="hidden" name="phone" value="{{ $phone }}">
                <button type="submit" class="defaultBtn mt-2"> {{ __('ingaz.send') }}</button>
            </form>
        </div>
    </section>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            $('#change-password-form').on('submit', function(e) {
                e.preventDefault();
                let formData = new FormData(this);
                let url = $(this).attr('action');
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Toastify({
                            text: response.message,
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                            stopOnFocus: true
                        }).showToast();
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 1000);
                    },
                    error: function(xhr) {
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

                // تبديل الأيقونة بين العين المفتوحة والمقفولة
                svg.innerHTML = isPassword
                    ? '<path fill="currentColor" d="M2 5.27L3.28 4 20 20.72 18.73 22l-2.35-2.35A10.91 10.91 0 0 1 12 19c-5 0-8.27-3.11-10-7a11.58 11.58 0 0 1 3.26-4.36L2 5.27zM12 8a4 4 0 0 1 4 4 3.9 3.9 0 0 1-.45 1.82l-1.5-1.5A1.9 1.9 0 0 0 14 12a2 2 0 0 0-2-2 1.9 1.9 0 0 0-.32.05l-1.5-1.5A3.9 3.9 0 0 1 12 8z"/>'
                    : '<path fill="currentColor" d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/>';
            });
        });
    </script>
@endsection
