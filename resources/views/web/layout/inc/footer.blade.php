<style>
    .container-wa .floating-button {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        background-color: #25d366;
        color: white;
        border-radius: 50px;
        text-align: center;
        font-size: 35px;
        cursor: pointer;
        box-shadow: 0px 2px 5px #666;
    }

    .container-wa .floating-button:hover {
        background-color: #1fad53;
    }

    .container-wa .floating-button .icon {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        line-height: 60px;
        text-align: center;
        transition: all 0.3s;
    }

    .container-wa .floating-button .icon.wa {
        animation: wa-out 0.3s;
    }

    .container-call .floating-button {
        position: fixed;
        bottom: 20px;
        left: 20px;
        width: 60px;
        height: 60px;
        background-color: #3C5C98;
        color: white;
        border-radius: 50px;
        text-align: center;
        font-size: 30px;
        cursor: pointer;
        box-shadow: 0px 2px 5px #666;
    }

    .container-call .floating-button:hover {
        background-color: #0d1298;
    }

    .container-call .floating-button i {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        line-height: 60px;
        text-align: center;
        transition: all 0.3s;
    }

    .container-call .floating-button .icon.call {
        animation: wa-out 0.3s;
    }
</style>


<!-- *** add location modal *** -->
<div class="modal fade add_location" id="addLocation" tabindex="-1" aria-labelledby="addLocationLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>
<!-- *** Js *** -->

<footer>
    @if ($whatsappNumber != null)
        <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank">
            <div class="container-wa">

                <div class="floating-button">

                    <link rel="stylesheet"
                        href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

                    <i class="fa fa-whatsapp icon wa"></i>

                </div>
            </div>
        </a>
    @endif
    <a href="tel:{{ $settings->phone ?? '+0553123833' }}" target="_blank">
        <div class="container-call">

            <div class="floating-button">

                <link rel="stylesheet"
                    href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

                <i class="fa-regular fa-phone"></i>
            </div>
        </div>
    </a>

    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-lg-0 mb-4">
                <a href="{{ route('web.home') }}" class="logo">
                    <img src="@if (isset($settings) && isset($settings->footer_header)) {{ showFile($settings->footer_header) }}
      @else
      {{ url('/') }}/web/assets/images/logowhite.svg @endif"
                        alt="" />
                </a>
                <p>
                    {{ $settings->footer_text ?? '' }}
                </p>
                <ul class="social">
                    <li>
                        <a href="{{ $settings->facebook ?? '#' }}"><i class="fa-brands fa-facebook-f"></i></a>
                    </li>
                    <li>
                        <a href="{{ $settings->instagram ?? '#' }}"><i class="fa-brands fa-instagram"></i></a>
                    </li>
                    <li>
                        <a href="{{ $settings->twitter ?? '#' }}"><i class="fa-brands fa-twitter"></i></a>
                    </li>
                    <li>
                        <a href="{{ $settings->linkedin ?? '#' }}"><i class="fa-brands fa-linkedin-in"></i></a>
                    </li>
                </ul>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-lg-0 mb-4">
                <h4 class="footer_head">
                    {{ helperTrans('web.important_links') }}
                </h4>
                <span class="footer_head_after"></span>
                <ul class="links">
                    <li>
                        <a href="{{ route('maids.index') }}">{{ helperTrans('web.services') }}</a>
                    </li>
                    <!-- <li>
<a href="Recruitment-steps.html">خطوات الاستقدام</a>
</li> -->

                    <li>
                        <a href="{{ route('web.faqs') }}"> {{ helperTrans('web.FAQs') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('web.contact-us.index') }}">{{ helperTrans('web.Contact_Us') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('web.profile') }}"> {{ helperTrans('web.my_account') }}</a>
                    </li>
                </ul>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                <h4 class="footer_head">
                    ابقى على اتصال معنا
                </h4>
                <span class="footer_head_after"></span>
                <ul class="links_contact">
                    <li>
                        <i class="fa-sharp fa-regular fa-location-dot"></i><b>عنوان مقرنا: </b>
                        <a
                            href="https://www.google.com/maps/place/%D8%B4%D8%B1%D9%83%D8%A9+%D8%A3%D8%B3%D8%B3+%D8%A7%D9%84%D8%A7%D9%86%D8%AC%D8%A7%D8%B2+%D9%84%D9%84%D8%A7%D8%B3%D8%AA%D9%82%D8%AF%D8%A7%D9%85%E2%80%AD/@24.8154273,46.7778357,15z/data=!4m2!3m1!1s0x0:0x4bf41f66a325c69f?sa=X&ved=2ahUKEwjN5_zxqfqCAxVpQ6QEHTqRDE4Q_BJ6BAgYEAA">
                            {{ $settings->address ?? 'الرياض حي اليرموك' }}
                        </a>
                    </li>
                    <li>
                        <i class="fa-light fa-envelope"></i><b> {{ helperTrans('web.email') }}</b>
                        <a
                            href="mailto:{{ $settings->email ?? 'assusalengaz@gmail.com' }}">{{ $settings->email ?? 'assusalengaz@gmail.com' }}</a>
                    </li>
                    <li>
                        <i class="fa-light fa-phone"></i><b>{{ helperTrans('web.tech_support') }}</b>
                        <a
                            href="tel:{{ $settings->phone ?? '+0553123833' }}">{{ $settings->phone ?? '+0553123833' }}</a>
                    </li>
                </ul>
            </div>
            <div class="col-12">
                <div class="copy">
                    <p>&copy; {{ date('Y') }} جميع الحقوق محفوظة لدى شركة اسس الانجاز للاستقدام </p>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- footer -->
