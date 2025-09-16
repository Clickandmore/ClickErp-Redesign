@extends('site.layouts.index')
@section('title')
@endsection
@section('content')
    <!-- main slider -->
    <section class="mainSlider">
        <div class="swiper">
            <div class="swiper-wrapper">
                @forelse($sliders as $slider)
                    <div class="swiper-slide" style="background-image: url({{ showFile($slider->image) }})">
                        <div class="info">
                            <h4 class="hint">
                                {{ $slider->title }}
                            </h4>
                            <h1 class="sliderTitle">
                                {{ $slider->description }}
                            </h1>
                        </div>
                    </div>
                @empty
                    <!-- swiper-slide -->
                    <div class="swiper-slide" style="background-image: url({{ asset('site') }}/assets/images/s1.webp)">

                        <div class="info">
                            <h4 class="hint">
                                بلاتينيوم الشرقية للاستقدام
                            </h4>
                            <h1 class="sliderTitle">
                                أكثر من 20 سنة من تقديم خدمات الاستقدام باحترافية عالية
                            </h1>
                        </div>
                    </div>
                    <!-- swiper-slide -->
                    <div class="swiper-slide" style="background-image: url({{ asset('site') }}/assets/images/s2.webp)">
                        <div class="info">
                            <h4 class="hint"> سهولة الاستخدام</h4>
                            <h1 class="sliderTitle">
                                ابدأ حجزك واتمم دفعك من خلال الموقع الالكتروني او التواصل معنا
                                بوقت وجيز وبخطوات مختصرة
                            </h1>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
    <!-- services -->
    <section class="hero-services" data-aos="fade-up">
        <div class="expertise">
            خبرةاكثر من
            <span> 20 <small> سنه </small> </span>
        </div>
        <a href="all-workers.html" class="service">
            <div class="icon">
                <svg viewBox="0 0 43.491 43.491">
                    <g>
                        <path
                            d="m41.625 9.778-3.22-1.961s2.054-5.874 1.875-6.688C40.104.316 39.232 0 39.232 0l-3.461 6.388-3.557-1.546-.021-2.333-1.087-.966s-.317 2.663-.77 3.308c-.449.644-2.229 1.851-2.229 1.851l.938.95 2.426-1.307 2.991 2.071-3.716 6.216s.749.923 1.486.521c.736-.402 4.342-5.037 4.342-5.037s3.501 1.958 3.873 2.043c.372.087 1.291.574 1.757-.54.466-1.113-.201-1.611-.579-1.841z">
                        </path>
                        <path
                            d="M36.078 16.177a15.68 15.68 0 0 0-1.732-1.745l-.899.93a14.635 14.635 0 0 1 1.592 1.587 15.085 15.085 0 0 1-3.024 1.496c-.832-2.472-2.047-4.528-3.511-5.924a13.92 13.92 0 0 1 1.609.646l.688-1.107a15.588 15.588 0 0 0-6.768-1.539H23.996l-.055.002c-4.798.028-9.092 2.224-11.951 5.654-.264.317-.514.645-.752.982a15.575 15.575 0 0 0-2.882 9.04 15.58 15.58 0 0 0 2.882 9.04c.238.337.488.666.752.981 2.86 3.431 7.153 5.625 11.951 5.653l.055.002H24.033c4.836 0 9.167-2.202 12.045-5.655.264-.316.514-.646.752-.982a15.586 15.586 0 0 0 2.881-9.04c0-3.365-1.068-6.483-2.881-9.04a14.816 14.816 0 0 0-.752-.981zM19.451 12.56c-1.441 1.39-2.638 3.422-3.463 5.861a14.877 14.877 0 0 1-2.959-1.473 14.452 14.452 0 0 1 6.422-4.388zm-7.177 5.372c.974.651 2.101 1.22 3.347 1.689a24.951 24.951 0 0 0-.856 6.446H9.646a14.31 14.31 0 0 1 2.628-8.135zm-2.581 9.422h5.096a24.65 24.65 0 0 0 .832 5.424 16.525 16.525 0 0 0-3.347 1.689 14.309 14.309 0 0 1-2.581-7.113zm3.34 8.093a14.951 14.951 0 0 1 2.953-1.469c.826 2.438 2.023 4.47 3.465 5.859a14.428 14.428 0 0 1-6.418-4.39zm10.358 5.096c-2.639-.361-4.917-3.068-6.209-6.967 1.862-.559 3.971-.896 6.209-.952v7.919zm0-9.204c-2.36.058-4.587.423-6.568 1.03a23.925 23.925 0 0 1-.745-5.016h7.313v3.986zm0-5.273h-7.339a24.25 24.25 0 0 1 .776-6.036c1.979.606 4.204.972 6.563 1.028v5.008zm0-6.293c-2.237-.055-4.346-.392-6.207-.95 1.293-3.896 3.568-6.607 6.207-6.969v7.919zm1.287-7.909c2.61.403 4.858 3.108 6.137 6.979-1.844.546-3.926.877-6.137.931v-7.91zm0 9.195c2.332-.056 4.53-.413 6.494-1.007.479 1.829.76 3.863.77 6.015h-7.264v-5.008zm0 6.295h7.238a24.033 24.033 0 0 1-.738 4.994c-1.965-.595-4.168-.953-6.5-1.009v-3.985zm0 13.18v-7.909c2.211.055 4.295.385 6.141.932-1.28 3.871-3.531 6.573-6.141 6.977zm3.824-.658c1.463-1.396 2.682-3.45 3.514-5.922a15.08 15.08 0 0 1 3.021 1.493 14.443 14.443 0 0 1-6.535 4.429zm7.292-5.409c-.991-.664-2.144-1.242-3.416-1.716.452-1.664.742-3.481.824-5.397h5.173a14.309 14.309 0 0 1-2.581 7.113zm-2.565-8.401c-.012-2.291-.317-4.462-.852-6.42 1.273-.474 2.424-1.052 3.415-1.714a14.302 14.302 0 0 1 2.628 8.134h-5.191z">
                        </path>
                        <path
                            d="M3.69 25.822c0-11.465 9.327-20.794 20.794-20.794 1.187 0 2.344.117 3.475.31l1.316-.811.434-1.44c-1.486-.3-3.65-.622-5.225-.622-12.88 0-23.357 10.479-23.357 23.357a30.4 30.4 0 0 0 .222 3.83c.641 5.061 7.079 13.838 12.19 13.839C8.005 39.533 3.69 33.273 3.69 25.822z">
                        </path>
                    </g>
                </svg>
            </div>
            <div class="text">
                <h4 class="title">طلب استقدام</h4>
                <p class="description">ايدي عاملة مميزة ومدربة بحرفية</p>
            </div>
        </a>
        <a href="all-workers.html" class="service">
            <div class="icon">
                <svg viewBox="0 0 64 64">
                    <g
                        transform="matrix(1.0799999999999996,0,0,1.0799999999999996,-2.5600001525878824,-2.5600003051757767)">
                        <path
                            d="M10.995 58a3.009 3.009 0 0 1-3-3V9a3.009 3.009 0 0 1 3-3h19a2.015 2.015 0 0 1 2 2v5a5.002 5.002 0 0 0 5 5h5a2.015 2.015 0 0 1 2 2v12.04c.33-.03.67-.04 1.01-.04.33 0 .66.01.99.04V20a3.951 3.951 0 0 0-1.04-2.68c-.04-.05-12.13-12.15-12.13-12.15A4.043 4.043 0 0 0 29.995 4h-19a5.002 5.002 0 0 0-5 5v46a5.002 5.002 0 0 0 5 5h26.54a14.377 14.377 0 0 1-2.71-2z">
                        </path>
                        <path
                            d="M40.995 22H19.473a1 1 0 0 0 0 2h21.522a1 1 0 0 0 0-2zM41.995 32a1 1 0 0 0-1-1H19.473a1 1 0 0 0 0 2h21.522a1 1 0 0 0 1-1zM31.745 40h-12.27a1 1 0 0 0 0 2h11.39a14.268 14.268 0 0 1 .88-2zM18.475 50a1.003 1.003 0 0 0 1 1h11.07a13.357 13.357 0 0 1-.4-2h-10.67a1.003 1.003 0 0 0-1 1zM16.702 20.626a1 1 0 0 0-1.414 0l-2.627 2.626-.959-.959a1 1 0 1 0-1.414 1.414l1.667 1.667a1 1 0 0 0 1.414 0l3.333-3.333a1 1 0 0 0 0-1.415zM16.702 29.626a1 1 0 0 0-1.414 0l-2.627 2.626-.959-.959a1 1 0 1 0-1.414 1.414l1.667 1.667a1 1 0 0 0 1.414 0l3.333-3.333a1 1 0 0 0 0-1.415zM16.702 38.626a1 1 0 0 0-1.414 0l-2.627 2.626-.959-.959a1 1 0 1 0-1.414 1.414l1.667 1.667a1 1 0 0 0 1.414 0l3.333-3.333a1 1 0 0 0 0-1.415zM15.288 47.626l-2.626 2.626-.96-.959a1 1 0 1 0-1.414 1.414l1.667 1.667a1 1 0 0 0 1.414 0l3.333-3.333a1 1 0 0 0-1.414-1.415zM45.005 34a13 13 0 1 0 13 13 13.012 13.012 0 0 0-13-13zm8.98 19.35a4.435 4.435 0 0 0-3.07-4.08l-.46-.15a7.137 7.137 0 0 1-10.91-.01l-.45.15a4.459 4.459 0 0 0-3.07 4.08 10.855 10.855 0 0 1-2.02-6.34 11 11 0 1 1 19.98 6.35z">
                        </path>
                        <circle cx="45.005" cy="44.49" r="5.16" transform="rotate(-13.34 44.909 44.48)"></circle>
                    </g>
                </svg>
            </div>
            <div class="text">
                <h4 class="title">نقل الخدمات</h4>
                <p class="description">تجربة نقل خدمات اسهل و افضل</p>
            </div>
        </a>
        <a href="all-workers.html" class="service">
            <div class="icon">
                <svg viewBox="0 0 64 64">
                    <g>
                        <path d="M32 4a28 28 0 1 0 28 28A28 28 0 0 0 32 4zm0 52A24 24 0 1 1 56 32 24 24 0 0 1 32 56z" />
                        <path d="M32 18a8 8 0 1 0 8 8 8 8 0 0 0-8-8zm0 12a4 4 0 1 1 4-4 4 4 0 0 1-4 4z" />
                        <path d="M32 34c-6.63 0-12 4.03-12 9v3h24v-3c0-4.97-5.37-9-12-9z" />
                    </g>
                </svg>
            </div>
            <div class="text">
                <h4 class="title">إيجار عمالة</h4>
                <p class="description">خدمات تأجير عمالة مدربة وموثوقة</p>
            </div>
        </a>

        <div class="warning">
            تنويه : يتم سداد تكاليف الاستقدام عن طريق منصة مساند
        </div>
    </section>
    <!-- about sec  -->
    <section class="about-section" id="about">
        <div class="container">
            <!-- Title & Intro -->
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <h2 class="section-title">
                    @if (isset($about) && isset($about->title))
                        {{ $about->title }}
                    @else
                        {{ __('ingaz.about_us') }}
                </h2>
                @endif
                <p class="section-subtitle">
                    @if (isset($about) && isset($about->description))
                        {!! $about->description !!}
                    @else
                        بلاتينيوم الشرقية للاستقدام مرخصة من وزارة العمل والموارد البشرية
                        لخدمات التوسط في استقدام العمالة الوافدة ومقرها المنطقة الشرقية في
                        المملكة العربية السعودية، وحرصاً منا على تقديم خدمات متميزة تم
                        اطلاق منصة رقمية متكاملة لتبسيط عملية الاستقدام وجعلها أكثر كفاءة
                        وسهولة لعملائنا بما يتوافق مع رؤية المملكة.
                    @endif
                </p>
                <ul class="about-highlights d-flex flex-wrap justify-content-center mt-4" data-aos="fade-up"
                    data-aos-delay="200">
                    @forelse($features as $feature)
                        <li><i class="{{ $feature->image }}"></i> {{ $feature->title }}</li>
                    @empty
                        <li><i class="fa-solid fa-headset"></i> خدمة عملاء على مدار الساعة</li>

                        <li><i class="fa-solid fa-id-card"></i> مرخص من وزارة الموارد البشرية والتنمية الاجتماعية</li>
                        <li><i class="fa-solid fa-shield-check"></i> معتمدين من منصة مساند للعمالة المنزلية</li>
                    @endforelse
                </ul>
            </div>

            <!-- Vision / Mission / Values -->
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="about-card">
                        <span class="about-number">01</span>
                        <i class="fa-solid fa-eye"></i>
                        <h4>{{ __('ingaz.vision') }}</h4>
                        <p>
                            @if (isset($about) && isset($about->vision))
                                {!! $about->vision !!}
                            @else
                                أن نكون الخيار الأول للاستقدام في المملكة العربية السعودية، وذلك
                                بالتفرد بتقديم أجود الخدمات وانتقاء أجود الكفاءات، والحرص على
                                راحة العملاء وحفظ حقوقهم، ونحقق ذلك بعد اكتساب ثقة العملاء
                                وتوصيتهم بنا لمن حولهم.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="about-card">
                        <span class="about-number">02</span>
                        <i class="fa-solid fa-envelope-open-text"></i>
                        <h4>{{ __('ingaz.mission') }}</h4>
                        <p>
                            @if (isset($about) && isset($about->mission))
                                {!! $about->mission !!}
                            @else
                                <strong>((انسانية))</strong> نفتخر بعملنا كونه يسهم بتوفير موارد
                                ودخل جيّد لأسرة وفرد بحاجة لهذه الموارد لدعم أسرته وبناء مستقبله،
                                وبالمقابل توفير سبل الراحة للأسرة السعودية والمقيمة في وطننا
                                المعطاء. <br />
                                ((( احتياجاتكم اولوياتنا ))).
                            @endif
                        </p>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="about-card">
                        <span class="about-number">03</span>
                        <i class="fa-solid fa-handshake"></i>
                        <h4>{{ __('ingaz.values') }}</h4>
                        <p>
                            @if (isset($about) && isset($about->value))
                                {!! $about->value !!}
                            @else
                                قيمنا تركز على رضى العميل، حيث نولي الأولوية للجودة والكمال في كل
                                خدماتنا. نحن روح الفريق لتحقيق النجاح، نحرص على السرعة والانجاز
                                لتلبية احتياجات عملائنا، ونعتبر النزاهة والشفافية أساساً في كل
                                تعاملاتنا.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================ services ================= -->
    <section class="services-sec" id="services">
        <div class="container">
            <div class="headTitle" data-aos="fade-up">
                <h2>{{ __('ingaz.our_services') }}</h2>
                <p>{{ __('ingaz.our_services_desc') }}</p>
            </div>
            <div class="row">
                @forelse($services as $service)
                    <div class="col p-2" data-aos="zoom-in" data-aos-delay="100">
                        <a href="#" class="service" style="background-image: url({{ asset($service->image) }})">
                            <i class="fa-solid fa-passport"></i>
                            <h3 class="serviceTitle"> {{ $service->title }}</h3>
                            <h6 class="serviceP">
                                {{ Str::limit($service->description, 100) }}
                            </h6>
                        </a>
                    </div>
                @empty
                    <div class="col p-2" data-aos="zoom-in" data-aos-delay="100">
                        <a href="visa.html" class="service" style="background-image: url(assets/images/passport-bg.webp)">
                            <i class="fa-solid fa-passport"></i>
                            <h3 class="serviceTitle">إصدار التأشيرة</h3>
                            <h6 class="serviceP">
                                طلب اصدار تاشيرة العمالة المنزلية الخاصة بك في برنامج مساند
                            </h6>
                        </a>
                    </div>

                    <div class="col p-2" data-aos="zoom-in" data-aos-delay="200">
                        <a href="select-worker.html" class="service"
                            style="background-image: url(assets/images/files.webp)">
                            <i class="fa-solid fa-users-gear"></i>
                            <h3 class="serviceTitle">اختيار العمالة</h3>
                            <h6 class="serviceP">
                                اختيار السيره الذاتيه للعماله المنزليه عبر البحث في برنامج مساند
                            </h6>
                        </a>
                    </div>

                    <div class="col p-2" data-aos="zoom-in" data-aos-delay="300">
                        <a href="arrive-worker.html" class="service"
                            style="background-image: url(assets/images/bg-plane.webp)">
                            <i class="fa-solid fa-plane-arrival"></i>
                            <h3 class="serviceTitle">وصول العمالة</h3>
                            <h6 class="serviceP">
                                وصول العمالة المنزلية من المطار المحلي الى المكتب
                            </h6>
                        </a>
                    </div>

                    <div class="col p-2" data-aos="zoom-in" data-aos-delay="400">
                        <a href="renew-contract.html" class="service"
                            style="background-image: url(assets/images/hands.webp)">
                            <i class="fa-solid fa-file-contract"></i>
                            <h3 class="serviceTitle">تجديد عقد عاملة</h3>
                            <h6 class="serviceP">
                                خدمة تجديد عقد العاملة المنزلية بشكل إلكتروني عبر منصة مساند
                            </h6>
                        </a>
                    </div>

                    <div class="col p-2" data-aos="zoom-in" data-aos-delay="500">
                        <a href="dailyLaborDemand.html" class="service"
                            style="background-image: url(assets/images/f3.webp)">
                            <i class="fa-solid fa-users-gear"></i>
                            <h3 class="serviceTitle">طلب العمالة المهنية</h3>
                            <h6 class="serviceP">
                                نحن قادرون على جلب أفضل أنواع العمالة المهنية من مختلف التخصصات لتلبية
                                احتياجاتك بأعلى جودة وكفاءة
                            </h6>
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- statistics sec  -->
    <section class="statistics" id="statistics">
        <div class="section-header" data-aos="fade-up" data-aos-duration="1000">
            <h1 class="title">الإحصائيات</h1>
            <h6 class="hint">إليك بعض إحصائيات العملاء الذين تشرفنا بالعمل معهم</h6>
        </div>
        <div class="stats-container">
            @forelse($statics as $static)
                <div class="stat-card" data-aos="zoom-in" data-aos-delay="100">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-{{ $static->image }}"></i>
                    </div>
                    <span class="counter" data-target="{{ $static->counter_num }}">0</span>
                    <h6>{{ $static->title }}</h6>
                </div>
            @empty
                <div class="stat-card" data-aos="zoom-in" data-aos-delay="100">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span class="counter" data-target="794">0</span>
                    <h6>عملاؤنا</h6>
                </div>

                <div class="stat-card" data-aos="zoom-in" data-aos-delay="200">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <span class="counter" data-target="812">0</span>
                    <h6>زوارنا</h6>
                </div>

                <div class="stat-card" data-aos="zoom-in" data-aos-delay="300">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <span class="counter" data-target="793">0</span>
                    <h6>خدمة العملاء</h6>
                </div>

                <div class="stat-card" data-aos="zoom-in" data-aos-delay="400">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <span class="counter" data-target="710">0</span>
                    <h6>عامل وعاملة</h6>
                </div>
            @endforelse
        </div>
    </section>

    <!-- ============= countries =============  -->
    <section class="countries" data-aos="fade-up">
        <div class="container">
            <!-- Section Title -->
            <div class="headTitle text-start" data-aos="fade-up" data-aos-delay="100">
                <h2> {{ __('ingaz.Recruitment countries') }} </h2>
                <p> {{ __('ingaz.countries_desc') }}</p>
            </div>
            <div class="allCountries">
                <div class="row justify-content-center">
                    @forelse($nationalities as $nationality)
                        <div class="col-6 col-md-3 p-1" data-aos="zoom-in" data-aos-delay="100">
                            <div class="country">
                                <img src="{{ showfile($nationality->image) }}" alt="">
                                <h2>{{ $nationality->title }}
                                </h2>
                                <p>
                                    {{ $nationality->sub_title }}
                                </p>
                                <a href="{{ route('site.all-workers.index') }}?nationality_id={{ $nationality->id }}"
                                    class="animatedLink">
                                    {{ __('ingaz.request now') }}
                                    <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-6 col-md-3 p-1" data-aos="zoom-in" data-aos-delay="100">
                            <div class="country">
                                <img src="assets/images/countries/1.png" alt="">
                                <h2> اوغندا </h2>
                                <p>مدة الاستقدام في خلال 60 يوم </p>
                                <a href="all-workers.html" class="animatedLink">
                                    اطلب الآن
                                    <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-6 col-md-3 p-1" data-aos="zoom-in" data-aos-delay="200">
                            <div class="country">
                                <img src="assets/images/countries/2.png" alt="">
                                <h2> كينيا </h2>
                                <p>مدة الاستقدام في خلال 60 يوم </p>
                                <a href="all-workers.html" class="animatedLink">
                                    اطلب الآن
                                    <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-6 col-md-3 p-1" data-aos="zoom-in" data-aos-delay="300">
                            <div class="country">
                                <img src="assets/images/countries/3.jpeg" alt="">
                                <h2> بنغلادش </h2>
                                <p>مدة الاستقدام في خلال 60 يوم </p>
                                <a href="all-workers.html" class="animatedLink">
                                    اطلب الآن
                                    <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-6 col-md-3 p-1" data-aos="zoom-in" data-aos-delay="400">
                            <div class="country">
                                <img src="assets/images/countries/4.jpeg" alt="">
                                <h2> الفلبين </h2>
                                <p>مدة الاستقدام في خلال 60 يوم </p>
                                <a href="all-workers.html" class="animatedLink">
                                    اطلب الآن
                                    <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-6 col-md-3 p-1" data-aos="zoom-in" data-aos-delay="500">
                            <div class="country">
                                <img src="assets/images/countries/5.png" alt="">
                                <h2> الهند </h2>
                                <p>مدة الاستقدام في خلال 60 يوم </p>
                                <a href="all-workers.html" class="animatedLink">
                                    اطلب الآن
                                    <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-6 col-md-3 p-1" data-aos="zoom-in" data-aos-delay="600">
                            <div class="country">
                                <img src="assets/images/countries/6.png" alt="">
                                <h2> موريتانيا </h2>
                                <p>مدة الاستقدام في خلال 60 يوم </p>
                                <a href="all-workers.html" class="animatedLink">
                                    اطلب الآن
                                    <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                </a>
                            </div>
                        </div>

                        <div class="col-6 col-md-3 p-1" data-aos="zoom-in" data-aos-delay="700">
                            <div class="country">
                                <img src="assets/images/countries/7.png" alt="">
                                <h2> جيبوتي </h2>
                                <p>مدة الاستقدام في خلال 60 يوم </p>
                                <a href="all-workers.html" class="animatedLink">
                                    اطلب الآن
                                    <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <!-- steps sec  -->
    <section class="steps" id="steps">
        <div class="container">
            <!-- Section Title -->
            <div class="SectionTitle">
                <h2 class="title" data-aos="fade-up">خطوات الاستقدام</h2>
                <h6 class="hint" data-aos="fade-up" data-aos-delay="100">
                    تعرف علي الخطوات التي نعمل بها ...
                </h6>
            </div>

            <!-- steps -->
            <div class="allSteps">
                @forelse($steps as $key=>$step)
                    <div class="step" data-aos="fade-up" data-aos-delay="100">
                        <div class="icon">
                            <i class="fa-thin fa-{{ $step->icon }}"></i>
                        </div>
                        <p>
                            {{ $step->title }}
                        </p>
                        @if ($key == 0)
                            <div class="images">
                                <img src="{{ asset('site') }}/assets/images/mc.webp" data-aos="zoom-in"
                                    data-aos-delay="200">
                                <img src="{{ asset('site') }}/assets/images/visa.webp" data-aos="zoom-in"
                                    data-aos-delay="300">
                                <img src="{{ asset('site') }}/assets/images/mada.webp" data-aos="zoom-in"
                                    data-aos-delay="400">
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="step" data-aos="fade-up" data-aos-delay="100">
                        <div class="icon">
                            <i class="fa-thin fa-wallet"></i>
                        </div>
                        <p>
                            سداد رسوم تاشيرة العمالة المنزلية الخاصة بك عبر الخدمات الحكومية
                            في البنك او عن طريق القنوات التالية
                        </p>
                        <div class="images">
                            <img src="{{ asset('site') }}/assets/images/mc.webp" data-aos="zoom-in"
                                data-aos-delay="200">
                            <img src="{{ asset('site') }}/assets/images/visa.webp" data-aos="zoom-in"
                                data-aos-delay="300">
                            <img src="{{ asset('site') }}/assets/images/mada.webp" data-aos="zoom-in"
                                data-aos-delay="400">
                        </div>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="200">
                        <div class="icon">
                            <i class="fa-thin fa-passport"></i>
                        </div>
                        <p>طلب اصدار تاشيرة العمالة المنزلية الخاصة بك في برنامج مساند</p>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="300">
                        <div class="icon">
                            <i class="fa-thin fa-id-card"></i>
                        </div>
                        <p>
                            اختيار السيرة الذاتية للعمالة المنزلية عبر البحث في برنامج مساند
                            عن طريق المهنة/ الجنسية
                        </p>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="400">
                        <div class="icon">
                            <i class="fa-thin fa-wallet"></i>
                        </div>
                        <p>
                            دفع مبلغ اختياري وتوثيق طلب استقدام العمالة المنزلية المحددة بعد
                            اختيار السيرة الذاتية
                        </p>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="500">
                        <div class="icon">
                            <i class="fa-thin fa-plane"></i>
                        </div>
                        <p>وصول العمالة المنزلية من المطار المحلي الى المكتب</p>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="600">
                        <div class="icon">
                            <i class="fa-thin fa-handshake"></i>
                        </div>
                        <p>يتم استلام العمالة الخاصة بك في المكتب</p>
                    </div>
                    <div class="step" data-aos="fade-up" data-aos-delay="700">
                        <div class="icon">
                            <i class="fa-thin fa-shield-check"></i>
                        </div>
                        <p>نضمن لك العمالة لمدة 3 شهور</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- ================ requirements ================= -->
    <section class="requirements">
        <div class="container">
            <div class="headTitle">
                <h2 data-aos="fade-up">{{ __('ingaz.Recruitment_requirements') }} </h2>
                <p data-aos="fade-up">
                    {{ __('ingaz.requirements_you_need') }}
                </p>
            </div>
            <div class="row">
                <div class="col-md-6 p-2">
                    <div class="requirement">
                        <div class="head" data-aos="fade-up">
                            <h4>{{ __('ingaz.Documents_domestic') }}</h4>
                        </div>
                        @forelse($Domestic as $domestic)
                            <ul>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="{{ $domestic->image }}"></i>
                                    </div>
                                    <p> {{ $domestic->title }}</p>
                                </li>
                            </ul>
                        @empty
                            <ul>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-address-card"></i>
                                    </div>
                                    <p> صورة الهوية الوطنية او الاقامة للمقيمين </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-headset"></i>
                                    </div>
                                    <p> خدمة عملاء متميزة </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-building-columns"></i>
                                    </div>
                                    <p> تعريف بالراتب من جهة العمل أو كشف حساب البنك </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-passport"></i>
                                    </div>
                                    <p> التأشيرة الأصل </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-calendar-lines-pen"></i>
                                    </div>
                                    <p> توقيع عقد إستقدام </p>
                                </li>
                            </ul>
                        @endforelse
                    </div>
                </div>

                <div class="col-md-6 p-2">
                    <div class="requirement">
                        <div class="head" data-aos="fade-up">
                            <h4>{{ __('ingaz.Documents_visa') }} </h4>
                            <p>
                                {{ __('ingaz.Please_visit_the_Domestic_Workers_Program_Support_website') }}
                                <a href="#!"> من هنا </a>
                            </p>
                        </div>
                        @forelse($Visa as $visa)
                            <ul>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="{{ $visa->image }}"></i>
                                    </div>
                                    <p> {{ $visa->title }} </p>
                                </li>
                            </ul>
                        @empty
                            <ul>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-address-card"></i>
                                    </div>
                                    <p> صورة الهوية الوطنية او الاقامة للمقيمين </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-headset"></i>
                                    </div>
                                    <p> خدمة عملاء متميزة </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-building-columns"></i>
                                    </div>
                                    <p> تعريف بالراتب من جهة العمل أو كشف حساب البنك </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-passport"></i>
                                    </div>
                                    <p> التأشيرة الأصل </p>
                                </li>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="fa-regular fa-calendar-lines-pen"></i>
                                    </div>
                                    <p> توقيع عقد إستقدام </p>
                                </li>
                            </ul>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================ references ================= -->
    <section class="references" data-aos="fade-up">
        <div class="container">
            <div class="swiper referencesSlider">
                <div class="swiper-wrapper">
                    @forelse($clients as $client)
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{ showfile($client->image) }}" alt="">
                            </div>
                        </div>
                    @empty
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{ asset('site') }}/assets/images/Ministry-of-Foreign-Affairs-01.svg"
                                    alt="">
                            </div>
                        </div>
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{ asset('site') }}/assets/images/Ministry-of-Interior-01.svg" alt="">
                            </div>
                        </div>
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{ asset('site') }}/assets/images/Ministry-of-Labor-and-Social-Development.svg"
                                    alt="">
                            </div>
                        </div>
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{ asset('site') }}/assets/images/musand.svg" alt="">
                            </div>
                        </div>
                    @endforelse
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    <!-- ================ contact Form ================= -->
    <section class="contactForm">
        <div class="container">
            <div class="row">
                <div class="col-md-6 p-2 mb-5 mb-md-0 wow fadeInUp">
                    <div class="headTitle text-start">
                        <h2> {{ __('ingaz.contact') }}</h2>
                        <p>
                            {{ __('ingaz.request your mid') }}
                        </p>
                    </div>
                    <form action="" id="contact-form" class="row needs-validation" novalidate>
                        @csrf

                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                        <div class="col-md-12 p-2">
                            <label class="form-label">
                                <i class="fas fa-user me-2"></i> {{ __('ingaz.full name') }}*
                            </label>
                            <input type="text" id="name" name="name" class="form-control" required />
                            <div class="invalid-feedback"> {{ __('ingaz.this field is required') }}</div>
                            <div class="valid-feedback">{{ __('ingaz.good') }}</div>
                        </div>
                        <div class="col-md-6 p-2">
                            <label class="form-label"><i class="fas fa-phone-alt me-2"></i> {{ __('ingaz.phone') }}*
                            </label>
                            <input type="tel" id="phone" name="phone" id="phoneNum" class="form-control"
                                required />
                            <div class="invalid-feedback"> {{ __('ingaz.this field is required') }} </div>
                        </div>
                        <div class="col-md-6 p-2">
                            <label class="form-label">
                                <i class="fa-solid fa-comment-lines me-2"></i> {{ __('ingaz.subject') }}
                            </label>
                            <input type="text" id="subject" name="subject" class="form-control" />
                        </div>
                        <div class="col-md-12 p-2">
                            <label class="form-label">
                                <i class="fas fa-feather-alt me-2"></i>{{ __('ingaz.Write your message') }}*
                            </label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            <div class="invalid-feedback"> {{ __('ingaz.this field is required') }} </div>
                        </div>
                        {!! NoCaptcha::renderJs() !!}
                        {!! NoCaptcha::display() !!}

                        @error('g-recaptcha-response')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror

                        <div class="col-md-12 text-center p-2">
                            <button type="submit" class="defaultBtn">
                                {{ __('ingaz.send') }} <i class="fas fa-paper-plane ms-2"></i>
                                <span></span>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 p-2 wow fadeInUp">
                    <section class="googleMap">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d57946.84623396069!2d46.75688!3d24.806481!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0xc74fdd6f15256594!2z2YXZg9iq2Kgg2LHZiNin2YHYryDZhtis2K8g2YTZhNil2LPYqtmC2K_Yp9mF!5e0!3m2!1sar!2ssa!4v1650724949947!5m2!1sar!2ssa"
                            frameborder="0" style="border: 0" allowfullscreen="" aria-hidden="false"
                            tabindex="0"></iframe>
                    </section>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
    <script>
        let map, marker;

        function initMap() {
            const defaultLocation = {
                lat: 30.0444,
                lng: 31.2357
            }; // القاهرة كمثال

            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 10,
                center: defaultLocation,
            });

            marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                draggable: true
            });

            // عند تحريك العلامة (Marker)
            google.maps.event.addListener(marker, 'dragend', function(event) {
                document.getElementById("latitude").value = event.latLng.lat();
                document.getElementById("longitude").value = event.latLng.lng();
            });
        }

        // تحميل الخريطة عند فتح الصفحة
        window.onload = initMap;
    </script>

    <script>
        $(document).ready(function() {
            $('#contact-form').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: "{{ route('site.contact-us.store') }}",
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: $('#name').val(),
                        phone: $('#phone').val(),
                        subject: $('#subject').val(),
                        message: $('#message').val(),
                        latitude: $('#latitude').val(),
                        longitude: $('#longitude').val(),
                        'g-recaptcha-response': grecaptcha.getResponse()

                    },
                    success: function(response) {

                        $('#contact-form')[0].reset();
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __('ingaz.success') }}',
                            text: '{{ __('ingaz.success') }}',
                            confirmButtonText: '{{ __('ingaz.ok') }}'
                        });
                    },
                    error: function(xhr) {
                        console.error("خطأ في الطلب:", xhr);
                    }
                });



            });
        });
    </script>
@endsection
