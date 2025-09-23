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
            <h1>{{helperTrans('web.auth')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / التحقق واكمال البيانات</h6>
        </section>

        <section class="auth_section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-12">
                        <form action="{{route('web.confirm-code')}}" class="auth_form" id="form_otp">
                            <h2>التحقق واكمال البيانات</h2>
                            <p>  {{helperTrans('web.please_enter_verification_code_sent_to_phone')}} <span>+966{{$user->phone}}</span></p>

                            @if(!isset($is_existing_user) || !$is_existing_user)
                            <!-- Name fields for new users -->
                            <div class="input_filed" id="nameFields">
                                <label for="first_name"><i class="fa-light fa-user"></i> الاسم الأول</label>
                                <input type="text" id="first_name" name="first_name" placeholder="ادخل الاسم الأول" required>
                            </div>

                            <div class="input_filed" id="lastNameFields">
                                <label for="last_name"><i class="fa-light fa-user"></i> الاسم الأخير</label>
                                <input type="text" id="last_name" name="last_name" placeholder="ادخل الاسم الأخير" required>
                            </div>
                            @endif

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

                            <button class="log" id="submitButton">
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
            console.log("OTP page loaded");            $('#form_otp').submit(function(e) {
                e.preventDefault();
                var inputElements = document.querySelectorAll('.otp-input');

                // Initialize an empty string to store the concatenated values
                var code = '';

                // Iterate through the input elements and concatenate their values
                inputElements.forEach(function(inputElement) {
                    code += inputElement.value;
                });

                const url = $(this).attr('action');
                var token = '{{csrf_token()}}';
                var phone_code = '{{$user->phone_code}}';
                var phone = '{{$user->phone}}';
                var first_name = $('#first_name').val();
                var last_name = $('#last_name').val();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: token,
                        phone_code: phone_code,
                        phone: phone,
                        code: code,
                        first_name: first_name,
                        last_name: last_name
                    },
                    success: function(response) {
                        console.log('OTP Response:', response);
                        if (response.code === 200) {
                            // التحقق من وجود بيانات طلب محفوظة
                            const pendingOrder = localStorage.getItem("pendingOrder");
                            console.log('Pending Order:', pendingOrder);
                            if (pendingOrder) {
                                try {
                                    const orderData = JSON.parse(pendingOrder);
                                    console.log('Order Data:', orderData);
                                    // إرسال البيانات إلى الخادم لحفظها في الجلسة
                                    $.ajax({
                                        url: "{{ route("web.save-pending-order") }}",
                                        method: "POST",
                                        data: {
                                            _token: "{{ csrf_token() }}",
                                            order_data: orderData
                                        },
                                        success: function() {
                                            console.log('Pending order saved to session successfully');
                                            // حذف البيانات من localStorage
                                            localStorage.removeItem("pendingOrder");

                                            // إعادة التوجيه إلى نفس الصفحة التي كان عليها العميل
                                            const returnUrl = orderData.return_url || "{{ route("web.maids.show", ["maid" => ":maid_id", "order_type" => ":order_type"]) }}"
                                                .replace(":maid_id", orderData.maid_id)
                                                .replace(":order_type", orderData.order_type);

                                            console.log('Redirecting back to:', returnUrl);
                                            window.location.href = returnUrl;
                                        },
                                        error: function() {
                                            // في حالة الخطأ، التوجيه للصفحة الرئيسية
                                            window.location.href = "/";
                                        }
                                    });
                                } catch (error) {
                                    console.error("Error parsing pending order:", error);
                                    window.location.href = "/";
                                }
                            } else {
                                console.log("No pending order found, redirecting to home");
                                            console.log("Redirecting to home page");
                                console.log('No pending order found, redirecting to home');
                                window.location.href = "/";
                            }
                        } else if (response.is_new_user) {
                            // Show name fields for new user
                            $('#nameFields').removeClass('hidden');
                            $('#lastNameFields').removeClass('hidden');
                            $('#submitButton').text('إكمال التسجيل');
                            toastr.info('الرجاء إدخال الاسم');
                        } else {
                                console.log("No pending order found, redirecting to home");
                                            console.log("Redirecting to home page");
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            if (xhr.responseJSON.is_new_user) {
                                // Show name fields for new user
                                $('#nameFields').removeClass('hidden');
                                $('#lastNameFields').removeClass('hidden');
                                $('#submitButton').text('إكمال التسجيل');
                                toastr.info('الرجاء إدخال الاسم');
                            } else {
                                console.log("No pending order found, redirecting to home");
                                            console.log("Redirecting to home page");
                                toastr.error(xhr.responseJSON.message);
                            }
                        } else {
                                console.log("No pending order found, redirecting to home");
                                            console.log("Redirecting to home page");
                            toastr.error('{{helperTrans("web.error_occurred")}}');
                        }
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
                                console.log("No pending order found, redirecting to home");
                                            console.log("Redirecting to home page");
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
@endsection
