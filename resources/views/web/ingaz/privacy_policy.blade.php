@extends('web.layout.app')

@section('content')
    <main>
        <!-- page header -->

        <section class="page_header">
            <h1>{{helperTrans('web.privacy')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.privacy')}}</h6>
        </section>
        <!-- terms_conditions section -->
        <section class="terms">
            <div class="container">
                <h1>{{helperTrans('web.privacy')}}</h1>
                {!! $settings->privacy ?? '' !!}
            </div>
        </section>
    </main>
@endsection

@section('js')

@endsection
