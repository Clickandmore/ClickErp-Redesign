{{-- تنسيقات نموذج العميل المشتركة بين شاشتي الإضافة والتعديل --}}
<style>
    .client-form-page {
        max-width: 1180px;
        margin: 0 auto;
    }

    .client-form-header,
    .client-form-card,
    .client-form-actions {
        border: 1px solid #e8edf3;
        border-radius: 8px;
        background: #fff;
    }

    .client-form-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 22px 24px;
        margin-bottom: 16px;
    }

    .client-form-header h4 {
        margin: 0;
        color: #202734;
        font-size: 22px;
        font-weight: 700;
    }

    .client-form-header p {
        margin: 8px 0 0;
        color: #667085;
        font-size: 14px;
    }

    .client-form-card {
        margin-bottom: 16px;
        box-shadow: 0 10px 26px rgba(16, 24, 40, .06);
        overflow: hidden;
    }

    .client-form-card .card-body,
    .client-form-card .card-header {
        padding: 22px 24px;
    }

    .client-form-card .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid #eef2f6;
        background: #fbfcfe;
    }

    .section-heading {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        color: #202734;
        font-size: 16px;
        font-weight: 700;
    }

    .section-heading i {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        color: #0f766e;
        background: #e6f6f3;
    }

    .optional-label,
    .required-label {
        display: inline-flex;
        align-items: center;
        height: 24px;
        padding: 0 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .required-label {
        color: #b42318;
        background: #fff1f0;
        border: 1px solid #ffd6d1;
    }

    .optional-label {
        color: #475467;
        background: #f2f4f7;
        border: 1px solid #e4e7ec;
    }

    .form-label {
        color: #344054;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .form-control,
    .form-select,
    .input-group-text {
        min-height: 44px;
        border-color: #d9e0ea;
        border-radius: 8px;
    }

    .input-group .form-control {
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
    }

    .input-group-text {
        color: #344054;
        background: #f8fafc;
        font-weight: 700;
    }

    /* ===== القوائم المنسدلة: شكل موحد مع باقي الحقول ===== */
    .form-control:focus,
    .form-select:focus {
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, .15);
    }

    /* القوائم العادية (التصنيف، مفتاح الدولة، شهر/سنة التقويم الهجري) */
    .client-form-page .form-select {
        color: #344054;
        background-color: #fff;
        cursor: pointer;
    }

    html[dir="rtl"] .client-form-page .form-select:not([dir="ltr"] *):not([dir="ltr"]) {
        padding-right: .75rem;
        padding-left: 2.25rem;
        background-position: left .75rem center;
    }

    .client-form-page .form-select:disabled {
        color: #667085;
        background-color: #f2f4f7;
        cursor: not-allowed;
    }

    .client-form-page .form-select-sm {
        min-height: 36px;
        border-radius: 6px;
        font-size: 13px;
    }

    /* مفتاح الدولة بجوار رقم الجوال الآخر */
    .phone-code-select {
        flex: 0 0 auto;
        width: 170px;
        max-width: 45%;
        background-color: #f8fafc !important;
        font-weight: 700;
    }

    /* Select2: الحقل المغلق */
    .client-form-page .select2-container {
        width: 100% !important;
    }

    .client-form-page .select2-container--default .select2-selection--single {
        display: flex;
        align-items: center;
        height: 44px;
        border: 1px solid #d9e0ea;
        border-radius: 8px;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .client-form-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        flex: 1;
        padding: 0 14px;
        color: #344054;
        line-height: 42px;
    }

    .client-form-page .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #98a2b3;
    }

    .client-form-page .select2-container--default .select2-selection--single .select2-selection__arrow {
        top: 0;
        width: 36px;
        height: 42px;
    }

    .client-form-page .select2-container--default .select2-selection--single .select2-selection__arrow b {
        width: 8px;
        height: 8px;
        margin-top: -6px;
        margin-left: -4px;
        border: solid #667085;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
        transition: transform .15s ease;
    }

    .client-form-page .select2-container--open .select2-selection--single .select2-selection__arrow b {
        margin-top: -2px;
        border-color: #0f766e;
        transform: rotate(-135deg);
    }

    /* في الواجهة العربية: السهم على اليسار ومساحة له حتى لا يغطي النص */
    html[dir="rtl"] .client-form-page .select2-container--default .select2-selection--single .select2-selection__arrow {
        right: auto;
        left: 4px;
    }

    html[dir="rtl"] .client-form-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-right: 14px;
        padding-left: 40px;
        text-align: right;
    }

    html[dir="ltr"] .client-form-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-right: 40px;
    }

    .client-form-page .select2-container--default .select2-selection--single .select2-selection__clear {
        margin: 0 4px;
        color: #98a2b3;
        font-size: 18px;
        font-weight: 400;
    }

    .client-form-page .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        color: #b42318;
    }

    .client-form-page .select2-container--default.select2-container--focus .select2-selection--single,
    .client-form-page .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, .15);
    }

    .client-form-page .select2-container--default.select2-container--disabled .select2-selection--single {
        background: #f2f4f7;
        cursor: not-allowed;
    }

    /* حقل به خطأ من التحقق */
    .client-form-page select.is-invalid + .select2-container .select2-selection--single {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, .12);
    }

    /* Select2: القائمة المفتوحة (تُضاف في نهاية الصفحة، لذلك بدون .client-form-page) */
    .select2-container--default .select2-dropdown {
        overflow: hidden;
        border: 1px solid #d9e0ea;
        border-radius: 8px;
        box-shadow: 0 14px 34px rgba(16, 24, 40, .14);
    }

    .select2-container--default .select2-dropdown--below {
        margin-top: 4px;
    }

    .select2-container--default .select2-dropdown--above {
        margin-top: -4px;
    }

    .select2-container--default .select2-search--dropdown {
        padding: 10px;
        background: #fbfcfe;
        border-bottom: 1px solid #eef2f6;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field {
        height: 38px;
        padding: 0 12px;
        border: 1px solid #d9e0ea;
        border-radius: 6px;
        outline: 0;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #0f766e;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, .15);
    }

    .select2-container--default .select2-results > .select2-results__options {
        max-height: 260px;
        padding: 4px;
    }

    .select2-container--default .select2-results__option {
        padding: 9px 12px;
        border-radius: 6px;
        color: #344054;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected],
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        color: #0f766e;
        background: #e6f6f3;
    }

    .select2-container--default .select2-results__option[aria-selected="true"],
    .select2-container--default .select2-results__option--selected {
        color: #fff;
        background: #0f766e;
        font-weight: 700;
    }

    .select2-container--default .select2-results__message {
        color: #667085;
        text-align: center;
    }

    .client-form-actions {
        position: sticky;
        bottom: 12px;
        z-index: 5;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 14px 16px;
        box-shadow: 0 10px 26px rgba(16, 24, 40, .08);
    }

    .hijri-calendar-popover {
        position: absolute;
        top: calc(100% + 6px);
        right: 0;
        z-index: 1060;
        width: min(350px, 92vw);
        padding: 14px;
        border: 1px solid #d9e0ea;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 14px 34px rgba(16, 24, 40, .18);
    }

    .hijri-calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
        direction: rtl;
    }

    .hijri-calendar-weekday {
        padding: 7px 2px;
        color: #667085;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
    }

    .hijri-calendar-day {
        width: 100%;
        aspect-ratio: 1;
        padding: 0;
        border: 0;
        border-radius: 7px;
        color: #344054;
        background: transparent;
    }

    .hijri-calendar-day:hover {
        color: #0f766e;
        background: #e6f6f3;
    }

    .hijri-calendar-day.is-selected {
        color: #fff;
        background: #0f766e;
        font-weight: 700;
    }

    /* أيام خارج نطاق العمر المسموح */
    .hijri-calendar-day:disabled {
        color: #adb5bd;
        background: transparent;
        cursor: not-allowed;
    }

    @media (max-width: 767.98px) {
        .client-form-header,
        .client-form-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .client-form-header .btn,
        .client-form-actions .btn {
            width: 100%;
        }
    }
</style>
