{{-- <footer class="page-footer px-xl-4 px-sm-2 px-0 py-3">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center">
        <p class="col-md-4 mb-0 text-muted">© {{date("Y")}} <a href="https://nami-tec.com/" target="_blank"
                                                      title="NAMI">NAMI</a>, All Rights
            Reserved.</p>
        <a href="#" class="col-md-4 d-flex align-items-center justify-content-center my-3 my-lg-0 me-lg-auto">
            NAMI
        </a>
        <ul class="nav col-md-4 justify-content-center justify-content-lg-end">
            <li class="nav-item"><a href="https://nami-tec.com/portfolio" target="_blank"
                                    class="nav-link px-2 text-muted">Portfolio</a></li>
            <li class="nav-item"><a href="https://nami-tec.com/terms-of-service" target="_blank"
                                    class="nav-link px-2 text-muted">licenses</a></li>
            <li class="nav-item"><a href="https://nami-tec.com/contact" target="_blank"
                                    class="nav-link px-2 text-muted">Support</a></li>
            <li class="nav-item"><a href="https://nami-tec.com/faq" target="_blank"
                                    class="nav-link px-2 text-muted">FAQs</a></li>
        </ul>
    </div>
</footer> --}}

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
                <img src="{{ $footerLogo }}" alt="{{ $footerName }}">
                <span class="dashboard-copyright-name">{{ $footerName }}</span>
            </a>
            <span class="dashboard-copyright-year">© {{ date('Y') }}</span>
        </p>
    </div>
</footer>
