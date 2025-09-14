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
                    <!-- ================ routeNav ================= -->
                    <div class="routeNav">
                        <button onclick="goBack()" class="Back">
                            <i class="fas fa-angle-right"></i>
                        </button>
                        <ul>
                            <li>
                                <a href="profile.html"> طلبات الاستقدام </a>
                            </li>
                            <li>
                                <a href="#!" class="active"> تفاصيل الطلب </a>
                            </li>
                        </ul>
                    </div>
                    <!-- ================ /routeNav ================= -->
                    <!-- status -->
                    <div class="status">
                        <ol>
                            <li class="completed">
                                <p>اختيار العمالة </p>
                            </li>
                            <li class="completed">
                                <p>حجز السيره الذاتية </p>
                            </li>
                            <li class="completed">
                                <p>اختار احد ممثلى خدمة العملاء </p>
                            </li>
                            <li>
                                <p>ابرام التعاقد </p>
                            </li>
                            <li>
                                <p> اصدار تاشيرة </p>
                            </li>
                            <li>
                                <p>وصول العمالة </p>
                            </li>
                        </ol>
                    </div>
                    <!-- order -->
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
                    </div>
                    <!-- customerInfo -->
                    <div class="selectedCustomerInfo">
                        <div class="row">
                            <div class="col-md-6 p-1">
                                <div class="info">
                                    <img src="assets/images/avater2.webp" alt="">
                                    <div class="text">
                                        <h5> أ.منار </h5>
                                        <p>خدمة العملاء</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3 p-1">
                                <a class="contact" href="#!" target="_blank">
                                    <i class="me-2 fa-brands fa-whatsapp-square"></i>
                                    <p>تواصل عبر الواتس اب</p>
                                </a>
                            </div>
                            <div class="col-6 col-md-3 p-1">
                                <a class="contact" href="#!" target="_blank">
                                    <i class="me-2 fa-solid fa-square-phone"></i>
                                    <p>تواصل عبر الهاتف </p>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="selectedCustomer">
                        <h6>سوف توصل العمالة فى خلال <span> 120 </span> يوم كحد اقصى </h6>
                        <div id="timer">
                            <div id="days"></div>
                            <div id="hours"></div>
                            <div id="minutes"></div>
                            <div id="seconds"></div>
                        </div>
                        <h6> تم ابرام التعاقد وفي انتظار اصدار التاشيرة لعاملتك </h6>
                        <!-- button for modal -->
                        <button class="defaultBtn mt-4" data-bs-toggle="modal" data-bs-target="#locationModal"><span></span>
                            اختيار موقع تسلم العمالة <i class="fa-solid fa-map-location ms-2"></i> </button>
                        <!-- Modal -->
                        <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel"
                             aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="locationModalLabel">اختيار موقع تسلم العمالة</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-start">
                                        <label class="form-label"> <i class="fas fa-city me-2"></i> المدينة </label>
                                        <select class="form-control">
                                            <option value=""> جدة </option>
                                            <option value=""> الرياض </option>
                                        </select>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> الغاء </button>
                                        <button type="button" class="btn btn-primary"> تاكيد </button>
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
