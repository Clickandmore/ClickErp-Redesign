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
                        <form action="{{route('web.sendLogin')}}" method="post" class="auth_form">
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
                            <input type="hidden" name="code" value="{{request()->code}}">
                            <div class="d-flex justify-content-end w-100">
                                <a href="{{route('web.reset_password')}}">نسيت كلمة المرور ؟</a>
                            </div>
                            <button class="log">
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
    $.validate({
        ignore: 'input[type=hidden]',
        modules: 'date, security',
        lang: '{{\App::currentLocale()}}',
        validateOnEvent: true
    });

    {{--$.formUtils.addValidator({--}}
    {{--    name : 'number',--}}
    {{--    validatorFunction : function (valid, $el, $form, errorMess) {--}}
    {{--        if (valid && $el.attr('name') === 'phone') {--}}
    {{--            const phoneValue = $el.val();--}}

    {{--            if (!/^5\d{8}$/.test(phoneValue)) {--}}
    {{--                return false;--}}
    {{--            }--}}
    {{--        }--}}
    {{--        return valid;--}}
    {{--    },--}}
    {{--    errorMessage : '{{__('web.phone_number_invalid')}}',--}}
    {{--    errorMessageKey: 'badEvenNumber'--}}
    {{--});--}}

</script>
<script>

    let inputImg = document.getElementById("img-upload");
    let uploadedImage = document.getElementById("uploadedImage");
    inputImg.addEventListener("change", () => {
        if (inputImg.files && inputImg.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                uploadedImage.src = e.target.result;
            };
            reader.readAsDataURL(inputImg.files[0]);
        }
    });

</script>

@endsection
