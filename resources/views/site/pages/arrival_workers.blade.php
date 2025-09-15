@extends('site.layouts.index')
@section('title')
@endsection
@section('content')
    <!-- Main Banner  -->
    <section class="mainBanner">
        <button onclick="goBack()" class="Back">
            <i class="fas fa-angle-left"></i>
        </button>
        <ul>
            <li>
                <a href="index.html"> الرئيسية </a>
            </li>
            <li>
                <a href="index.html#services">رحلة الاستقدام </a>
            </li>
            <li>
                <a href="#!" class="active"> وصول العمالة </a>
            </li>
        </ul>
        <figure>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                preserveAspectRatio="none"
                x="0px"
                y="0px"
                viewBox="0 0 300 100"
                style="margin-bottom: -35px; enable-background: new 0 0 300 100"
                class="injected-svg js-svg-injector"
                data-parent="#SVGwave1BottomSMShape"
            >
                <g>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        opacity=".8"
                        d="M10.9,63.9c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7C293.7,41,315,61.2,315,61.2v54.4H10.9V63.9z"
                    ></path>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        opacity=".6"
                        d="M-55.7,64.6c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7c48.7-4.1,69.9,16.2,69.9,16.2v54.4H-55.7     V64.6z"
                    ></path>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        opacity=".4"
                        fill-opacity="0"
                        d="M23.4,118.3c0,0,48.3-68.9,109.1-68.9c65.9,0,98,67.9,98,67.9v3.7H22.4L23.4,118.3z"
                    ></path>
                    <path
                        class="wave-bottom-1-sm-0"
                        fill="#fdfdfd"
                        d="M-54.7,83c0,0,56-45.7,120.3-27.8c81.8,22.7,111.4,6.2,146.6-4.7c53.1-16.4,104,36.9,104,36.9l1.3,36.7l-372-3     L-54.7,83z"
                    ></path>
                </g>
            </svg>
        </figure>
    </section>
    <!-- ================ /arrive Worker ================= -->
    <div class="arriveWorker">
        <div class="container">
            <!-- arrive Worker Info -->
            <div class="arriveWorkerInfo">
                <div class="image wow fadeInUp">
                    <img src="assets/images/arrive.webp">
                </div>
                <div class="info wow fadeInUp">
                    <h3> وصول العمالة </h3>
                    <p>
                        فريق العمل يضمن دائما وباستمرار تقديم خدمات سريعة ومتميزة في استقدام العمالة المنزلية المدربة
                        داخل
                        المملكة العربية السعودية مع متابعة مستمرة للعامل أو العاملة من خلال إشعار العميل بوقت وصول
                        العمالة الوافدة
                        بسرعة ودقة عالية، والتنسيق لاستقبال السائق الخاص أو الخادمة المنزلية واستلام كامل وثائق استقدام
                        العمالة
                        المنزلية
                    </p>
                    <h5>عائلتك اسعد ,حياتك افضل </h5>
                </div>
            </div>
            <!-- arrive Worker Info -->
            <div class="arriveWorkerInfo">
                <div class="image wow fadeInUp">
                    <img src="assets/images/f1.webp">
                </div>
                <div class="info wow fadeInUp">
                    <h3> خدمة العملاء </h3>
                    <p>
                        دائما ما نتبنى أحدث الأساليب والتقنيات للتعامل مع العملاء وضمان جودة عالية في الخدمات المقدمة في
                        مكتب
                        ترف للاستقدام كما أننا نوفر جميع الأدوات لدراسة السوق لتطوير وتوفير خدمات ذات جودة عالية
                        لاستقدام
                        العمالة المنزلية وللتواصل مع العملاء قبل وبعد وصول العمالة المنزلية , نلتزم بشكل تام لخدمة مختلف
                        الاحتياجات بمصداقية عالية وشفافية مطلقة حيث أننا نطمح لتجاوز توقعات عملائنا وبناء علاقة طويلة
                        المدى مع
                        كافة العملاء لمعرفة كافة احتياجاتهم ومتطلباتهم.


                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- ================ references ================= -->
    <section class="references">
        <div class="container">
            <div class="swiper referencesSlider ">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="referenceLogo ">
                            <img src="assets/images/Ministry-of-Foreign-Affairs-01.svg" alt="">
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="referenceLogo ">
                            <img src="assets/images/Ministry-of-Interior-01.svg" alt="">
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="referenceLogo ">
                            <img src="assets/images/Ministry-of-Labor-and-Social-Development.svg" alt="">
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="referenceLogo ">
                            <img src="assets/images/musand.svg" alt="">
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>
@endsection
@section('js')
@endsection
