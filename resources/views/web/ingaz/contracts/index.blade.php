@extends('web.layout.app')

@section('content')
    <main>
        <!-- page header -->

        <section class="page_header">
            <h1>{{helperTrans('web.my_contracts')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.my_contracts')}}</h6>
        </section>
        <!-- contracts_section -->

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
                                        @foreach($current as $current_contract)
                                        <div class="col-lg-6 col-md-6 col-12 p-2">
                                            <div class="order_card">
                                                <h4>{{$current_contract->status}}</h4>
                                                <div class="title">
                                                    <p>#{{$current_contract->id}}</p>
                                                    <a href="{{route('web.contracts.show',$current_contract->id)}}">{{helperTrans('web.details')}}</a>
                                                </div>
                                                <div class="pro">
                                                    <div class="staus">
                                                        <p>@lang('web.current_contract')</p>
                                                    </div>
                                                </div>
                                                <span class="time">{{@$current_contract->created_at->translatedFormat('d F , Y h:i a')}}</span>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <!-- previous orders tab -->
                                <div class="tab-pane fade" id="pills-previousOrders" role="tabpanel"
                                     aria-labelledby="pills-previousOrders-tab">
                                    <div class="row">
                                        @foreach($previous as $previous_contract)
                                        <div class="col-lg-6 col-md-6 col-12 p-2">
                                            <div class="order_card">
                                                <h4>{{$previous_contract->status}}</h4>
                                                <div class="title">
                                                    <p>#{{$previous_contract->id}}</p>
                                                    <a href="{{route('web.contracts.show',$previous_contract->id)}}">{{helperTrans('web.details')}}</a>
                                                </div>
                                                <div class="pro">
                                                    <div class="staus">
                                                        <p>@lang('web.previous_contract')</p>
                                                    </div>
                                                </div>
                                                <span class="time">{{@$previous_contract->created_at->translatedFormat('d F , Y h:i a')}}</span>
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

@section('js')
@endsection
