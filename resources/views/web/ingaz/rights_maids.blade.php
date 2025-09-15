@extends('web.layout.app')

@section('content')

    <main>
        <!-- *** pages header *** -->
        <section class="page_header">
            <h1>{{helperTrans('web.rights_maids')}}</h1>
            <h6><a href="{{route('web.home')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.rights_maids')}}</h6>
        </section>
        <section class="terms">
            <div class="container">
                {!! @$settings->rights_maids !!}
            </div>
        </section>
    </main>
@endsection

@section('js')


    <script>


    </script>
@endsection
