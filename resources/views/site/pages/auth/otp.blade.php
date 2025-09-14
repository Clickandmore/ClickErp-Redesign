@extends('site.layouts.index')
@section('title')

@endsection
@section('content')
    <section class="accountForm">
        <div class="imageBG">
            <img src="{{asset('site')}}/assets/images/s1.webp" alt=""/>
        </div>
        <div class="cardForm" data-aos="fade-up">
            <form action="newPassword.html">
                <div class="loginLogo">
                    <button
                        class="back"
                        type="button"
                        onclick="window.history.back()">
                        رجوع
                    </button>
                    <img src="assets/images/logo.svg" alt="img" class="img-fluid"/>
                </div>
                <h3 class="text-center fw-bold text-uppercase">نسيت كلمة المرور</h3>
                <label class="formLabel">
                    من فضلك ادخل رمز التحقق المرسل الى:
                    <span>+96605000000</span>
                </label>
                <div class="otp">
                    <input class="otp-input" type="text" maxlength="1"/>
                    <input class="otp-input" type="text" maxlength="1"/>
                    <input class="otp-input" type="text" maxlength="1"/>
                    <input class="otp-input" type="text" maxlength="1"/>
                    <input class="otp-input" type="text" maxlength="1"/>
                    <!-- Store OTP Value -->
                    <input class="otp-value" type="hidden" name="otp-value"/>
                </div>
                <button type="submit" class="defaultBtn mt-2">ارسال</button>
            </form>
        </div>
    </section>
@endsection
@section('js')
@endsection
