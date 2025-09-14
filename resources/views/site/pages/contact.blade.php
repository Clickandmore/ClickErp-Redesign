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
                <a href="#!" class="active"> تواصل معنا </a>
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
    <!-- mapEarth -->
    <section class="mapEarth">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="worldMap">
                        <div class="earth"></div>
                        <div class="orbic">
                            <svg viewBox="0 0 500 500" width="0" height="0">
                                <g id="orbic_path">
                                    <ellipse
                                        cx="250"
                                        cy="250"
                                        rx="240"
                                        ry="100"
                                        transform="rotate(-10,250,250)"
                                    ></ellipse>
                                    <path d="M230,192Q300,25 375,146"></path>
                                    <path d="M375,146Q450,175 410,301"></path>
                                    <path d="M40,234Q300,125 410,301"></path>
                                    <path d="M410,301Q260,165 125,354"></path>
                                    <path d="M125,354Q150,220 230,192"></path>
                                    <path d="M40,234Q130,200 125,354"></path>
                                </g>
                                <g id="orbic_dots">
                                    <defs>
                                        <circle id="orbic_dot" cx="0" cy="0" r="6"></circle>
                                    </defs>
                                    <use id="orbic_dot1" xlink:href="#orbic_dot"></use>
                                    <use id="orbic_dot2" xlink:href="#orbic_dot"></use>
                                    <use id="orbic_dot3" xlink:href="#orbic_dot"></use>
                                    <use id="orbic_dot4" xlink:href="#orbic_dot"></use>
                                    <use id="orbic_dot5" xlink:href="#orbic_dot"></use>
                                </g>
                                <g id="orbic_users">
                                    <image
                                        id="orbic_user1"
                                        xlink:href="assets/images/user1.webp"
                                        width="20%"
                                        height="20%"
                                    ></image>
                                    <image
                                        id="orbic_user2"
                                        xlink:href="assets/images/user2.webp"
                                        width="20%"
                                        height="20%"
                                    ></image>
                                    <image
                                        id="orbic_user3"
                                        xlink:href="assets/images/user3.webp"
                                        width="20%"
                                        height="20%"
                                    ></image>
                                    <image
                                        id="orbic_user4"
                                        xlink:href="assets/images/user4.webp"
                                        width="20%"
                                        height="20%"
                                    ></image>
                                    <image
                                        id="orbic_user5"
                                        xlink:href="assets/images/user5.webp"
                                        width="20%"
                                        height="20%"
                                    ></image>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 pt-lg-5">
                    <h4 class="title"><span> كن علي اتصال </span></h4>
                    <div class="companyInfo">
                        <ul>
                            <li class="wow fadeInUp">
                                <span><i class="fa-solid fa-map-location"></i></span>
                                <p class="ms-3">
                                    عنوان مقرنا :
                                    <a href="#!">
                                        طريق الدمام الفرعي، غرناطة، الرياض 13242
                                    </a>
                                </p>
                            </li>
                            <li class="wow fadeInUp">
                                <span><i class="fa-solid fa-phone"></i></span>
                                <p class="ms-3">
                                    المبيعات :
                                    <a href="tel:0550061304"> 0550061304 </a>
                                    <a href="tel:0550078902"> 0550078902 </a>
                                </p>
                            </li>
                            <li class="wow fadeInUp">
                                <span><i class="fa-solid fa-message-question"></i></span>
                                <p class="ms-3">
                                    الشكاوي والاقتراحات :
                                    <a href="tel:0552333182"> 0552333182 </a>
                                </p>
                            </li>
                            <li class="wow fadeInUp">
                                <span><i class="fas fa-envelope"></i></span>
                                <p class="ms-3">
                                    البريد الالكتروني :
                                    <a href="mailto:info@rawafd-najd.com "
                                    >info@rawafd-najd.com
                                    </a>
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================ contact Form ================= -->
    <section class="contactForm">
        <div class="container">
            <div class="row">
                <div class="col-md-6 p-2 mb-5 mb-md-0 wow fadeInUp">
                    <div class="headTitle text-start">
                        <h2>تواصل معنا</h2>
                        <p>
                            اطلب عاملتك الان وسيقوم فريق خدمة العملاء لدينا بالتواصل معك
                            بأسرع وقت ...
                        </p>
                    </div>
                    <form action="" class="row needs-validation" novalidate>
                        <div class="col-md-12 p-2">
                            <label class="form-label">
                                <i class="fas fa-user me-2"></i> الاسم كامل*
                            </label>
                            <input type="text" class="form-control" required/>
                            <div class="invalid-feedback">هذا الحقل ضروري</div>
                            <div class="valid-feedback">جيد</div>
                        </div>
                        <div class="col-md-6 p-2">
                            <label class="form-label"
                            ><i class="fas fa-phone-alt me-2"></i> رقم الهاتف *
                            </label>
                            <input type="tel" id="phoneNum" class="form-control" required/>
                            <div class="invalid-feedback">هذا الحقل ضروري</div>
                        </div>
                        <div class="col-md-6 p-2">
                            <label class="form-label">
                                <i class="fa-solid fa-comment-lines me-2"></i> الموضوع
                            </label>
                            <input type="text" class="form-control"/>
                        </div>
                        <div class="col-md-12 p-2">
                            <label class="form-label">
                                <i class="fas fa-feather-alt me-2"></i> اكتب رسالتك*
                            </label>
                            <textarea class="form-control" rows="5" required></textarea>
                            <div class="invalid-feedback">هذا الحقل ضروري</div>
                        </div>
                        <div class="col-md-12 text-center p-2">
                            <button type="submit" class="defaultBtn">
                                ارسال <i class="fas fa-paper-plane ms-2"></i>
                                <span></span>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 p-2 wow fadeInUp">
                    <section class="googleMap">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d57946.84623396069!2d46.75688!3d24.806481!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0xc74fdd6f15256594!2z2YXZg9iq2Kgg2LHZiNin2YHYryDZhtis2K8g2YTZhNil2LPYqtmC2K_Yp9mF!5e0!3m2!1sar!2ssa!4v1650724949947!5m2!1sar!2ssa"
                            frameborder="0"
                            style="border: 0"
                            allowfullscreen=""
                            aria-hidden="false"
                            tabindex="0"
                        ></iframe>
                    </section>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
@endsection
