<!DOCTYPE html>
<html lang="en">
<head>
    @include("nami.layout.inc._meta")
    <title>@yield('page-title')</title>


    @include("nami.layout.inc._css")

    <link href="{{ asset('admin/custom/style.css') }}" rel="stylesheet" type="text/css" />
    @if (App::getLocale() == 'ar')
        <link href="{{ asset('admin/custom/style-rtl.css') }}" rel="stylesheet" type="text/css" />
    @endif

    <link rel="stylesheet" type="text/css" href="{{ asset('plugins/sweetalert/sweetalert.css') }}">

    @yield("style")
    @yield("css")
    @stack("style")
    @stack("css")

    <link rel="stylesheet" type="text/css" href="{{ asset('admin/assets/css/tables.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/assets/css/theme-clickmore.css') }}?v=10">


</head>
<body class="layout-1  @if (app()->getLocale() == 'ar') rtl_mode @endif " data-luno="theme-blue"> <!-- h-menu  -->

@include("admin.layout.inc.sidebar")


<div class="wrapper">

    @include("admin.layout.inc.header")

     {{--@include("admin.layout.inc.top_menu")--}}
    @include('nami.layout.inc.page_loader')
    @yield('up-card')


    @include("nami.layout.inc.footer")
</div>
@include("nami.layout.inc.global_modals")
@include("nami.layout.inc.siderbar_models")


@include("nami.layout.inc._js")
@include("nami.layout.inc._js_custom")
<script src="{{ asset('admin/assets/js/clickmore-loader.js') }}?v=2"></script>
<script src="{{ asset('admin/assets/js/clickmore-sidebar-state.js') }}?v=2"></script>

<script>
     $("input[type=search]").on("input", function() {
            var searchTerm = $(this).val().toLowerCase().trim();
            if (searchTerm.length > 0) {
                $(".nav-sidebar li").each(function() {
                    var listItemText = $(this).text().toLowerCase();
                    if (listItemText.indexOf(searchTerm) === -1) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });
            } else {
                $(".nav-sidebar li").show();
            }
        });
</script>
@yield("js")
@stack("js")
</body>
</html>
