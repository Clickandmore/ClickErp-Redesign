<footer class="page-footer dashboard-footer px-xl-4 px-sm-2 px-0 py-3">
    <div class="container-fluid d-flex justify-content-center align-items-center">
        @php
            $footerName = 'Click ERP';
            $footerUrl = 'https://clickerp.com.sa';
            $footerLogo = asset('site/assets/images/fav.png');
        @endphp
        <p class="dashboard-copyright d-flex flex-wrap align-items-center justify-content-center gap-2 mb-0">
            <span class="dashboard-copyright-text">جميع الحقوق محفوظة لـ</span>
            <a href="{{ $footerUrl }}" target="_blank" rel="noopener" title="{{ $footerName }}" class="namiFotter dashboard-copyright-brand">
                <img class="dashboard-copyright-logo" src="{{ $footerLogo }}" alt="{{ $footerName }}"
                     style="width:24px!important;height:24px!important;max-width:24px!important;max-height:24px!important;object-fit:contain!important;">
                <span class="dashboard-copyright-name">{{ $footerName }}</span>
            </a>
            <span class="dashboard-copyright-year">© {{ date('Y') }}</span>
        </p>
    </div>
</footer>
