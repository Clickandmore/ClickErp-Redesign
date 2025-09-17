@extends('site.layouts.index')
@section('title')
@endsection
@section('content')
<section class="selectedCustomer selected-page">
    @if(isset($customer))
        <img src="{{showFile($customer->image)}}" alt="">
        <h4 class="massage">
        {{__('ingaz.please_contact')}} <span> {{$customer->name}}</span> {{__('ingaz.The one you have chosen within 24 hours to avoid canceling your CV reservation.')}}
        </h4>
        <div class="contactType">
        <a class="contact" href="https://wa.me/{{@$customer->phone}}" target="_blank">
            <i class="fa-brands fa-whatsapp"></i>
            <p>{{__('ingaz.through_whatsapp')}}</p>
        </a>
        <a class="contact" href="tel:{{@$customer->phone}}" target="_blank">
            <i class="fa-solid fa-phone"></i>
            <p>{{__('ingaz.through_phone')}}</p>
        </a>
        </div>
    @else
        <h4 class="massage">
          {{__('ingaz.Booking successful')}}
        </h4>
    @endif
  </section>
@endsection
@section('js')
@endsection
