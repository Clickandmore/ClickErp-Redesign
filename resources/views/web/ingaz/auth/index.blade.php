@extends('web.layout.app')

@section('content')
    <main>
        <!-- page header -->
        <section class="page_header">
            <h1>{{helperTrans('web.auth')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.auth')}}</h6>
        </section>

        <!-- auth section -->
        <section class="auth_section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-12">
                        <form action="{{route('web.sendOtp')}}" method="get" class="auth_form" id="authForm">
                            @csrf
                            <h2>{{helperTrans('web.welcome')}}</h2>
                            <p>{{helperTrans('web.enter_phone_to_continue')}}</p>

                            <div class="input_filed phone">
                                <label for="phone"><i class="fa-light fa-mobile-notch"></i>
                                    {{helperTrans('web.Phone')}}
                                </label>
                                <input type="hidden" name="phone_code" value="+966">
                                <input type="hidden" name="reset" value="0">

                                <div class="d-flex gap-2">
                                    <input type="tel" data-validation="required,number" id="phone" name="phone" placeholder="5xxxxxxxxxxx+">
                                    <div class="phone_code">
                                        <img src="{{asset('web')}}/assets/images/Flag_of_Saudi_Arabia.png" alt="Flag_of_Saudi_Arabia">
                                    </div>
                                </div>
                            </div>
                            <span class="help-block form-error" id="yourErrorDiv"></span>
                            <p class="w-100 mb-2">
                                {{helperTrans('web.by_continuing_you_agree')}}
                                <a href="{{route('web.privacyPolicy')}}">{{helperTrans('web.privacyPolicy')}}</a> و
                                <a href="{{route('web.termsConditions')}}">{{helperTrans('web.termsConditions')}}</a> {{helperTrans('web.for_our_website')}}
                            </p>
                            <button class="log">
                                {{helperTrans('web.continue')}}
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
    $(document).ready(function() {
        // تحقق من رقم الجوال عند إرسال النموذج
        $("#authForm").on("submit", function(e) {
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
                alert("رقم الجوال غير صحيح!\n\nيجب أن يكون:\n• 9 أرقام\n• يبدأ بـ 5\n\nمثال: 5xxxxxxxxx");

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
