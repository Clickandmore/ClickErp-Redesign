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
                                                <h4>{{ getContractStatus($current_contract->status) }}</h4>
                                                <div class="title">
                                                    <p>#{{$current_contract->id}}</p>
                                                    <div class="title_actions">
                                                        <a href="{{route('web.contracts.show',$current_contract->id)}}">{{helperTrans('web.details')}}</a>
                                                        @if(!$current_contract->hasPendingReturnRequest())
                                                        <a href="{{route('web.contract-return-requests.create', $current_contract->id)}}" class="return_request_btn">
                                                            <i class="fa-light fa-undo"></i>
                                                            طلب استرجاع
                                                        </a>
                                                        @else
                                                        <span class="return_request_pending">
                                                            <i class="fa-light fa-clock"></i>
                                                            طلب استرجاع معلق
                                                        </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="pro">
                                                    <div class="staus">
                                                        <p>@lang('web.current_contract')</p>
                                                    </div>
                                                </div>
                                                @if($current_contract->maid && $current_contract->maid->passport_num)
                                                <div class="pro">
                                                    <div class="staus">
                                                        <p>{{helperTrans('web.passport_number')}}: {{$current_contract->maid->passport_num}}</p>
                                                    </div>
                                                </div>
                                                @endif
                                                @if($current_contract->info && $current_contract->info->visa_number)
                                                <div class="pro">
                                                    <div class="staus">
                                                        <p>{{helperTrans('web.visa_number')}}: {{$current_contract->info->visa_number}}</p>
                                                    </div>
                                                </div>
                                                @endif
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
                                                <h4>{{ getContractStatus($previous_contract->status) }}</h4>
                                                <div class="title">
                                                    <p>#{{$previous_contract->id}}</p>
                                                    <div class="title_actions">
                                                        <a href="{{route('web.contracts.show',$previous_contract->id)}}">{{helperTrans('web.details')}}</a>
                                                        @if(!$previous_contract->hasPendingReturnRequest())
                                                        <a href="{{route('web.contract-return-requests.create', $previous_contract->id)}}" class="return_request_btn">
                                                            <i class="fa-light fa-undo"></i>
                                                            طلب استرجاع
                                                        </a>
                                                        @else
                                                        <span class="return_request_pending">
                                                            <i class="fa-light fa-clock"></i>
                                                            طلب استرجاع معلق
                                                        </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="pro">
                                                    <div class="staus">
                                                        <p>@lang('web.previous_contract')</p>
                                                    </div>
                                                </div>
                                                @if($previous_contract->maid && $previous_contract->maid->passport_num)
                                                <div class="pro">
                                                    <div class="staus">
                                                        <p>{{helperTrans('web.passport_number')}}: {{$previous_contract->maid->passport_num}}</p>
                                                    </div>
                                                </div>
                                                @endif
                                                @if($previous_contract->info && $previous_contract->info->visa_number)
                                                <div class="pro">
                                                    <div class="staus">
                                                        <p>{{helperTrans('web.visa_number')}}: {{$previous_contract->info->visa_number}}</p>
                                                    </div>
                                                </div>
                                                @endif
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

@section('styles')
<style>
.title_actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
}

.title_actions a {
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.return_request_btn {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white !important;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    border: none;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
}

.return_request_btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
    color: white !important;
    text-decoration: none;
    background: linear-gradient(135deg, #dc2626, #b91c1c);
}

.return_request_btn i {
    font-size: 0.9rem;
}

.return_request_pending {
    background: linear-gradient(135deg, #64748b, #475569);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    opacity: 0.9;
    box-shadow: 0 2px 8px rgba(100, 116, 139, 0.2);
}

.return_request_pending i {
    font-size: 0.9rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

@media (max-width: 768px) {
    .title_actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .return_request_btn,
    .return_request_pending {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endsection
