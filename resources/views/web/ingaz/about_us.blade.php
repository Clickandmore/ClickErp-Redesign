@extends('web.layout.app')

@section('content')
    <main>
        <!-- page header -->
        <div class="page_header container d-flex align-items-center">
            <h6>
                <a href="{{route('web.home')}}">{{__('web.Home')}}</a>
                <i class="fa-regular fa-angle-left"></i>
                {{__('web.overview')}}
            </h6>
        </div>
        <!-- about us section -->
        <section class="about-us">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-12">
                        <div class="aboutus_contect">

                            <div class="aboxxutt">
                                <h1>{{$about_us_main->title}}</h1>
                                <p class="about_us__text">
                                    {{$about_us_main->description}}
                                </p>
                            </div>
                            @foreach($about_us as $item)
                            <div class="{{$item->type}}">
                                <h3>{{$item->title}}</h3>
                                <p>
                                    {{$item->description}}
                                </p>
                            </div>

                            @endforeach
                        </div>
                    </div>
                    <div class="col-lg-6 col-12">
                        <div class="image">
                            <img src="@if(isset($settings) && isset($settings->aboutus_image))
      {{ showFile($settings->aboutus_image) }}
      @else
      {{url('/')}}/web/assets/images/about-us.jpg
       @endif" alt="about">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- testimonials -->
        <section class="testimonials">
            <div class="container">
                <h2>آراء العملاء</h2>
                <div class="swiper testimonilasSwiper">
                    <div class="swiper-wrapper">
                        @foreach($testimonials as $testimonial)
                        <div class="swiper-slide">
                            <div class="review">
                                <p>
                                    {{$testimonial->comment}}
                                </p>
                            </div>
                            <div class="review-owner">
                                <div class="img">
                                    <img src=" {{showFile($testimonial->image)}}" alt="reviewer">
                                </div>
                                <div class="name">
                                    <h5> {{$testimonial->name}}</h5>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="testimonialsSwiperPagination"></div>
                </div>
            </div>
        </section>
        <!-- downloadApp-section -->
        <section class="downloadApp">
            <div class="container">
                <h2>{{__('web.nice_about_app')}}</h2>
                <p>
                    {{__('web.do_you_want_go_nice_about_app')}}
                </p>
                <div class="btns">
                    <a href="@if(isset($settings) && isset($settings->app_store_link))
      {{ $settings->app_store_link }}
      @else
      #
       @endif">
                        <img src="{{url('/web')}}/assets/images/app-store.svg" alt="app-store">
                    </a>
                    <a href="@if(isset($settings) && isset($settings->google_play_link))
      {{ $settings->google_play_link }}
      @else
      #
       @endif">
                        <img src="{{url('/web')}}/assets/images/google-play.svg" alt="google-play">
                    </a>
                </div>
            </div>
        </section>
        <!-- features -->
        <section class="features">
            <div class="container">
                <div class="row">
                    @foreach($features as $feature)
                        <div class="col-lg-3 col-6 p-2">
                            <div class="feature">
                                <img src="{{showFile($feature->image)}}" alt="">
                                <h5>{{$feature->title}}</h5>
                                <p>{{$feature->description}}</p>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>
        </section>
    </main>
@endsection

@section('js')

@endsection
