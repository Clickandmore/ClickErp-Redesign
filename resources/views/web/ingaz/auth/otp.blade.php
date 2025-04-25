@extends('web.layout.app')

@section('content')
    <style>
        /* Style to initially hide the element */
        .hidden {
            display: none;
        }
    </style>
    <main>

        <section class="page_header">
            <h1>{{helperTrans('web.register')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.enter_verification_code')}}</h6>
        </section>

        <section class="auth_section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-12">
                        <form action="{{route('web.confirm-code')}}" class="auth_form"  id="form_otp">
                            <h2>{{helperTrans('web.enter_verification_code')}}</h2>
                            <p>  {{helperTrans('web.please_enter_verification_code_sent_to_phone')}} <span>+966{{$user->phone}}</span></p>
                            <div class="otp-container">
                                <input class="otp-input" type="number" value="{{env('MESSAGE_Live')? "":"1"}}" maxlength="1" inputmode="numeric" pattern="[0-9]"
                                       required id="finput">
                                <input class="otp-input" type="number" value="{{env('MESSAGE_Live')? "":"2"}}" maxlength="1" inputmode="numeric" pattern="[0-9]"
                                       required>
                                <input class="otp-input" type="number" value="{{env('MESSAGE_Live')? "":"3"}}" maxlength="1" inputmode="numeric" pattern="[0-9]"
                                       required>
                                <input class="otp-input" type="number" value="{{env('MESSAGE_Live')? "":"4"}}" maxlength="1" inputmode="numeric" pattern="[0-9]"
                                       required>
                                <input class="otp-input" type="number" value="{{env('MESSAGE_Live')? "":"5"}}" maxlength="1" inputmode="numeric" pattern="[0-9]"
                                       required>
                            </div>
                            <div class="d-flex justify-content-between w-100">
                                <a href="#" id="resendOtp" class="hidden">{{helperTrans("web.resend_code")}}</a>
                                <div class="timer">
                                    <p id="countdown">01:00</p>
                                </div>
                            </div>
                            <button class="log">
                                {{helperTrans("web.confirmation")}}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection

@section('js')
    <!-- Add Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.6.5/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.5/firebase-auth.js"></script>
    <script>
        const otpContainer = document.querySelector('.otp-container');
        const firstInput = document.getElementById("finput").focus();
        otpContainer.addEventListener('input', (event) => {
            const currentInput = event.target;
            const nextInput = currentInput.nextElementSibling;
            const prevInput = currentInput.previousElementSibling;
            if (currentInput.value && nextInput) {
                nextInput.focus();
            } else if (!currentInput.value && prevInput) {
                prevInput.focus();
            }
        });
        otpContainer.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace') {
                const currentInput = event.target;
                const prevInput = currentInput.previousElementSibling;
                if (!currentInput.value && prevInput) {
                    prevInput.focus();
                }
            }
        });
        $(document).ready(function() {
            $('#form_otp').submit(function(e) {
                e.preventDefault();
                var inputElements = document.querySelectorAll('.otp-input');

                // Initialize an empty string to store the concatenated values
                var code = '';

                // Iterate through the input elements and concatenate their values
                inputElements.forEach(function(inputElement) {
                    code += inputElement.value;
                });

                // Log or use the concatenated value as needed
                // console.log(concatenatedValue);

                const url = $(this).attr('action');
                var token = '{{csrf_token()}}';
                var phone_code = '{{$user->phone_code}}';
                var phone = '{{$user->phone}}';
                var type = '{{request()->type}}';
                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    data: {'code': code,'phone_code': phone_code,'phone': phone,'_token':token},

                    success: function (response) {

                        // $("#form_contact")[0].reset();
                        console.log(response);
                        // setTimeout(function() {
                        //  Swal.fire('تم تاكيد الكود بنجاج', '', 'success');
                        if(response.data == null){
                            {{--                                --}}{{--if(type == '{{\App\Enums\UserType::Store->value}}'){--}}
                            {{--                                    return window.location.href = '{{route('web.register-store')}}?code='+code;--}}
                            {{--                                }--}}
                                return window.location.href = '{{route('web.register')}}?code='+code+'&phone='+phone;
                        }

                        Swal.fire({
                            icon: 'success',
                            title: '{{helperTrans('web.data_updated_successfully')}}',
                            html: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        })
                        {{--if(response.data.type == '{{\App\Enums\UserType::Store->value}}'){--}}
                        {{--    return window.location.href = '{{route('store.dashboard.index')}}';--}}
                        {{--}--}}
                            return window.location.href = '{{route('web.home')}}';


                        // }, 1000);

                    },
                    error: function (xhr, status, error) {

                        let errors = xhr.responseJSON.data;
                        console.log(xhr);

                        let errorMessage = '';
                        $.each(errors, function (key, value) {
                            errorMessage += value[0] + '<br>';
                        });
                        if(xhr.responseJSON.code === 422){
                            errorMessage += xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            html: errorMessage,
                        });
                    }
                });

            });
        });
    </script>
    <script>
        // Set the target date and time for the 1-minute countdown
        const targetDate = new Date();
        targetDate.setMinutes(targetDate.getMinutes() + 1);

        // Update the countdown every second
        const countdownInterval = setInterval(updateCountdown, 1000);

        function updateCountdown() {
            // Get the current date and time
            const currentDate = new Date();

            // Calculate the remaining time
            const remainingTime = targetDate - currentDate;

            if (remainingTime <= 0) {
                // Timer expired, do something here
                clearInterval(countdownInterval);
                document.getElementById('countdown').innerHTML = '00:00';
                document.getElementById('resendOtp').style.display = 'block';

            } else {
                // Convert remaining time to minutes and seconds
                const minutes = Math.floor((remainingTime % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((remainingTime % (1000 * 60)) / 1000);

                // Format the time as MM:SS
                const formattedTime = `${padZero(minutes)}:${padZero(seconds)}`;

                // Update the countdown display
                document.getElementById('countdown').innerHTML = formattedTime;
            }
        }

        function padZero(value) {
            // Helper function to pad single-digit numbers with a leading zero
            return value < 10 ? `0${value}` : value;
        }
    </script>
    <script>
        $.validate({
            ignore: 'input[type=hidden]',
            modules: 'date, security',
            lang: '{{\App::currentLocale()}}',
            validateOnEvent: true
        });

        $.formUtils.addValidator({
            name : 'number',
            validatorFunction : function (valid, $el, $form, errorMess) {
                if (valid && $el.attr('name') === 'phone') {
                    const phoneValue = $el.val();

                    if (!/^5\d{8}$/.test(phoneValue)) {
                        return false;
                    }
                }
                return valid;
            },
            errorMessage : '{{helperTrans('web.phone_number_invalid')}}',
            errorMessageKey: 'badEvenNumber'
        });

    </script>
    <script>



    </script>

@endsection
