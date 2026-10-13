<!DOCTYPE html>
<html @if (app()->getLocale() == 'ar') lang="ar" dir="rtl" @else  lang="en" dir="ltr" @endif>

<head>
    @include('site.includes.meta')
    @include('site.includes.css')

    @yield('style')
    <title> @yield('title') |
        {{ isset($settings) ? optional($settings->translate(app()->getLocale()))->website_name : documentSetting('website_name', config('app.name')) }}
    </title>
    @if (isset($settings) && !empty($settings->tag_manager_head))
        {!! $settings->tag_manager_head !!}
    @endif
    @if (isset($settings) && !empty($settings->tiktok_tag))
        {!! $settings->tiktok_tag !!}
    @endif
    @if (isset($settings) && !empty($settings->snapchat_tag))
        {!! $settings->snapchat_tag !!}
    @endif
    @if (isset($settings) && !empty($settings->zoho_tag))
        {!! $settings->zoho_tag !!}
    @endif
    @if (isset($settings) && !empty($settings->hotjar_tag))
        {!! $settings->hotjar_tag !!}
    @endif
    @if (isset($settings) && !empty($settings->meta_pixel_tag))
        {!! $settings->meta_pixel_tag !!}
    @endif

</head>

<body>
    <div class="preloader" role="status" aria-label="{{ app()->getLocale() === 'ar' ? 'جاري تحميل الصفحة' : 'Loading page' }}">
        <div class="preloaderImg"></div>
    </div>
    <script>
        (function () {
            let preloaderDismissed = false;

            function dismissPreloader() {
                if (preloaderDismissed) {
                    return;
                }

                const preloader = document.querySelector('.preloader');
                if (!preloader) {
                    return;
                }

                preloaderDismissed = true;
                preloader.setAttribute('aria-hidden', 'true');
                preloader.style.pointerEvents = 'none';
                preloader.style.opacity = '0';
                preloader.style.transition = 'opacity 200ms ease';

                window.setTimeout(function () {
                    preloader.style.display = 'none';
                }, 200);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', dismissPreloader, { once: true });
            } else {
                dismissPreloader();
            }

            window.addEventListener('load', dismissPreloader, { once: true });

            // External scripts must never be able to keep the page covered indefinitely.
            window.setTimeout(dismissPreloader, 2500);
        })();
    </script>
    <header class="sticky-top">
        @include('site.layouts.header')
    </header>
    <main>
        @php
            $announcementImage = (string) ($setting['popup_image'] ?? '');
            $announcementEnabled = (bool) ($setting['show_popup'] ?? false) && $announcementImage !== '';
            $announcementLink = trim((string) ($setting['popup_link'] ?? ''));
            $announcementNationalityIds = collect($setting['popup_nationality_ids'] ?? [])->filter()->unique()->values();

            if (($setting['popup_destination_type'] ?? 'general') === 'nationalities' && $announcementNationalityIds->isNotEmpty()) {
                $announcementParams = ['type' => 'estqdam'];
                if ($announcementNationalityIds->count() === 1) {
                    $announcementParams['nationality_id'] = $announcementNationalityIds->first();
                } else {
                    $announcementParams['nationality_ids'] = $announcementNationalityIds->implode(',');
                }
                $announcementLink = route('site.all-workers.index', $announcementParams);
            }
            $announcementVersion = substr(sha1($announcementImage), 0, 12);
        @endphp
        @if ($announcementEnabled)
            <div id="ad-popup" class="ad-popup announcement-popup" data-announcement-version="{{ $announcementVersion }}" role="dialog" aria-modal="true" aria-label="{{ __('admin.popup_settings') }}">
                <div class="announcement-popup__card">
                    <button id="close-ad" class="ad-popup-close announcement-popup__close" type="button" aria-label="{{ app()->getLocale() === 'ar' ? 'إغلاق' : 'Close' }}">&times;</button>
                    @if ($announcementLink !== '')
                        <a href="{{ $announcementLink }}" class="announcement-popup__link">
                            <img src="{{ showFile($announcementImage) }}" alt="{{ __('admin.popup_settings') }}" class="announcement-popup__image">
                        </a>
                    @else
                        <img src="{{ showFile($announcementImage) }}" alt="{{ __('admin.popup_settings') }}" class="announcement-popup__image">
                    @endif
                </div>
            </div>
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
    @if(config('website-visitors.legacy_internal_tracking', false))
        @include('site.partials.visitor_tracking')
    @endif
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
    @if (isset($settings) && !empty($settings->live_chat_tag))
        {!! $settings->live_chat_tag !!}
    @endif


</body>

</html>
