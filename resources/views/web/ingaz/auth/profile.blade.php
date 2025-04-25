@extends('web.layout.app')

@section('content')
    <main>
        <!-- page header -->

        <section class="page_header">
            <h1>{{helperTrans('web.my_profile')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.update_profile')}}</h6>
        </section>
        <!-- profile section -->
        <section class="setting_setion">
            <div class="container">
                <div class="row ">
                    @include('web.layout.inc.profile_aside')

                    <div class="col-lg-8 col-12">
                        <form  action="{{route('web.update-profile')}}" id="update-profile" method="post" class="auth_form">
                            @csrf
                            <div class="input_filed">
                                <label for="name"><i class="fa-light fa-user"></i> {{helperTrans('web.Full_name')}}</label>
                                <div class="d-flex gap-2">
                                    <input type="text" id="name" name="name" value="{{getGuardedUser('name')}}" placeholder="{{helperTrans('web.Name')}} ">
                                </div>
                            </div>
{{--                            <div class="input_filed phone">--}}
{{--                                <label for="phone"><i class="fa-light fa-mobile-notch"></i> {{helperTrans('web.Phone')}}</label>--}}
{{--                                <div class="d-flex gap-2">--}}
{{--                                    <input type="tel" id="phone" name="phone" placeholder="5XXXXXXXX">--}}
{{--                                    <div class="phone_code">--}}
{{--                                        <img src="{{asset('web')}}/assets/images/Flag_of_Saudi_Arabia.png" alt="Flag_of_Saudi_Arabia">--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
                            <div class="input_filed">
                                <label for="newPass">
                                    <i class="fa-sharp fa-light fa-lock"></i>{{helperTrans('web.new_password')}} </label>
                                <div class="d-flex">
                                    <input type="password" id="password" class="newPass" name="password"
                                           placeholder="***********">
                                    <button class="showPas"
                                            onclick="return togglePasswordVisibility(event, 'newPass', this)">
                                        <i class="fa-sharp fa-regular fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="input_filed">
                                <label for="confirmPass"><i class="fa-sharp fa-light fa-lock"></i>
                                     {{helperTrans('web.confirmation_password')}}
                                </label>
                                <div class="d-flex">
                                    <input type="password" id="confirmation_password" class="confirmPass" name="password"
                                           placeholder="***********">
                                    <button class="showPas"
                                            onclick="return togglePasswordVisibility(event, 'confirmPass', this)">
                                        <i class="fa-sharp fa-regular fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="log">
                                {{helperTrans('web.confirmation')}}
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
        document.getElementById('update-profile').addEventListener('submit', function (event) {
            var password = document.getElementById('password').value;
            var confirmPassword = document.getElementById('confirmation_password').value;

            if (password !== confirmPassword) {
                event.preventDefault(); // Prevent form submission
                alert('{{helperTrans('web.password_mismatch')}}');
            }
        });
    </script>

@endsection
