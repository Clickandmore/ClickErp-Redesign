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
                <a href="{{ route('site.home.index') }}"> {{ __('ingaz.home') }} </a>
            </li>
            <li>
                <a href="#!" class="active"> {{ __('ingaz.recruitment_request') }} </a>
            </li>
        </ul>
        <figure>
            <svg xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" x="0px" y="0px" viewBox="0 0 300 100"
                style="margin-bottom: -35px; enable-background: new 0 0 300 100" class="injected-svg js-svg-injector"
                data-parent="#SVGwave1BottomSMShape">
                <g>
                    <path class="wave-bottom-1-sm-0" fill="#fdfdfd" opacity=".8"
                        d="M10.9,63.9c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7C293.7,41,315,61.2,315,61.2v54.4H10.9V63.9z">
                    </path>
                    <path class="wave-bottom-1-sm-0" fill="#fdfdfd" opacity=".6"
                        d="M-55.7,64.6c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7c48.7-4.1,69.9,16.2,69.9,16.2v54.4H-55.7     V64.6z">
                    </path>
                    <path class="wave-bottom-1-sm-0" fill="#fdfdfd" opacity=".4" fill-opacity="0"
                        d="M23.4,118.3c0,0,48.3-68.9,109.1-68.9c65.9,0,98,67.9,98,67.9v3.7H22.4L23.4,118.3z"></path>
                    <path class="wave-bottom-1-sm-0" fill="#fdfdfd"
                        d="M-54.7,83c0,0,56-45.7,120.3-27.8c81.8,22.7,111.4,6.2,146.6-4.7c53.1-16.4,104,36.9,104,36.9l1.3,36.7l-372-3     L-54.7,83z">
                    </path>
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
                        <h4 class="sideTitle mb-4">{{ __('ingaz.advanced_search') }}</h4>

                        <div class="toggleBtns mb-4">
                            <button type="button" class="btn toggle-btn {{ $type == 'all' ? 'active' : '' }}" data-target="all">
                                {{ __('ingaz.all') }}
                            </button>
                            <button type="button" class="btn toggle-btn {{ $type == 'estqdam' ? 'active' : '' }}" data-target="estqdam">
                                {{ __('ingaz.recruitment') }}
                            </button>
                            <button type="button" class="btn toggle-btn {{ $type == 'egar' ? 'active' : '' }}" data-target="egar">
                                {{ __('ingaz.Labor_rental') }}
                            </button>
                            <button type="button" class="btn toggle-btn {{ $type == 'transfer' ? 'active' : '' }}" data-target="transfer">
                                {{ __('ingaz.Labor_transfer') }}
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
                            <div class="pt-4 d-flex align-items-center justify-content-center flex-wrap">
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
                    <div class="workers-list" id="workers-list">
                        @include('site.pages.maids_list', ['maids' => $maids])
                    </div>
                    <div class="pagination-outer" id="pagination">
                        <div class="pagination-style1">
                            {{ $maids->links('vendor.pagination.custom_2') }}
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
                                                    <a data-fancybox="user1-CV" href="assets/images/Driver.jpg">
                                                        <img src="assets/images/Driver.jpg" alt="" />
                                                    </a>
                                                </div>
                                                <div class="moreImgs">
                                                    <a data-fancybox="user1-CV" href="assets/images/cv1.webp">
                                                        <img src="assets/images/cv1.webp" alt="" />
                                                    </a>
                                                    <a data-fancybox="user1-CV" href="assets/images/cv2.webp">
                                                        <img src="assets/images/cv2.webp" alt="" />
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
                <button type="button" class="btn-close p-2" data-bs-dismiss="alert" aria-label="Close"></button>
                <h6>يمكننا توفير العمالة المناسبة طبقا لمواصفاتك الخاصه</h6>
                <a class="customBtn" href="recruitment-request.html"><span></span> تقديم طلب خاص
                </a>
            </div>
            <!-- end custom Order -->
        </div>
    </section>
@endsection
@section('js')

<script>
    const toggleBtns = document.querySelectorAll(".toggle-btn");
    const cards = document.querySelectorAll(".workers-list .col-12");
    const pagination = document.getElementById("pagination");

    const type = "{{ $type }}";

    // Function to handle filtering
    function filterCards(target) {
        let hasVisible = false;

        cards.forEach((card) => {
            const typeAttr = card.dataset.type || ""; // fallback if missing
            const types = typeAttr.split(" ").filter(Boolean); // split safely

            if (target === "all" || types.includes(target)) {
            card.classList.remove("d-none");
            hasVisible = true;
            } else {
            card.classList.add("d-none");
            }
        });

        // Show/hide pagination depending on if we have visible items
        if (hasVisible) {
            pagination.classList.remove("d-none");
        } else {
            pagination.classList.add("d-none");
        }
    }

    // Initial state on page load
    let initialBtn = document.querySelector(`.toggle-btn[data-target="${type}"]`);
    if (!initialBtn) {
      initialBtn = document.querySelector(`.toggle-btn[data-target="all"]`);
    }

    toggleBtns.forEach((b) => b.classList.remove("active"));
    initialBtn.classList.add("active");
    filterCards(initialBtn.dataset.target);

    // Handle click events
    toggleBtns.forEach((btn) => {
      btn.addEventListener("click", () => {
        toggleBtns.forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        filterCards(btn.dataset.target);
      });
    });
</script>

@endsection
