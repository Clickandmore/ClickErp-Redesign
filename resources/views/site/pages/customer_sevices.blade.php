@extends('site.layouts.index')
@section('title')
@endsection
@section('content')
    <!-- select Customer Service -->
    <section class="selectCustomerService">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 p-2">
                    <div class="headTitle">
                        <h1> {{__("ingaz.choose_customer_service")}} </h1>
                        <p>
                           {{__("ingaz.customer_service_desc")}}
                        </p>
                    </div>
                    <form action="{{route('site.customer-service.update', $order->id)}}" method="post">
                        @csrf
                        @method('put')
                        <div class="choose">
                            @foreach ($customer_services as $key => $customer_service)
                                <div class="customerOption " data-aos=" fade-up">
                                    <input type="radio" class="btn-check" value="{{$customer_service->id}}" name="customer" id="option_{{$customer_service->id}}">
                                    <label class="btn btn-outline" for="option_{{$customer_service->id}}">
                                        <img src="{{asset('site')}}/assets/images/avater2.webp" alt="">
                                        <span>
                                            {{ $customer_service->name }}
                                            <small class="d-block text-muted mt-1">اتصال: {{ $customer_service->contact_phone ?? 'غير محدد' }}</small>
                                            <small class="d-block text-muted">واتساب: {{ $customer_service->whatsapp_phone ?? 'غير محدد' }}</small>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class=" pt-4 p-2 text-center">
                            <button type="submit" class="btn px-5 m-auto">
                                {{__('ingaz.confirm')}}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <!-- themeColor -->
    <!-- <div class="themeColor">
        <input type="color" id="colorInput" class="color-input">
    </div> -->
@endsection
@section('js')
@endsection
