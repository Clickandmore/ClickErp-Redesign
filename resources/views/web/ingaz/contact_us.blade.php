@extends('web.layout.app')

@section('content')
    <script src="https://www.google.com/recaptcha/api.js?hl={{app()->getLocale()}}" async defer></script>



    <main>
        <!-- *** pages header *** -->
        <section class="page_header">
            <h1>{{helperTrans('web.Contact_Us')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.Contact_Us')}}</h6>
        </section>
        <!-- *** contact section *** -->
        <section class="contact_section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-12 mb-lg-0 mb-3">
                        <div class="contact_info">
                            <h3>ابقى على تواصل معنا</h3>
                            <ul>
                                <li class="field">
                                    <div class="icon">
                                        <i class="fa-light fa-map-location-dot"></i>
                                    </div>
                                    <div class="text">
                                        <h4>{{helperTrans('web.location')}} : </h4>
                                        <p>{{ $settings->location ?? 'طريق الأمير محمد بن فهد بن عبدالعزيز، الشاطئ الشرقي، الدمام 32413'}}</p>
                                    </div>
                                </li>
                                <li class="field">
                                    <div class="icon">
                                        <i class="fa-sharp fa-light fa-envelope"></i>
                                    </div>
                                    <div class="text">
                                        <h4>{{helperTrans('web.email')}} : </h4>
                                        <a href="mailto:{{ $settings->eamil ?? 'info@platinumeastern.com' }}">{{ $settings->eamil ?? 'info@platinumeastern.com' }}</a>
                                    </div>
                                </li>
                                <li class="field">
                                    <div class="icon">
                                        <i class="fa-light fa-phone"></i>
                                    </div>
                                    <div class="text">
                                        <h4>{{helperTrans('web.phone')}} : </h4>
                                        <a href="tel:+{{ $settings->phone ?? '+966501142288' }}">{{ $settings->phone ?? '+966501142288' }}+</a>
                                    </div>
                                </li>
                                <h5>مواعيد العمل</h5>
                                <p>فى خدمتكم جميع ايام الأسبوع من 9ص حتى 8م عدا الجمعه</p>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-8 col-12">
                        <form action="{{route('web.contact-us.store')}}" method="post" id="form_contact">
                            @csrf
                            <div class="form-group">
                                <!-- field-set -->
                                <div class="inputfield">
                                    <label for="name"><i class="fa-light fa-user"></i>{{helperTrans('web.Full_name')}}ً</label>
                                    <input type="text" id="name" name="name" onfocus="highlight(this)"
                                           onblur="dehighlight(this)">
                                    <span class="highlight"></span>
                                </div>
                                <!-- field-set -->
                                <div class="inputfield">
                                    <label for="email"><i class="fa-light fa-envelope"></i> {{helperTrans('web.Email')}}</label>
                                    <input type="email" id="email" name="email" onfocus="highlight(this)"
                                           onblur="dehighlight(this)">
                                    <span class="highlight"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <!-- field-set -->
                                <div class="inputfield">
                                    <label for="phone"><i class="fa-light fa-phone"></i> {{helperTrans('web.phone')}}</label>
                                    <input type="tel" id="phone" name="phone" onfocus="highlight(this)"
                                           onblur="dehighlight(this)">
                                    <span class="highlight"></span>
                                </div>
                                <!-- field-set -->
                                <div class="inputfield">
                                    <label for="subject"><i class="fa-regular fa-comment-dots"></i> {{helperTrans('web.Subject')}}</label>
                                    <input type="text" id="subject" name="subject" onfocus="highlight(this)"
                                           onblur="dehighlight(this)">
                                    <span class="highlight"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <!-- field-set -->
                                <div class="inputfield">
                                    <label for="message" class="message-label"><i
                                            class="fa-light fa-feather-pointed"></i> {{helperTrans('web.Message')}}
                                    </label>
                                    <textarea name="message" id="message" onfocus="highlight(this)"
                                              onblur="dehighlight(this)"></textarea>
                                    <span class="highlight"></span>
                                </div>
                            </div>
                            <div class="g-recaptcha" data-sitekey="{{env('SiteKeyRecaptcha')}}"></div>
                            <div id="alert_div"></div>
                            <button type="submit">{{helperTrans('web.send')}} <i class="fa-sharp fa-light fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <!-- *** map *** -->
        <section class="map" style="margin-bottom: -12px;">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d14485.66588537678!2d46.7778357!3d24.8154273!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e2effbbf9a95863%3A0x4bf41f66a325c69f!2z2LTYsdmD2Kkg2KPYs9izINin2YTYp9mG2KzYp9iyINmE2YTYp9iz2KrZgtiv2KfZhQ!5e0!3m2!1sar!2seg!4v1701858521842!5m2!1sar!2seg"
                allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                style="width: 100%; height: 300px;"></iframe>
        </section>
    </main>
@endsection

@section('js')
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl={{app()->getLocale()}}"
            async defer>
    </script>

    <script>

        $(document).ready(function() {
            $('#form_contact').submit(function(e) {
                e.preventDefault();
                var form = document.getElementById('form_contact');
                var isValidForm = form.checkValidity();
                var capcha = document.getElementById("g-recaptcha-response").value;

                if (!isValidForm || capcha ==="") {
                    if(!isValidForm){
                        $("#alert_div").html('<div class="alert alert-danger">\n' +
                            '  <strong>خطأ!</strong>  تأكد من ادخال جميع البيانات  \n' +
                            '</div>');
                    }else{

                        $("#alert_div").html('<div class="alert alert-danger">\n' +
                            '  <strong>خطأ!</strong>  تأكد من اختيار انك ليس انسان الي  \n' +
                            '</div>');
                    }

                } else {
                    // alert('ddd');
                    const formData = new FormData(this);
                    const url = $(this).attr('action');
                    const submitButton = $(document.activeElement);
                    let buttonText = submitButton.text();

                    $.ajax({
                        url: url,
                        method: 'POST',
                        beforeSend: function () {
                            submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + buttonText)
                        },
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (response) {
                            $("#form_contact")[0].reset();
                            // console.log(response);
                            $("#alert_div").html('');
                            Swal.fire('تم ارسال البيانات بنجاج', '', 'success');
                            location.reload();
                        },
                        error: function (xhr, status, error) {
                            let errors = xhr.responseJSON.data;
                            console.log(xhr.responseJSON.data);
                            let errorMessage = '';
                            $.each(errors, function (key, value) {
                                errorMessage += value[0] + '<br>';
                            });
                            Swal.fire({
                                icon: 'error',
                                title: 'Validation Error',
                                html: errorMessage,
                            });
                        }
                    });
                }
            });
        });
    </script>
@endsection
