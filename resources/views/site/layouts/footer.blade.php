@php
    $defaultCompanyLogo = asset('site/assets/images/company_logo.png');
    $footerLogo =
        isset($settings) && !empty($settings->logo_footer) ? showFile($settings->logo_footer) : $defaultCompanyLogo;
    $footerText =
        isset($settings) && !empty($settings->footer_text)
            ? $settings->footer_text
            : 'بلاتينيوم الشرقية للاستقدام يقدم خدمات استقدام العمالة المنزلية باحترافية وجودة مميزة.';
    $taxLogo =
        isset($settings) && !empty($settings->zatch_logo)
            ? showFile($settings->zatch_logo)
            : asset('site/assets/images/zatca-logo.png');
    $taxNumber = isset($settings) && !empty($settings->tax_number) ? $settings->tax_number : '314232024200003';
    $footerLocation = isset($settings) && !empty($settings->location) ? $settings->location : 'الدمام 32413';
    $footerEmail = isset($settings) && !empty($settings->email) ? $settings->email : 'info@ruknedem.com';
    $footerPhone = isset($settings) && !empty($settings->phone) ? $settings->phone : '920003174';
    $footerOtherPhone = isset($settings) && !empty($settings->other_phone) ? $settings->other_phone : '0552333182';
    $footerBrandName = isset($settings) ? optional($settings->translate(app()->getLocale()))->website_name : null;
    $footerBrandName = $footerBrandName ?: 'شركة بلاتنيوم الشرقية';
@endphp
<div class="container">
    <div class="row align-items-start">
        <!-- الشعار والوصف -->
        <div class="col-lg-4 col-md-6 mb-4 mb-md-0 footer-about">
            <img src="{{ $footerLogo }}" alt="Logo" class="footer-logo"
                onerror="this.onerror=null;this.src='{{ $defaultCompanyLogo }}';" />
            <p class="footer-desc">
                {{ $footerText }}
            </p>

            <div class="tax-info">
                <img src="{{ $taxLogo }}" alt="شعار هيئة الزكاة والضريبة والجمارك" class="tax-logo"
                    onerror="this.onerror=null;this.src='{{ asset('site/assets/images/zatca-logo.png') }}';" />
                <p class="tax-number">الرقم الضريبي: <span>{{ $taxNumber }}</span></p>
            </div>
        </div>

        <!-- خدماتنا -->
        <div class="col-6 col-lg-2 col-md-6 mb-4">
            <h6 class="footer-title">{{ __('ingaz.our_services') }}</h6>
            <ul class="footer-links">
                <li>
                    <a
                        href="{{ route('site.all-workers.index') }}?type=estqdam">{{ __('ingaz.recruitment_request') }}</a>
                </li>
                <li><a
                        href="{{ route('site.all-workers.index') }}?type=egar">{{ __('ingaz.labor_rental_request') }}</a>
                </li>
                <li>
                    <a
                        href="{{ route('site.daily-lebor-demand.index') }}">{{ __('ingaz.request_professional_labor') }}</a>
                </li>
                <li>
                    <a
                        href="{{ route('site.renew-contract.index') }}">{{ __('ingaz.renewing_employment_contract') }}</a>
                </li>
            </ul>
        </div>

        <!-- سياسات الاستقدام -->
        <div class="col-6 col-lg-3 col-md-6 mb-4">
            <h6 class="footer-title">{{ __('ingaz.recruitment_policies') }}</h6>
            <ul class="footer-links">
                <li><a href="{{ route('site.visa.index') }}">{{ __('ingaz.visa_issuance') }}</a></li>
                <li><a href="{{ route('site.select-worker.index') }}">{{ __('ingaz.selection_of_workers') }}</a></li>
                <li><a href="{{ route('site.all-workers.index') }}">{{ __('ingaz.recruitment_contract') }}</a></li>
                <li><a href="{{ route('site.arrival-worker.index') }}">{{ __('ingaz.arrival_worker') }}</a></li>
            </ul>
        </div>

        <!-- تواصل معنا -->
        <div class="col-12 col-lg-3 col-md-6 mb-4">
            <h6 class="footer-title">{{ __('ingaz.contact_us') }}</h6>
            <ul class="footer-contact">
                <li><i class="fa-solid fa-map-marker-alt"></i> {{ $footerLocation }}</li>
                <li><i class="fas fa-envelope"></i> {{ $footerEmail }}</li>
                <li><i class="fa-solid fa-phone"></i> {{ $footerPhone }}</li>
                <li><i class="fa-solid fa-headset"></i> {{ $footerOtherPhone }}</li>
            </ul>
        </div>
    </div>
</div>

<div class="footer-bottom">
    <div class="container">
        <div class="footer-bottom-content">
            <p>
                {{ __('ingaz.all_rights_reserved') }} ©
                <script>
                    document.write(new Date().getFullYear());
                </script>
                <a target="_blank" href="{{ url('/') }}"> {{ $footerBrandName }} </a>
            </p>
            <ul class="footer-social">
                @if (isset($settings) && $settings->facebook)
                    <li><a href="{{ $settings->facebook }}"><i class="fab fa-facebook-f"></i></a></li>
                @endif
                @if (isset($settings) && $settings->twitter)
                    <li><a href="{{ $settings->twitter }}"><i class="fab fa-twitter"></i></a></li>
                @endif
                @if (isset($settings) && $settings->instagram)
                    <li><a href="{{ $settings->instagram }}"><i class="fab fa-instagram"></i></a></li>
                @endif
                @if (isset($settings) && $settings->snapchat)
                    <li><a href="{{ $settings->snapchat }}"><i class="fab fa-snapchat-ghost"></i></a></li>
                @endif
                @if (isset($settings) && $settings->whatsapp)
                    <li>
                        <a href="https://wa.me/{{ ltrim($settings->whatsapp, '+') }}" target="_blank" rel="noopener">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </li>
                @endif
                @if (isset($settings) && $settings->email)
                    <li>
                        <a href="mailto:{{ $settings->email }}"><i class="fas fa-envelope"></i></a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
<script>
    function toArabicNumbers(num) {
        const arabicNums = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        return num.toString().split('').map(d => arabicNums[d] || d).join('');
    }

    var lang = '{{ app()->getLocale() }}';
    if (lang === 'ar') {
        const span = document.querySelector('.tax-number span');
        span.textContent = toArabicNumbers(span.textContent);
    }
</script>
