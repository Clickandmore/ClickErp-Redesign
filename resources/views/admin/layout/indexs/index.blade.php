<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @include("admin.layout.inc._meta")
    <title>@hasSection('page-title') @yield('page-title') @else @yield('title', config('app.name')) @endif</title>


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
    <link rel="stylesheet" href="{{ asset('admin/assets/css/theme-clickmore.css') }}?v=17">
    @if (\App\Support\SystemModuleManager::usesRedesign())
        <link rel="stylesheet" href="{{ asset('admin/custom/clickerp-redesign.css') }}?v=1">
        <link rel="stylesheet" href="{{ asset('admin/custom/clickerp-shell.css') }}?v=2">
    @endif

</head>
<body class="layout-1 @if (app()->getLocale() == 'ar') rtl_mode @endif @if (\App\Support\SystemModuleManager::usesRedesign()) clickerp-redesign @endif" data-luno="theme-blue" data-admin-design="{{ \App\Support\SystemModuleManager::adminDesign() }}"> <!-- h-menu  -->

@php
    $sidebarViewData = get_defined_vars();
    echo \App\Support\BranchScopedPermissions::withCachedPermissions(
        fn () => view('admin.layout.inc.sidebar', $sidebarViewData)->render()
    );
@endphp


<div class="wrapper">

    @include("admin.layout.inc.header")

     {{--@include("admin.layout.inc.top_menu")--}}
    @include('admin.layout.inc.page_loader')
    @yield('up-card')


    @include("admin.layout.inc.footer")
</div>
@include("admin.layout.inc.global_modals")
@include("admin.layout.inc.siderbar_models")
@include("admin.layout.inc.scroll_controls")


@include("admin.layout.inc._js")
@include("admin.layout.inc._datatables_locale")
@include("admin.layout.inc._js_custom")
<script src="{{ asset('admin/assets/js/clickmore-loader.js') }}?v=2"></script>
<script src="{{ asset('admin/assets/js/clickmore-sidebar-state.js') }}?v=5"></script>
<script src="{{ asset('admin/assets/js/notifications.js') }}?v=2"></script>
<script src="{{ asset('admin/custom/list-state.js') }}?v=1"></script>
@if (\App\Support\SystemModuleManager::usesRedesign())
    <script src="{{ asset('admin/custom/clickerp-shell.js') }}?v=2"></script>
@endif

<script src="{{ asset('admin/assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

@yield("js")
@stack("js")
</body>
</html>
