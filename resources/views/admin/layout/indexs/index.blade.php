<!DOCTYPE html>
<html lang="en">
<head>
    @include("admin.layout.inc._meta")
    <title>@yield('page-title')</title>


    @include("admin.layout.inc._css")

    <link href="{{ asset('admin/custom/style.css') }}" rel="stylesheet" type="text/css" />
    @if (App::getLocale() == 'ar')
        <link href="{{ asset('admin/custom/style-rtl.css') }}" rel="stylesheet" type="text/css" />
    @endif

    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/sweetalert/sweetalert.css') }}">

    <!-- Admin Tables & Sidebar Design System -->
    
    @yield("style")
    @yield("css")
    @stack("style")
    @stack("css")

    {{-- Table some styles --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('admin/assets/css/tables.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/assets/css/theme-clickmore.css') }}?v=8">

</head>
<body class="layout-1  @if (app()->getLocale() == 'ar') rtl_mode @endif " data-luno="theme-blue"> <!-- h-menu  -->

@include("admin.layout.inc.sidebar")


<div class="wrapper">

    @include("admin.layout.inc.header")

     {{--@include("admin.layout.inc.top_menu")--}}
    @include('admin.layout.inc.page_loader')
    @yield('up-card')


    @include("admin.layout.inc.footer")
</div>
@include("admin.layout.inc.global_modals")
@include("admin.layout.inc.siderbar_models")


@include("admin.layout.inc._js")
@include("admin.layout.inc._js_custom")

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@section('js')
    <script src="{{asset('admin/assets/js/notifications.js')}}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/ar.js"></script>
    <script>
        moment.locale('ar');
    </script>
@endsection

@yield("js")
@stack("js")
</body>
</html>
