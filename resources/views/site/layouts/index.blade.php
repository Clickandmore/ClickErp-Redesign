<!DOCTYPE html>
<html @if(app()->getLocale() == 'ar') lang="ar" dir="rtl" @else  lang="en" dir="ltr"@endif>
<head>
    @include('site.includes.meta')
    @include('site.includes.css')

    @yield('style')
    <title>  @yield('title')  |  {{__("ingaz.Platinum Al Sharqiyah Recruitment Agency")}}</title>

</head>
<body>
<div class="preloader">
    <div class="preloaderImg"></div>
</div>
<header class="sticky-top">
    @include('site.layouts.header')
</header>
<main>
    @yield('content')
    <div class="modal fade add_location" id="addLocation" tabindex="-1" aria-labelledby="addLocationLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>
</main>
<!-- themeColor -->
<div class="themeColor">
    <input type="color" id="colorInput" class="color-input">
</div>
<footer class="footer">
    @include('site.layouts.footer')
</footer>
@include('site.includes.js')
@yield('scripts')
</body>
</html>
