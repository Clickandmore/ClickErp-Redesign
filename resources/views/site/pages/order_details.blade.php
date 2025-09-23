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
                                <a href="{{route('site.profile.index')}}"> {{__('ingaz.Recruitment requests')}} </a>
                            </li>
                            <li>
                                <a class="active"> {{__('ingaz.order_details')}} </a>
                            </li>
                        </ul>
                    </div>
                    <!-- status -->
                    <div class="status">
                        @php
                            $steps = [
                                \App\Enums\ingaz\ContractStatusEnum::New,
                                \App\Enums\ingaz\ContractStatusEnum::Msaned,
                                \App\Enums\ingaz\ContractStatusEnum::Tafwed,
                                \App\Enums\ingaz\ContractStatusEnum::Taffez,
                                \App\Enums\ingaz\ContractStatusEnum::Tazker,
                                \App\Enums\ingaz\ContractStatusEnum::UnderWarranty,
                            ];

                            $currentStatus = \App\Enums\ingaz\ContractStatusEnum::from($order->status);

                            $currentIndex = array_search($currentStatus, $steps);
                        @endphp
                        <ol>
                            @foreach($steps as $status)
                                <li class="{{ $loop->index <= $currentIndex ? 'completed' : '' }}">
                                    <p>{{ $status->lang() }}</p>
                                </li>
                            @endforeach
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
                                            <a data-fancybox="user1-CV" href="{{$order->maid->cv_preview}}">
                                                <img src="{{$order->maid->cv_preview}}" alt="">
                                            </a>
                                        </div>
                                        {{-- <!-- cv image -->
                                        <div class="swiper-slide ">
                                            <a data-fancybox="user1-CV" href="assets/images/cv2.webp">
                                                <img src="assets/images/cv2.webp" alt="">
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
                    </div>
                    @if ($order->customer_id != null)
                        <!-- customerInfo -->
                        <div class="selectedCustomerInfo">
                            <div class="row">
                                <div class="col-md-6 p-1">
                                    <div class="info">
                                        <img src="{{asset('site/assets/images/avater2.webp')}}" alt="">
                                        <div class="text">
                                            <h6> {{$order->customer->name}}</h6>
                                            <p>{{__('ingaz.customers_service')}}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 p-1">
                                    <a class="contact" href="https://wa.me/{{$order->customer->phone}}" target="_blank">
                                        <i class="me-2 fa-brands fa-whatsapp-square"></i>
                                        <p>{{__('ingaz.through_whatsapp')}}</p>
                                    </a>
                                </div>
                                <div class="col-6 col-md-3 p-1">
                                    <a class="contact" href="tel:{{$order->customer->phone}}" target="_blank">
                                        <i class="me-2 fa-solid fa-square-phone"></i>
                                        <p>{{__('ingaz.through_phone')}}</p>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
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
