{{-- صيغة التاريخ الموحدة dd-mm-yyyy (العرض فقط؛ القيم المرسلة تبقى yyyy-mm-dd). انظر public/admin/assets/js/date-format.js --}}
<link rel="stylesheet" href="{{ asset('admin/assets/cssbundle/flatpickr.min.css') }}">
<style>
    .df-date-input { direction: ltr; unicode-bidi: plaintext; }
    html[dir="rtl"] .df-date-input { text-align: right; }
    .df-date-input[readonly], .df-date-input:disabled { background-color: var(--bs-secondary-bg, #e9ecef); }
    .flatpickr-calendar { z-index: 2000; }
</style>
<script src="{{ asset('admin/assets/js/bundle/flatpickr.bundle.js') }}"></script>
<script src="{{ asset('admin/assets/js/date-format.js') }}?v=1"></script>
