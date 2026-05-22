<footer class="page-footer px-xl-4 px-sm-2 px-0 py-3">
    <div class="container-fluid d-flex flex-wrap justify-content-center align-items-center">
        @php
            $footerName = 'Click ERP';
            $footerUrl = 'https://clickerp.com.sa';
            $footerLogo = isset($settings) && !empty($settings->admin_logo)
                ? showFile($settings->admin_logo)
                : (isset($settings) && !empty($settings->logo_header)
                    ? showFile($settings->logo_header)
                    : asset('site/assets/images/company_logo.png'));
        @endphp
        <p class="dashboard-copyright text-muted d-flex flex-wrap align-items-center justify-content-center gap-2 mb-0">
            <span>جميع الحقوق محفوظة لـ</span>
            <a href="{{ $footerUrl }}" target="_blank" rel="noopener" title="{{ $footerName }}" class="namiFotter dashboard-copyright-brand">
                <img src="{{ $footerLogo }}" alt=""
                     onerror="this.onerror=null;this.style.display='none';">
                <span>{{ $footerName }}</span>
            </a>
            <span>© {{ date('Y') }}</span>
        </p>
    </div>
</footer>
