@extends('web.layout.app')

@section('content')
    <main>

        <!-- *** pages header *** -->
        <section class="page_header">
            <h1>{{helperTrans('web.my_profile')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.notifications')}}</h6>
        </section>
        <section class="setting_setion">
            <div class="container">
                <div class="row ">
                    @include('web.layout.inc.profile_aside')

                    <div class="col-lg-8 col-12">
                        @forelse($notifications as $notification)
                         @if($notification->order_id)  <a href="{{route('web.orders.show',$notification->order_id)}}"> @endif
                            <div class="notification">
                                <p>{{$notification->description}}</p>
                                <span>{{\Carbon\Carbon::parse($notification->created_at)->diffForHumans()}}</span>
                            </div>
                         @if($notification->order_id)   </a> @endif
                        @empty
                            <div class="alert alert-info active text-center" style="color: #1d70b7 ;height: 15%"> لا يوجد اشعارات</div>
                        @endforelse

                    </div>
                </div>
            </div>
        </section>

    </main>
@endsection

@section('js')

@endsection
