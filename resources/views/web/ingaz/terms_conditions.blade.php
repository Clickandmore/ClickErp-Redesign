@extends('web.layout.app')

@section('content')
    <main>
        <!-- page header -->

        <section class="page_header">
            <h1>{{helperTrans('web.Terms_Conditions')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.Terms_Conditions')}}</h6>
        </section>
        <section class="terms">
            <div class="container">
                <h1 class="mb-5">{{helperTrans('web.Terms_Conditions')}}</h1>
               {!! $settings->terms_conditions ?? '' !!}
            </div>
        </section>
    </main>
@endsection

@section('js')

@endsection
