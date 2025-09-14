@extends('site.layouts.index')
@section('title')

@endsection
@section('content')
    <section class="accountForm">
        <div class="imageBG">
            <img src="{{asset('site')}}/assets/images/s1.webp" alt="" />
        </div>
        <div class="cardForm" data-aos="fade-up">
            <form action="code.html">
                <div class="loginLogo">
                    <button
                        class="back"
                        type="button"
                        onclick="window.history.back()"
                    >
                        رجوع
                    </button>
                    <img src="assets/images/logo.svg" alt="img" class="img-fluid" />
                </div>
                <h3 class="text-center fw-bold text-uppercase">نسيت كلمة المرور</h3>
                <p class="text-center mb-3">
                    الرجاء ادخال رقم الجوال الخاص بك لاستعادة كلمة المرور
                </p>
                <div class="formGroup">
                    <label for="phoneNum">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M15.045 0h-6.09A5.006 5.006 0 0 0 4 4.999v14c0 2.757 2.243 5 5 5h6c2.757 0 5-2.243 5-5V5a5.007 5.007 0 0 0-4.955-5ZM18 18.999c0 1.654-1.346 3-3 3H9c-1.654 0-3-1.346-3-3V5a3.006 3.006 0 0 1 2.411-2.942l.694 1.389A1 1 0 0 0 10 4h4c.379 0 .725-.214.895-.553l.694-1.389A3.005 3.005 0 0 1 18 5v14Zm-5 1h-2a1 1 0 0 1 0-2h2a1 1 0 0 1 0 2Z"
                                ></path>
                            </svg>
                        </div>
                        رقم الجوال
                    </label>
                    <div class="inputDiv ltr">
                        <span> +966 </span>
                        <input
                            type="tel"
                            class="form-control"
                            id="phoneNum"
                            placeholder="05 000 0000"
                        />
                    </div>
                </div>
                <button type="submit" class="defaultBtn mt-2">ارسال</button>
            </form>
        </div>
    </section>
@endsection
@section('js')
@endsection
