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
                <a href="{{route('site.home.index')}}"> {{__("ingaz.home")}} </a>
            </li>
            <li>
                <a href="{{route('site.home.index')}}#services">{{__("ingaz.recruitment_journey")}}  </a>
            </li>
            <li>
                <a href="#!" class="active">{{__("ingaz.employment_rules_and_regulations")}} </a>
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
        <div class="container arriveWorker-content py-5 px-4 rounded shadow-sm bg-white">
            <h3 class="mb-4">
                {{ __('ingaz.home_title') }}
            </h3>
            <div class="mb-3 text-muted">
                {{ __('ingaz.home_sub') }}<br>
                <span class="badge bg-light text-dark mt-2">{{ __('ingaz.home_date') }}</span>
            </div>
            <p class="mb-4">
                {{ __('ingaz.home_desc') }}
            </p>
            <ol class=" mb-0 ps-0">
                <li class="mb-2 d-flex align-items-center flex-wrap">
                    <span class="fw-bold">{{ __('ingaz.link_1_text') }}</span>
                    <a href="{{isset($settings) ? $settings->link_rights_and_duties : ''}}"
                       target="_blank" rel="noopener" class="btn btn-outline btn-sm ms-2">
                        <i class="fa-solid fa-link"></i> {{ __('ingaz.link_1_btn') }}
                    </a>
                </li>
                <li class="mb-2 d-flex align-items-center flex-wrap">
                    <span class="fw-bold">{{ __('ingaz.link_2_text') }}</span>
                    <a href="https://beta.musaned.com.sa/ar/labor-education" target="_blank" rel="noopener"
                       class="btn btn-outline btn-sm ms-2">
                        <i class="fa-solid fa-link"></i> {{ __('ingaz.link_2_btn') }}
                    </a>
                </li>
            </ol>
        </div>
    </div>

    {{--    <div class="arriveWorker">--}}
    {{--        <div class="container">--}}
    {{--            <!-- Worker Rights Info -->--}}
    {{--            @forelse($arrivalWorkers as $key=>$item)--}}
    {{--                <div class="arriveWorkerInfo @if($key%2 == 0) mt-5 @endif">--}}
    {{--                    <div class="image wow fadeInUp">--}}
    {{--                        <img src="{{showFile($item->image)}}" alt="{{$item->title}}">--}}
    {{--                    </div>--}}
    {{--                    <div class="info wow fadeInUp">--}}
    {{--                        <h3>{{$item->title}}</h3>--}}
    {{--                        <p>{{$item->sub_title}}</p>--}}
    {{--                        {!! $item->body !!}--}}
    {{--                    </div>--}}
    {{--                </div>--}}
    {{--            @empty--}}
    {{--                <div class="arriveWorkerInfo">--}}
    {{--                    <div class="image wow fadeInUp">--}}
    {{--                        <img src="{{asset('site')}}/assets/images/info1.png" alt="حقوق وواجبات العمالة">--}}
    {{--                    </div>--}}
    {{--                    <div class="info wow fadeInUp">--}}
    {{--                        <h3>حقوق وواجبات العمالة المنزلية</h3>--}}
    {{--                        <p>--}}
    {{--                            يلتزم مكتبنا بضمان حقوق العمالة المنزلية وفقًا لقانون العمل السعودي وقواعد حقوق الإنسان، حيث--}}
    {{--                            تشمل هذه الحقوق:--}}
    {{--                        <ul>--}}
    {{--                            <li>العمل في بيئة آمنة وصحية مع احترام كرامتهم وخصوصيتهم.</li>--}}
    {{--                            <li>الحصول على أجر مناسب ومحدد وفق العقد الموقع بين العامل وصاحب العمل.</li>--}}
    {{--                            <li>الراحة الأسبوعية والإجازات الرسمية المقررة حسب القانون.</li>--}}
    {{--                            <li>توفير السكن الملائم والغذاء الصحي عند الحاجة.</li>--}}
    {{--                            <li>حق العامل في إنهاء العقد وفق الشروط القانونية واللجوء إلى الجهات المختصة عند وجود أي--}}
    {{--                                انتهاك--}}
    {{--                                للحقوق.--}}
    {{--                            </li>--}}
    {{--                        </ul>--}}
    {{--                        <h5>احترام حقوق العمالة واجب، واتباع القوانين يحفظ كرامة الجميع</h5>--}}
    {{--                    </div>--}}
    {{--                </div>--}}

    {{--                <!-- Labor Regulation Info -->--}}
    {{--                <div class="arriveWorkerInfo mt-5">--}}
    {{--                    <div class="image wow fadeInUp">--}}
    {{--                        <img src="{{asset('site')}}/assets/images/f1.webp" alt="قواعد تنظيم العمالة">--}}
    {{--                    </div>--}}
    {{--                    <div class="info wow fadeInUp">--}}
    {{--                        <h3>قواعد تنظيم العمالة المنزلية</h3>--}}
    {{--                        <p>--}}
    {{--                            نلتزم بتطبيق قواعد تنظيم العمالة المنزلية لضمان حقوق جميع الأطراف، وتشمل:--}}
    {{--                        <ul>--}}
    {{--                            <li>تسجيل العاملين في أنظمة وزارة الموارد البشرية والتنمية الاجتماعية بشكل قانوني.</li>--}}
    {{--                            <li>التأكد من توقيع عقود عمل واضحة تحدد الواجبات والحقوق والرواتب.</li>--}}
    {{--                            <li>تقديم التدريب والإرشادات اللازمة للعمالة لتأدية مهامهم بكفاءة.</li>--}}
    {{--                            <li>التواصل المستمر مع صاحب العمل لضمان بيئة عمل آمنة ومنظمة.</li>--}}
    {{--                            <li>حل النزاعات بطريقة عادلة وشفافة مع احترام جميع القوانين والأنظمة المحلية والدولية.</li>--}}
    {{--                        </ul>--}}
    {{--                        </p>--}}
    {{--                        <h5>تنظيم العمالة يحفظ الحقوق ويضمن بيئة عمل آمنة للجميع</h5>--}}
    {{--                    </div>--}}
    {{--                </div>--}}
    {{--            @endforelse--}}
    {{--        </div>--}}

    {{--    </div>--}}
@endsection
@section('js')
@endsection
