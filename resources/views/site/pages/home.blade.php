@extends('site.layouts.index')
@section('title')
    {{__("ingaz.home")}}
@endsection
@section('content')
    <!-- main slider -->
    <section class="mainSlider">
        <div class="swiper">
            <div class="swiper-wrapper">
                @forelse($sliders as $slider)
                    <div class="swiper-slide" style="background-image: url({{showFile($slider->image)}})">
                        <div class="info">
                            <h4 class="hint">
                                {{$slider->title}}
                            </h4>
                            <h1 class="sliderTitle">
                                {{$slider->description}}
                            </h1>
                        </div>
                    </div>
                @empty
                    <!-- swiper-slide -->
                    <div class="swiper-slide" style="background-image: url({{asset('site')}}/assets/images/s1.webp)">

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
                    <div class="swiper-slide" style="background-image: url({{asset('site')}}/assets/images/s2.webp)">
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
        <!-- <div class="expertise">
            {{__('ingaz.experience more than')}}
            <span> {{isset($settings->years) ? $settings->years : ''}} <small> {{__('ingaz.years')}} </small> </span>
        </div> -->
          <div class="expertise">
          <img src="{{asset('site')}}/assets/images/experience.jpg"
          alt="" />

        </div>
        <a href="{{route('site.all-workers.index') }}?type=estqdam" class="service">
            <div class="icon">
                <i class="fa-duotone fa-user-plus fa-2x mb-2"></i>
            </div>
            <div class="text">
                <h4 class="title">{{__('ingaz.recruitment_request')}}</h4>
                <p class="description">{{__('ingaz.recruitment_request_text')}}</p>
            </div>
        </a>
        <a href="{{route('site.all-workers.index') }}?type=transfer" class="service">
            <div class="icon">
                <i class="fa-duotone fa-people-arrows fa-2x mb-2"></i>
            </div>
            <div class="text">
                <h4 class="title">{{__('ingaz.Labor_transfer')}}</h4>
                <p class="description">{{__('ingaz.Labor_transfer_text')}}</p>
            </div>
        </a>
        <a href="{{route('site.all-workers.index') }}?type=egar" class="service">
            <div class="icon">
             <i class="fa-duotone fa-business-time"></i>
            </div>
            <div class="text">
                <h4 class="title">{{__('ingaz.Labor_rental')}}</h4>
                <p class="description">{{__('ingaz.Labor_rental_text')}}</p>
            </div>
        </a>

        <div class="warning">
            {{__('ingaz.warning')}} :
            @if(isset($settings))
                {{ app()->getLocale() == 'ar' ? $settings->note_ar : $settings->note_en }}
            @else
                {{__('ingaz.warning_text')}}
            @endif
        </div>
    </section>
    <!-- about sec  -->
    <section class="about-section" id="about">
        <div class="container">
            <!-- Title & Intro -->
            <div class="section-header text-center mb-5" data-aos="fade-up">
                <h2 class="section-title">
                    @if(isset($about)&& isset($about->title))
                        {{$about->title}}
                    @else
                        {{__('ingaz.about_us')}}
                </h2>
                @endif
                <p class="section-subtitle">
                    @if(isset($about)&& isset($about->description))
                        {!!$about->description!!}
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
                        <li><i class="{{$feature->image}}"></i> {{$feature->title}}</li>
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
        <div class="about-card" style="--bg-image: url('{{ asset('site/assets/images/selectworker.webp') }}');">
            <span class="about-number">01</span>
            <i class="fa-solid fa-eye"></i>
            <h4>{{ __('ingaz.vision') }}</h4>
            <p>
                @if(isset($about) && isset($about->vision))
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
        <div class="about-card" style="--bg-image: url('{{ asset('site/assets/images/service2.webp') }}');">
            <span class="about-number">02</span>
            <i class="fa-solid fa-envelope-open-text"></i>
            <h4>{{ __('ingaz.mission') }}</h4>
            <p>
                @if(isset($about) && isset($about->mission))
                    {!! $about->mission !!}
                @else
                    نفتخر بعملنا كونه يسهم بتوفير موارد
                    ودخل جيّد لأسرة وفرد بحاجة لهذه الموارد لدعم أسرته وبناء مستقبله،
                    وبالمقابل توفير سبل الراحة للأسرة السعودية والمقيمة في وطننا
                    المعطاء.
                    ((( احتياجاتكم اولوياتنا ))).
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
        <div class="about-card" style="--bg-image: url('{{ asset('site/assets/images/about.webp') }}');">
            <span class="about-number">03</span>
            <i class="fa-solid fa-handshake"></i>
            <h4>{{ __('ingaz.values') }}</h4>
            <p>
                @if(isset($about) && isset($about->value))
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
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 p-2" data-aos="zoom-in" data-aos-delay="100">
                            <div class="country-card">
                                <div class="flag-container">
                                    <img src="{{ showfile($nationality->logo) }}" alt="" width="100px">
                                </div>
                                <div class="country-content">
                                    <h3>{{ $nationality->title }}
                                    </h3>
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
                        </div>
                    @empty
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 p-2" data-aos="zoom-in" data-aos-delay="100">
                            <div class="country-card">
                                <div class="flag-container">
                                    <img src="{{asset('site')}}/assets/images/countries/1.png" alt="">
                                </div>
                                <div class="country-content">
                                    <h3> اوغندا </h3>
                                    <p>مدة الاستقدام في خلال 60 يوم </p>
                                    <a href="#" class="animatedLink">
                                        اطلب الآن
                                        <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                    </a>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 p-2" data-aos="zoom-in" data-aos-delay="100">
                                <div class="country-card">
                                    <div class="flag-container">
                                        <img src="{{asset('site')}}/assets/images/countries/2.png" alt="">
                                    </div>
                                    <div class="country-content">
                                        <h3> كينيا </h3>
                                        <p>مدة الاستقدام في خلال 60 يوم </p>
                                        <a href="#" class="animatedLink">
                                            اطلب الآن
                                            <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 p-2" data-aos="zoom-in" data-aos-delay="100">
                                <div class="country-card">
                                    <div class="flag-container">
                                        <img src="{{asset('site')}}/assets/images/countries/3.jpeg" alt="">
                                    </div>
                                    <div class="country-content">
                                        <h3> بنغلادش </h3>
                                        <p>مدة الاستقدام في خلال 60 يوم </p>
                                        <a href="#" class="animatedLink">
                                            اطلب الآن
                                            <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 p-2" data-aos="zoom-in" data-aos-delay="100">
                                <div class="country-card">
                                    <div class="flag-container">
                                        <img src="{{asset('site')}}/assets/images/countries/4.jpeg" alt="">
                                    </div>
                                    <div class="country-content">
                                        <h3> الفلبين </h3>
                                        <p>مدة الاستقدام في خلال 60 يوم </p>
                                        <a href="#" class="animatedLink">
                                            اطلب الآن
                                            <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 p-2" data-aos="zoom-in" data-aos-delay="100">
                                <div class="country-card">
                                    <div class="flag-container">
                                        <img src="{{asset('site')}}/assets/images/countries/5.png" alt="">
                                    </div>
                                    <div class="country-content">
                                        <h3> الهند </h3>
                                        <p>مدة الاستقدام في خلال 60 يوم </p>
                                        <a href="#" class="animatedLink">
                                            اطلب الآن
                                            <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 p-2" data-aos="zoom-in" data-aos-delay="100">
                                <div class="country-card">
                                    <div class="flag-container">
                                        <img src="{{asset('site')}}/assets/images/countries/6.png" alt="">
                                    </div>
                                    <div class="country-content">
                                        <h3> موريتانيا </h3>
                                        <p>مدة الاستقدام في خلال 60 يوم </p>
                                        <a href="#" class="animatedLink">
                                            اطلب الآن
                                            <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 p-2" data-aos="zoom-in" data-aos-delay="100">
                                <div class="country-card">
                                    <div class="flag-container">
                                        <img src="{{asset('site')}}/assets/images/countries/7.png" alt="">
                                    </div>
                                    <div class="country-content">
                                        <h3> جيبوتي </h3>
                                        <p>مدة الاستقدام في خلال 60 يوم </p>
                                        <a href="#" class="animatedLink">
                                            اطلب الآن
                                            <i class="fa-regular fa-left-long ms-2"><span></span></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endforelse
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

        <!-- السلايدر -->
        <div class="services-slider swiper" data-aos="fade-up">
            <div class="swiper-wrapper">
                @forelse($services as $service)
                    <div class="swiper-slide">
                        <a href="#"
                           class="service d-flex flex-column justify-content-center"
                           style="background: url('{{ showFile($service->image) }}') no-repeat center/cover;">
                            <i class="fa-solid fa-passport"></i>
                            <h3 class="serviceTitle">{{ $service->title }}</h3>
                            <h6 class="serviceP">
                                {{ Str::limit($service->description, 100) }}
                            </h6>
                        </a>
                    </div>
                @empty
                    <div class="swiper-slide">
                        <a href="#"
                           class="service d-flex flex-column justify-content-center"
                           style="background: url('{{ asset('site/assets/images/passport-bg.webp') }}') no-repeat center/cover;">
                            <i class="fa-solid fa-passport"></i>
                            <h3 class="serviceTitle">إصدار التأشيرة</h3>
                            <h6 class="serviceP">
                                طلب اصدار تاشيرة العمالة المنزلية الخاصة بك في برنامج مساند
                            </h6>
                        </a>
                    </div>

                    <div class="swiper-slide">
                        <a href="#"
                           class="service d-flex flex-column justify-content-center"
                           style="background: url('{{ asset('site/assets/images/files.webp') }}') no-repeat center/cover;">
                            <i class="fa-solid fa-users-gear"></i>
                            <h3 class="serviceTitle">اختيار العمالة</h3>
                            <h6 class="serviceP">
                                اختيار السيره الذاتيه للعماله المنزليه عبر البحث في برنامج مساند
                            </h6>
                        </a>
                    </div>

                    <div class="swiper-slide">
                        <a href="#"
                           class="service d-flex flex-column justify-content-center"
                           style="background: url('{{ asset('site/assets/images/bg-plane.webp') }}') no-repeat center/cover;">
                            <i class="fa-solid fa-plane-arrival"></i>
                            <h3 class="serviceTitle">وصول العمالة</h3>
                            <h6 class="serviceP">
                                وصول العمالة المنزلية من المطار المحلي الى المكتب
                            </h6>
                        </a>
                    </div>

                    <div class="swiper-slide">
                        <a href="#"
                           class="service d-flex flex-column justify-content-center"
                           style="background: url('{{ asset('site/assets/images/hands.webp') }}') no-repeat center/cover;">
                            <i class="fa-solid fa-file-contract"></i>
                            <h3 class="serviceTitle">تجديد عقد عاملة</h3>
                            <h6 class="serviceP">
                                خدمة تجديد عقد العاملة المنزلية بشكل إلكتروني عبر منصة مساند
                            </h6>
                        </a>
                    </div>
                @endforelse
            </div>

            <!--  nav btns -->
            <div class="swiper-button-next custom-swiper-btn"></div>
            <div class="swiper-button-prev custom-swiper-btn"></div>
        </div>

        <!--  last card  -->
        <div class="wide-card mt-4" data-aos="fade-up">
            <a href="#"
               class="service d-flex flex-column justify-content-center"
               style="background: url('{{ asset('site/assets/images/f3.webp') }}') no-repeat center/cover;">
                <i class="fa-solid fa-users-gear"></i>
                <h3 class="serviceTitle">طلب العمالة المهنية</h3>
                <h6 class="serviceP">
                    نحن قادرون على جلب أفضل أنواع العمالة المهنية من مختلف التخصصات
                    لتلبية احتياجاتك بأعلى جودة وكفاءة
                </h6>
            </a>
        </div>
    </div>
</section>

    <!-- statistics sec  -->
    <section class="statistics" id="statistics">
        <div class="section-header" data-aos="fade-up" data-aos-duration="1000">
            <h1 class="title">{{__('ingaz.Statistics')}}</h1>
            <h6 class="hint">{{__('ingaz.Statistics_text')}}</h6>
        </div>
        <div class="stats-container">
            @forelse($statics as $static)
                <div class="stat-card" data-aos="zoom-in" data-aos-delay="100">
                    <div class="icon-wrapper">
                        <i class="fa-solid fa-{{$static->image}}"></i>
                    </div>
                    <span class="counter" data-target="{{$static->counter_num}}">0</span>
                    <h6>{{$static->title}}</h6>
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

    <!-- steps sec  -->
    <section class="steps" id="steps">
        <div class="container">
            <!-- Section Title -->
            <div class="SectionTitle">
                <h2 class="title" data-aos="fade-up">{{__('ingaz.Recruitment steps')}}</h2>
                <h6 class="hint" data-aos="fade-up" data-aos-delay="100">
                    {{__('ingaz.Recruitment steps hint')}}
                </h6>
            </div>

            <!-- steps -->
            <div class="allSteps">
                @forelse($steps as $key=>$step)
                    <div class="step" data-aos="fade-up" data-aos-delay="100">
                        <div class="icon">
                            <i class="fa-thin fa-{{$step->icon}}"></i>
                        </div>
                        <p>
                            {{$step->title}}
                        </p>
                        @if($key == 0)
                            <div class="images">
                                <img src="{{asset('site')}}/assets/images/mc.webp" data-aos="zoom-in"
                                     data-aos-delay="200">
                                <img src="{{asset('site')}}/assets/images/visa.webp" data-aos="zoom-in"
                                     data-aos-delay="300">
                                <img src="{{asset('site')}}/assets/images/mada.webp" data-aos="zoom-in"
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
                            <img src="{{asset('site')}}/assets/images/mc.webp" data-aos="zoom-in" data-aos-delay="200">
                            <img src="{{asset('site')}}/assets/images/visa.webp" data-aos="zoom-in"
                                 data-aos-delay="300">
                            <img src="{{asset('site')}}/assets/images/mada.webp" data-aos="zoom-in"
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
                <h2 data-aos="fade-up">{{__('ingaz.Recruitment_requirements')}} </h2>
                <p data-aos="fade-up">
                    {{__('ingaz.requirements_you_need')}}
                </p>
            </div>
            <div class="row">
                <div class="col-md-6 p-2">
                    <div class="requirement">
                        <div class="head" data-aos="fade-up">
                            <h4>{{__('ingaz.Documents_domestic')}}</h4>
                        </div>
                        @forelse($Domestic as $domestic)
                            <ul>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="{{$domestic->image}}"></i>
                                    </div>
                                    <p> {{$domestic->title}}</p>
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
                            <h4>{{__('ingaz.Documents_visa')}} </h4>
                            <p>
                                {{__('ingaz.Please_visit_the_Domestic_Workers_Program_Support_website')}}
                                <a href="{{route('site.musaned.index')}}"> من هنا </a>
                            </p>
                        </div>
                        @forelse($Visa as $visa)
                            <ul>
                                <li data-aos="fade-up">
                                    <div class="icon">
                                        <i class="{{$visa->image}}"></i>
                                    </div>
                                    <p> {{$visa->title}} </p>
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
<!-- ================ musaned-platform ================= -->
 <!-- musaned-platform  -->
     <section class="musaned-platform">
        <div class="container">
          <div class="platform-content">
            <div class="row">
              <div class="col-lg-8 col-md-12">
                <div class="platform-detail">
            <h3>{{ __('ingaz.musaned_platform_title') }}</h3>
            <p>{{ __('ingaz.musaned_platform_desc') }}</p>
          </div>

            <div class="twm-explore-bottom-section">
                  <div class="row">
              <!-- feature 1 -->
                <div class="col-lg-6 col-md-6">
                      <div class="detail-info">
                        <div class="info-icon">
                          <i class="fa-solid fa-badge-check me-3"></i>
                        </div>
                        <div class="info-text">
                    <h3>{{ __('ingaz.feature_1_title') }}</h3>
                    <p>{{ __('ingaz.feature_1_desc') }}</p>
                  </div>
                </div>
              </div>

              <!-- feature 2 -->
             <div class="col-lg-6 col-md-6">
                      <div class="detail-info">
                        <div class="info-icon">
                          <i class="fa-solid fa-badge-check me-3"></i>
                        </div>
                        <div class="info-text">
                    <h3>{{ __('ingaz.feature_2_title') }}</h3>
                    <p>{{ __('ingaz.feature_2_desc') }}</p>
                  </div>
                </div>
              </div>

              <!-- feature 3 -->
              <div class="col-lg-6 col-md-6">
                      <div class="detail-info">
                        <div class="info-icon">
                          <i class="fa-solid fa-badge-check me-3"></i>
                        </div>
                        <div class="info-text">
                    <h3>{{ __('ingaz.feature_3_title') }}</h3>
                    <p>{{ __('ingaz.feature_3_desc') }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- الفيديو -->
         <div class="col-lg-4 col-md-12">
                <div class="platform-media">
                  <div class="platform-video" style="background: url('{{ asset('site/assets/images/family2.webp') }}') no-repeat center/cover;">
              <a
                class="videopopup"
                href="https://www.youtube.com/embed/wlLjqeDDi2Y"
                data-fancybox
              >
                <i class="fa-solid fa-play"></i>
                <!-- <span class="video-text">{{ __('ingaz.watch_video') }}</span> -->
              </a>
            </div>
          </div>
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
                                <img src="{!! showfile($client->image) !!}" alt="">
                            </div>
                        </div>
                    @empty
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{asset('site')}}/assets/images/Ministry-of-Foreign-Affairs-01.svg" alt="">
                            </div>
                        </div>
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{asset('site')}}/assets/images/Ministry-of-Interior-01.svg" alt="">
                            </div>
                        </div>
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{asset('site')}}/assets/images/Ministry-of-Labor-and-Social-Development.svg"
                                     alt="">
                            </div>
                        </div>
                        <div class="swiper-slide" data-aos="zoom-in">
                            <div class="referenceLogo">
                                <img src="{{asset('site')}}/assets/images/musand.svg" alt="">
                            </div>
                        </div>
                    @endforelse
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    <!-- ================ contact Form ================= -->
{{--    <section class="contactForm">--}}
{{--        <div class="container">--}}
{{--            <div class="row">--}}
{{--                <div class="col-md-6 p-2 mb-5 mb-md-0 wow fadeInUp">--}}
{{--                    <div class="headTitle text-start">--}}
{{--                        <h2> {{__('ingaz.contact')}}</h2>--}}
{{--                        <p>--}}
{{--                            {{__('ingaz.request your mid')}}--}}
{{--                        </p>--}}
{{--                    </div>--}}
{{--                    <form action="" id="contact-form" class="row needs-validation" novalidate>--}}
{{--                        @csrf--}}

{{--                        <input type="hidden" name="latitude" id="latitude">--}}
{{--                        <input type="hidden" name="longitude" id="longitude">--}}
{{--                        <div class="col-md-12 p-2">--}}
{{--                            <label class="form-label">--}}
{{--                                <i class="fas fa-user me-2"></i> {{__('ingaz.full name')}}*--}}
{{--                            </label>--}}
{{--                            <input type="text" id="name" name="name" class="form-control" required/>--}}
{{--                            <div class="invalid-feedback"> {{__('ingaz.this field is required')}}</div>--}}
{{--                            <div class="valid-feedback">{{__('ingaz.good')}}</div>--}}
{{--                        </div>--}}
{{--                        <div class="col-md-6 p-2">--}}
{{--                            <label class="form-label"><i class="fas fa-phone-alt me-2"></i> {{__('ingaz.phone')}}*--}}
{{--                            </label>--}}
{{--                            <input type="tel" id="phone" name="phone" id="phoneNum" class="form-control" required/>--}}
{{--                            <div class="invalid-feedback"> {{__('ingaz.this field is required')}} </div>--}}
{{--                        </div>--}}
{{--                        <div class="col-md-6 p-2">--}}
{{--                            <label class="form-label">--}}
{{--                                <i class="fa-solid fa-comment-lines me-2"></i> {{__('ingaz.subject')}}--}}
{{--                            </label>--}}
{{--                            <input type="text" id="subject" name="subject" class="form-control"/>--}}
{{--                        </div>--}}
{{--                        <div class="col-md-12 p-2">--}}
{{--                            <label class="form-label">--}}
{{--                                <i class="fas fa-feather-alt me-2"></i>{{__('ingaz.Write your message')}}*--}}
{{--                            </label>--}}
{{--                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>--}}
{{--                            <div class="invalid-feedback"> {{__('ingaz.this field is required')}} </div>--}}
{{--                        </div>--}}
{{--                        {!! NoCaptcha::renderJs() !!}--}}
{{--                        {!! NoCaptcha::display() !!}--}}

{{--                        @error('g-recaptcha-response')--}}
{{--                        <span class="text-danger">{{ $message }}</span>--}}
{{--                        @enderror--}}

{{--                        <div class="col-md-12 text-center p-2">--}}
{{--                            <button type="submit" class="defaultBtn">--}}
{{--                                {{__('ingaz.send')}} <i class="fas fa-paper-plane ms-2"></i>--}}
{{--                                <span></span>--}}
{{--                            </button>--}}
{{--                        </div>--}}
{{--                    </form>--}}
{{--                </div>--}}
{{--                <div class="col-md-6 p-2 wow fadeInUp">--}}
{{--                    <section class="googleMap">--}}
{{--                        <iframe--}}
{{--                            src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d57946.84623396069!2d46.75688!3d24.806481!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0xc74fdd6f15256594!2z2YXZg9iq2Kgg2LHZiNin2YHYryDZhtis2K8g2YTZhNil2LPYqtmC2K_Yp9mF!5e0!3m2!1sar!2ssa!4v1650724949947!5m2!1sar!2ssa"--}}
{{--                            frameborder="0"--}}
{{--                            style="border: 0"--}}
{{--                            allowfullscreen=""--}}
{{--                            aria-hidden="false"--}}
{{--                            tabindex="0"></iframe>--}}
{{--                    </section>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </section>--}}
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
            google.maps.event.addListener(marker, 'dragend', function (event) {
                document.getElementById("latitude").value = event.latLng.lat();
                document.getElementById("longitude").value = event.latLng.lng();
            });
        }

        // تحميل الخريطة عند فتح الصفحة
        window.onload = initMap;
    </script>

    <script>
        $(document).ready(function () {
            $('#contact-form').on('submit', function (e) {
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
                    success: function (response) {

                        $('#contact-form')[0].reset();
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("ingaz.success") }}',
                            text: '{{ __("ingaz.success") }}',
                            confirmButtonText: '{{ __("ingaz.ok") }}'
                        });
                    },
                    error: function (xhr) {
                        console.error("خطأ في الطلب:", xhr);
                    }
                });


            });
        });
    </script>
@endsection
