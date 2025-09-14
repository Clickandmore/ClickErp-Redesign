@extends('web.layout.app')

@section('content')
    <main>
        <!-- page header -->
        <section class="page_header">
            <h1>{{helperTrans('web.login')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.login')}}</h6>
        </section>

        <!-- *** auth section (login) *** -->
        <section class="auth_section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-12">
                        <form action="{{route('web.sendLogin')}}" method="post" class="auth_form" id="loginForm">
                            @csrf
                            <h2>{{helperTrans('web.welcome_back')}}</h2>
                            <p>{{helperTrans('web.login_now_and_request_employees_easily')}}</p>
                            <div class="input_filed phone">
                                <label for="phone"><i class="fa-light fa-mobile-notch"></i>
                                    {{helperTrans('web.Phone')}}
                                </label>
                                <div class="d-flex gap-2">
                                    <input type="hidden" name="phone_code" value="+966">
                                    <input type="tel" data-validation="required,number" value="{{old('phone')}}" id="phone" name="phone" placeholder="5xxxxxxxxxxx+">
                                    <div class="phone_code">
                                        <img src="{{asset('web')}}/assets/images/Flag_of_Saudi_Arabia.png" alt="Flag_of_Saudi_Arabia">
                                    </div>
                                </div>
                            </div>

                            <div class="input_filed">
                                <label for="password"><i class="fa-sharp fa-light fa-lock"></i> {{helperTrans('web.password')}}</label>
                                <div class="d-flex">
                                    <input type="password" id="password" class="password" name="password"
                                           placeholder="***********">
                                    <button class="showPas"
                                            onclick="return togglePasswordVisibility(event, 'password', this)">
                                        <i class="fa-sharp fa-regular fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Name fields for returning users -->
                            <div class="input_filed hidden" id="nameFields">
                                <label for="name"><i class="fa-light fa-user"></i> {{helperTrans('web.Full_name')}}</label>
                                <div class="d-flex gap-2">
                                    <input type="text" id="name" name="name" placeholder="{{helperTrans('web.Full_name')}}">
                                </div>
                            </div>

                            <input type="hidden" name="code" value="{{request()->code}}">
                            <div class="d-flex justify-content-end w-100">
                                <a href="{{route('web.reset_password')}}">نسيت كلمة المرور ؟</a>
                            </div>
                            <button class="log" type="submit">
                                {{helperTrans('web.login')}}
                            </button>

                            <div class="d-flex justify-content-center w-100">
                                <p>ليس لديك حساب ؟ <a href="{{route('web.auth')}}">  {{helperTrans('web.register')}}</a></p>
                            </div>
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
    $(document).ready(function() {
        // تحقق من رقم الجوال عند إرسال النموذج
        $("#loginForm").on("submit", function(e) {
            var phone = $("#phone").val();

            // إزالة أي رسائل خطأ سابقة
            $("#phoneError").remove();

            // التحقق من أن الرقم يبدأ بـ 5 ويتكون من 9 أرقام
            if (!/^5\d{8}$/.test(phone)) {
                e.preventDefault();

                // إضافة رسالة خطأ تحت الحقل
                $("#phone").after('<div id="phoneError" style="color: red; font-size: 12px; margin-top: 5px;">رقم الجوال غير صحيح! يجب أن يكون 9 أرقام ويبدأ بـ 5</div>');

                // إضافة تأثير بصري
                $("#phone").css("border-color", "red");

                // إظهار alert أيضاً
                alert("رقم الجوال غير صحيح!\n\nيجب أن يكون:\n• 9 أرقام\n• يبدأ بـ 5\n\nمثال: 594249640");

                $("#phone").focus();
                return false;
            }

            // إزالة رسالة الخطأ إذا كان الرقم صحيح
            $("#phoneError").remove();
            $("#phone").css("border-color", "");
        });

        // مراقبة تغيير الرقم لإزالة رسالة الخطأ
        $("#phone").on("input", function() {
            var phone = $(this).val();

            // إذا كان الرقم صحيح، إزالة رسالة الخطأ
            if (/^5\d{8}$/.test(phone)) {
                $("#phoneError").remove();
                $(this).css("border-color", "");
            }
        });
    });
    </script>
@endsection
