@extends('site.layouts.index')
@section('title')
@endsection
@section('content')
    <section class="profile">
        <div class="container">
            <!-- user Header  -->
            @include('site.pages.profile.includes.user_header')
            <!-- /user Header  -->
            <div class="row">
                <!-- links -->
                @include('site.pages.profile.includes.sidebar')
                <!-- content -->
                <div class="col-md-8 col-lg-9 p-2 profileContent">
                    <!-- order  -->
                    @foreach ($orders as $order)
                        <div class="order mb-2">
                            <div class="row">
                                <div class="col-sm-4 p-2">
                                    <div class="swiper workerCvSlider">
                                        <div class="swiper-wrapper">
                                            <!-- cv image -->
                                            <div class="swiper-slide">
                                                <a data-fancybox="user1-CV" href="{{ showFile($order->maid->cv_preview) }}">
                                                    <img src="{{ showFile($order->maid->cv_preview) }}" alt="" />
                                                </a>
                                            </div>
                                            {{-- <!-- cv image -->
                                            <div class="swiper-slide">
                                                <a data-fancybox="user1-CV" href="assets/images/cv2.webp">
                                                    <img src="assets/images/cv2.webp" alt="" />
                                                </a>
                                            </div> --}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-8 p-2">
                                    <ul class="info">
                                        <li>
                                            <h6> {{__('ingaz.nationality')}} : </h6>
                                            <p>{{$order->maid->nationality->title}} </p>
                                        </li>
                                        <li>
                                            <h6> {{__('ingaz.occupation')}} : </h6>
                                            <p> {{ $order->maid->jobTitles->first()->title }}</p>
                                        </li>
                                        <li>
                                            <h6> {{__('ingaz.booking_code')}} :</h6>
                                            <p>{{$order->order_code ?? ''}}</p>
                                        </li>
                                    </ul>
                                    <h5 class="orderStatus text-{{App\Enums\ingaz\ContractStatusEnum::from($order->status)->color()}}">
                                        {{App\Enums\ingaz\ContractStatusEnum::from($order->status)->lang()}}
                                        {{-- لقد انتهت صلاحية الحجز وتم الغاءة --}}
                                    </h5>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    {{-- <!-- order  -->
                    <div class="order mb-2">
                        <div class="row">
                            <div class="col-sm-4 p-2">
                                <div class="swiper workerCvSlider">
                                    <div class="swiper-wrapper">
                                        <!-- cv image -->
                                        <div class="swiper-slide">
                                            <a
                                                data-fancybox="user1-CV"
                                                href="assets/images/cv1.webp"
                                            >
                                                <img src="assets/images/cv1.webp" alt=""/>
                                            </a>
                                        </div>
                                        <!-- cv image -->
                                        <div class="swiper-slide">
                                            <a
                                                data-fancybox="user1-CV"
                                                href="assets/images/cv2.webp"
                                            >
                                                <img src="assets/images/cv2.webp" alt=""/>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-8 p-2">
                                <ul class="info">
                                    <li>
                                        <h6>الجنسية :</h6>
                                        <p>كينيا</p>
                                    </li>
                                    <li>
                                        <h6>المهنة :</h6>
                                        <p>--</p>
                                    </li>
                                    <li>
                                        <h6>كود الحجز :</h6>
                                        <p>RKE2022042844</p>
                                    </li>
                                </ul>
                                <h5 class="orderStatus done">تم تنفيذ المهمة بنجاح</h5>
                            </div>
                        </div>
                    </div> --}}
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
@endsection
