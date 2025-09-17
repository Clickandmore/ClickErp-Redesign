<!DOCTYPE html>
<html @if(app()->getLocale() == 'ar') lang="ar" dir="rtl" @else  lang="en" dir="ltr"@endif>
<head>
    @include('site.includes.meta')
    @include('site.includes.css')
    <title>  @yield('title') بلاتينيوم الشرقية للاستقدام | </title>

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
</main>
<!-- themeColor -->
<div class="themeColor">
    <input type="color" id="colorInput" class="color-input">
</div>
<footer class="footer">
    @include('site.layouts.footer')
</footer>
@include('site.includes.js')
</body>
</html>
