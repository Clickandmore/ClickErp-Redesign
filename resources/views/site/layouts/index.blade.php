<!DOCTYPE html>
<html @if (app()->getLocale() == 'ar') lang="ar" dir="rtl" @else  lang="en" dir="ltr" @endif>

<head>
    @include('site.includes.meta')
    @include('site.includes.css')

    @yield('style')
    <title> @yield('title') |
        {{ isset($settings) ? optional($settings->translate(app()->getLocale()))->website_name : documentSetting('website_name', config('app.name')) }}
    </title>
    <!-- Google tag (gtag.js) event -->
    <script>
        gtag('event', 'ads_conversion___1', {
            // <event_parameters>
        });
    </script>
    <!-- Google tag (gtag.js) event -->
    <script>
        gtag('event', 'ads_conversion___2', {
            // <event_parameters>
        });
    </script>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-923324756"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-923324756');
    </script>
    @if (isset($settings) && !empty($settings->tag_manager_head))
        {!! $settings->tag_manager_head !!}
    @endif

</head>

<body>
    <div class="preloader">
        <div class="preloaderImg"></div>
    </div>
    <header class="sticky-top">
        @include('site.layouts.header')
    </header>
    <main>
        @if (isset($settings))
            @if ($settings->there_are_offer == 1)
                <div id="ad-popup" class="ad-popup">
                    <div class="ad-popup-content">
                        <h5 class="ad-popup-title">{{ __('ingaz.Special offer!') }}</h5>
                        <p class="ad-popup-desc">{{ $settings->offer_text }}</p>
                    </div>
                    <button id="close-ad" class="ad-popup-close" aria-label="إغلاق">&times;</button>
                </div>
            @endif
        @endif


        @yield('content')
        <div class="modal fade add_location" id="addLocation" tabindex="-1" aria-labelledby="addLocationLabel"
            aria-hidden="true">
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
    <!-- <div class="themeColor">
    <input type="color" id="colorInput" class="color-input">
</div> -->
    @include('site.partials.whatsapp_float')
    <footer class="footer">
        @include('site.layouts.footer')
    </footer>
    @include('site.includes.js')
    @yield('scripts')
    @if (!empty($zohoSalesIqWidgetUrl))
        <script>
            window.$zoho = window.$zoho || {};
            $zoho.salesiq = $zoho.salesiq || {
                ready: function() {}
            }
        </script>
        <script id="zsiqscript" src="{{ $zohoSalesIqWidgetUrl }}" defer></script>
    @endif
    @if (isset($settings) && !empty($settings->tag_manager_footer))
        {!! $settings->tag_manager_footer !!}
    @endif


</body>

</html>
