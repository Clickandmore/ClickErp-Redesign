@extends('site.layouts.index')
@section('title')
    {{ __('ingaz.password_forgett') }}
@endsection
@section('content')
    <section class="accountForm">
        <div class="imageBG">
            <img src="{{ asset('site') }}/assets/images/s3.webp" alt="" />
        </div>
        <div class="cardForm" data-aos="fade-up">
            <form id="forget-password-form" action="{{ route('site.forget-password.store') }}" method="post">
                @csrf
                <div class="loginLogo">
                    {{--                    <button class="back" type="button" onclick="window.history.back()"> --}}
                    {{--                        رجوع --}}
                    {{--                    </button> --}}
                    <img src="{{ asset('site') }}/assets/images/logo.svg" alt="img" class="img-fluid" />
                </div>
                <h3 class="text-center fw-bold text-uppercase">{{ __('ingaz.password_forgett') }}</h3>
                <p class="text-center mb-3">
                    {{ __('ingaz.Please enter your mobile phone number to reset your password') }}
                </p>
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
                        <input type="tel" class="form-control" id="phoneNum" name="phone"
                            placeholder="5 000 0000" />
                    </div>
                </div>
                {!! NoCaptcha::renderJs(app()->getLocale()) !!}
                {!! NoCaptcha::display() !!}

                @error('g-recaptcha-response')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <button type="submit" class="defaultBtn mt-2"> {{ __('ingaz.send') }}</button>
            </form>
        </div>
    </section>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            $('#forget-password-form').on('submit', function(e) {
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
                        $('#forget-password-form button[type="submit"]').html('<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>');
                    },
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
                    },
                    complete: function() {
                        $('#forget-password-form button[type="submit"]').html('{{ __('ingaz.send') }}');
                    }

                });
            })
        })
    </script>
@endsection
