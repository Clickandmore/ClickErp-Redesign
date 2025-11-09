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
                                <path
                                    d="M19 8.424V7A7 7 0 0 0 5 7v1.424A5 5 0 0 0 2 13v6a5.006 5.006 0 0 0 5 5h10a5.006 5.006 0 0 0 5-5v-6a5 5 0 0 0-3-4.576ZM7 7a5 5 0 0 1 10 0v1H7Zm13 12a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3Z">
                                </path>
                                <path d="M12 14a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-1-1Z"></path>
                            </svg>
                        </div>
                        {{ __('ingaz.password') }}
                    </label>
                    <input type="password" class="form-control" name="password" id="password" />
                </div>
                <div class="formGroup">
                    <label for="password_confirmation">
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
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" />
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
@endsection
