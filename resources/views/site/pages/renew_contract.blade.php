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
            <a href="{{route('site.home.index')}}#services">{{__("ingaz.services")}} </a>
        </li>
        <li>
            <a href="#!" class="active">{{__("ingaz.renewing_a_worker_contract")}} </a>
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
            data-parent="#SVGwave1BottomSMShape">
            <g>
                <path
                    class="wave-bottom-1-sm-0"
                    fill="#fdfdfd"
                    opacity=".8"
                    d="M10.9,63.9c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7C293.7,41,315,61.2,315,61.2v54.4H10.9V63.9z"></path>
                <path
                    class="wave-bottom-1-sm-0"
                    fill="#fdfdfd"
                    opacity=".6"
                    d="M-55.7,64.6c0,0,42.9-34.5,87.5-14.2c77.3,35.1,113.3-2,146.6-4.7c48.7-4.1,69.9,16.2,69.9,16.2v54.4H-55.7     V64.6z"></path>
                <path
                    class="wave-bottom-1-sm-0"
                    fill="#fdfdfd"
                    opacity=".4"
                    fill-opacity="0"
                    d="M23.4,118.3c0,0,48.3-68.9,109.1-68.9c65.9,0,98,67.9,98,67.9v3.7H22.4L23.4,118.3z"></path>
                <path
                    class="wave-bottom-1-sm-0"
                    fill="#fdfdfd"
                    d="M-54.7,83c0,0,56-45.7,120.3-27.8c81.8,22.7,111.4,6.2,146.6-4.7c53.1-16.4,104,36.9,104,36.9l1.3,36.7l-372-3     L-54.7,83z"></path>
            </g>
        </svg>
    </figure>
</section>

<section class="contractRenew">
    <div class="container">
        <div class="headTitle text-center">
            <h2>{{__("ingaz.contract_renewal")}}</h2>
            <p>
                {{__("ingaz.contract_renewal_text")}}
            </p>
        </div>
        <div class="row">
            <div class="col-md-4 p-3">
                @include('site.partials.renew_contract_info')
            </div>
            <!-- جزء بيانات العاملة -->
            <div class="col-md-8 p-3">
                @include('site.partials.renew_contract_form')
            </div>
        </div>
    </div>
</section>

@endsection
@section('js')
@include('site.partials.renew_contract_form_js')
@endsection
