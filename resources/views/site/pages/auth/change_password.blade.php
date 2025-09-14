@extends('site.layouts.index')
@section('title')

@endsection
@section('content')
    <section class="accountForm">
        <div class="imageBG">
            <img src="{{asset('site')}}/assets/images/s1.webp" alt="" />
        </div>
        <div class="cardForm" data-aos="fade-up">
            <form action="login.html">
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
                <h3 class="text-center fw-bold text-uppercase">
                    تغيير كلمة المرور
                </h3>
                <p class="text-center mb-3">
                    يجب انت تكون كلمة المرور قوية ويسهل تذكرها
                </p>
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
                <button type="submit" class="defaultBtn mt-2">ارسال</button>
            </form>
        </div>
    </section>
@endsection
@section('js')
@endsection
