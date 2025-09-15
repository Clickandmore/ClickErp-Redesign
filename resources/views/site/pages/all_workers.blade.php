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
                <a href="#!" class="active"> طلب استقدام </a>
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
    <section class="allWorkers">
        <div class="container">
            <div class="row">
                <div class="col-md-4 col-lg-4 p-1">
                    <!-- filter -->
                    <div class="filter">
                        <h4 class="sideTitle mb-4">بحث متقدم</h4>

                        <div class="toggleBtns mb-4">
                            <button type="button" class="btn toggle-btn active" data-target="all">
                                الكل
                            </button>
                            <button type="button" class="btn toggle-btn" data-target="estqdam">
                                استقدام
                            </button>
                            <button type="button" class="btn toggle-btn" data-target="naql">
                                نقل خدمات
                            </button>
                            <button type="button" class="btn toggle-btn" data-target="egar">
                                إيجار عمالة
                            </button>
                        </div>

                        <form action="" class="row">
                            <!-- age -->
                            <div class="py-3 col col-md-12" style="min-width: 120px">
                                <label for="age">
                                    <i class="fa-duotone fa-hourglass me-1"></i> العمر
                                </label>
                                <select id="age" class="select2">
                                    <option value="" selected>الكل</option>
                                    <option value="">من 20 الى 30سنة</option>
                                    <option value="">من 31 الى 40سنة</option>
                                    <option value="">من 41 الى 50سنة</option>
                                </select>
                            </div>
                            <!-- Occupation -->
                            <div class="py-3 col col-md-12" style="min-width: 120px">
                                <label for="job">
                                    <i class="fa-duotone fa-briefcase me-1"></i> المهنه
                                </label>
                                <select id="job" class="select2">
                                    <option value="" selected>الكل</option>
                                    <option value="">سائق خاص</option>
                                    <option value="">عمالة منزلية</option>
                                </select>
                            </div>
                            <!-- Nationality -->
                            <div class="py-3 col col-md-12" style="min-width: 120px">
                                <label for="nationality">
                                    <i class="fa-duotone fa-flag me-1"></i> الجنسية
                                </label>
                                <select id="nationality" class="select2">
                                    <option value="" selected>الكل</option>
                                    <option value="">كينيا</option>
                                    <option value="">بنجلادش</option>
                                    <option value="">الفليبين</option>
                                </select>
                            </div>
                            <div
                                class="pt-4 d-flex align-items-center justify-content-center flex-wrap"
                            >
                                <button type="button" class="btn clear m-1">
                                    مسح
                                    <span></span>
                                </button>
                                <button type="submit" class="btn confirm">
                                    <span></span>
                                    تطبيق
                                </button>
                            </div>
                        </form>
                    </div>
                    <!-- end filter -->
                </div>
                <div class="col-md-8 col-lg-8 p-0">
                    <div class="workers-list">
                        <div class="row">
                            <!-- كارد مشترك (ايجار + نقل خدمات) -->
                            <div
                                class="col-12 col-md-6 p-2 mb-5"
                                data-type="egar naql"
                            >
                                <a href="cv.html" class="worker-box-link">
                                    <div class="worker-box">
                                        <div class="worker-media">
                                            <img src="assets/images/Driver.jpg" alt="worker"/>
                                        </div>
                                        <div class="worker-content">
                                            <div class="worker-header">
                                                <div class="worker-age"><span>30</span> سنة</div>
                                                <div class="worker-id">M-SUM1 / 003</div>
                                            </div>
                                            <h4>خالد توفيق مجدي</h4>
                                            <div class="workerInfo">
                                                <p>الجنسية : الفلبين</p>
                                                <p>الديانة : غير مسلم</p>
                                                <p>المهنة : سائق خاص</p>
                                                <p>الخبرة : سنة</p>
                                                <p>التكلفة شاملة الضريبة : 6500 ريال</p>
                                                <p>مدة الاستقدام / النقل : 20 يوم</p>
                                            </div>
                                            <p class="guarantee">
                                                تنويه : يتم سداد التكاليف عن طريق منصة مساند
                                            </p>
                                            <div class="actions">
                                                <a
                                                    data-fancybox="cv1"
                                                    href="assets/images/cv1.webp"
                                                    class="icon-btn"
                                                >
                                                    <i class="fa-regular fa-file-pdf"></i>
                                                </a>
                                                <a
                                                    data-fancybox="video1"
                                                    href="assets/images/video.mp4"
                                                    class="icon-btn"
                                                >
                                                    <i class="fa-solid fa-video"></i>
                                                </a>
                                                <button class="btn">ايجار عمالة</button>
                                                <button class="btn">نقل خدمات</button>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- كارد خاص بالاستقدام -->
                            <div class="col-12 col-md-6 p-2 mb-5" data-type="estqdam">
                                <a href="cv.html" class="worker-box-link">
                                    <div class="worker-box">
                                        <div class="worker-media">
                                            <img src="assets/images/house.png" alt="worker"/>
                                        </div>
                                        <div class="worker-content">
                                            <div class="worker-header">
                                                <div class="worker-age"><span>25</span> سنة</div>
                                                <div class="worker-id">K-SUM1 / 002</div>
                                            </div>
                                            <h4>ماريا توفيق مجدى</h4>
                                            <div class="workerInfo">
                                                <p>الجنسية : الفلبين</p>
                                                <p>الديانة : غير مسلم</p>
                                                <p>المهنة : عاملة منزلية</p>
                                                <p>الخبرة : لم يسبق العمل</p>
                                                <p>التكلفة شاملة الضريبة : 5700 ريال</p>
                                                <p>مدة الاستقدام : خلال 45 يوم</p>
                                            </div>
                                            <p class="guarantee">
                                                تنويه : يتم السداد عبر منصة مساند
                                            </p>

                                            <div class="actions">
                                                <a
                                                    data-fancybox="cv2"
                                                    href="assets/images/cv1.webp"
                                                    class="icon-btn"
                                                >
                                                    <i class="fa-regular fa-file-pdf"></i>
                                                </a>
                                                <a
                                                    data-fancybox="video2"
                                                    href="assets/images/video.mp4"
                                                    class="icon-btn"
                                                >
                                                    <i class="fa-solid fa-video"></i>
                                                </a>
                                                <button class="btn action-btn">استقدام</button>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <!-- كارد خاص بالاستقدام -->
                            <div class="col-12 col-md-6 p-2 mb-5" data-type="estqdam">
                                <a href="cv.html" class="worker-box-link">
                                    <div class="worker-box">
                                        <div class="worker-media">
                                            <img src="assets/images/house.png" alt="worker"/>
                                        </div>
                                        <div class="worker-content">
                                            <div class="worker-header">
                                                <div class="worker-age"><span>25</span> سنة</div>
                                                <div class="worker-id">K-SUM1 / 002</div>
                                            </div>
                                            <h4>ماريا توفيق مجدى</h4>
                                            <div class="workerInfo">
                                                <p>الجنسية : الفلبين</p>
                                                <p>الديانة : غير مسلم</p>
                                                <p>المهنة : عاملة منزلية</p>
                                                <p>الخبرة : لم يسبق العمل</p>
                                                <p>التكلفة شاملة الضريبة : 5700 ريال</p>
                                                <p>مدة الاستقدام : خلال 45 يوم</p>
                                            </div>
                                            <p class="guarantee">
                                                تنويه : يتم السداد عبر منصة مساند
                                            </p>

                                            <div class="actions">
                                                <a
                                                    data-fancybox="cv2"
                                                    href="assets/images/cv1.webp"
                                                    class="icon-btn"
                                                >
                                                    <i class="fa-regular fa-file-pdf"></i>
                                                </a>
                                                <a
                                                    data-fancybox="video2"
                                                    href="assets/images/video.mp4"
                                                    class="icon-btn"
                                                >
                                                    <i class="fa-solid fa-video"></i>
                                                </a>
                                                <button class="btn action-btn">استقدام</button>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <!-- كارد خاص بالنقل -->
                            <div class="col-12 col-md-6 p-2 mb-5" data-type="naql">
                                <a href="cv.html" class="worker-box-link">
                                    <div class="worker-box">
                                        <div class="worker-media">
                                            <img src="assets/images/Driver.jpg" alt="worker"/>
                                        </div>
                                        <div class="worker-content">
                                            <div class="worker-header">
                                                <div class="worker-age"><span>30</span> سنة</div>
                                                <div class="worker-id">N-SUM1 / 001</div>
                                            </div>
                                            <h4>خالد توفيق مجدى</h4>
                                            <div class="workerInfo">
                                                <p>الجنسية : الفلبين</p>
                                                <p>الديانة : غير مسلم</p>
                                                <p>المهنة : سائق خاص</p>
                                                <p>الخبرة : لم يسبق العمل</p>
                                                <p>التكلفة شاملة الضريبة : 5700 ريال</p>
                                                <p>مدة النقل : خلال 15 يوم</p>
                                            </div>
                                            <p class="guarantee">
                                                تنويه : يتم سداد التكاليف عن طريق منصة مساند
                                            </p>
                                            <div class="actions">
                                                <a
                                                    data-fancybox="cv2"
                                                    href="assets/images/cv1.webp"
                                                    class="icon-btn"
                                                >
                                                    <i class="fa-regular fa-file-pdf"></i>
                                                </a>
                                                <a
                                                    data-fancybox="video2"
                                                    href="assets/images/video.mp4"
                                                    class="icon-btn"
                                                >
                                                    <i class="fa-solid fa-video"></i>
                                                </a>
                                                <button class="btn action-btn">نقل خدمات</button>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- كارد خاص بالإيجار -->
                            <div class="col-12 col-md-6 p-2 mb-5" data-type="egar">
                                <a href="cv.html" class="worker-box-link">
                                    <div class="worker-box">
                                        <div class="worker-media">
                                            <img src="assets/images/house.png" alt="worker"/>
                                        </div>
                                        <div class="worker-content">
                                            <div class="worker-header">
                                                <div class="worker-age"><span>28</span> سنة</div>
                                                <div class="worker-id">E-SUM1 / 001</div>
                                            </div>
                                            <h4>ماريا أحمد</h4>
                                            <div class="workerInfo">
                                                <p>الجنسية : أوغندا</p>
                                                <p>الديانة : مسلم</p>
                                                <p>المهنة : عاملة منزلية</p>
                                                <p>الخبرة : سنتين</p>
                                                <p>الإيجار الشهري : 2500 ريال</p>
                                                <p>مدة العقد : 6 أشهر</p>
                                            </div>
                                            <p class="guarantee">
                                                تنويه : يتم توثيق العقد عبر منصة مساند
                                            </p>
                                            <div class="actions">
                                                <a
                                                    data-fancybox="cv2"
                                                    href="assets/images/cv1.webp"
                                                    class="icon-btn"
                                                >
                                                    <i class="fa-regular fa-file-pdf"></i>
                                                </a>
                                                <a
                                                    data-fancybox="video2"
                                                    href="assets/images/video.mp4"
                                                    class="icon-btn"
                                                >
                                                    <i class="fa-solid fa-video"></i>
                                                </a>
                                                <button class="btn action-btn">ايجار عمالة</button>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-12 col-md-6 p-2 mb-5" data-type="egar naql">
                                <a href="cv.html" class="worker-box-link">
                                    <div class="worker-box">
                                        <div class="worker-media">
                                            <img src="assets/images/Driver.jpg" alt="worker"/>
                                        </div>
                                        <div class="worker-content">
                                            <div class="worker-header">
                                                <div class="worker-age"><span>30</span> سنة</div>
                                                <div class="worker-id">N-SUM1 / 001</div>
                                            </div>
                                            <h4>خالد توفيق مجدى</h4>
                                            <div class="workerInfo">
                                                <p>الجنسية : الفلبين</p>
                                                <p>الديانة : غير مسلم</p>
                                                <p>المهنة : سائق خاص</p>
                                                <p>الخبرة : لم يسبق العمل</p>
                                                <p>التكلفة شاملة الضريبة : 5700 ريال</p>
                                                <p>مدة النقل : خلال 15 يوم</p>
                                            </div>
                                            <p class="guarantee">
                                                تنويه : يتم سداد التكاليف عن طريق منصة مساند
                                            </p>
                                            <div class="actions">
                                                <a
                                                    data-fancybox="cv2"
                                                    href="assets/images/cv1.webp"
                                                    class="icon-btn"
                                                >
                                                    <i class="fa-regular fa-file-pdf"></i>
                                                </a>
                                                <a
                                                    data-fancybox="video2"
                                                    href="assets/images/video.mp4"
                                                    class="icon-btn"
                                                >
                                                    <i class="fa-solid fa-video"></i>
                                                </a>
                                                <button class="btn action-btn">نقل خدمات</button>
                                                <button class="btn action-btn">ايجار عمالة</button>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="pagination-outer">
                        <div class="pagination-style1">
                            <ul class="clearfix">
                                <li class="prev">
                                    <a href="javascript:;"
                                    ><span> <i class="fa fa-angle-right"></i> </span
                                        ></a>
                                </li>
                                <li><a href="javascript:;">1</a></li>
                                <li class="active"><a href="javascript:;">2</a></li>
                                <li><a href="javascript:;">3</a></li>
                                <li>
                                    <a class="javascript:;" href="javascript:;"
                                    ><i class="fa fa-ellipsis-h"></i
                                        ></a>
                                </li>
                                <li><a href="javascript:;">5</a></li>
                                <li class="next">
                                    <a href="javascript:;"
                                    ><span> <i class="fa fa-angle-left"></i> </span
                                        ></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- Modal -->
                    <div class="modal fade cvModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <button class="closeBtn" data-bs-dismiss="modal">
                                    <i class="fa-regular fa-xmark"></i>
                                </button>
                                <div class="modal-body p-0">
                                    <div class="cv">
                                        <div class="row">
                                            <div class="col-lg-5 p-2">
                                                <div class="mainImg">
                                                    <a
                                                        data-fancybox="user1-CV"
                                                        href="assets/images/Driver.jpg"
                                                    >
                                                        <img src="assets/images/Driver.jpg" alt=""/>
                                                    </a>
                                                </div>
                                                <div class="moreImgs">
                                                    <a
                                                        data-fancybox="user1-CV"
                                                        href="assets/images/cv1.webp"
                                                    >
                                                        <img src="assets/images/cv1.webp" alt=""/>
                                                    </a>
                                                    <a
                                                        data-fancybox="user1-CV"
                                                        href="assets/images/cv2.webp"
                                                    >
                                                        <img src="assets/images/cv2.webp" alt=""/>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="col-lg-7 p-2">
                                                <div class="info">
                                                    <ul>
                                                        <li>
                                                            <p class="title">المهنة :</p>
                                                            <h5 class="data">سائق خاص</h5>
                                                        </li>
                                                        <li>
                                                            <p class="title">الجنسية :</p>
                                                            <h5 class="data">الهند</h5>
                                                        </li>
                                                        <li>
                                                            <p class="title">الديانة :</p>
                                                            <h5 class="data">غير مسلم</h5>
                                                        </li>
                                                        <li>
                                                            <p class="title">الحالة الإجتماعية :</p>
                                                            <h5 class="data">أعزب</h5>
                                                        </li>
                                                        <li>
                                                            <p class="title">الخبرة العلمية :</p>
                                                            <h5 class="data">----------</h5>
                                                        </li>
                                                        <li>
                                                            <p class="title">راتب العمالة :</p>
                                                            <h5 class="data">900 ريال</h5>
                                                        </li>
                                                        <li>
                                                            <p class="title">سعر الاستقدام :</p>
                                                            <h5 class="data">1600 ريال</h5>
                                                        </li>
                                                    </ul>
                                                    <p class="alertText">
                                                        لضمان حقك، لا يتم سداد الرسوم بعد الحجز إلا عند
                                                        طريق منصة مساند
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <a href="#!" class="btn">تنزيل السيرة الذاتية </a>
                                    <a href="customer-service.html" class="btn book">
                                        احجز السيرة الذاتية
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- custom Order -->
            <div class="alert fade show customOrder wow fadeInUp" role="alert">
                <button
                    type="button"
                    class="btn-close p-2"
                    data-bs-dismiss="alert"
                    aria-label="Close"
                ></button>
                <h6>يمكننا توفير العمالة المناسبة طبقا لمواصفاتك الخاصه</h6>
                <a class="customBtn" href="recruitment-request.html"
                ><span></span> تقديم طلب خاص
                </a>
            </div>
            <!-- end custom Order -->
        </div>
    </section>
@endsection
@section('js')
@endsection
