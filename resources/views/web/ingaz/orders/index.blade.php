@extends('web.layout.app')

@section('content')
<main>
    <!-- page header -->
    <section class="page_header">
        <h1>{{helperTrans('web.my_orders')}}</h1>
        <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.my_orders')}}</h6>
    </section>
    <!-- orders_section -->
    <section class="setting_setion">
        <div class="container">
            <div class="row ">
                @include('web.layout.inc.profile_aside')

                <div class="col-lg-8 col-12">
                    <div class="orders">
                        <ul class="nav saved_items_tabs nav-pills mb-3" id="pills-tab" role="tablist">
                            <!-- currentOrders tab btn -->
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pills-currentOrders-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-currentOrders" type="button" role="tab"
                                    aria-controls="pills-currentOrders" aria-selected="true">
                                    {{helperTrans('web.current')}}
                                </button>
                            </li>
                            <!-- previousOrders tab btn -->
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pills-previousOrders-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-previousOrders" type="button" role="tab"
                                    aria-controls="pills-previousOrders" aria-selected="false">
                                    {{helperTrans('web.previous_order')}}
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content" id="pills-tabContent">
                            <!-- currentOrders tab -->
                            <div class="tab-pane fade show active" id="pills-currentOrders" role="tabpanel"
                                aria-labelledby="pills-currentOrders-tab">
                                <div class="row">
                                    @foreach($current as $current_order)
                                        <div class="col-lg-6 col-md-6 col-12 p-2">
                                            <div class="order_card">
                                                <h4>{{$current_order->jobType->title}}</h4>
                                                <div class="title">
                                                    <p>#{{$current_order->order_code}}</p>
                                                    <a
                                                        href="{{route('web.orders.show', $current_order->id)}}">{{helperTrans('web.details')}}</a>
                                                </div>
                                                <div class="pro">
                                                    <div class="staus">
                                                        <p>{{\App\Helpers\Constant::globalArray['orders_statuses'][$current_order->status][app()->getLocale()]}}
                                                        </p>
                                                    </div>
                                                    <div class="progress ">
                                                        <div class="progress-bar danger" role="progressbar"
                                                            style="width: {{$order_progressbar[$current_order->status]}}%"
                                                            aria-valuenow="{{$order_progressbar[$current_order->status]}}"
                                                            aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    @if($current_order->status == \App\Enums\ingaz\OrderEnum::New ->value && $current_order->jobType->id == 1)
                                                        @php($responsibleAdmin = $current_order->resolveResponsibleAdmin())
                                                        <div class="cta">
                                                            <p>
                                                                {{ helperTrans('web.please_contact_with') . ' ' . ($responsibleAdmin?->name ?? '') . ' ' . helperTrans('web.in_24_for_cv') }}
                                                            </p>
                                                            <div class="btns">
                                                                <a href="https://wa.me/{{ $responsibleAdmin?->formatted_whatsapp_phone }}"
                                                                    class="whatsapp" target="_blank">
                                                                    <i class="fa-brands fa-whatsapp"></i>
                                                                </a>
                                                                <a href="tel:{{ $responsibleAdmin?->formatted_contact_phone }}"
                                                                    target="_blank">
                                                                    <i class="fa-light fa-phone"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- أزرار التوجيه حسب نوع الطلب --}}
                                                    @if($current_order->status == \App\Enums\ingaz\OrderEnum::Completed ->value)
                                                        @if($current_order->jobType->id == 2)
                                                            {{-- طلب إيجار عمالة - توجيه إلى إنشاء عقد إيجار --}}
                                                            <div class="cta">
                                                                <p>إنشاء عقد إيجار</p>
                                                                <div class="btns">
                                                                    <a href="/admin/RentContract/create?order_id={{$current_order->id}}&maid_id={{$current_order->maid_id}}&user_id={{$current_order->user_id}}"
                                                                       class="btn btn-primary" target="_blank">
                                                                        <i class="fa-light fa-file-contract"></i>
                                                                        إنشاء عقد إيجار
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        @elseif($current_order->jobType->id == 3)
                                                            {{-- طلب استلام فوري - توجيه إلى إنشاء طلب نقل كفالة --}}
                                                            <div class="cta">
                                                                <p>إنشاء طلب نقل كفالة</p>
                                                                <div class="btns">
                                                                    <a href="/admin/SponsorshipTransfer/create?order_id={{$current_order->id}}&maid_id={{$current_order->maid_id}}&user_id={{$current_order->user_id}}"
                                                                       class="btn btn-success" target="_blank">
                                                                        <i class="fa-light fa-exchange"></i>
                                                                        إنشاء طلب نقل كفالة
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endif

                                                </div>
                                                <span
                                                    class="time">{{@$current_order->created_at->translatedFormat('d F , Y h:i a')}}</span>
                                            </div>
                                        </div>
                                    @endforeach

                                </div>
                            </div>
                            <!-- previous orders tab -->
                            <div class="tab-pane fade" id="pills-previousOrders" role="tabpanel"
                                aria-labelledby="pills-previousOrders-tab">
                                <div class="row">
                                    @foreach($previous as $previous_order)
                                        <div class="col-lg-6 col-md-6 col-12 p-2">
                                            <div class="order_card">
                                                <h4>{{$previous_order->jobType->title}}</h4>
                                                <div class="title">
                                                    <p>#{{$previous_order->order_code}}</p>
                                                    <a href="{{route('web.orders.show', $previous_order->id)}}">التفاصيل</a>
                                                </div>
                                                <span
                                                    class="time">{{@$previous_order->created_at->translatedFormat('d F , Y h:i a')}}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
