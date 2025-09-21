@extends('site.layouts.index')
@section('title')
@endsection
@section('content')
<!-- ================ musaned ================= -->
<section class="musaned" data-aos="fade-up">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 p-2">
                <div class="intro">
                    <img
                        src="{{asset('site')}}/assets/images/musand.svg"
                        data-aos="zoom-in"
                        data-aos-delay="100"
                        alt="" />
                    <h4 class="head" data-aos="fade-up" data-aos-delay="200">
                        {{__('ingaz.musaned_intro_title')}}
                    </h4>
                    <p class="info" data-aos="fade-up" data-aos-delay="300">
                        {{__('ingaz.musaned_intro_description')}}
                    </p>
                </div>
            </div>
            <div class="col-md-6 p-2">
                <div class="introImg" data-aos="fade-left" data-aos-delay="400">
                    <img src="{{asset('site')}}/assets/images/musanedMap.svg" alt="" />
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================ musaned Features ================= -->
<section class="musanedFeatures" data-aos="fade-up">
    <div class="container">
        <div class="musanedServices">
            <div class="headTitle" data-aos="fade-up">
                <h2> {{__('ingaz.musaned_services_title')}}</h2>
                <p>{{__('ingaz.musaned_services_subtitle')}}</p>
            </div>
            <iframe
                src="https://www.youtube.com/embed/wlLjqeDDi2Y"
                data-aos="zoom-in"
                data-aos-delay="200"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen></iframe>
        </div>
        <div class="Feature">
            <div class="row flex-wrap">
                <!-- single Feature -->
                <div
                    class="col p-2 singleFeature"
                    data-aos="fade-up"
                    data-aos-delay="100">
                    <div class="info">
                        <div class="content">
                            <div class="icon">
                                <i class="fa-solid fa-badge-check"></i>
                            </div>
                            <h3> {{__('ingaz.feature_e_contract')}}</h3>
                            <p>
                                {{__('ingaz.feature_common_description')}}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- single Feature -->
                <div
                    class="col p-2 singleFeature"
                    data-aos="fade-up"
                    data-aos-delay="200">
                    <div class="info">
                        <div class="content">
                            <div class="icon">
                                <i class="fa-solid fa-buildings"></i>
                            </div>
                            <h3> {{__('ingaz.feature_manage_offices')}}</h3>
                            <p>
                                {{__('ingaz.feature_common_description')}}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- single Feature -->
                <div
                    class="col p-2 singleFeature"
                    data-aos="fade-up"
                    data-aos-delay="300">
                    <div class="info">
                        <div class="content">
                            <div class="icon">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <h3> {{__('ingaz.feature_rental_services')}} </h3>
                            <p>
                                {{__('ingaz.feature_common_description')}}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================ musaned recruitment ================= -->
<section class="musanedRecruitment">
    <div class="container">
        <div class="headTitle" data-aos="fade-up">
            <h4>{{__('ingaz.recruitment_journey_heading')}}</h4>
            <h1>{{__('ingaz.recruitment_journey_title')}}</h1>
            <p>
             {{__('ingaz.recruitment_journey_description')}}
            </p>
        </div>
        <div class="row flex-wrap">
            <div class="col p-2">
                <div class="specifications" data-aos="zoom-in">
                    <i class="fa-solid fa-ballot-check"></i>
                    <h5> {{__('ingaz.recruitment_specifications_title')}}</h5>
                </div>
            </div>
            <div class="col p-2">
                <div
                    class="specifications"
                    data-aos="zoom-in"
                    data-aos-delay="150">
                    <i class="fa-solid fa-briefcase"></i>
                    <h5>{{__('ingaz.recruitment_job_title')}}</h5>
                </div>
            </div>
            <div class="col p-2">
                <div
                    class="specifications"
                    data-aos="zoom-in"
                    data-aos-delay="300">
                    <i class="fa-solid fa-globe"></i>
                    <h5>{{__('ingaz.recruitment_nationality_title')}}</h5>
                </div>
            </div>
        </div>
        <div class="pt-5 mt-5">
            <div class="headTitle" data-aos="fade-up">
                <h4>{{__('ingaz.recruitment_steps_heading')}}</h4>
                <h1>{{__('ingaz.recruitment_steps_title')}}</h1>
            </div>
            <div class="row flex-wrap">
                <div class="col p-2">
                    <div class="specifications" data-aos="fade-up">
                        <i class="fa-solid fa-hand-pointer"></i>
                        <h5>{{__('ingaz.step_select_type_title')}}</h5>
                        <p>
                            {{__('ingaz.step_select_type_description')}}
                        </p>
                    </div>
                </div>
                <div class="col p-2">
                    <div
                        class="specifications"
                        data-aos="fade-up"
                        data-aos-delay="150">
                        <i class="fa-solid fa-buildings"></i>
                        <h5>     {{__('ingaz.step_select_provider_title')}}</h5>
                        <p>
                            {{__('ingaz.step_select_provider_description')}}
                         </p>
                    </div>
                </div>
                <div class="col p-2">
                    <div
                        class="specifications"
                        data-aos="fade-up"
                        data-aos-delay="300">
                        <i class="fa-solid fa-file-pen"></i>
                        <h5>    {{__('ingaz.step_receive_offers_title')}}</h5>
                        <p>
                          {{__('ingaz.step_receive_offers_description')}}
                        </p>
                    </div>
                </div>
                <div class="col p-2">
                    <div
                        class="specifications"
                        data-aos="fade-up"
                        data-aos-delay="450">
                        <i class="fa-solid fa-credit-card"></i>
                        <h5>    {{__('ingaz.step_payment_title')}} </h5>
                        <p>
                            {{__('ingaz.step_payment_description')}}
                        </p>
                    </div>
                </div>
                <div class="col p-2">
                    <div
                        class="specifications"
                        data-aos="fade-up"
                        data-aos-delay="600">
                        <i class="fa-solid fa-location-crosshairs"></i>
                        <h5>    {{__('ingaz.step_track_title')}}</h5>
                        <p>
                            {{__('ingaz.step_track_description')}}
                         </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- ================ musaned Fees ================= -->
<section class="musanedFees">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-9 p-2">
                <div class="headTitle wow fadeInUp">
                    <h4> {{__('ingaz.fees_heading')}}</h4>
                    <h2>{{__('ingaz.fees_title')}}</h2>
                </div>
                <p>
{{__('ingaz.fees_description')}}
                </p>
                <div class="images">
                    <img src="{{asset('site')}}/assets/images/mc.webp" />
                    <img src="{{asset('site')}}/assets/images/visa.webp" />
                    <img src="{{asset('site')}}/assets/images/mada.webp" />
                </div>
            </div>
        </div>
    </div>
</section>
<!-- ================ /musaned Fees ================= -->
<!-- ================ musaned app ================= -->
<section class="musanedApp">
    <div class="container">
        <div class="row">
            <div class="col-md-6 p-0 p-md-2 d-none d-md-block">
                <div class="appImg">
                    <img src="{{asset('site')}}/assets/images/app.webp" alt="" />
                </div>
            </div>
            <div class="col-md-6 p-2">
                <div class="info">
                    <img src="assets/images/musand.svg" alt="" />
                    <h4>{{__('ingaz.app_title')}}</h4>
                    <p>
                         {{__('ingaz.app_description')}}
                    </p>

                    <div class="appImg d-md-none">
                        <img src="{{asset('site')}}/assets/images/app.webp" alt="" />
                    </div>

                    <div class="links">
                        <a href="{{$settings->android_link}}" target="_blank">
                            <img src="{{asset('site')}}/assets/images/google-play-android.webp" alt="" />
                        </a>
                        <a href="{{$settings->ios_link}}" target="_blank">
                            <img src="{{asset('site')}}/assets/images/apple-iphone-ios.webp" alt="" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- ================ /musaned app ================= -->
@endsection
@section('js')
@endsection