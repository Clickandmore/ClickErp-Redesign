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
                    <img src="{{asset('site')}}/assets/images/logo.svg" alt="img" class="img-fluid" />
                </div>
                <div class="formGroup">
                    <label for="userName">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M12 12a6 6 0 1 0-6-6 6.006 6.006 0 0 0 6 6Zm0-10a4 4 0 1 1-4 4 4 4 0 0 1 4-4ZM12 14a9.01 9.01 0 0 0-9 9 1 1 0 0 0 2 0 7 7 0 0 1 14 0 1 1 0 0 0 2 0 9.01 9.01 0 0 0-9-9Z"
                                ></path>
                            </svg>
                        </div>
                        اسم المستخدم
                    </label>
                    <input
                        type="text"
                        class="form-control"
                        id="userName"
                        placeholder=" ادخل اسم المستخدم "
                    />
                </div>
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
                <div class="formGroup">
                    <label for="email">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M19 1H5a5.006 5.006 0 0 0-5 5v12a5.006 5.006 0 0 0 5 5h14a5.006 5.006 0 0 0 5-5V6a5.006 5.006 0 0 0-5-5ZM5 3h14a3 3 0 0 1 2.78 1.887l-7.658 7.659a3.007 3.007 0 0 1-4.244 0L2.22 4.887A3 3 0 0 1 5 3Zm14 18H5a3 3 0 0 1-3-3V7.5l6.464 6.46a5.007 5.007 0 0 0 7.072 0L22 7.5V18a3 3 0 0 1-3 3Z"
                                ></path>
                            </svg>
                        </div>
                        البريد الالكتروني
                    </label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        placeholder="ادخل البريد الالكتروني"
                    />
                </div>
                <div class="formGroup">
                    <label for="password">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M19 8.424V7A7 7 0 0 0 5 7v1.424A5 5 0 0 0 2 13v6a5.006 5.006 0 0 0 5 5h10a5.006 5.006 0 0 0 5-5v-6a5 5 0 0 0-3-4.576ZM7 7a5 5 0 0 1 10 0v1H7Zm13 12a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3Z"
                                ></path>
                                <path
                                    d="M12 14a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-1-1Z"
                                ></path>
                            </svg>
                        </div>
                        كلمة المرور
                    </label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        placeholder="ادخل كلمة المرور "
                    />
                </div>
                <div class="formGroup">
                    <label for="confirmPassword">
                        <div class="icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M19 8.424V7A7 7 0 0 0 5 7v1.424A5 5 0 0 0 2 13v6a5.006 5.006 0 0 0 5 5h10a5.006 5.006 0 0 0 5-5v-6a5 5 0 0 0-3-4.576ZM7 7a5 5 0 0 1 10 0v1H7Zm13 12a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3Z"
                                ></path>
                                <path
                                    d="M12 14a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-1-1Z"
                                ></path>
                            </svg>
                        </div>
                        تأكيد كلمة المرور
                    </label>
                    <input
                        type="password"
                        class="form-control"
                        id="confirmPassword"
                        placeholder="ادخل كلمة المرور "
                    />
                </div>
                <button type="submit" class="defaultBtn mt-2">انشاء</button>
                <p class="text-center w-100 mt-3">
                    لديك حساب بالفعل ؟
                    <a href="{{route('site.login.index')}}"> تسجيل الدخول </a>
                </p>
            </form>
        </div>
    </section>
@endsection
@section('js')
@endsection
