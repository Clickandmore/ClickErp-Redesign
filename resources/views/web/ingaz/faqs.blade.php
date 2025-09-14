@extends('web.layout.app')

@section('content')
    <main>
        <section class="page_header">
            <h1>{{helperTrans('web.FAQs')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.FAQs')}}</h6>
        </section>
        <!-- page header -->
        <!-- faqs section -->
        <section class="faqs">
            <div class="container">
                <div class="row align-items-center justify-content-around">
                    <div class="col-12 p-3 faqs-content">
                        <h3>   {{__('web.FAQs')}} <i class="fa-sharp fa-solid fa-question"></i></h3>
                        <div class="accordion" id="accordionExample">
                            @foreach($faqs as $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading{{$faq->id}}">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse{{$faq->id}}" aria-expanded="true" aria-controls="collapse{{$faq->id}}">
                                        {{$faq->title}}
                                    </button>
                                </h2>
                                <div id="collapse{{$faq->id}}" class="accordion-collapse collapse {{$faq === $faqs->first() ? 'show' :'' }}"
                                     aria-labelledby="heading{{$faq->id}}" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <p>
                                            {{$faq->description}}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection

@section('js')

@endsection
