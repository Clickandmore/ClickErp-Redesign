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
    <!-- Hotjar Tracking Code for https://ussusalenjaz.sa/ -->
    <script>
        (function (h, o, t, j, a, r) {
            h.hj = h.hj || function () {
                (h.hj.q = h.hj.q || []).push(arguments)
            };
            h._hjSettings = {
                hjid: 3895725,
                hjsv: 6
            };
            a = o.getElementsByTagName('head')[0];
            r = o.createElement('script');
            r.async = 1;
            r.src = t + h._hjSettings.hjid + j + h._hjSettings.hjsv;
            a.appendChild(r);
        })(window, document, 'https://static.hotjar.com/c/hotjar-', '.js?sv=');
    </script>

   <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-K89T452S');</script>
    <!-- End Google Tag Manager -->

    
</head>

<body>
    <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-K89T452S"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    {{-- <!-- Loading indicator -->
    <div class="page-loader">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div> --}}

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
</body>

</html>
