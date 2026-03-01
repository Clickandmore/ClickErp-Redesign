<!DOCTYPE html>
<html>

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Primary Meta Tags -->
    <title>
        @if (isset($settings) && isset($settings->website_name))
            {{ $settings->website_name }}
        @endif
    </title>
    <!-- Loading indicator styles -->

    @include('web.layout.inc._meta')
    @include('web.layout.inc._css')
    @yield('css')
    @stack('css')




</head>

<body>

    <!-- ================ loader ================= -->
    <div class="preloader">
        <div class="preloaderImg"></div>
    </div>
    <!-- ================ Header ================= -->
    <div id="header-ajax">
        @include('web.layout.inc.header')
    </div>
    <!--(((((((((((((((((((((((()))))))))))))))))))))))-->
    <!--((((((((((((((((((( content )))))))))))))))))))-->
    <!--(((((((((((((((((((((((()))))))))))))))))))))))-->
    <content>
        @yield('content')
    </content>
    <!-- ================ Footer ================= -->

    @include('web.layout.inc.footer')
    <div id="footer-ajax">
        @include('web.layout.inc._js')
        @include('web.layout.inc._js_custom')
    </div>


    <!-- Add script to remove loader -->
    <script>
        window.addEventListener('load', function() {
            document.querySelector('.page-loader').classList.add('fade-out');
        });
    </script>

    @yield('js')
    @stack('js')
    <script>
        window.$zoho = window.$zoho || {};
        $zoho.salesiq = $zoho.salesiq || {
            ready: function() {}
        }
    </script>
    <script id="zsiqscript"
        src="https://salesiq.zohopublic.sa/widget?wc=e8345d69f3cb2a0945e374d8901771ae6b79f887aa0dd1ef960b4169df0f9cae"
        defer></script>

</body>

</html>
