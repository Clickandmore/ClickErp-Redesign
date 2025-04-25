@extends('web.layout.app')

@section('content')
    <main>
        <!-- page header -->

        <section class="page_header">
            <h1>{{helperTrans('web.register')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.register')}}</h6>
        </section>

        <!-- auth-login section -->

        <section class="auth_section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-12">
                        <form action="{{route('web.sendOtp')}}" method="get"  class="auth_form">
                            @csrf
                            <h2>{{helperTrans('web.register')}}</h2>
                            <p>{{helperTrans('web.register')}}</p>
                            <div class="input_filed phone">
                                <label for="phone"><i class="fa-light fa-mobile-notch"></i>
                                    {{helperTrans('web.Phone')}}
                                </label>
                                <input type="hidden" name="phone_code" value="+966">
                                <input type="hidden" name="reset" value="0">

                                <div class="d-flex gap-2" >

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
                                {{helperTrans('web.send')}}
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
        errorMessage : '{{helperTrans('web.phone_number_invalid')}}',
        errorMessageKey: 'badEvenNumber',
        errorDiv: 'yourErrorDiv', // Specify the ID or selector of your error div

    });

</script>
<script>



</script>

@endsection
