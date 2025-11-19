@extends('site.layouts.index')
@section('title')
    {{ __('ingaz.otp') }}
@endsection
<style>
    .otp {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 15px;
    }

    .otp-input {
        width: 40px;
        height: 45px;
        text-align: center;
        font-size: 20px;
        border: 1px solid #ccc;
        border-radius: 8px;
        outline: none;
    }

    .otp-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 4px rgba(0, 123, 255, 0.4);
    }

    .resendBtn {
        background-color: #ccc;
        border: none;
        color: #fff;
        padding: 8px 14px;
        border-radius: 8px;
        cursor: not-allowed;
        transition: all 0.3s ease;
    }

    .resendBtn.enabled {
        background-color: #007bff;
        cursor: pointer;
    }

    .timerText {
        font-weight: 600;
        color: #555;
        min-width: 35px;
        text-align: center;
    }
</style>
@section('content')
    <section class="accountForm">
        <div class="imageBG">
            <img src="{{ asset('site') }}/assets/images/s3.webp" alt=""/>
        </div>
        <div class="cardForm" data-aos="fade-up">
            <form id="otp-form"
                  action="{{ $type == 'register' ? route('site.otp-register.store') : route('site.otp.store') }}"
                  method="post">
                @csrf
                <div class="loginLogo">
                    <img src="{{ asset('site') }}/assets/images/logo.svg" alt="img" class="img-fluid"/>
                </div>

{{--                <h3 class="text-center fw-bold text-uppercase"> {{__('ingaz.password_forgett')}}</h3>--}}
                @if($type == 'register')
                    <h3 class="text-center fw-bold text-uppercase">{{ __('web.enter_otp_to_complete_register') }}</h3>
                @else
                    <h3 class="text-center fw-bold text-uppercase">{{ __('ingaz.password_forget') }}</h3>
                @endif

                <label class="formLabel">
                    {{ __('ingaz.Please enter the verification code that was sent to you') }} :
                    <span>{{ $phone }} 966+</span>
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
                <div id="resend-container" class="d-flex align-items-center justify-content-between gap-2">
                    <span id="timer" class="timerText">30s</span>

                    <button type="button" id="resend-btn" class="btn btn-link resendBtn" disabled>
                        {{ __('web.resend_code') }}
                    </button>
                </div>
                <div class="d-flex justify-content-center align-items-center gap-3 mt-3">
                    <button type="submit" class="defaultBtn">{{ __('ingaz.send') }}</button>


                </div>
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
                        // لو الكود غلط (إحنا مرجعينه بكود 422 داخل jsonSuccess)
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

                            // فضي كل الخانات بتاعت OTP
                            document.querySelectorAll('.otp-input').forEach(function (input) {
                                input.value = '';
                            });
                            document.querySelector('.otp-value').value = '';

                            return;
                        }

                        // لو نجح التحقق
                        Toastify({
                            text: response.message,
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                            stopOnFocus: true
                        }).showToast();

                        // روح على الصفحة اللي backend رجعها (url.intended أو الرئيسية)
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
                            Swal.fire(
                                '{{ __('ingaz.error') }}',
                                '{{ __('ingaz.error_from_server') }}',
                                'error'
                            );
                        }
                    }
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const otpInputs = document.querySelectorAll('.otp-input');
            const otpValue = document.querySelector('.otp-value');
            const resendBtn = document.getElementById('resend-btn');
            const timerText = document.getElementById('timer');

            // ====== OTP INPUT HANDLING ======
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    if (input.value.length === 1 && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                    otpValue.value = Array.from(otpInputs).map(i => i.value).join('');
                });

                input.addEventListener('keydown', e => {
                    if (e.key === 'Backspace' && !input.value && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });
            });

            // ====== TIMER HANDLING ======
            let timeLeft = 30;
            let timerInterval;

            function startTimer() {
                resendBtn.disabled = true;
                resendBtn.classList.remove('enabled');
                resendBtn.style.cursor = 'not-allowed';
                timerText.textContent = `${timeLeft}s`;

                timerInterval = setInterval(() => {
                    timeLeft--;
                    timerText.textContent = `${timeLeft}s`;

                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        timerText.textContent = '';
                        resendBtn.disabled = false;
                        resendBtn.style.cursor = 'pointer';
                    }
                }, 1000);
            }
            startTimer(); // يبدأ العد أول ما الصفحة تفتح
            // ====== RESEND ANDLER ======
            resendBtn.addEventListener('click', () => {
                if (resendBtn.disabled) return;
                clearInterval(timerInterval);
                timeLeft = 30;
                startTimer();
                fetch('{{ route("site.resend-otp.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({phone: '{{ $phone }}'})
                })
                    .then(res => res.json())
                    .then(data => {
                        Toastify({
                            text: data.code,
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                            stopOnFocus: true
                        }).showToast();
                    })
                    .catch(err => {
                        Toastify({
                            text: 'حدث خطأ في الاتصال بالخادم ❌',
                            duration: 4000,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "linear-gradient(to right, #8b0000, #b22222)",
                            stopOnFocus: true
                        }).showToast();
                    });
            });
        });
    </script>
@endsection
