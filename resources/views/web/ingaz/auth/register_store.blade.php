@extends('web.layout.app')

@section('content')
    <!-- dropify -->
    <link rel="stylesheet" type="text/css" href="https://jeremyfagis.github.io/dropify/dist/css/dropify.min.css">
    <main>
        <!-- page header -->
        <div class="page_header container d-flex align-items-center">
            <h6>
                <a href="{{route('web.home')}}">{{__('web.Home')}}</a>
                <i class="fa-regular fa-angle-left"></i>
                {{__('web.register')}}
            </h6>
        </div>
        <!-- auth-login section -->
        <section class="profile_section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-12">
                        <form action="{{route('web.send-register-store')}}" class="submit_form" method="post" enctype="multipart/form-data">
                         @csrf
                            <div class="input-field image-change-wrapper">
                                <div class="img-wrap">
                                    <img id="uploadedImage" src="{{asset('web')}}/assets/images/user.png" alt="user">
                                </div>
                                <div class="d-flex w-100 pe-5 ps-5 justify-content-between align-items-center">
                                    <label for="img">  {{__('web.image')}}</label>
                                    <label class="upload">
                                        <div class="plus">
                                            <i class="fa-regular fa-plus"></i>
                                        </div>
                                        <input type="file" name="logo" id="img-upload" accept="image/*">
                                    </label>
                                </div>
                            </div>
                                <!-- field set -->
                                <div class="input_field">
                                    <label for="firstName">
                                        {{__("web.store_name")}}
                                    </label>
                                    <input type="text" id="name" name="name" placeholder="{{__("web.store_name")}}"
                                           value="">
                                </div>

                            <div class="input_field">
                                <label for="phone">
                                    {{__("web.Email")}}
                                </label>
                                <input type="email" id="email" name="email" placeholder="email@gmil.com">
                            </div>
                            <div class="form-group">
                            <div class="input_field">
                                <label for="password">
                                    {{__("web.password")}}
                                </label>
                                <input type="password" id="password" name="password" >
                            </div>


                            <div class="input_field">
                                <label for="password">
                                    {{__("web.password_confirmation")}}
                                </label>
                                <input type="password" id="password_confirmation" name="password_confirmation" >
                            </div>
                            </div>
                            <div class="input_field">
                                 <label for="phone">

                                     {{__("web.commercial_register")}}
                                </label>
                            <input name="commercial_register" type="file" class="dropify"
                                   data-height="130" />
                            </div>
                            <div class="input_field">
                                    <label for="firstName">
                                        {{__("web.address")}}
                                    </label>
                                    <input type="text" id="places-search" placeholder='{{__("web.address")}}' class="mapLiftingLocation">
                                    <div id="mapLifting" style="height: 320px"></div>
                                    <input type="hidden" id="latitude" name="latitude" >
                                    <input type="hidden" id="longitude" name="longitude" >
                                </div>

                            <input type="hidden" name="type" id="" value="{{\App\Enums\UserType::Store->value}}">
                            <input type="hidden" name="register_from" id="" value="web">

                            <!-- field set -->

                                <input type="hidden" name="phone" value="{{$user->phone}}">
                                <input type="hidden" name="country_code" value="{{request()->code}}">
                            <button>
                                {{__('web.register')}}
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
    <script type="text/javascript" src="https://jeremyfagis.github.io/dropify/dist/js/dropify.min.js"></script>
    <script>
        $('.dropify').dropify();
        $(document).ready(function () {
            //Initialize tooltips
            $(".nav-tabs > li a[title]").tooltip();
            //Wizard
            $('a[data-toggle="tab"]').on("show.bs.tab", function (e) {
                var $target = $(e.target);
                if ($target.parent().hasClass("disabled")) {
                    return false;
                }
            });
            $(".next-step").click(function (e) {
                var $active = $(".wizard .nav-tabs li.active");
                $active.next().removeClass("disabled");
                nextTab($active);
            });
            $(".prev-step").click(function (e) {
                var $active = $(".wizard .nav-tabs li.active");
                prevTab($active);
            });
        });
        function nextTab(elem) {
            $(elem).next().find('a[data-toggle="tab"]').click();
        }
        function prevTab(elem) {
            $(elem).prev().find('a[data-toggle="tab"]').click();
        }
    </script>
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
    <script>

        $(document).ready(function() {
            $('.submit_form').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const url = $(this).attr('action');
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        // $("#form_contact")[0].reset();
                        console.log(response);
                        // $("#alert_div").html('');
                        Swal.fire({
                            icon: 'success',
                            title: '{{__('web.done_successfully')}}',
                            html: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        })
                        return window.location.href = '{{route('store.dashboard.index')}}';
                    },
                    error: function (xhr, status, error) {
                        let errors = xhr.responseJSON.data;
                        let response = xhr.responseJSON;
                        console.log(xhr.responseJSON);
                        let errorMessage = '';
                        // if(xhr.responseJSON.code == 422){
                        //     return  Swal.fire({
                        //         icon: 'error',
                        //         title: response.message.title,
                        //         html: response.message.message,
                        //         showConfirmButton: false,
                        //         timer: 2500
                        //     })
                        // }
                        $.each(errors, function (key, value) {
                            errorMessage += value[0] + '<br>';
                        });
                        Swal.fire({
                            icon: 'error',
                            title: '',
                            html: errorMessage,
                            showConfirmButton: false,
                            timer: 2500
                        });


                    }
                });

            });
        });
    </script>

@endsection
