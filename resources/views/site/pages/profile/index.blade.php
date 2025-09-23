@extends('site.layouts.index')
@section('title')
    {{__('ingaz.profile')}}
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
                    <a href="{{route('site.all-workers.index') }}" class="newOrder">
                        <i class="fa-duotone fa-file-circle-plus"></i>
                        <p> {{__('ingaz.Submit a new recruitment application')}} </p>
                    </a>
                    <!-- orders -->
                    <div class="row">
                        @foreach ($orders as $order)
                            <!-- single order -->
                            <div class="col-lg-6 p-2">
                                <div class="order">
                                    <div class="row">
                                        <div class="col-sm-6 p-2">
                                            <div class="swiper workerCvSlider ">
                                                <div class="swiper-wrapper">
                                                    <!-- cv image -->
                                                    <div class="swiper-slide ">
                                                        <a data-fancybox="user1-CV" href="{{showFile($order->maid->cv_preview)}}">
                                                            <img src="{{showFile($order->maid->cv_preview)}}" alt="">
                                                        </a>
                                                    </div>
                                                    {{-- <!-- cv image -->
                                                    <div class="swiper-slide ">
                                                        <a data-fancybox="user1-CV" href="{{showFile($order->maid->cv_preview)}}">
                                                            <img src="{{showFile($order->maid->cv_preview)}}" alt="">
                                                        </a>
                                                    </div> --}}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 p-2">
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
                                                    <h6> {{__('ingaz.order_status')}} : </h6>
                                                    <p>
                                                        @if ($order->customer_id != null)
                                                            {{__('ingaz.One of the customer service representatives has been selected.')}}
                                                        @else
                                                            {{__('ingaz.The order is not yet assigned to a customer service representative.')}}
                                                        @endif
                                                    </p>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="profileCustomerInfo">
                                        @if ($order->customer_id != null)
                                            <div class="info">
                                                <img src="{{asset('site/assets/images/avater2.webp')}}" alt="">
                                                <div class="text">
                                                    <h6> {{$order->customer->name}}</h6>
                                                    <p>{{__('ingaz.customers_service')}}</p>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="contact">
                                            <a href="{{route('site.recruitment-orders.show', $order->id)}}">
                                                <p> {{__('ingaz.details')}} </p>
                                                <i class="fa-duotone fa-left-long ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
@endsection
