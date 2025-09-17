@extends('site.layouts.index')
@section('title')

@endsection
@section('content')
    <section class="accountForm">
        <div class="imageBG">
            <img src="{{asset('site')}}/assets/images/s1.webp" alt=""/>
        </div>
        <div class="cardForm" data-aos="fade-up">
            <form id="otp-form"
                  action="{{ $type == 'register' ? route('site.otp-register.store') : route('site.otp.store') }}"
                  method="post">
                @csrf
                <div class="loginLogo">
                    <button class="back" type="button" onclick="window.history.back()">رجوع</button>
                    <img src="{{ asset('site') }}/assets/images/logo.svg" alt="img" class="img-fluid"/>
                </div>

                <h3 class="text-center fw-bold text-uppercase">نسيت كلمة المرور</h3>

                <label class="formLabel">
                    من فضلك ادخل رمز التحقق المرسل الى:
                    <span> {{ $phone }} 966+</span>
                </label>
                <div class="otp">
                    <input class="otp-input" type="text" maxlength="1"/>
                    <input class="otp-input" type="text" maxlength="1"/>
                    <input class="otp-input" type="text" maxlength="1"/>
                    <input class="otp-input" type="text" maxlength="1"/>
                    <input class="otp-input" type="text" maxlength="1"/>
                    <input class="otp-value" type="hidden" name="code"/>
                    <input type="hidden" name="phone" value="{{ $phone }}"/>
                </div>
                <button type="submit" class="defaultBtn mt-2">ارسال</button>
            </form>
        </div>
    </section>
@endsection
@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('otp-form');
            const inputs = document.querySelectorAll('.otp-input');
            const hiddenOtp = document.querySelector('.otp-value');

            inputs.forEach((input, idx) => {
                input.addEventListener('input', function () {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value.length === 1 && idx < inputs.length - 1) {
                        inputs[idx + 1].focus();
                    }
                });
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Backspace' && !this.value && idx > 0) {
                        inputs[idx - 1].focus();
                    }
                });
            });
            form.addEventListener('submit', function () {
                let code = '';
                inputs.forEach(i => code += i.value.trim());
                hiddenOtp.value = code;
            });
        });
    </script>
    <script>
        $(document).ready(function () {
            $('#otp-form').on('submit', function (e) {
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
                        if (response.code === 422) {
                            Toastify({
                                text: response.message,
                                duration: 4000,
                                gravity: "top",
                                position: "right",
                                backgroundColor: "linear-gradient(to right, #e53935, #b71c1c)",
                                stopOnFocus: true,
                                className: "toast-error"
                            }).showToast();
                            document.querySelectorAll('.otp-input').forEach(function (input) {
                                input.value = '';
                            });
                            document.querySelector('.otp-value').value = '';
                        } else {
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
                        }
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessages = '';
                            for (let key in errors) {
                                errorMessages += `<p style="margin: 0 0 5px; color: red; text-align: center;">${errors[key][0]}</p>`;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("ingaz.error") }}',
                                html: `<div>${errorMessages}</div>`,
                            });
                        } else {
                            Swal.fire('{{ __("ingaz.error") }}', '{{ __("ingaz.error_from_server") }}', 'error');
                        }
                    }

                });
            })
        })
    </script>
@endsection
