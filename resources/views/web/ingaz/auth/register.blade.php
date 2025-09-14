@extends('web.layout.app')

@section('content')
    <main>
       <!-- page header -->
        <section class="page_header">
            <h1>{{helperTrans('web.register')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.register')}}</h6>
        </section>


        <!-- *** auth section (login) *** -->
        <section class="auth_section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-12">
                        <form action="{{route('web.sendRegister')}}" method="post" class="auth_form">
                            @csrf
                            <h2>{{helperTrans('web.open_new_horizons_for_your_business')}}</h2>
                            <p>{{helperTrans('web.create_new_account_now_and_recruit_employees_easily')}}</p>
                            <div class="input_filed">
                                <label for="Full_name"><i class="fa-light fa-user"></i> {{helperTrans('web.Full_name')}}</label>
                                <div class="d-flex gap-2">
                                    <input type="text" id="Full_name" name="name" placeholder="{{helperTrans('web.Full_name')}} ">
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
                            <input type="hidden" name="phone" value="{{request()->phone}}">

                            <button class="log">
                                {{helperTrans('web.register')}}
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
        errorMessage : '{{__('web.phone_number_invalid')}}',
        errorMessageKey: 'badEvenNumber'
    });

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
