<div class="page-body px-xl-4 px-sm-2 px-0 py-lg-2 py-1">
    <div id="loading-wrapper" class="cm-global-loader">
        <div class="cm-loader-card" role="status" aria-live="polite" aria-label="Loading">
            <div class="cm-loader-line" aria-hidden="true">
                <span></span>
            </div>
        </div>
    </div>

    <div class="container-fluid bg-white p-2" style="min-height: calc(100vh - 196px); border-radius: 10px;">
        @yield('content')
    </div>
</div>
