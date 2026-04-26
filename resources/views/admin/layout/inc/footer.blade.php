<footer class="page-footer px-xl-4 px-sm-2 px-0 py-3">
    <div class="container-fluid d-flex flex-wrap justify-content-center align-items-center">
        @php
            $locale = app()->getLocale();
            $footerName = isset($settings) ? optional($settings->translate($locale))->dashboard_name : null;
            $footerName = $footerName ?: (isset($settings) ? optional($settings->translate($locale))->website_name : null);
            $footerName = $footerName ?: 'بلاتنيوم الشرقية للاستقدام';
            $footerCopyright = isset($settings) ? optional($settings->translate($locale))->copyright : null;
        @endphp
        <p class=" text-muted d-flex align-items-center gap-1">
            {{ $footerCopyright ?: __('auth.All Rights Reserved.') }}
            <a href="{{ url('/') }}" target="_blank" title="{{ $footerName }}" class="namiFotter">
                {{ $footerName }}
            </a>
            © {{ date('Y') }}
        </p>
    </div>
</footer>
