@extends('site.layouts.index')
@section('title')

@endsection
@section('content')
    <section class="profile">
        <div class="container ">
            <!-- user Header  -->
            @include('site.pages.profile.includes.user_header')
            <!-- /user Header  -->
            <div class="row">
                <!-- links -->
                @include('site.pages.profile.includes.sidebar')
                <!-- content -->
                <div class=" col-md-8 col-lg-9 p-2 profileContent">
                    <a href="all-workers.html" class="newOrder">
                        <i class="fa-duotone fa-file-circle-plus"></i>
                        <p> تقديم طلب استقدام جديد </p>
                    </a>
                    <!-- orders -->
                    <div class="row">
                        <!-- single order -->
                        <div class="col-lg-6 p-2">
                            <div class="order">
                                <div class="row">
                                    <div class="col-sm-6 p-2">
                                        <div class="swiper workerCvSlider ">
                                            <div class="swiper-wrapper">
                                                <!-- cv image -->
                                                <div class="swiper-slide ">
                                                    <a data-fancybox="user1-CV" href="assets/images/cv1.webp">
                                                        <img src="assets/images/cv1.webp" alt="">
                                                    </a>
                                                </div>
                                                <!-- cv image -->
                                                <div class="swiper-slide ">
                                                    <a data-fancybox="user1-CV" href="assets/images/cv2.webp">
                                                        <img src="assets/images/cv2.webp" alt="">
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 p-2">
                                        <ul class="info">
                                            <li>
                                                <h6> الجنسية : </h6>
                                                <p>كينيا </p>
                                            </li>
                                            <li>
                                                <h6> المهنة : </h6>
                                                <p> -- </p>
                                            </li>
                                            <li>
                                                <h6> حالة الاستقدام : </h6>
                                                <p> تم اختيار احد مسؤلى خدمة العملاء </p>
                                            </li>

                                        </ul>
                                    </div>
                                </div>
                                <div class="profileCustomerInfo">
                                    <div class="info">
                                        <img src="assets/images/avater2.webp" alt="">
                                        <div class="text">
                                            <h6> أ.منار </h6>
                                            <p>خدمة العملاء</p>
                                        </div>
                                    </div>
                                    <div class="contact">
                                        <a href="order-details.html">
                                            <p> التفاصيل </p>
                                            <i class="fa-duotone fa-left-long ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- single order -->
                        <div class="col-lg-6 p-2">
                            <div class="order">
                                <div class="row">
                                    <div class="col-sm-6 p-2">
                                        <div class="swiper workerCvSlider ">
                                            <div class="swiper-wrapper">
                                                <!-- cv image -->
                                                <div class="swiper-slide ">
                                                    <a data-fancybox="user2-CV" href="assets/images/cv1.webp">
                                                        <img src="assets/images/cv1.webp" alt="">
                                                    </a>
                                                </div>
                                                <!-- cv image -->
                                                <div class="swiper-slide ">
                                                    <a data-fancybox="user2-CV" href="assets/images/cv2.webp">
                                                        <img src="assets/images/cv2.webp" alt="">
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 p-2">
                                        <ul class="info">
                                            <li>
                                                <h6> الجنسية : </h6>
                                                <p>كينيا </p>
                                            </li>
                                            <li>
                                                <h6> المهنة : </h6>
                                                <p> -- </p>
                                            </li>
                                            <li>
                                                <h6> حالة الاستقدام : </h6>
                                                <p> تم اختيار احد مسؤلى خدمة العملاء </p>
                                            </li>

                                        </ul>
                                    </div>
                                </div>
                                <div class="profileCustomerInfo">
                                    <div class="info">
                                        <img src="assets/images/avater2.webp" alt="">
                                        <div class="text">
                                            <h6> أ.منار </h6>
                                            <p>خدمة العملاء</p>
                                        </div>
                                    </div>
                                    <div class="contact">
                                        <a href="order-details.html">
                                            <p> التفاصيل </p>
                                            <i class="fa-duotone fa-left-long ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- single order -->
                        <div class="col-lg-6 p-2">
                            <div class="order">
                                <div class="row">
                                    <div class="col-sm-6 p-2">
                                        <div class="swiper workerCvSlider ">
                                            <div class="swiper-wrapper">
                                                <!-- cv image -->
                                                <div class="swiper-slide ">
                                                    <a data-fancybox="user3-CV" href="assets/images/cv1.webp">
                                                        <img src="assets/images/cv1.webp" alt="">
                                                    </a>
                                                </div>
                                                <!-- cv image -->
                                                <div class="swiper-slide ">
                                                    <a data-fancybox="user3-CV" href="assets/images/cv2.webp">
                                                        <img src="assets/images/cv2.webp" alt="">
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 p-2">
                                        <ul class="info">
                                            <li>
                                                <h6> الجنسية : </h6>
                                                <p>كينيا </p>
                                            </li>
                                            <li>
                                                <h6> المهنة : </h6>
                                                <p> -- </p>
                                            </li>
                                            <li>
                                                <h6> حالة الاستقدام : </h6>
                                                <p> تم اختيار احد مسؤلى خدمة العملاء </p>
                                            </li>

                                        </ul>
                                    </div>
                                </div>
                                <div class="profileCustomerInfo">
                                    <div class="info">
                                        <img src="assets/images/avater2.webp" alt="">
                                        <div class="text">
                                            <h6> أ.منار </h6>
                                            <p>خدمة العملاء</p>
                                        </div>
                                    </div>
                                    <div class="contact">
                                        <a href="order-details.html">
                                            <p> التفاصيل </p>
                                            <i class="fa-duotone fa-left-long ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
@endsection
