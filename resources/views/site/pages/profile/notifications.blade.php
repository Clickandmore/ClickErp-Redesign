@extends('site.layouts.index')
@section('title')
    {{__("ingaz.notifications")}}

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

                    <div class="notifications">
                        <ul>
                            @forelse($notifications as $notification)
                                <li class="notification">
                                    <h6 class="notificationTitle">
                                        {{ $notification->getTranslation('title', app()->getLocale()) }}
                                    </h6>
                                    <p>
                                        {{ $notification->getTranslation('description', app()->getLocale()) }}
                                    </p>
                                    <div class="date d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="fas fa-calendar-alt me-2"></i>
                                            {{ $notification->created_at->format('d / m / Y') }}
                                        </div>
                                        <div>
                                            <i class="far fa-clock me-2"></i>
                                            {{ $notification->created_at->format('h:i a') }}
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="text-center text-muted">{{__("ingaz.no_notifications_found")}}</li>
                            @endforelse
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
@endsection
