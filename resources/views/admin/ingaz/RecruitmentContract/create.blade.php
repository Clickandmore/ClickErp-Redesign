@extends('admin.layout.indexs.index')
@section('page-title')
    {{ helperTrans('ingaz.add Recruitment contract') }}
@endsection

@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .contract-page-intro {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.25rem;
            border: 1px solid #dce6f2;
            border-radius: 24px;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 55%, #f9fcff 100%);
            box-shadow: 0 18px 40px rgba(28, 76, 135, 0.08);
        }

        .contract-page-intro__title {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            color: #183b63;
        }

        .contract-page-intro__text {
            margin: .35rem 0 0;
            color: #5f6f82;
        }

        .contract-page-intro__badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .7rem 1rem;
            border-radius: 999px;
            background: rgba(31, 122, 140, 0.12);
            color: #0f5e6d;
            font-weight: 700;
            white-space: nowrap;
        }

        .contract-page-tabs {
            gap: .75rem;
            border-bottom: 0;
        }

        .contract-page-tabs .nav-link {
            min-width: 180px;
            padding: 1rem 1.2rem;
            border: 1px solid #d8e3ef;
            border-radius: 18px;
            background: #fff;
            color: #49627f;
            font-weight: 700;
            transition: all .2s ease;
            box-shadow: 0 8px 24px rgba(31, 52, 74, 0.05);
        }

        .contract-page-tabs .nav-link:hover {
            transform: translateY(-1px);
            color: #1f5fa4;
            border-color: #b9d1eb;
        }

        .contract-page-tabs .nav-link.active {
            border-color: #1f6fb2;
            background: linear-gradient(135deg, #1f6fb2 0%, #2d8cbc 100%);
            color: #fff;
            box-shadow: 0 14px 30px rgba(31, 111, 178, 0.22);
        }

        .contract-page-content {
            padding: 1.25rem;
            border-radius: 28px;
            background: linear-gradient(180deg, #f7fbff 0%, #ffffff 100%);
            border: 1px solid #edf2f8;
        }

        .contract-page-content .card {
            border: 1px solid #e2eaf3;
            border-radius: 22px;
            box-shadow: 0 14px 34px rgba(17, 38, 63, 0.06);
            overflow: hidden;
        }

        .contract-page-content .card-header {
            border-bottom: 0;
            padding: 1rem 1.25rem;
        }

        .contract-page-content .card-body {
            padding: 1.35rem;
        }

        .contract-page-content .form-section {
            width: 100%;
            padding: 1.25rem;
            border: 1px solid #dde8f4;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 12px 26px rgba(27, 55, 88, 0.05);
        }

        .contract-page-content .section-header {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            margin-bottom: 1.15rem;
            padding-bottom: .9rem;
            border-bottom: 1px solid #e8eef6;
        }

        .contract-page-content .section-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, #1f6fb2 0%, #2d8cbc 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            box-shadow: 0 10px 18px rgba(31, 111, 178, 0.16);
        }

        .contract-page-content .section-title {
            margin: 0 0 .2rem;
            font-size: 1.05rem;
            font-weight: 800;
            color: #233d59;
        }

        .contract-page-content .section-subtitle {
            margin: 0;
            color: #728196;
            font-size: .88rem;
        }

        .contract-picker-row {
            padding: 1rem;
            margin: 0 0 1.25rem;
            border: 1px dashed #cfdceb;
            border-radius: 18px;
            background: #f8fbff;
        }

        .quick-create-client {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            border-radius: 14px;
            font-weight: 700;
        }

        .maid-mode-options {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }

        .maid-mode-options .btn {
            min-height: 48px;
            border-radius: 14px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: normal;
            line-height: 1.35;
            font-weight: 700;
        }

        .contract-header {
            padding: 1.4rem 1.5rem;
            background: linear-gradient(135deg, #1f6fb2 0%, #246d8f 100%);
            color: #fff;
        }

        .contract-field-hint {
            margin-top: .5rem;
            color: #6b7d90;
            font-size: .9rem;
        }

        .contract-city-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            align-items: center;
            margin-top: .75rem;
        }

        .contract-page-content .form-label {
            font-weight: 700;
            color: #36506b;
            margin-bottom: .45rem;
        }

        .contract-page-content .form-control,
        .contract-page-content .form-select {
            min-height: 46px;
            border-radius: 14px;
            border-color: #d5e0eb;
            box-shadow: none;
        }

        .contract-page-content textarea.form-control {
            min-height: 120px;
        }

        .contract-page-content .form-control:focus,
        .contract-page-content .form-select:focus {
            border-color: #77a8d8;
            box-shadow: 0 0 0 0.22rem rgba(52, 118, 181, 0.12);
        }

        .contract-page-content .select2-container--default .select2-selection--single {
            min-height: 46px;
            border-radius: 14px;
            border-color: #d5e0eb;
            display: flex;
            align-items: center;
        }

        .contract-page-content .select2-container .select2-selection__rendered {
            line-height: 42px;
            padding-right: 14px;
            padding-left: 28px;
        }

        .contract-page-content .select2-container .select2-selection__arrow {
            height: 44px;
        }

        .contract-page-content select.is-invalid + .select2 .select2-selection {
            border-color: #dc3545 !important;
        }

        .select2-container--default .select2-results__option {
            padding: .55rem .75rem;
            border-bottom: 1px solid #f0f4f8;
            transition: background-color .12s ease;
        }

        .select2-container--default .select2-results__option:last-child {
            border-bottom: 0;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #1f6fb2;
            color: #fff;
        }

        .select2-container--default .select2-results__options {
            max-height: 320px;
        }

        .select2-container--default .select2-dropdown {
            border-radius: 14px;
            border-color: #d5e0eb;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(31, 55, 90, 0.14);
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border-radius: 10px;
            border-color: #d5e0eb;
            padding: .5rem .75rem;
        }

        .ajax-result-row {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .ajax-result-row__icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #eaf3fb 0%, #d7e8f6 100%);
            color: #1f6fb2;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1rem;
        }

        .ajax-result-row__body {
            flex: 1;
            min-width: 0;
        }

        .ajax-result-row__title {
            font-weight: 700;
            color: #223547;
            font-size: .95rem;
            margin-bottom: .2rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ajax-result-row__meta {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem .85rem;
            font-size: .82rem;
            color: #64768a;
        }

        .ajax-result-row__meta span {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
        }

        .ajax-result-row__meta i {
            color: #8fa7bd;
            font-size: .75rem;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] .ajax-result-row__title,
        .select2-container--default .select2-results__option--highlighted[aria-selected] .ajax-result-row__meta,
        .select2-container--default .select2-results__option--highlighted[aria-selected] .ajax-result-row__meta i {
            color: #fff;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] .ajax-result-row__icon {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
        }

        .contract-submit-card .card-body {
            padding: 1rem;
        }

        .maid-list-notice {
            border: 1px solid #cfe4f7;
            border-radius: 16px;
            background: linear-gradient(135deg, #f4faff 0%, #eef6ff 100%);
            overflow: hidden;
        }

        .maid-list-notice__toggle {
            display: flex;
            align-items: center;
            gap: .65rem;
            width: 100%;
            padding: .85rem 1rem;
            border: 0;
            background: transparent;
            color: #17456f;
            font-weight: 700;
            text-align: start;
        }

        .maid-list-notice__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            flex: 0 0 32px;
            border-radius: 10px;
            background: rgba(31, 111, 178, 0.12);
            color: #1f6fb2;
        }

        .maid-list-notice__title {
            flex: 1 1 auto;
        }

        .maid-list-notice__more {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .82rem;
            font-weight: 600;
            color: #1f6fb2;
            white-space: nowrap;
        }

        .maid-list-notice__toggle[aria-expanded="true"] .maid-list-notice__more i {
            transform: rotate(180deg);
        }

        .maid-list-notice__more i {
            transition: transform .2s ease;
        }

        .maid-list-notice__body {
            padding: 0 1rem 1rem;
            color: #41576f;
            font-size: .88rem;
            border-top: 1px solid #ddecfa;
            padding-top: .85rem;
        }

        .maid-list-notice__rules,
        .maid-list-notice__steps {
            margin: 0 0 .85rem;
            padding-inline-start: 1.15rem;
        }

        .maid-list-notice__rules li,
        .maid-list-notice__steps li {
            margin-bottom: .3rem;
        }

        .maid-list-notice__subtitle {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-weight: 700;
            color: #9a6207;
        }

        @media (max-width: 575.98px) {
            .maid-list-notice__more span,
            .maid-list-notice__more {
                font-size: .78rem;
            }
        }

        @media (max-width: 991.98px) {
            .contract-page-intro {
                flex-direction: column;
                align-items: flex-start;
            }

            .contract-page-tabs .nav-link {
                min-width: auto;
                width: 100%;
            }

            .maid-mode-options {
                grid-template-columns: 1fr;
            }
        }

        .internal-company-toggle {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem 1.25rem;
            background: #fff;
            border: 1px solid #dbe5ef;
            border-inline-start: 4px solid #1f6fb2;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(31, 111, 178, 0.06);
            cursor: pointer;
            transition: background-color .2s, border-color .2s, box-shadow .2s;
        }

        .internal-company-toggle:hover {
            border-color: #b9d0e6;
            box-shadow: 0 6px 18px rgba(31, 111, 178, 0.1);
        }

        .internal-company-toggle.is-active {
            background: #eef6fd;
            border-color: #1f6fb2;
        }

        .internal-company-toggle__icon {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: rgba(31, 111, 178, 0.1);
            color: #1f6fb2;
            font-size: 1.2rem;
        }

        .internal-company-toggle__body {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            gap: .25rem;
            min-width: 0;
        }

        .internal-company-toggle__title {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
            font-weight: 700;
            color: #1d3557;
        }

        .internal-company-toggle__name {
            padding: .15rem .65rem;
            border-radius: 999px;
            background: #1f6fb2;
            color: #fff;
            font-size: .85rem;
            font-weight: 600;
        }

        .internal-company-toggle__hint {
            font-size: .85rem;
            color: #6c7a89;
        }

        .internal-company-toggle__switch {
            position: relative;
            flex: 0 0 auto;
            width: 48px;
            height: 26px;
        }

        .internal-company-toggle__switch input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .internal-company-toggle__slider {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            background: #cfd8e3;
            transition: background-color .2s;
        }

        .internal-company-toggle__slider::before {
            content: "";
            position: absolute;
            top: 3px;
            inset-inline-start: 3px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .2);
            transition: inset-inline-start .2s;
        }

        .internal-company-toggle__switch input:checked + .internal-company-toggle__slider {
            background: #1f6fb2;
        }

        .internal-company-toggle__switch input:checked + .internal-company-toggle__slider::before {
            inset-inline-start: 25px;
        }

        .internal-company-toggle__switch input:focus-visible + .internal-company-toggle__slider {
            box-shadow: 0 0 0 3px rgba(31, 111, 178, 0.3);
        }
    </style>
@endsection

@section('content')

    @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE))
        @php
            $plainTextValue = function ($value) {
                $decoded = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
                return trim(preg_replace('/\s+/u', ' ', $decoded));
            };
        @endphp

        @if ($errors->any())
            <div class="alert alert-danger" dir="rtl">
                <strong>يرجى مراجعة البيانات التالية:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="contract-page-intro">
            <div>
                <h3 class="contract-page-intro__title">إضافة عقد استقدام</h3>
                <p class="contract-page-intro__text">نفس منطق الإضافة الحالي مع تنظيم أوضح للتبويبات، وتحسين اختيار الطلب، والعميل، ومدينة التسليم.</p>
            </div>
            <div class="contract-page-intro__badge">
                <i class="fas fa-layer-group"></i>
                <span>تبويبان: عقد جديد أو طلب مسبق</span>
            </div>
        </div>

        <!-- التبويبات أعلى الصفحة -->
        <ul class="nav nav-tabs contract-page-tabs mb-4" id="contractTabs" role="tablist">

            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-new" data-bs-toggle="tab" data-bs-target="#tab-new-pane"
                    type="button" role="tab" aria-controls="tab-new-pane" aria-selected="false">
                    <span><i class="fas fa-user-plus me-1"></i>عقد جديد</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-order" data-bs-toggle="tab" data-bs-target="#tab-order-pane" type="button"
                    role="tab" aria-controls="tab-order-pane" aria-selected="true">
                    <span><i class="fas fa-list-check me-1"></i>طلب مسبق</span>

                </button>
            </li>
        </ul>


        <div class="tab-content contract-page-content" id="contractTabsContent">
            <!-- تبويب العقد الجديد بدون طلب مسبق -->
            <div class="tab-pane fade contract-tab-pane" id="tab-new-pane" role="tabpanel" aria-labelledby="tab-new">
                <form id="newContractForm" method="post" action="{{ route('RecruitmentContract.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="contract_type" value="new_contract">
                    <input type="hidden" name="client_type" value="new_client">
                    @php($internalCompany = \App\Models\CompanyProfile::current())
                    @if ($internalCompany)
                        <label class="internal-company-toggle" for="internal-company-contract">
                            <span class="internal-company-toggle__icon"><i class="fas fa-building"></i></span>
                            <span class="internal-company-toggle__body">
                                <span class="internal-company-toggle__title">
                                    عقد للشركة
                                    <span class="internal-company-toggle__name">{{ $internalCompany->name }}</span>
                                </span>
                                <span class="internal-company-toggle__hint">تُستخدم بيانات الشركة من إعداداتها دون إنشاء عميل.</span>
                            </span>
                            <span class="internal-company-toggle__switch">
                                <input type="checkbox" id="internal-company-contract">
                                <span class="internal-company-toggle__slider"></span>
                            </span>
                        </label>
                    @endif

                    <div class="card mb-4 contract-workspace-card">
                        <div class="contract-header">
                            <div class="text-center">
                                <h4 class="mb-2">
                                    <i class="fas fa-handshake me-2"></i>
                                    إنشاء عقد استقدام جديد
                                </h4>
                                <p class="mb-0 opacity-75">قم بتعبئة البيانات المطلوبة لإنشاء عقد استقدام بدون طلب مسبق</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 align-items-end contract-picker-row">
                                <div class="col-lg-9">
                                    <label for="new_existing_client_id" class="form-label">اختر عميل
                                        <small class="text-muted">(اختياري)</small></label>
                                    <select name="new_existing_client_id" id="new_existing_client_id"
                                        class="form-select ajax-client-select" style="width:100%"
                                        data-placeholder="ابحث بالاسم أو الجوال أو الهوية">
                                        <option value=""></option>
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <button type="button" id="use-direct-client-data"
                                        class="btn btn-outline-primary quick-create-client w-100"
                                        title="إدخال بيانات عميل جديد داخل نفس الشاشة"><i
                                            class="fas fa-user-plus"></i> عميل جديد من البيانات</button>
                                </div>
                            </div>

                            <!-- بيانات العميل والعامل/ة (تظهر بعد الاختيار) في التبويب الجديد -->
                            <div class="col-md-12">
                                <div class="form-section">
                                    <div class="section-header">
                                        <div class="section-icon">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <h5 class="section-title">معلومات العميل</h5>
                                            <p class="section-subtitle">البيانات الشخصية والمهنية</p>
                                        </div>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="row">
                                            <div class="col-12 mb-2">
                                                <label class="form-label d-block">{{ helperTrans('ingaz.client_type') }} <span class="text-danger">*</span></label>
                                                <div class="d-flex flex-wrap gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input js-account-client-type" type="radio"
                                                            name="account_client_type" id="new_account_client_type_person"
                                                            value="person" checked>
                                                        <label class="form-check-label" for="new_account_client_type_person">{{ helperTrans('ingaz.person') }}</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input js-account-client-type" type="radio"
                                                            name="account_client_type" id="new_account_client_type_company"
                                                            value="company">
                                                        <label class="form-check-label" for="new_account_client_type_company">{{ helperTrans('ingaz.company') }}</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">{{ helperTrans('ingaz.client_name') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="new_client_name" name="new_client_name"
                                                    class="form-control" data-validation="required">
                                                <div class="invalid-feedback" id="err_new_client_name"></div>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">{{ helperTrans('ingaz.client_phone') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="tel" id="new_client_phone" name="new_client_phone"
                                                    class="form-control" data-validation="required" dir="rtl">
                                                <div class="invalid-feedback" id="err_new_client_phone"></div>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label for="new_client_nationality_id"
                                                    class="form-label">{{ helperTrans('ingaz.nationality_id') }} <span
                                                        class="text-danger">*</span></label>
                                                <select id="new_client_nationality_id" name="client_nationality_id"
                                                    class="form-select form-control select2" data-validation="required">
                                                    <option value="">{{ helperTrans('ingaz.choose') }}</option>
                                                    @foreach ($countries as $country)
                                                        <option value="{{ $country->id }}"
                                                            {{ $country->code === 'SA' ? 'selected' : '' }}>
                                                            {{ $country->name_ar }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback" id="err_new_client_nationality_id"></div>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">{{ helperTrans('ingaz.email') }} <small
                                                        class="text-muted">(اختياري)</small></label>
                                                <input type="email" id="new_client_email" name="new_client_email"
                                                    class="form-control">
                                                <div class="invalid-feedback" id="err_new_client_email"></div>
                                            </div>
                                            <div class="col-md-6 mb-2 js-client-document-field">
                                                <label class="form-label js-client-document-label">{{ helperTrans('ingaz.national_num') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="new_client_national_num"
                                                    name="new_client_national_num" class="form-control"
                                                    data-validation="required">
                                                <div class="invalid-feedback" id="err_new_client_national_num"></div>
                                            </div>
                                            <div class="col-md-6 mb-2 js-client-company-field d-none">
                                                <label class="form-label">{{ helperTrans('ingaz.license_number') }} <small
                                                        class="text-muted">(اختياري)</small></label>
                                                <input type="text" id="new_client_license_number"
                                                    name="new_client_license_number" class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-2 js-client-birthdate-field js-client-person-field">
                                                <label class="form-label">{{ helperTrans('ingaz.birthdate') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="new_client_birthdate"
                                                    name="new_client_birthdate" class="form-control"
                                                    data-validation="required" placeholder="مثال: 1990-05-20 أو 1410/10/20">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">{{ helperTrans('ingaz.phone2') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="tel" id="new_client_phone2" name="new_client_phone2"
                                                    class="form-control" data-validation="required">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">{{ helperTrans('ingaz.address') }} <small
                                                        class="text-muted">(اختياري)</small></label>
                                                <input type="text" id="new_client_address" name="new_client_address"
                                                    class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">{{ helperTrans('ingaz.city of receive') }} <span
                                                        class="text-danger">*</span></label>
                                                <select id="new_client_city" name="new_client_city"
                                                    class="form-select select2 select2-city" data-validation="required"
                                                    data-placeholder="اختر مدينة تسليم العميل">
                                                    <option value="">{{ helperTrans('ingaz.choose') }}</option>
                                                    @foreach ($saudiCities ?? collect() as $city)
                                                        <option value="{{ $city->name_ar }}">{{ $city->name_ar }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="contract-city-actions">
                                                    <a href="{{ route('cities.create') }}" target="_blank"
                                                        class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-plus-circle ms-1"></i>إضافة مدينة سعودية
                                                    </a>
                                                    <small class="text-muted">ابحث باسم المدينة أو أضفها إذا لم تكن
                                                        موجودة.</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-2 js-client-person-field">
                                                <label class="form-label">{{ helperTrans('ingaz.beds number') }} <small
                                                        class="text-muted">(اختياري)</small></label>
                                                <input type="text" id="new_client_beds_num" name="new_client_beds_num"
                                                    class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-2 js-client-person-field">
                                                <label class="form-label">{{ helperTrans('ingaz.family_number') }} <small
                                                        class="text-muted">(اختياري)</small></label>
                                                <input type="text" id="new_client_family_num"
                                                    name="new_client_family_num" class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-2 js-client-person-field">
                                                <label class="form-label">{{ helperTrans('ingaz.children') }} <small
                                                        class="text-muted">(اختياري - تحت سن 12)</small></label>
                                                <input type="number" id="new_client_children" name="new_client_children"
                                                    class="form-control" max="50">
                                            </div>
                                            <div class="col-md-6 mb-2 js-client-person-field">
                                                <label class="form-label">{{ helperTrans('ingaz.job') }} <small
                                                        class="text-muted">(اختياري)</small></label>
                                                <input type="text" id="new_client_job" name="new_client_job"
                                                    class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-section">
                                    <div class="section-header">
                                        <div class="section-icon">
                                            <i class="fas fa-female"></i>
                                        </div>
                                        <div>
                                            <h5 class="section-title">{{ __('ingaz.worker_data') }}</h5>
                                            <p class="section-subtitle">{{ __('ingaz.personal_professional_data') }}</p>
                                        </div>
                                    </div>
                                    <div class="card-body p-2" id="new-maid-section-card">
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">{{ __('ingaz.worker_add_method') }} <span
                                                        class="text-danger">*</span></label>
                                                <div class="maid-mode-options" role="group" aria-label="{{ __('ingaz.worker_add_method') }}">
                                                    <input type="radio" class="btn-check" name="maid_selection_mode"
                                                        id="maid_mode_existing" value="existing" checked>
                                                    <label class="btn btn-outline-success" for="maid_mode_existing">{{ __('ingaz.existing_worker') }}</label>

                                                    <input type="radio" class="btn-check" name="maid_selection_mode"
                                                        id="maid_mode_manual" value="manual">
                                                    <label class="btn btn-outline-primary" for="maid_mode_manual">{{ __('ingaz.short_worker') }}</label>

                                                    <input type="radio" class="btn-check" name="maid_selection_mode"
                                                        id="maid_mode_postponed" value="postponed">
                                                    <label class="btn btn-outline-warning" for="maid_mode_postponed">{{ __('ingaz.postpone_worker_selection') }}</label>
                                                </div>
                                                <small class="text-muted d-block mt-2">{{ __('ingaz.worker_add_method_hint') }}</small>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 mb-3" id="new-existing-maid-picker">
                                                <div class="maid-list-notice mb-3">
                                                    <button class="maid-list-notice__toggle" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#maidListNoticeBody"
                                                        aria-expanded="false" aria-controls="maidListNoticeBody">
                                                        <span class="maid-list-notice__icon"><i class="fas fa-circle-info"></i></span>
                                                        <span class="maid-list-notice__title">{{ __('ingaz.worker_list_notice_title') }}</span>
                                                        <span class="maid-list-notice__more">{{ __('ingaz.worker_list_notice_toggle') }}
                                                            <i class="fas fa-chevron-down"></i></span>
                                                    </button>
                                                    <div class="collapse" id="maidListNoticeBody">
                                                        <div class="maid-list-notice__body">
                                                            <p class="mb-2">{{ __('ingaz.worker_list_notice_intro') }}</p>
                                                            <ul class="maid-list-notice__rules">
                                                                <li>{{ __('ingaz.worker_list_rule_status') }}</li>
                                                                <li>{{ __('ingaz.worker_list_rule_visible') }}</li>
                                                                <li>{{ __('ingaz.worker_list_rule_available_at') }}</li>
                                                                <li>{{ __('ingaz.worker_list_rule_no_contract') }}</li>
                                                                <li>{{ __('ingaz.worker_list_rule_no_order') }}</li>
                                                                <li>{{ __('ingaz.worker_list_rule_named') }}</li>
                                                            </ul>
                                                            <p class="maid-list-notice__subtitle mb-2">
                                                                <i class="fas fa-triangle-exclamation"></i>
                                                                {{ __('ingaz.worker_list_notice_missing_title') }}
                                                            </p>
                                                            <ol class="maid-list-notice__steps">
                                                                <li>{{ __('ingaz.worker_list_missing_step_one') }}</li>
                                                                <li>{{ __('ingaz.worker_list_missing_step_two') }}</li>
                                                                <li>{{ __('ingaz.worker_list_missing_step_three') }}</li>
                                                            </ol>
                                                            @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW))
                                                                <a href="{{ route('maids.index') }}" target="_blank"
                                                                    rel="noopener"
                                                                    class="btn btn-sm btn-outline-primary mt-1">
                                                                    <i class="fas fa-up-right-from-square me-1"></i>{{ __('ingaz.worker_list_open_maids_page') }}
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <label for="new_new_maid_id" class="form-label">{{ __('ingaz.select_worker') }}</label>
                                                <select name="new_new_maid_id" id="new_new_maid_id"
                                                    class="form-select ajax-maid-select" style="width:100%"
                                                    data-placeholder="{{ __('ingaz.worker_search_placeholder') }}">
                                                    <option value=""></option>
                                                </select>
                                            </div>
                                        </div>

                                        <div id="existing-maid-summary" class="row">
                                            <div class="col-6 mb-2">
                                                <label class="form-label">{{ __('ingaz.name') }}</label>
                                                <input type="text" id="new_maid_name" name="new_maid_name"
                                                    class="form-control" readonly>
                                                <div class="invalid-feedback" id="err_new_maid_name"></div>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <label class="form-label">{{ __('ingaz.passport_number') }}</label>
                                                <input type="text" id="new_maid_passport" name="new_maid_passport"
                                                    class="form-control" readonly>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <label class="form-label">{{ __('ingaz.religion') }}</label>
                                                <input type="text" id="new_maid_religion" name="new_maid_religion"
                                                    class="form-control" readonly>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <label class="form-label">{{ __('ingaz.profession') }}</label>
                                                <input type="text" id="new_maid_job" name="new_maid_job"
                                                    class="form-control" readonly>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <label class="form-label">{{ __('ingaz.nationality') }}</label>
                                                <input type="text" id="new_maid_nationality"
                                                    name="new_maid_nationality" class="form-control" readonly>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <label class="form-label">{{ __('ingaz.salary') }}</label>
                                                <input type="text" id="new_maid_salary" name="new_maid_salary"
                                                    class="form-control" readonly>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <label class="form-label">{{ __('ingaz.experience_years') }}</label>
                                                <input type="text" id="new_maid_experience_years"
                                                    name="new_maid_experience_years" class="form-control" readonly>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <label class="form-label">{{ __('ingaz.age') }}</label>
                                                <input type="text" id="new_maid_age" name="new_maid_age"
                                                    class="form-control" readonly>
                                            </div>
                                        </div>

                                        <div id="manual-maid-section-card" class="row g-3" style="display:none;">
                                            <div class="col-md-6">
                                                <label for="manual_maid_name" class="form-label">{{ __('ingaz.short_worker_name') }}
                                                    <span class="text-danger">*</span></label>
                                                <input type="text" id="manual_maid_name" name="manual_maid_name"
                                                    class="form-control" placeholder="{{ __('ingaz.short_worker_name_placeholder') }}">
                                                <div class="invalid-feedback" id="err_manual_maid_name"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="manual_maid_gender" class="form-label">{{ __('ingaz.gender') }}
                                                    <span class="text-danger">*</span></label>
                                                <select id="manual_maid_gender" name="manual_maid_gender"
                                                    class="form-select">
                                                    <option value="female" selected>{{ __('ingaz.female') }}</option>
                                                    <option value="male">{{ __('ingaz.male') }}</option>
                                                </select>
                                                <div class="invalid-feedback" id="err_manual_maid_gender"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="manual_maid_passport_num" class="form-label">{{ __('ingaz.passport_number') }}
                                                    <span class="text-danger">*</span></label>
                                                <input type="text" id="manual_maid_passport_num"
                                                    name="manual_maid_passport_num" class="form-control"
                                                    placeholder="{{ __('ingaz.passport_number') }}">
                                                <div class="invalid-feedback" id="err_manual_maid_passport_num"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="manual_maid_job_title_id" class="form-label">{{ __('ingaz.profession') }}
                                                    <span class="text-danger">*</span></label>
                                                <select id="manual_maid_job_title_id" name="manual_maid_job_title_id"
                                                    class="form-select select2" data-placeholder="{{ __('ingaz.select_profession') }}">
                                                    <option value="">{{ __('ingaz.select_profession_placeholder') }}</option>
                                                    @foreach ($maidJobTitles as $jobTitle)
                                                        <option value="{{ $jobTitle->id }}">{{ $jobTitle->title }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback" id="err_manual_maid_job_title_id"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="manual_maid_nationality_id" class="form-label">{{ __('ingaz.nationality') }}
                                                    <span class="text-danger">*</span></label>
                                                <select id="manual_maid_nationality_id" name="manual_maid_nationality_id"
                                                    class="form-select select2" data-placeholder="{{ __('ingaz.select_nationality') }}">
                                                    <option value="">{{ __('ingaz.select_nationality_placeholder') }}</option>
                                                    @foreach ($maidNationalities as $nationality)
                                                        <option value="{{ $nationality->id }}">{{ $nationality->display_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback" id="err_manual_maid_nationality_id"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="manual_maid_office_id" class="form-label">{{ __('ingaz.external_office') }}
                                                    <span class="text-danger">*</span></label>
                                                <select id="manual_maid_office_id" name="manual_maid_office_id"
                                                    class="form-select select2"
                                                    data-placeholder="{{ __('ingaz.select_external_office') }}">
                                                    <option value="">{{ __('ingaz.select_external_office_placeholder') }}</option>
                                                    @foreach ($recruitmentOffices as $office)
                                                        <option value="{{ $office->id }}">{{ $office->title }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback" id="err_manual_maid_office_id"></div>
                                            </div>
                                            <div class="col-12">
                                                <label for="manual_maid_attachment" class="form-label">{{ __('ingaz.worker_attachment') }}
                                                    <small class="text-muted">({{ __('ingaz.optional') }})</small></label>
                                                <input type="file" id="manual_maid_attachment"
                                                    name="manual_maid_attachment" class="form-control"
                                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                                <div class="invalid-feedback" id="err_manual_maid_attachment"></div>
                                                <small class="text-muted d-block mt-1">{{ __('ingaz.worker_attachment_hint') }}</small>
                                            </div>
                                            <div class="col-12">
                                                <div class="alert alert-info mb-0">
                                                    <i class="fas fa-circle-info me-2"></i>
                                                    {{ __('ingaz.short_worker_creation_hint') }}
                                                </div>
                                            </div>
                                        </div>

                                        <div id="new-no-maid-alert" style="display:none;">
                                            <div class="alert alert-warning mt-2">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                {{ __('ingaz.no_worker_selected_hint') }}
                                            </div>

                                            <div class="row mt-3">
                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_nationality" class="form-label">الجنسية المطلوبة
                                                        للعامل/ة
                                                        <span class="text-danger">*</span></label>
                                                    <select name="required_maid_nationality" id="required_maid_nationality"
                                                        class="form-select" data-validation="required">
                                                        <option value="">اختر الجنسية المطلوبة...</option>
                                                        @foreach ($maidNationalities as $nationality)
                                                            <option value="{{ $nationality->id }}">{{ $nationality->display_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="invalid-feedback" id="err_required_maid_nationality"></div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_religion" class="form-label">الدين المطلوب
                                                        للعامل/ة <span class="text-danger">*</span></label>
                                                    <select name="required_maid_religion" id="required_maid_religion"
                                                        class="form-select" data-validation="required">
                                                        <option value="">اختر الدين المطلوب...</option>
                                                        @foreach ($maidReligions as $religion)
                                                            <option value="{{ $religion->id }}">{{ $religion->title }}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="invalid-feedback" id="err_required_maid_religion"></div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_age_min" class="form-label">الحد الأدنى للعمر
                                                        <span class="text-danger">*</span></label>
                                                    <input type="number" name="required_maid_age_min"
                                                        id="required_maid_age_min" class="form-control" min="18"
                                                        max="65" placeholder="مثال: 25" data-validation="required">
                                                    <div class="invalid-feedback" id="err_required_maid_age_min"></div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_age_max" class="form-label">الحد الأقصى للعمر
                                                        <span class="text-danger">*</span></label>
                                                    <input type="number" name="required_maid_age_max"
                                                        id="required_maid_age_max" class="form-control" min="18"
                                                        max="65" placeholder="مثال: 45" data-validation="required">
                                                    <div class="invalid-feedback" id="err_required_maid_age_max"></div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_experience_min" class="form-label">الحد الأدنى
                                                        للخبرة
                                                        (سنوات) <span class="text-danger">*</span></label>
                                                    <input type="number" name="required_maid_experience_min"
                                                        id="required_maid_experience_min" class="form-control" min="0"
                                                        max="20" placeholder="مثال: 2" data-validation="required">
                                                    <div class="invalid-feedback" id="err_required_maid_experience_min">
                                                    </div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_job_title" class="form-label">المهنة المطلوبة
                                                        <small class="text-muted">(اختياري)</small></label>
                                                    <select name="required_maid_job_title" id="required_maid_job_title"
                                                        class="form-select">
                                                        <option value="">اختر المهنة المطلوبة...</option>
                                                        @foreach ($maidJobTitles as $jobTitle)
                                                            <option value="{{ $jobTitle->id }}">{{ $jobTitle->title }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="invalid-feedback" id="err_required_maid_job_title"></div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_experience_max" class="form-label">الحد الأقصى
                                                        للخبرة
                                                        (سنوات)</label>
                                                    <input type="number" name="required_maid_experience_max"
                                                        id="required_maid_experience_max" class="form-control" min="0"
                                                        max="20" placeholder="مثال: 10">
                                                    <div class="invalid-feedback" id="err_required_maid_experience_max">
                                                    </div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_salary_min" class="form-label">الحد الأدنى
                                                        للراتب
                                                        (ريال)</label>
                                                    <input type="number" name="required_maid_salary_min"
                                                        id="required_maid_salary_min" class="form-control" min="0"
                                                        placeholder="مثال: 1500">
                                                    <div class="invalid-feedback" id="err_required_maid_salary_min"></div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_salary_max" class="form-label">الحد الأقصى
                                                        للراتب
                                                        (ريال)</label>
                                                    <input type="number" name="required_maid_salary_max"
                                                        id="required_maid_salary_max" class="form-control" min="0"
                                                        placeholder="مثال: 3000">
                                                    <div class="invalid-feedback" id="err_required_maid_salary_max"></div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_education" class="form-label">المؤهل التعليمي
                                                        المطلوب</label>
                                                    <select name="required_maid_education" id="required_maid_education"
                                                        class="form-select">
                                                        <option value="">اختر المؤهل التعليمي...</option>
                                                        <option value="primary">ابتدائي</option>
                                                        <option value="middle">متوسط</option>
                                                        <option value="secondary">ثانوي</option>
                                                        <option value="university">جامعي</option>
                                                        <option value="any">أي مؤهل</option>
                                                    </select>
                                                    <div class="invalid-feedback" id="err_required_maid_education"></div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_marital_status" class="form-label">الحالة
                                                        الاجتماعية
                                                        المطلوبة</label>
                                                    <select name="required_maid_marital_status"
                                                        id="required_maid_marital_status" class="form-select">
                                                        <option value="">اختر الحالة الاجتماعية...</option>
                                                        <option value="single">عزباء</option>
                                                        <option value="married">متزوجة</option>
                                                        <option value="divorced">مطلقة</option>
                                                        <option value="widowed">أرملة</option>
                                                        <option value="any">أي حالة</option>
                                                    </select>
                                                    <div class="invalid-feedback" id="err_required_maid_marital_status">
                                                    </div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_languages" class="form-label">اللغات
                                                        المطلوبة</label>
                                                    <select name="required_maid_languages[]" id="required_maid_languages"
                                                        class="form-select" multiple>
                                                        <option value="arabic">العربية</option>
                                                        <option value="english">الإنجليزية</option>
                                                        <option value="urdu">الأردية</option>
                                                        <option value="filipino">الفلبينية</option>
                                                        <option value="indonesian">الإندونيسية</option>
                                                        <option value="bengali">البنغالية</option>
                                                        <option value="sri_lankan">السريلانكية</option>
                                                        <option value="nepali">النيبالية</option>
                                                    </select>
                                                    <small class="form-text text-muted">اضغط Ctrl (أو Cmd) لاختيار أكثر من
                                                        لغة</small>
                                                    <div class="invalid-feedback" id="err_required_maid_languages"></div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_skills" class="form-label">المهارات
                                                        المطلوبة</label>
                                                    <textarea name="required_maid_skills" id="required_maid_skills" class="form-control" rows="3"
                                                        placeholder="مثال: طبخ، تنظيف، رعاية أطفال، غسيل ملابس..."></textarea>
                                                    <div class="invalid-feedback" id="err_required_maid_skills"></div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="required_maid_notes" class="form-label">ملاحظات إضافية</label>
                                                    <textarea name="required_maid_notes" id="required_maid_notes" class="form-control" rows="3"
                                                        placeholder="أي متطلبات خاصة أو ملاحظات إضافية..."></textarea>
                                                    <div class="invalid-feedback" id="err_required_maid_notes"></div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        <strong>تنبيه:</strong> الحقول المطلوبة (الجنسية، الدين، العمر،
                                                        الخبرة) إلزامية
                                                        عند اختيار "بدون عامل/ة". هذه المتطلبات ضرورية للعثور على العامل/ة
                                                        المناسبة
                                                        لاحقاً.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="new-no-maid-message" class="alert alert-warning mt-3"
                                            style="display:none;">
                                            <i class="fas fa-user-slash me-1"></i> لا يوجد عامل/ة حالياً.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- بيانات العقد -->
                            <div class="col-12 mt-4">
                                <div class="form-section">
                                    <div class="section-header">
                                        <div class="section-icon">
                                            <i class="fas fa-file-contract"></i>
                                        </div>
                                        <div>
                                            <h5 class="section-title">بيانات العقد</h5>
                                            <p class="section-subtitle">البيانات الفنية والزمنية</p>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @include('admin.ingaz.RecruitmentContract.contract-section')
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mb-2">
                                <label class="form-label">اضافة ملاحظة (اختياري) : </label>
                                <textarea id="new_contract_note" name="contract_note" class="form-control" cols="30"
                                    rows="6"></textarea>
                            </div>
                        </div>
                    </div>
                    <!-- زر الحفظ في تبويب عقد جديد بدون طلب مسبق -->
                    <div class="col-12 mt-3">
                        <div class="card contract-submit-card">
                            <div class="card-body text-center">
                                <button class="btn btn-primary btn-lg px-3" type="submit" id="newContractSubmitBtn"
                                    onclick="console.log('Submit button clicked')">
                                    <i class="fa-solid fa-floppy-disk me-2"></i>
                                    {{ __('buttons.save') }}
                                </button>

                                @if (\App\Support\InvoiceAuthorization::canCreate())
                                <button class="btn btn-success btn-lg px-3" id="save-and-invoice" type="button">
                                    <i class="fa-solid fa-file-invoice-dollar me-2"></i>
                                    {{ __('ingaz.save_and_issue_an_invoice') }}
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <!-- تبويب الطلب المسبق (الحالي) -->
            <div class="tab-pane fade show active contract-tab-pane" id="tab-order-pane" role="tabpanel"
                aria-labelledby="tab-order">
                <form id="maidForm" class="row g-3 contractForm" method="post"
                    action="{{ route('RecruitmentContract.store') }}">
                    @csrf
                    <input type="hidden" name="client_type" value="ex_client" id="client_type">
                    <input type="hidden" name="maid_id" id="maid_id">

                    <div class="col-12 mb-4">
                        <div class="card contract-workspace-card">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0">معلومات العميل</h4>
                            </div>
                            <div class="card-body">
                                {{-- <div class="col-12 mb-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="client_type" id="ex_client" value="ex_client" checked>
                            <label class="form-check-label" for="ex_client">{{helperTrans("ingaz.ex_client")}}</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="client_type" id="new_client" value="new_client">
                            <label class="form-check-label" for="new_client">{{helperTrans("ingaz.new_client")}}</label>
                        </div>
                        </div> --}}

                                <div id="order-selection" class="col-12 mb-4">
                                    <label class="form-label">{{ helperTrans('ingaz.select_order') }}</label>
                                    <select name="order_id" id="order_id" class="form-select form-control select2"
                                        data-placeholder="اختر طلب استقدام بدون عقد"
                                        data-validation="required">
                                        <option disabled selected>{{ helperTrans('ingaz.choose') }}</option>
                                        @foreach ($orders as $order)
                                            <option value="{{ $order->id }}"
                                                data-client-name="{{ $plainTextValue($order->user->name ?? '') }}"
                                                data-client-phone="{{ $plainTextValue($order->user->phone ?? '') }}"
                                                data-client-email="{{ $plainTextValue($order->user->email ?? '') }}"
                                                data-client-national-num="{{ $plainTextValue($order->user?->documentNumber() ?? '') }}"
                                                data-account-client-type="{{ $order->user?->client_type ?? 'person' }}"
                                                data-client-document-label="{{ $plainTextValue($order->user?->documentNumberLabel() ?? helperTrans('ingaz.national_num')) }}"
                                                data-client-tax-number="{{ $plainTextValue($order->user?->tax_number ?? '') }}"
                                                data-client-license-number="{{ $plainTextValue($order->user?->license_number ?? '') }}"
                                                data-client-birthdate="{{ $order->user->birthdate ?? '' }}"
                                                data-client-phone2="{{ $plainTextValue($order->user->phone2 ?? '') }}"
                                                data-client-address="{{ $plainTextValue($order->user->address ?? '') }}"
                                                data-client-city="{{ $plainTextValue($order->user->city ?? '') }}"
                                                data-client-beds-num="{{ $order->user->rooms_num ?? $order->user->beds_num ?? '' }}"
                                                data-client-family-num="{{ $order->user->family_num ?? '' }}"
                                                data-client-children="{{ $order->user->children ?? '' }}"
                                                data-client-job="{{ $plainTextValue($order->user->job ?? '') }}"
                                                data-maid-name="{{ $plainTextValue($order->maid->name ?? '') }}"
                                                data-maid-passport="{{ $plainTextValue($order->maid->passport_num ?? '') }}"
                                                data-client-nationality="{{ $order->user->nationality_id ?? '' }}"
                                                data-maid-id="{{ $order->maid->id ?? '' }}"
                                                data-maid-nationality="{{ $order->maid->nationality_id ?? '' }}"
                                                data-maid-religion="{{ $order->maid->religion_id ?? '' }}"
                                                data-maid-job=""
                                                data-maid-salary="{{ $order->maid->salary ?? '' }}"
                                                data-maid-experience="{{ $order->maid->experience_years ?? '' }}"
                                                data-maid-age="{{ $order->maid->age ?? '' }}"
                                                @if (isset($orderId) && $orderId == $order->id) selected @endif>
                                                {{ $plainTextValue($order->user->name ?? '') }} -
                                                {{ $plainTextValue($order->user->phone ?? '') }} -
                                                {{ $plainTextValue($order->maid->name ?? '') }}
                                                ({{ $plainTextValue($order->maid->passport_num ?? '') }}) -
                                                {{ $plainTextValue($order->jobType->title ?? '') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="contract-field-hint">تظهر هنا فقط طلبات الاستقدام بدون عقد في حالة
                                        <strong>تحت الإجراء</strong> أو الحالات المنتهية التي تحتاج إضافة عقد.
                                    </div>
                                </div>

                                @include('admin.ingaz.RecruitmentContract.client-section')
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-4">
                        <div class="card contract-workspace-card">
                            <div class="card-header bg-success text-white">
                                <h4 class="mb-0">معلومات الخادمة</h4>
                            </div>
                            <div class="card-body">
                                @include('admin.ingaz.RecruitmentContract.maid-section')
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-4">
                        <div class="card contract-workspace-card">
                            <div class="card-header bg-info text-white">
                                <h4 class="mb-0">معلومات العقد</h4>
                            </div>
                            <div class="card-body">
                                @include('admin.ingaz.RecruitmentContract.contract-section')
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mb-4">
                        <div class="card contract-workspace-card">
                            <div class="card-header bg-info text-white">
                                <h4 class="mb-0">ملاحظات على العقد</h4>
                            </div>
                            <div class="card-body">
                                <div class="col-12 mb-2">
                                    <label class="form-label">اضافة ملاحظة (اختياري) : </label>
                                    <textarea id="contract_note" name="contract_note" class="form-control" id="" cols="30"
                                        rows="6"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>



                    <div class="col-12">
                        <div class="card contract-submit-card">
                            <div class="card-body text-center">
                                <button class="btn btn-primary btn-lg px-5" type="submit"
                                    id="orderContractSubmitBtn">
                                    <i class="fa-solid fa-floppy-disk me-2"></i>
                                    {{ __('buttons.save') }}
                                </button>

                                @if (\App\Support\InvoiceAuthorization::canCreate())
                                <button class="btn btn-success btn-lg px-5" id="order-save-and-invoice"
                                    type="button">
                                    <i class="fa-solid fa-file-invoice-dollar me-2"></i>
                                    {{ __('ingaz.save_and_issue_an_invoice') }}
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    @else
        @include('components.no_permissions')
    @endif

@endsection

@section('js')
    <script>
        var indexRoute = "{{ route('RecruitmentContract.index') }}";
        var createContractRoute = "{{ route('RecruitmentContract.create') }}";
        var currentContractsRoute = "{{ route('RecruitmentContract.index', ['status' => 'current']) }}";
        var searchClientsRoute = "{{ route('RecruitmentContract.searchClients') }}";
        var availableMaidsRoute = "{{ route('RecruitmentContract.availableMaids') }}";
        var passportUniqueRoute = "{{ route('check.passport.unique') }}";
        var manualPassportCheckTimeout;
        var isManualPassportDuplicate = false;
        var selectedOrder = @json($selectedOrder);
        var hasOrderId = {{ isset($orderId) ? 'true' : 'false' }};
        var select2Direction = @json(app()->getLocale() === 'ar' ? 'rtl' : 'ltr');
        var recruitmentContractTranslations = {
            workerRequiredOrChangeMethod: @json(__('ingaz.worker_required_or_change_method')),
            shortWorkerNameRequired: @json(__('ingaz.short_worker_name_required')),
            genderRequired: @json(__('ingaz.gender_required')),
            passportRequired: @json(__('ingaz.passport_required')),
            passportAlreadyExists: @json(__('ingaz.passport_already_exists')),
            professionRequired: @json(__('ingaz.profession_required')),
            nationalityRequired: @json(__('ingaz.nationality_required')),
            externalOfficeRequired: @json(__('ingaz.external_office_required')),
            errorTitle: @json(__('ingaz.error_title')),
            ok: @json(__('ingaz.ok')),
            selectWorkerRequired: @json(__('ingaz.select_worker_required'))
        };
    </script>

    <script>
        function sanitizeTextValue(value) {
            if (value === undefined || value === null) {
                return '';
            }

            var temp = document.createElement('div');
            temp.innerHTML = String(value);
            return (temp.textContent || temp.innerText || '').replace(/\s+/g, ' ').trim();
        }

        function sanitizePhoneValue(value) {
            if (value === undefined || value === null) {
                return '';
            }

            var rawValue = String(value);
            var telMatch = rawValue.match(/href\s*=\s*["']?tel:([^"'>\s]+)/i);
            if (telMatch && telMatch[1]) {
                return telMatch[1].trim();
            }

            var sanitizedValue = sanitizeTextValue(rawValue);
            var compactPhone = sanitizedValue.replace(/[^\d+]/g, '');
            return compactPhone || sanitizedValue;
        }

        function setSearchableSelectValue(selector, value) {
            var $select = $(selector);
            var cleanedValue = sanitizeTextValue(value);

            if (!$select.length) {
                return;
            }

            if (!cleanedValue) {
                $select.val('').trigger('change');
                return;
            }

            var optionExists = $select.find('option').filter(function() {
                return $(this).val() === cleanedValue;
            }).length > 0;

            if (!optionExists) {
                $select.append(new Option(cleanedValue, cleanedValue, false, false));
            }

            $select.val(cleanedValue).trigger('change');
        }

        function getTrimmedInputValue(selector) {
            var value = $(selector).val();

            if (typeof value !== 'string') {
                return '';
            }

            return value.trim();
        }

        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
        }

        function select2SelectedData(selector) {
            if (!$.fn.select2 || !$(selector).data('select2')) {
                return {};
            }

            var data = $(selector).select2('data');
            return data && data.length ? data[0] : {};
        }

        function destroySelect2IfInitialized($element) {
            if (!$.fn.select2 || !$element.length || !$element.data('select2')) {
                return;
            }

            $element.select2('destroy');
        }

        function refreshSelect2IfInitialized(selector) {
            if (!$.fn.select2) {
                return;
            }

            var $element = $(selector);
            if (!$element.length || !$element.data('select2')) {
                return;
            }

            $element.trigger('change.select2');
        }

        function optionOrAjaxData($option, ajaxData, key, attrName) {
            if (ajaxData && ajaxData[key] !== undefined && ajaxData[key] !== null) {
                return ajaxData[key];
            }

            return $option.data(attrName || key);
        }

        function isCompanyClientType(value) {
            return String(value || '').toLowerCase() === 'company';
        }

        function selectedClientDocumentNumber(clientData) {
            if (!clientData) {
                return '';
            }

            return isCompanyClientType(clientData.client_type)
                ? (clientData.tax_number || clientData.document_number || clientData.national_num || '')
                : (clientData.document_number || clientData.national_num || '');
        }

        function applyRecruitmentClientDocumentMode(options) {
            var isCompany = isCompanyClientType(options.clientType);
            var label = options.documentLabel || (isCompany ? @json(helperTrans('ingaz.tax_number')) : @json(helperTrans('ingaz.national_num')));
            var $documentInput = $(options.documentInput);
            var $birthdateInput = $(options.birthdateInput);
            var $documentWrapper = $documentInput.closest('.js-client-document-field, .col-md-6, .col-lg-6');
            var $birthdateWrapper = $birthdateInput.closest('.js-client-birthdate-field, .col-md-6, .col-lg-6');
            var $personFieldWrappers = options.personFields
                ? $(options.personFields).map(function() {
                    return $(this).closest('.js-client-person-field, .col-md-6, .col-lg-6')[0];
                })
                : $birthdateWrapper;
            var $companyFieldWrappers = options.companyFields
                ? $(options.companyFields).map(function() {
                    return $(this).closest('.js-client-company-field, .col-md-6, .col-lg-6')[0];
                })
                : $();
            var $accountTypeInputs = options.accountTypeInputs
                ? $(options.accountTypeInputs)
                : $documentWrapper.closest('form').find('input[name="account_client_type"]');

            $documentWrapper.find('label').first().html(label + ' <span class="text-danger">*</span>');
            $personFieldWrappers.toggleClass('d-none', isCompany);
            $personFieldWrappers.find('input, select, textarea').prop('disabled', isCompany);
            $companyFieldWrappers.toggleClass('d-none', !isCompany);
            $companyFieldWrappers.find('input, select, textarea').prop('disabled', !isCompany);
            $accountTypeInputs.filter('[value="' + (isCompany ? 'company' : 'person') + '"]').prop('checked', true);

            if (isCompany) {
                $personFieldWrappers.find('input, select, textarea').val('').removeClass('is-invalid');
                $personFieldWrappers.find('.invalid-feedback').not('#err_new_client_national_num').remove();
            } else {
                $companyFieldWrappers.find('input, select, textarea').val('').removeClass('is-invalid');
            }
        }

        function getNewMaidMode() {
            return $('input[name="maid_selection_mode"]:checked').val() || 'existing';
        }

        function clearContractValidationErrors(formSelector) {
            var $form = $(formSelector);
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.ajax-validation-feedback').remove();
        }

        function contractFieldSelector(field, formSelector) {
            var fieldMap = {
                client_id: '#new_existing_client_id',
                new_existing_client_id: '#new_existing_client_id',
                maid_id: '#new_new_maid_id',
                new_new_maid_id: '#new_new_maid_id',
                client_name: '#new_client_name',
                client_phone: '#new_client_phone',
                client_email: '#new_client_email',
                order_id: '#order_id',
                marketer_id: '#marketer_id'
            };

            if (formSelector === '#maidForm') {
                fieldMap.client_name = '#client_name';
                fieldMap.client_phone = '#client_phone';
                fieldMap.client_email = '#client_email';
                fieldMap.maid_id = '#maid_id';
            }

            if (fieldMap[field]) {
                return formSelector + ' ' + fieldMap[field];
            }

            return formSelector + ' [name="' + field + '"]';
        }

        function contractRequestErrorMessage(xhr) {
            if (xhr.status === 0) {
                return 'تعذر الاتصال بالخادم. تحقق من اتصال الإنترنت ومن وجود العقد في القائمة قبل إعادة الحفظ.';
            }
            if (xhr.status === 419 || xhr.status === 401) {
                return 'انتهت جلسة الدخول. يرجى تسجيل الدخول مجددًا ثم إعادة المحاولة.';
            }
            if (xhr.status === 403) {
                return 'ليس لديك صلاحية تنفيذ هذه العملية.';
            }
            if (xhr.status === 413) {
                return 'حجم المرفقات أكبر من المسموح به على الخادم. يرجى تقليل حجم الملفات.';
            }
            if (xhr.status === 429) {
                return 'تم إرسال طلبات كثيرة خلال وقت قصير. يرجى الانتظار ثم المحاولة.';
            }
            if (xhr.responseJSON && xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }
            return 'تعذر إتمام الطلب بسبب خطأ في الخادم (HTTP ' + xhr.status + '). يرجى إبلاغ مسؤول النظام والتحقق من وجود العقد قبل إعادة الحفظ.';
        }

        function showContractValidationErrors(errors, formSelector) {
            clearContractValidationErrors(formSelector);

            var messages = [];
            var firstInvalidField = null;

            $.each(errors || {}, function(field, fieldMessages) {
                var message = $.isArray(fieldMessages) ? fieldMessages[0] : fieldMessages;
                var $field = $(contractFieldSelector(field, formSelector)).first();

                messages.push(message);

                if ($field.length) {
                    $field.addClass('is-invalid');

                    var $feedback = $('<div class="invalid-feedback d-block ajax-validation-feedback"></div>')
                        .text(message);

                    if ($field.hasClass('select2-hidden-accessible')) {
                        $field.next('.select2-container').after($feedback);
                    } else {
                        $field.after($feedback);
                    }

                    if (!firstInvalidField) {
                        firstInvalidField = $field;
                    }
                }
            });

            if (firstInvalidField && firstInvalidField.length) {
                $('html, body').animate({
                    scrollTop: firstInvalidField.offset().top - 120
                }, 400);
            }

            if (messages.length) {
                Swal.fire({
                    icon: 'error',
                    title: 'يرجى مراجعة البيانات',
                    html: '<div class="text-start" dir="rtl">' + messages.map(function(message) {
                        return '<div class="mb-1">- ' + escapeHtml(message) + '</div>';
                    }).join('') + '</div>',
                    confirmButtonText: 'حسناً'
                });
            }
        }

        function handleContractCreated(response, redirectToInvoice, invoiceCreateUrl) {
            if (redirectToInvoice && response.contract && response.contract.id) {
                window.location.href = invoiceCreateUrl + '?contract_id=' + response.contract.id;
                return;
            }

            if (response.next_action === 'select_maid' && response.next_url) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم إنشاء العقد بنجاح',
                    text: response.message || 'تم إنشاء العقد بنجاح',
                    showDenyButton: true,
                    confirmButtonText: 'اختيار العامل/ة الآن',
                    denyButtonText: 'العقود بانتظار اختيار العامل/ة',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = response.next_url;
                    } else {
                        window.location.href = indexRoute + '?status=pending_maid_selection';
                    }
                });
                return;
            }

            Swal.fire({
                icon: 'success',
                title: 'تم إنشاء العقد بنجاح',
                text: response.message || 'تم إنشاء العقد بنجاح',
                showDenyButton: true,
                confirmButtonText: 'إضافة عقد آخر',
                denyButtonText: 'العقود السارية',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = createContractRoute;
                } else {
                    window.location.href = currentContractsRoute;
                }
            });
        }
    </script>

    <script>
        $(document).ready(function() {
            // تهيئة التبويبات حسب وجود order_id
            var hasOrderId = {{ isset($orderId) ? 'true' : 'false' }};
            if (!hasOrderId) {
                $('#tab-order').removeClass('active');
                $('#tab-order-pane').removeClass('show active');
                $('#tab-new').addClass('active');
                $('#tab-new-pane').addClass('show active');
            } else {
                // إذا كان هناك order_id، تأكد من أن التبويب القديم نشط
                $('#tab-new').removeClass('active');
                $('#tab-new-pane').removeClass('show active');
                $('#tab-order').addClass('active');
                $('#tab-order-pane').addClass('show active');
            }

            // تهيئة حقل تاريخ الميلاد
            $('#new_client_birthdate').on('change', function() {
                var selectedDate = new Date($(this).val());
                var today = new Date();

                if (selectedDate > today) {
                    alert('لا يمكن اختيار تاريخ في المستقبل');
                    $(this).val('');
                }

                // طباعة التاريخ المختار للتأكد
                console.log('Selected birthdate:', $(this).val());
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Hide both client type options if we have an orderId
            if (hasOrderId) {
                $('#new_client').parent().hide();
                $('#ex_client').parent().hide();
                $('#client_type').val('ex_client'); // Set client type to ex_client when orderId is present

                // Set maid_id from selected order
                if (selectedOrder && selectedOrder.maid) {
                    $('#maid_id').val(selectedOrder.maid.id);
                }

                // Lock all maid data fields and make them read-only
                $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age')
                    .prop('disabled', true).css('background-color', '#e9ecef');

                // Prevent any changes to maid data
                $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age')
                    .on('change keyup', function(e) {
                        e.preventDefault();
                        return false;
                    });
            }

            // Initialize Select2
            $('.select2').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    return;
                }

                $(this).select2({
                    placeholder: $(this).data('placeholder') || 'اختر من القائمة...',
                    allowClear: true,
                    dir: select2Direction,
                    width: '100%'
                });
            });

            function escapeSelect2Text(value) {
                if (value === null || value === undefined) return '';
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function formatClientResult(client) {
                if (client.loading) return client.text;
                if (!client.id) return client.text;
                var name = escapeSelect2Text(client.name || client.text || '');
                var phone = client.phone ? escapeSelect2Text(client.phone) : '';
                var doc = client.document_number || client.national_num || client.tax_number;
                doc = doc ? escapeSelect2Text(doc) : '';
                var $row = $('<div class="ajax-result-row"></div>');
                $row.append('<div class="ajax-result-row__icon"><i class="fas fa-user"></i></div>');
                var body = '<div class="ajax-result-row__body">' +
                    '<div class="ajax-result-row__title">' + name + '</div>' +
                    '<div class="ajax-result-row__meta">' +
                    (phone ? '<span><i class="fas fa-phone"></i> ' + phone + '</span>' : '') +
                    (doc ? '<span><i class="fas fa-id-card"></i> ' + doc + '</span>' : '') +
                    '</div></div>';
                $row.append(body);
                return $row;
            }

            function formatClientSelection(client) {
                if (!client.id) return client.text || 'اختر عميل...';
                var parts = [client.name || client.text];
                if (client.phone) parts.push(client.phone);
                var doc = client.document_number || client.national_num || client.tax_number;
                if (doc) parts.push(doc);
                return parts.filter(Boolean).join(' - ');
            }

            function formatMaidResult(maid) {
                if (maid.loading) return maid.text;
                if (!maid.id) return maid.text;
                var name = escapeSelect2Text(maid.name || maid.text || '');
                var passport = maid.passport ? escapeSelect2Text(maid.passport) : '';
                var nationality = maid.nationality ? escapeSelect2Text(maid.nationality) : '';
                var job = maid.job ? escapeSelect2Text(maid.job) : '';
                var age = maid.age ? escapeSelect2Text(maid.age) + ' سنة' : '';
                var $row = $('<div class="ajax-result-row"></div>');
                $row.append('<div class="ajax-result-row__icon"><i class="fas fa-user-tie"></i></div>');
                var body = '<div class="ajax-result-row__body">' +
                    '<div class="ajax-result-row__title">' + name + '</div>' +
                    '<div class="ajax-result-row__meta">' +
                    (passport ? '<span><i class="fas fa-passport"></i> ' + passport + '</span>' : '') +
                    (nationality ? '<span><i class="fas fa-flag"></i> ' + nationality + '</span>' : '') +
                    (job ? '<span><i class="fas fa-briefcase"></i> ' + job + '</span>' : '') +
                    (age ? '<span><i class="fas fa-birthday-cake"></i> ' + age + '</span>' : '') +
                    '</div></div>';
                $row.append(body);
                return $row;
            }

            function formatMaidSelection(maid) {
                if (!maid.id) return maid.text || 'اختر عامل/ة...';
                var parts = [maid.name || maid.text];
                if (maid.passport) parts.push(maid.passport);
                if (maid.nationality) parts.push(maid.nationality);
                return parts.filter(Boolean).join(' - ');
            }

            // Remove any pre-selected state before opening so every result is clickable (including the one currently selected)
            function clearStaleOptionsOnOpen($select) {
                $select.on('select2:opening', function() {
                    var $s = $(this);
                    $s.find('option').each(function() {
                        if (this.value !== '') {
                            $(this).remove();
                        }
                    });
                });
            }

            // Force clickability on any result option Select2 has marked as aria-selected.
            // This handles the case where Select2's internal state considers an option already selected
            // but we still want the user to be able to re-select it (to re-fill the form).
            $(document).on('mouseup touchend', '.select2-results__option[aria-selected="true"]', function(e) {
                var $option = $(this);
                var data = $option.data('data');
                if (!data || !data.id) return;
                var $container = $option.closest('.select2-container');
                var selectId = ($container.attr('id') || '').replace(/^select2-/, '').replace(/-container$/, '');
                if (!selectId) return;
                var $select = $('#' + selectId);
                if (!$select.length) return;
                if (!$select.hasClass('ajax-client-select') && !$select.hasClass('ajax-maid-select')) return;

                // Ensure the option exists in the underlying select then trigger change
                if (!$select.find('option[value="' + data.id + '"]').length) {
                    var $opt = $('<option></option>').val(data.id).text(data.text || '');
                    $select.append($opt);
                }
                $select.val(data.id);
                $select.select2('close');
                $select.trigger('change');
                e.preventDefault();
                e.stopPropagation();
            });

            $('.ajax-client-select').each(function() {
                destroySelect2IfInitialized($(this));

                $(this).select2({
                    placeholder: $(this).data('placeholder') || 'ابحث عن العميل أو اختر من القائمة...',
                    allowClear: true,
                    dir: select2Direction,
                    width: '100%',
                    minimumInputLength: 0,
                    templateResult: formatClientResult,
                    templateSelection: formatClientSelection,
                    ajax: {
                        url: searchClientsRoute,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results || [],
                                pagination: {
                                    more: !!(data.pagination && data.pagination.more)
                                }
                            };
                        },
                        cache: false
                    },
                    language: {
                        inputTooShort: function() { return ''; },
                        searching: function() { return 'جاري البحث...'; },
                        noResults: function() { return 'لا توجد نتائج'; },
                        loadingMore: function() { return 'تحميل المزيد...'; }
                    }
                });
                clearStaleOptionsOnOpen($(this));
            });

            $('.ajax-maid-select').each(function() {
                destroySelect2IfInitialized($(this));

                $(this).select2({
                    placeholder: $(this).data('placeholder') || 'ابحث عن العامل/ة أو اختر من القائمة...',
                    allowClear: true,
                    dir: select2Direction,
                    width: '100%',
                    minimumInputLength: 0,
                    templateResult: formatMaidResult,
                    templateSelection: formatMaidSelection,
                    ajax: {
                        url: availableMaidsRoute,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                select2: 1,
                                search: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results || [],
                                pagination: {
                                    more: !!(data.pagination && data.pagination.more)
                                }
                            };
                        },
                        cache: false
                    },
                    language: {
                        inputTooShort: function() { return ''; },
                        searching: function() { return 'جاري البحث...'; },
                        noResults: function() { return 'لا توجد عاملات متاحات'; },
                        loadingMore: function() { return 'تحميل المزيد...'; }
                    }
                });
                clearStaleOptionsOnOpen($(this));
            });


            // If we have a selected order, populate the form fields
            if (selectedOrder) {
                console.log('Selected order data:', selectedOrder);

                // Set the order in the dropdown
                $('#order_id').val(selectedOrder.id).trigger('change');

                // Fill in client data
                if (selectedOrder.user) {
                    var selectedOrderClientType = selectedOrder.user.client_type || 'person';
                    var selectedOrderDocumentLabel = isCompanyClientType(selectedOrderClientType)
                        ? @json(helperTrans('ingaz.tax_number'))
                        : @json(helperTrans('ingaz.national_num'));

                    $('#client_name').val(sanitizeTextValue(selectedOrder.user.name));
                    $('#client_phone').val(sanitizePhoneValue(selectedOrder.user.phone));
                    $('#client_email').val(sanitizeTextValue(selectedOrder.user.email));
                    $('#client_national_num').val(sanitizeTextValue(selectedClientDocumentNumber(selectedOrder.user)));
                    $('#client_birthdate').val(selectedOrder.user.birthdate || '');
                    applyRecruitmentClientDocumentMode({
                        clientType: selectedOrderClientType,
                        documentLabel: selectedOrderDocumentLabel,
                        documentInput: '#client_national_num',
                        birthdateInput: '#client_birthdate',
                        personFields: '#client_birthdate, #beds_num, #family_num, #children, #job',
                        companyFields: '#client_license_number'
                    });
                    $('#phone2').val(sanitizePhoneValue(selectedOrder.user.phone2));
                    $('#address').val(sanitizeTextValue(selectedOrder.user.address));
                    $('#client_license_number').val(sanitizeTextValue(selectedOrder.user.license_number));
                    $('#beds_num').val(selectedOrder.user.beds_num || '');
                    $('#family_num').val(selectedOrder.user.family_num || '');
                    $('#children').val(selectedOrder.user.children || '');
                    $('#job').val(sanitizeTextValue(selectedOrder.user.job));
                    applyRecruitmentClientDocumentMode({
                        clientType: selectedOrderClientType,
                        documentLabel: selectedOrderDocumentLabel,
                        documentInput: '#client_national_num',
                        birthdateInput: '#client_birthdate',
                        personFields: '#client_birthdate, #beds_num, #family_num, #children, #job',
                        companyFields: '#client_license_number'
                    });
                    setSearchableSelectValue('#city', selectedOrder.user.city);
                    if (selectedOrder.user.nationality_id) {
                        $('#client_nationality_id').val(selectedOrder.user.nationality_id).trigger('change');
                    }
                }

                // Set default contract duration to 24 months
                $('#contract_duration').val('2');

                // Fill in maid data
                if (selectedOrder.maid) {
                    // Set maid name directly from the order data
                    $('#maid_name').val(selectedOrder.maid.name);
                    $('#maid_passport').val(selectedOrder.maid.passport_num);

                    // Fetch maid details to get proper names/values
                    if (selectedOrder.maid.id) {
                        $.ajax({
                            url: '/admin/maids/' + selectedOrder.maid.id + '/details',
                            type: 'GET',
                            success: function(data) {
                                console.log('Maid details response:', data);

                                // Set religion
                                if (data.religion_title) {
                                    $('#maid_religion').val(data.religion_title);
                                }

                                // Handle job type - use the actual job type from maid data
                                if (data.job_types && data.job_types.length > 0) {
                                    $('#maid_job').val(data.job_types[0].title);
                                }

                                // Handle nationality
                                if (data.nationality && data.nationality.title) {
                                    $('#maid_nationality').val(data.nationality.title);
                                }

                                // Set other fields
                                $('#maid_salary').val(data.salary || '');
                                $('#experience_years').val(data.experience_years || '');
                                $('#age').val(data.age || '');

                                refreshSelect2IfInitialized('#maid_nationality');
                                refreshSelect2IfInitialized('#maid_religion');
                                refreshSelect2IfInitialized('#maid_job');
                            },
                            error: function(error) {
                                console.error('Error fetching maid details:', error);
                            }
                        });
                    }
                }

                // Set client type to existing client
                $('#ex_client').prop('checked', true).trigger('change');
            }

            $('#client_type').change(function() {
                if ($(this).val() === 'new_client') {
                    $('#new-client-section').show();
                    $('#order-selection').hide();
                    $('#maid-selection').show();
                    $('#maid_id').prop('required', true);
                    $('#order_id').prop('required', false);
                    $('#order_id').val('').trigger('change');
                } else {
                    $('#new-client-section').hide();
                    $('#order-selection').show();
                    $('#maid-selection').hide();
                    $('#maid_id').prop('required', false);
                    $('#order_id').prop('required', true);
                }
            });

            $('#maid_id').change(function() {
                var maidId = $(this).val();
                if (maidId) {
                    var selectedOption = $(this).find('option:selected');
                    console.log('Selected maid data:', {
                        name: selectedOption.data('name'),
                        passport: selectedOption.data('passport'),
                        religion: selectedOption.data('religion'),
                        job: selectedOption.data('job'),
                        nationality: selectedOption.data('nationality'),
                        salary: selectedOption.data('salary'),
                        experience: selectedOption.data('experience'),
                        age: selectedOption.data('age')
                    });

                    // Enable fields temporarily
                    $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age')
                        .prop('disabled', false);

                    // Set basic information from data attributes
                    $('#maid_name').val(selectedOption.data('name'));
                    $('#maid_passport').val(selectedOption.data('passport'));
                    $('#maid_religion').val(selectedOption.data('religion'));
                    $('#maid_job').val(selectedOption.data('job'));
                    $('#maid_nationality').val(selectedOption.data('nationality'));
                    $('#maid_salary').val(selectedOption.data('salary'));
                    $('#experience_years').val(selectedOption.data('experience'));
                    $('#age').val(selectedOption.data('age'));

                    refreshSelect2IfInitialized('#maid_nationality');
                    refreshSelect2IfInitialized('#maid_religion');
                    refreshSelect2IfInitialized('#maid_job');

                    // Disable fields again after setting values
                    setTimeout(function() {
                        $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age')
                            .prop('disabled', true);
                    }, 100);
                }
            });

            // Handle order selection change
            $('#order_id').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var maidId = selectedOption.data('maid-id');

                // Set client type to ex_client when an order is selected
                $('#client_type').val('ex_client');

                // Set maid_id from selected order
                $('#maid_id').val(maidId);

                // Clear all fields first
                $('#client_name, #client_phone, #client_email, #client_national_num, #client_birthdate, #phone2, #address, #beds_num, #family_num, #children, #job, #maid_name, #maid_passport, #maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age')
                    .val('');
                $('#client_nationality_id, #city').val('').trigger('change');
                applyRecruitmentClientDocumentMode({
                    clientType: 'person',
                    documentInput: '#client_national_num',
                    birthdateInput: '#client_birthdate',
                    personFields: '#client_birthdate, #beds_num, #family_num, #children, #job',
                    companyFields: '#client_license_number'
                });

                if (maidId) {
                    var orderClientType = selectedOption.data('account-client-type') || 'person';
                    var orderDocumentLabel = selectedOption.data('client-document-label') || (isCompanyClientType(orderClientType) ? @json(helperTrans('ingaz.tax_number')) : @json(helperTrans('ingaz.national_num')));

                    // Fill in the client form fields
                    $('#client_name').val(sanitizeTextValue(selectedOption.data('client-name')));
                    $('#client_phone').val(sanitizePhoneValue(selectedOption.data('client-phone')));
                    $('#client_email').val(sanitizeTextValue(selectedOption.data('client-email')));
                    $('#client_national_num').val(sanitizeTextValue(selectedOption.data('client-national-num')));
                    $('#client_birthdate').val(selectedOption.data('client-birthdate') || '');
                    applyRecruitmentClientDocumentMode({
                        clientType: orderClientType,
                        documentLabel: orderDocumentLabel,
                        documentInput: '#client_national_num',
                        birthdateInput: '#client_birthdate',
                        personFields: '#client_birthdate, #beds_num, #family_num, #children, #job',
                        companyFields: '#client_license_number'
                    });
                    $('#phone2').val(sanitizePhoneValue(selectedOption.data('client-phone2')));
                    $('#address').val(sanitizeTextValue(selectedOption.data('client-address')));
                    $('#client_license_number').val(sanitizeTextValue(selectedOption.data('client-license-number')));
                    $('#beds_num').val(selectedOption.data('client-beds-num') || '');
                    $('#family_num').val(selectedOption.data('client-family-num') || '');
                    $('#children').val(selectedOption.data('client-children') || '');
                    $('#job').val(sanitizeTextValue(selectedOption.data('client-job')));
                    applyRecruitmentClientDocumentMode({
                        clientType: orderClientType,
                        documentLabel: orderDocumentLabel,
                        documentInput: '#client_national_num',
                        birthdateInput: '#client_birthdate',
                        personFields: '#client_birthdate, #beds_num, #family_num, #children, #job',
                        companyFields: '#client_license_number'
                    });
                    setSearchableSelectValue('#city', selectedOption.data('client-city'));
                    if (selectedOption.data('client-nationality')) {
                        $('#client_nationality_id').val(selectedOption.data('client-nationality')).trigger(
                            'change');
                    }

                    // Fill in the maid form fields
                    $('#maid_name').val(selectedOption.data('maid-name'));
                    $('#maid_passport').val(selectedOption.data('maid-passport'));

                    // Fetch maid details to get proper names/values
                    $.ajax({
                        url: '/admin/maids/' + maidId + '/details',
                        type: 'GET',
                        success: function(data) {
                            console.log('Maid details response:', data);

                            // Enable fields temporarily
                            $('#maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age')
                                .prop('disabled', false);

                            // Set religion
                            console.log('Setting religion:', data.religion_title);
                            $('#maid_religion').val(data.religion_title || '');

                            // Handle job type
                            var jobTypeTitle = '';
                            if (data.job_types && data.job_types.length > 0) {
                                jobTypeTitle = data.job_types[0].title || '';
                                console.log('Setting job type:', jobTypeTitle);
                            }
                            $('#maid_job').val(jobTypeTitle);

                            // Handle nationality
                            var nationalityTitle = '';
                            if (data.nationality) {
                                nationalityTitle = data.nationality.title || '';
                                console.log('Setting nationality:', nationalityTitle);
                            }
                            $('#maid_nationality').val(nationalityTitle);

                            // Set other fields
                            console.log('Setting salary:', data.salary);
                            $('#maid_salary').val(data.salary || '');

                            console.log('Setting experience years:', data.experience_years);
                            $('#experience_years').val(data.experience_years || '');

                            console.log('Setting age:', data.age);
                            $('#age').val(data.age || '');

                            refreshSelect2IfInitialized('#maid_nationality');
                            refreshSelect2IfInitialized('#maid_religion');
                            refreshSelect2IfInitialized('#maid_job');

                            // Disable fields again after setting values
                            setTimeout(function() {
                                $('#maid_religion, #maid_job, #maid_nationality, #maid_salary, #experience_years, #age')
                                    .prop('disabled', true);
                            }, 100);
                        },
                        error: function(error) {
                            console.error('Error fetching maid details:', error);
                        }
                    });
                }
            });

            function getNewMaidMode() {
                return $('input[name="maid_selection_mode"]:checked').val() || 'existing';
            }

            function clearSelectedMaidSummary() {
                $('#new_maid_name, #new_maid_passport, #new_maid_religion, #new_maid_job, #new_maid_nationality, #new_maid_salary, #new_maid_experience_years, #new_maid_age')
                    .val('');
            }

            function applyNewMaidMode(mode) {
                var hasSelectedExistingMaid = !!$('#new_new_maid_id').val();

                $('#new-existing-maid-picker').toggle(mode === 'existing');
                $('#existing-maid-summary').toggle(mode === 'existing' && hasSelectedExistingMaid);
                $('#manual-maid-section-card').toggle(mode === 'manual');
                $('#new-no-maid-alert').toggle(mode === 'postponed');
                $('#new-no-maid-message').toggle(mode === 'postponed');

                if (mode !== 'existing' || !hasSelectedExistingMaid) {
                    clearSelectedMaidSummary();
                }

                if (mode !== 'existing') {
                    $('#new_new_maid_id').removeClass('is-invalid');
                    $('#err_new_new_maid_id').remove();
                }

                if (mode === 'manual') {
                    loadManualMaidOfficesByNationality();
                }
            }

            var $manualMaidOfficeSelect = $('#manual_maid_office_id');
            var allManualMaidOfficeOptions = $manualMaidOfficeSelect.children().clone();
            var manualMaidOfficeRequest = null;

            function loadManualMaidOfficesByNationality() {
                var nationalityId = String($('#manual_maid_nationality_id').val() || '');

                if (manualMaidOfficeRequest) {
                    manualMaidOfficeRequest.abort();
                    manualMaidOfficeRequest = null;
                }

                if (!nationalityId) {
                    $manualMaidOfficeSelect.empty()
                        .append(allManualMaidOfficeOptions.clone())
                        .val('')
                        .trigger('change.select2');
                    return;
                }

                $manualMaidOfficeSelect.empty()
                    .append($('<option>', {
                        value: '',
                        text: @json(__('ingaz.select_external_office_placeholder'))
                    }))
                    .val('')
                    .trigger('change.select2');

                var request = $.ajax({
                    url: @json(route('getOfficesByNationality')),
                    type: 'GET',
                    data: { nationality_id: nationalityId },
                    success: function(offices) {
                        if (String($('#manual_maid_nationality_id').val() || '') !== nationalityId) {
                            return;
                        }

                        (offices || []).forEach(function(office) {
                            $manualMaidOfficeSelect.append($('<option>', {
                                value: office.id,
                                text: office.title
                            }));
                        });
                        $manualMaidOfficeSelect.trigger('change.select2');
                    },
                    error: function(xhr) {
                        if (xhr.statusText === 'abort') {
                            return;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: @json(__('ingaz.error')),
                            text: @json(__('ingaz.office_fetch_error'))
                        });
                    },
                    complete: function() {
                        if (manualMaidOfficeRequest === request) {
                            manualMaidOfficeRequest = null;
                        }
                    }
                });
                manualMaidOfficeRequest = request;
            }

            $('input[name="maid_selection_mode"]').on('change', function() {
                applyNewMaidMode($(this).val());
            });

            $('#manual_maid_nationality_id').on('change', loadManualMaidOfficesByNationality);

            applyNewMaidMode(getNewMaidMode());
            loadManualMaidOfficesByNationality();

            $('#manual_maid_passport_num').on('input', function() {
                var passportNum = $(this).val().trim();
                var $field = $(this);
                var $feedback = $('#err_manual_maid_passport_num');

                clearTimeout(manualPassportCheckTimeout);
                isManualPassportDuplicate = false;
                $field.removeClass('is-valid is-invalid');
                $feedback.removeClass('valid-feedback invalid-feedback').text('');

                if (!passportNum) {
                    return;
                }

                manualPassportCheckTimeout = setTimeout(function() {
                    $.ajax({
                        url: passportUniqueRoute,
                        type: 'POST',
                        data: {
                            passport_num: passportNum,
                            _token: $('input[name="_token"]').first().val()
                        },
                        success: function(response) {
                            if (response.exists) {
                                isManualPassportDuplicate = true;
                                $field.addClass('is-invalid');
                                $feedback.addClass('invalid-feedback').text(
                                    'رقم الجواز مسجل مسبقاً في النظام، يرجى التحقق من الرقم أو اختيار رقم آخر'
                                );
                            } else {
                                $field.addClass('is-valid');
                                $feedback.addClass('valid-feedback').text('رقم الجواز متاح');
                            }
                        },
                        error: function() {
                            $field.removeClass('is-valid is-invalid');
                            $feedback.removeClass('valid-feedback invalid-feedback').text('');
                        }
                    });
                }, 500);
            });

            // عند اختيار عميل في التبويب الجديد: عبئ بيانات العميل (حقول منفصلة)
            var isFillingClientFromSelection = false;

            function clearSelectedClientForDirectEntry() {
                $('#new_existing_client_id').val('').trigger('change');
                $('#new_existing_client_id').removeClass('is-invalid');
                $('#err_new_existing_client_id').remove();
                applyRecruitmentClientDocumentMode({
                    clientType: 'person',
                    documentInput: '#new_client_national_num',
                    birthdateInput: '#new_client_birthdate',
                    personFields: '#new_client_birthdate, #new_client_beds_num, #new_client_family_num, #new_client_children, #new_client_job',
                    companyFields: '#new_client_license_number'
                });
            }

            $('#use-direct-client-data').on('click', function() {
                clearSelectedClientForDirectEntry();
                $('#new_client_name').trigger('focus');
            });

            $('input[name="account_client_type"]').on('change', function() {
                var $form = $(this).closest('form');
                var isNewContractForm = $form.attr('id') === 'newContractForm';

                applyRecruitmentClientDocumentMode({
                    clientType: $(this).val(),
                    documentInput: isNewContractForm ? '#new_client_national_num' : '#client_national_num',
                    birthdateInput: isNewContractForm ? '#new_client_birthdate' : '#client_birthdate',
                    personFields: isNewContractForm
                        ? '#new_client_birthdate, #new_client_beds_num, #new_client_family_num, #new_client_children, #new_client_job'
                        : '#client_birthdate, #beds_num, #family_num, #children, #job',
                    companyFields: isNewContractForm ? '#new_client_license_number' : '#client_license_number',
                    accountTypeInputs: $form.find('input[name="account_client_type"]')
                });
            });

            $('#new_client_name, #new_client_phone, #new_client_email, #new_client_national_num, #new_client_license_number, #new_client_birthdate, #new_client_phone2, #new_client_address, #new_client_beds_num, #new_client_family_num, #new_client_children, #new_client_job')
                .on('input', function() {
                    if (!isFillingClientFromSelection && $('#new_existing_client_id').val()) {
                        clearSelectedClientForDirectEntry();
                    }
                });

            $('#new_client_nationality_id, #new_client_city').on('change', function() {
                if (!isFillingClientFromSelection && $('#new_existing_client_id').val()) {
                    clearSelectedClientForDirectEntry();
                }
            });

            $('#new_existing_client_id').on('change', function() {
                $(this).removeClass('is-invalid');
                $('#err_new_existing_client_id').remove();

                var selectedOption = $(this).find('option:selected');
                var selectedClient = select2SelectedData(this);
                if (selectedOption.val()) {
                    isFillingClientFromSelection = true;
                    // Debug: طباعة بيانات العميل المختار
                    console.log('Selected client data:', {
                        name: optionOrAjaxData(selectedOption, selectedClient, 'name'),
                        phone: optionOrAjaxData(selectedOption, selectedClient, 'phone'),
                        email: optionOrAjaxData(selectedOption, selectedClient, 'email'),
                        nationality: optionOrAjaxData(selectedOption, selectedClient, 'nationality'),
                        national_num: optionOrAjaxData(selectedOption, selectedClient, 'national_num', 'national-num'),
                        birthdate: optionOrAjaxData(selectedOption, selectedClient, 'birthdate'),
                        phone2: optionOrAjaxData(selectedOption, selectedClient, 'phone2'),
                        address: optionOrAjaxData(selectedOption, selectedClient, 'address'),
                        city: optionOrAjaxData(selectedOption, selectedClient, 'city'),
                        beds_num: optionOrAjaxData(selectedOption, selectedClient, 'beds_num', 'beds-num'),
                        family_num: optionOrAjaxData(selectedOption, selectedClient, 'family_num', 'family-num'),
                        children: optionOrAjaxData(selectedOption, selectedClient, 'children_count', 'children'),
                        job: optionOrAjaxData(selectedOption, selectedClient, 'job')
                    });

                    // تعبئة الحقول المطلوبة
                    $('#new_client_name').val(sanitizeTextValue(optionOrAjaxData(selectedOption, selectedClient, 'name')));
                    $('#new_client_phone').val(sanitizePhoneValue(optionOrAjaxData(selectedOption, selectedClient, 'phone')));
                    $('#new_client_email').val(sanitizeTextValue(optionOrAjaxData(selectedOption, selectedClient, 'email')));
                    $('#new_client_national_num').val(sanitizeTextValue(optionOrAjaxData(selectedOption, selectedClient, 'national_num', 'national-num')));
                    $('#new_client_birthdate').val(optionOrAjaxData(selectedOption, selectedClient, 'birthdate'));
                    applyRecruitmentClientDocumentMode({
                        clientType: optionOrAjaxData(selectedOption, selectedClient, 'client_type', 'account-client-type') || 'person',
                        documentLabel: optionOrAjaxData(selectedOption, selectedClient, 'document_label', 'client-document-label'),
                        documentInput: '#new_client_national_num',
                        birthdateInput: '#new_client_birthdate',
                        personFields: '#new_client_birthdate, #new_client_beds_num, #new_client_family_num, #new_client_children, #new_client_job',
                        companyFields: '#new_client_license_number'
                    });
                    $('#new_client_phone2').val(sanitizePhoneValue(optionOrAjaxData(selectedOption, selectedClient, 'phone2')));
                    $('#new_client_address').val(sanitizeTextValue(optionOrAjaxData(selectedOption, selectedClient, 'address')));
                    $('#new_client_license_number').val(sanitizeTextValue(optionOrAjaxData(selectedOption, selectedClient, 'license_number', 'license-number')));
                    setSearchableSelectValue('#new_client_city', optionOrAjaxData(selectedOption, selectedClient, 'city'));

                    // تعبئة الحقول الاختيارية
                    $('#new_client_beds_num').val(optionOrAjaxData(selectedOption, selectedClient, 'beds_num', 'beds-num'));
                    $('#new_client_family_num').val(optionOrAjaxData(selectedOption, selectedClient, 'family_num', 'family-num'));
                    $('#new_client_children').val(optionOrAjaxData(selectedOption, selectedClient, 'children_count', 'children'));
                    $('#new_client_job').val(sanitizeTextValue(optionOrAjaxData(selectedOption, selectedClient, 'job')));
                    applyRecruitmentClientDocumentMode({
                        clientType: optionOrAjaxData(selectedOption, selectedClient, 'client_type', 'account-client-type') || 'person',
                        documentLabel: optionOrAjaxData(selectedOption, selectedClient, 'document_label', 'client-document-label'),
                        documentInput: '#new_client_national_num',
                        birthdateInput: '#new_client_birthdate',
                        personFields: '#new_client_birthdate, #new_client_beds_num, #new_client_family_num, #new_client_children, #new_client_job',
                        companyFields: '#new_client_license_number'
                    });

                    // تعبئة حقل الجنسية في dropdown
                    var selectedNationality = optionOrAjaxData(selectedOption, selectedClient, 'nationality');
                    if (selectedNationality) {
                        $('#new_client_nationality_id').val(selectedNationality).trigger(
                            'change');
                        console.log('Setting client nationality to:', selectedNationality);
                    } else {
                        console.log('No nationality data found for client');
                    }

                    // Debug: طباعة القيم بعد التعبئة
                    console.log('Filled client data:', {
                        name: $('#new_client_name').val(),
                        phone: $('#new_client_phone').val(),
                        email: $('#new_client_email').val(),
                        nationality: $('#new_client_nationality_id').val(),
                        national_num: $('#new_client_national_num').val(),
                        birthdate: $('#new_client_birthdate').val(),
                        phone2: $('#new_client_phone2').val(),
                        address: $('#new_client_address').val(),
                        city: $('#new_client_city').val(),
                        beds_num: $('#new_client_beds_num').val(),
                        family_num: $('#new_client_family_num').val(),
                        children: $('#new_client_children').val(),
                        job: $('#new_client_job').val()
                    });
                    isFillingClientFromSelection = false;
                } else {
                    // مسح جميع الحقول عند عدم اختيار عميل
                    if (isFillingClientFromSelection) {
                        return;
                    }
                    applyRecruitmentClientDocumentMode({
                        clientType: 'person',
                        documentInput: '#new_client_national_num',
                        birthdateInput: '#new_client_birthdate',
                        personFields: '#new_client_birthdate, #new_client_beds_num, #new_client_family_num, #new_client_children, #new_client_job',
                        companyFields: '#new_client_license_number'
                    });
                }
            });
            // عند اختيار عامل/ة في التبويب الجديد: عبئ بيانات العامل/ة (حقول منفصلة)
            $('#new_new_maid_id').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var selectedMaid = select2SelectedData(this);
                if (selectedOption.val()) {
                    // Debug: طباعة البيانات المختارة
                    console.log('Selected maid data:', {
                        name: optionOrAjaxData(selectedOption, selectedMaid, 'name'),
                        passport: optionOrAjaxData(selectedOption, selectedMaid, 'passport'),
                        religion: optionOrAjaxData(selectedOption, selectedMaid, 'religion'),
                        job: optionOrAjaxData(selectedOption, selectedMaid, 'job'),
                        nationality: optionOrAjaxData(selectedOption, selectedMaid, 'nationality'),
                        salary: optionOrAjaxData(selectedOption, selectedMaid, 'salary'),
                        experience: optionOrAjaxData(selectedOption, selectedMaid, 'experience'),
                        age: optionOrAjaxData(selectedOption, selectedMaid, 'age')
                    });

                    // طباعة البيانات الخام من data attributes
                    console.log('Raw data attributes:', {
                        'data-name': selectedOption.attr('data-name'),
                        'data-passport': selectedOption.attr('data-passport'),
                        'data-religion': selectedOption.attr('data-religion'),
                        'data-job': selectedOption.attr('data-job'),
                        'data-nationality': selectedOption.attr('data-nationality'),
                        'data-salary': selectedOption.attr('data-salary'),
                        'data-experience': selectedOption.attr('data-experience'),
                        'data-age': selectedOption.attr('data-age')
                    });

                    // طباعة تفاصيل المهنة
                    console.log('Job data details:', {
                        'job-attr': selectedOption.attr('data-job'),
                        'job-data': selectedOption.data('job'),
                        'job-value': selectedOption.data('job') || 'No job data'
                    });

                    // طباعة تفاصيل المهنة من jobTitles
                    console.log('JobTitles details:', {
                        'jobTitles-count': selectedOption.data('job') ? 'Has job title' :
                            'No job title',
                        'job-title-value': selectedOption.data('job')
                    });

                    $('#new_maid_name').val(optionOrAjaxData(selectedOption, selectedMaid, 'name'));
                    $('#new_maid_passport').val(optionOrAjaxData(selectedOption, selectedMaid, 'passport'));
                    $('#new_maid_religion').val(optionOrAjaxData(selectedOption, selectedMaid, 'religion'));
                    $('#new_maid_job').val(optionOrAjaxData(selectedOption, selectedMaid, 'job'));
                    console.log('Setting maid job to:', optionOrAjaxData(selectedOption, selectedMaid, 'job'));
                    console.log('Maid job field value after setting:', $('#new_maid_job').val());
                    console.log('Job title from jobTitles:', optionOrAjaxData(selectedOption, selectedMaid, 'job'));
                    $('#new_maid_nationality').val(optionOrAjaxData(selectedOption, selectedMaid, 'nationality'));
                    console.log('Setting maid nationality to:', optionOrAjaxData(selectedOption, selectedMaid, 'nationality'));
                    console.log('Maid nationality field value after setting:', $('#new_maid_nationality')
                        .val());
                    $('#new_maid_salary').val(optionOrAjaxData(selectedOption, selectedMaid, 'salary'));
                    $('#new_maid_experience_years').val(optionOrAjaxData(selectedOption, selectedMaid, 'experience'));
                    $('#new_maid_age').val(optionOrAjaxData(selectedOption, selectedMaid, 'age'));

                    // Debug: طباعة القيم بعد التعبئة
                    console.log('Filled nationality field:', $('#new_maid_nationality').val());
                    console.log('Filled job field:', $('#new_maid_job').val());
                    console.log('Filled religion field:', $('#new_maid_religion').val());
                    console.log('All maid fields after filling:', {
                        name: $('#new_maid_name').val(),
                        passport: $('#new_maid_passport').val(),
                        religion: $('#new_maid_religion').val(),
                        job: $('#new_maid_job').val(),
                        nationality: $('#new_maid_nationality').val(),
                        salary: $('#new_maid_salary').val(),
                        experience: $('#new_maid_experience_years').val(),
                        age: $('#new_maid_age').val()
                    });

                    // طباعة تفاصيل إضافية عن المهنة
                    console.log('Job field details:', {
                        'field-id': '#new_maid_job',
                        'field-value': $('#new_maid_job').val(),
                        'field-visible': $('#new_maid_job').is(':visible'),
                        'field-disabled': $('#new_maid_job').is(':disabled')
                    });
                } else {
                    clearSelectedMaidSummary();
                }

                applyNewMaidMode(getNewMaidMode());
            });

            // // Initialize Select2 for maid_id with custom template
            // if ($.fn.select2) {
            //     $('#maid_id').select2({
            //         placeholder: 'اختر العامل/ة',
            //         allowClear: true,
            //         templateResult: formatMaidOption,
            //         templateSelection: formatMaidOption
            //     });
            // }

            function formatMaidOption(maid) {
                if (!maid.id) return maid.text;
                var $maid = $(maid.element);
                var $container = $(
                    '<div class="select2-result-maid">' +
                    '<div class="select2-result-maid__name">' + $maid.data('name') + '</div>' +
                    '<div class="select2-result-maid__details">' +
                    'رقم الجواز: ' + $maid.data('passport') + ' | ' +
                    'الجنسية: ' + $maid.data('nationality') +
                    '</div>' +
                    '</div>'
                );
                return $container;
            }



            var orderShouldRedirectToInvoice = false;
            var orderInvoiceCreateUrl = "{{ route('invoices.create') }}";
            var orderSubmitButton = $('#orderContractSubmitBtn');
            var orderInvoiceButton = $('#order-save-and-invoice');
            var orderSubmitButtonHtml = orderSubmitButton.html();
            var orderInvoiceButtonHtml = orderInvoiceButton.html();

            orderInvoiceButton.on('click', function() {
                orderShouldRedirectToInvoice = true;
                $('#maidForm').trigger('submit');
            });

            // معالج للنموذج القديم (عقد من طلب مسبق)
            $('#maidForm').on('submit', function(e) {
                e.preventDefault();
                console.log('Old form submission started');

                var redirectToInvoice = orderShouldRedirectToInvoice === true;
                orderShouldRedirectToInvoice = false;

                // تحقق من التبويب النشط
                var isOrderTab = $('#tab-order-pane').hasClass('show active');
                var isNewTab = $('#tab-new-pane').hasClass('show active');
                console.log('Order tab active:', isOrderTab);
                console.log('New tab active:', isNewTab);

                // إذا كان التبويب الجديد نشط، لا نرسل الفورم القديم
                if (isNewTab) {
                    console.log('New tab active, stopping old form submission');
                    return false;
                }

                // Clear previous validation errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                let isValid = true;
                let firstInvalidField = null;

                // Validate required fields for order tab
                $('#tab-order-pane [data-validation="required"]').each(function() {
                    if (!$(this).val()) {
                        isValid = false;
                        $(this).addClass('is-invalid');

                        // Add error message
                        var fieldName = $(this).attr('name');
                        var errorMessage = '';

                        switch (fieldName) {
                            case 'contract_date':
                                errorMessage = 'يرجى ادخال تاريخ العقد';
                                break;
                            case 'msaned_number':
                                errorMessage = 'يرجى ادخال رقم مساند';
                                break;
                            case 'visa_number':
                                errorMessage = 'يرجى ادخال رقم التأشيرة';
                                break;
                            case 'contract_duration':
                                errorMessage = 'يرجى ادخال مدة العقد';
                                break;
                            case 'order_id':
                                errorMessage = 'يرجى اختيار الطلب';
                                break;
                            default:
                                errorMessage = 'هذا الحقل مطلوب';
                        }

                        $(this).after('<div class="invalid-feedback">' + errorMessage + '</div>');

                        if (!firstInvalidField) {
                            firstInvalidField = $(this);
                        }
                    }
                });

                // Additional validation for maid_id - allow postponing maid selection
                if (!$('#maid_id').val()) {
                    // Check if this is a new contract tab where maid selection can be postponed
                    var isNewContractTab = $('#tab-new-pane').hasClass('show active');
                    if (isNewContractTab) {
                        // For new contracts, maid selection can be postponed
                        console.log('Maid selection postponed for new contract');
                    } else {
                        // For existing orders, maid is required
                        isValid = false;
                        Swal.fire({
                            icon: 'error',
                            title: recruitmentContractTranslations.errorTitle,
                            text: recruitmentContractTranslations.selectWorkerRequired,
                            confirmButtonText: recruitmentContractTranslations.ok
                        });
                        return false;
                    }
                }

                if (!isValid) {
                    // Scroll to first invalid field
                    if (firstInvalidField) {
                        $('html, body').animate({
                            scrollTop: firstInvalidField.offset().top - 100
                        }, 500);
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'يرجى مراجعة البيانات',
                        text: 'توجد حقول مطلوبة أو غير مكتملة قبل حفظ العقد.',
                        confirmButtonText: 'حسناً'
                    });
                    return false;
                }

                // If all validations pass, submit the form via AJAX
                let formData = new FormData(this);
                formData.set('client_type', 'ex_client');

                // Debug: طباعة البيانات المرسلة
                console.log('Form data being sent:');
                for (var pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                let url = $(this).attr('action');
                let submitButton = redirectToInvoice ? orderInvoiceButton : orderSubmitButton;

                // Disable action buttons and show loading state
                orderSubmitButton.prop('disabled', true);
                orderInvoiceButton.prop('disabled', true);
                submitButton.html('<i class="fa fa-spinner fa-spin me-2"></i>جاري الحفظ...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Server response:', response);
                        if (response.success) {
                            handleContractCreated(response, redirectToInvoice, orderInvoiceCreateUrl);
                        } else {
                            // Show error message
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء إنشاء العقد',
                                confirmButtonText: 'حسناً'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            showContractValidationErrors(xhr.responseJSON.errors, '#maidForm');
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: contractRequestErrorMessage(xhr),
                            confirmButtonText: 'حسناً'
                        });
                    },
                    complete: function() {
                        orderSubmitButton.prop('disabled', false).html(orderSubmitButtonHtml);
                        orderInvoiceButton.prop('disabled', false).html(orderInvoiceButtonHtml);
                    }
                });
            });
        });
    </script>

    <script>
        // Global error handler
        window.addEventListener('error', function(e) {
            console.error('JavaScript error:', e.error);

            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ في الصفحة',
                    text: e.message || 'حدث خطأ غير متوقع أثناء تنفيذ العملية',
                    confirmButtonText: 'حسناً'
                });
            }
        });

        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled promise rejection:', e.reason);

            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ في الصفحة',
                    text: (e.reason && e.reason.message) || 'حدث خطأ غير متوقع أثناء تنفيذ العملية',
                    confirmButtonText: 'حسناً'
                });
            }
        });

        $(document).ready(function() {
            console.log('Document ready - New Contract Form initialized');
            $('#internal-company-contract').on('change', function() {
                var companySelected = this.checked;
                $(this).closest('.internal-company-toggle').toggleClass('is-active', companySelected);
                $('#new_client_name').closest('.form-section').parent().toggle(!companySelected);
                $('#new_existing_client_id').closest('.contract-picker-row').toggle(!companySelected);
                $('#new_client_name').closest('.form-section').find(':input').prop('disabled', companySelected);
                $('#new_existing_client_id').prop('disabled', companySelected);
            });
            console.log('Form elements found:', {
                newContractForm: $('#newContractForm').length,
                newContractSubmitBtn: $('#newContractSubmitBtn').length,
                contractDate: $('#contract_date').length,
                msanedNumber: $('#msaned_number').length,
                visaNumber: $('#visa_number').length
            });

            console.log('Contract fields in new tab:', {
                contractDate: $('#tab-new-pane #contract_date').length,
                msanedNumber: $('#tab-new-pane #msaned_number').length,
                visaNumber: $('#tab-new-pane #visa_number').length,
                contractDuration: $('#tab-new-pane #contract_duration').length
            });

            // Check if form exists
            if ($('#newContractForm').length === 0) {
                console.error('newContractForm not found in DOM');
            } else {
                console.log('newContractForm found in DOM');
            }

            // Check which tab is active
            console.log('Active tab:', {
                tabOrderActive: $('#tab-order').hasClass('active'),
                tabNewActive: $('#tab-new').hasClass('active'),
                tabOrderPaneActive: $('#tab-order-pane').hasClass('show active'),
                tabNewPaneActive: $('#tab-new-pane').hasClass('show active')
            });

            // معالجة حفظ العقد الجديد بدون طلب مسبق
            console.log('Setting up form submission handler');
            console.log('jQuery version:', $.fn.jquery);
            console.log('Form element:', $('#newContractForm')[0]);

            var shouldRedirectToInvoice = false;
            var invoiceCreateUrl = "{{ route('invoices.create') }}";

            $('#save-and-invoice').on('click', function() {
                shouldRedirectToInvoice = true;
                $('#newContractForm').trigger('submit');
            });

            $('#newContractForm').on('submit', function(e) {
                console.log('Form submit event triggered');
                e.preventDefault();
                console.log('Form submission started');

                var redirectToInvoice = shouldRedirectToInvoice === true;
                shouldRedirectToInvoice = false;

                // تحقق من التبويب النشط
                var isNewContractTab = $('#tab-new-pane').hasClass('show active');
                console.log('Active tab check:', {
                    isNewContractTab: isNewContractTab,
                    tabNewPaneActive: $('#tab-new-pane').hasClass('show active'),
                    tabOrderPaneActive: $('#tab-order-pane').hasClass('show active')
                });

                // تحقق من وجود عميل
                var clientId = $('#new_existing_client_id').val();
                var internalCompanyContract = isNewContractTab && $('#internal-company-contract').is(':checked');
                var maidId = $('#new_new_maid_id').val();
                var maidSelectionMode = getNewMaidMode();
                var contractType = 'new_contract';
                var isValid = true;
                var clientName = '';
                var clientPhone = '';
                var clientNationalityNum = '';
                var clientBirthdate = '';
                var clientPhone2 = '';
                var clientAddress = '';
                var clientCity = '';
                var clientEmail = '';
                var clientNationalityId = '';

                console.log('Initial values:', {
                    clientId: clientId,
                    maidId: maidId,
                    maidSelectionMode: maidSelectionMode,
                    contractType: contractType
                });

                // التحقق من الحقول فقط إذا كان التبويب الجديد نشط
                if (isNewContractTab) {
                    $('#new_existing_client_id').removeClass('is-invalid');
                    $('#err_new_existing_client_id').remove();
                    if (!internalCompanyContract) {

                    // تحقق الاسم ثلاثي
                    clientName = getTrimmedInputValue('#new_client_name');
                    console.log('Validating client name:', clientName);
                    if (!clientName) {
                        isValid = false;
                        $('#new_client_name').addClass('is-invalid');
                        $('#err_new_client_name').text('يرجى ادخال اسم العميل');
                        console.log('Client name validation failed: empty');
                    } else {
                        $('#new_client_name').removeClass('is-invalid');
                        $('#err_new_client_name').text('');
                        console.log('Client name validation passed');
                    }

                    // تحقق رقم الجوال
                    clientPhone = getTrimmedInputValue('#new_client_phone');
                    console.log('Validating client phone:', clientPhone);
                    if (!clientPhone) {
                        isValid = false;
                        $('#new_client_phone').addClass('is-invalid');
                        $('#err_new_client_phone').text('يرجى ادخال رقم الجوال');
                        console.log('Client phone validation failed: empty');
                    } else {
                        $('#new_client_phone').removeClass('is-invalid');
                        $('#err_new_client_phone').text('');
                        console.log('Client phone validation passed');
                    }

                    clientNationalityNum = getTrimmedInputValue('#new_client_national_num');
                    $('#new_client_national_num').removeClass('is-invalid');
                    $('#err_new_client_national_num').text('');

                    clientBirthdate = $('#new_client_birthdate').val() || '';
                    $('#new_client_birthdate').removeClass('is-invalid');
                    $('#new_client_birthdate').next('.invalid-feedback').remove();

                    clientPhone2 = getTrimmedInputValue('#new_client_phone2');
                    $('#new_client_phone2').removeClass('is-invalid');
                    $('#new_client_phone2').next('.invalid-feedback').remove();

                    // تحقق العنوان (اختياري)
                    clientAddress = getTrimmedInputValue('#new_client_address');
                    if (clientAddress) {
                        $('#new_client_address').removeClass('is-invalid');
                        $('#new_client_address').next('.invalid-feedback').remove();
                    }

                    clientCity = getTrimmedInputValue('#new_client_city');
                    $('#new_client_city').removeClass('is-invalid');
                    $('#new_client_city').next('.invalid-feedback').remove();

                    // تحقق البريد الإلكتروني (اختياري)
                    clientEmail = getTrimmedInputValue('#new_client_email');
                    if (clientEmail && !/^\S+@\S+\.\S+$/.test(clientEmail)) {
                        isValid = false;
                        $('#new_client_email').addClass('is-invalid');
                        $('#err_new_client_email').text('صيغة البريد الإلكتروني غير صحيحة');
                    } else {
                        $('#new_client_email').removeClass('is-invalid');
                        $('#err_new_client_email').text('');
                    }

                    // تحقق الجنسية (مطلوب في التبويب الجديد)
                    clientNationalityId = $('#new_client_nationality_id').val();
                    console.log('Validating client nationality:', clientNationalityId);
                    if (!clientNationalityId) {
                        isValid = false;
                        $('#new_client_nationality_id').addClass('is-invalid');
                        $('#err_new_client_nationality_id').text('يرجى اختيار الجنسية');
                        console.log('Client nationality validation failed: empty');
                    } else {
                        $('#new_client_nationality_id').removeClass('is-invalid');
                        $('#err_new_client_nationality_id').text('');
                        console.log('Client nationality validation passed');
                    }

                    }
                    if (maidSelectionMode === 'existing') {
                        if (!maidId) {
                            isValid = false;
                            $('#new_new_maid_id').addClass('is-invalid');
                            if ($('#err_new_new_maid_id').length === 0) {
                                $('#new-existing-maid-picker').append(
                                    '<div class="invalid-feedback d-block" id="err_new_new_maid_id">' + recruitmentContractTranslations.workerRequiredOrChangeMethod + '</div>'
                                );
                            }
                        } else {
                            $('#new_new_maid_id').removeClass('is-invalid');
                            $('#err_new_new_maid_id').remove();
                        }
                    } else if (maidSelectionMode === 'manual') {
                        var manualMaidName = $('#manual_maid_name').val().trim();
                        var manualMaidGender = $('#manual_maid_gender').val();
                        var manualMaidPassport = $('#manual_maid_passport_num').val().trim();
                        var manualMaidJobTitle = $('#manual_maid_job_title_id').val();
                        var manualMaidNationality = $('#manual_maid_nationality_id').val();
                        var manualMaidOffice = $('#manual_maid_office_id').val();

                        if (!manualMaidName) {
                            isValid = false;
                            $('#manual_maid_name').addClass('is-invalid');
                            $('#err_manual_maid_name').text(recruitmentContractTranslations.shortWorkerNameRequired);
                        } else {
                            $('#manual_maid_name').removeClass('is-invalid');
                            $('#err_manual_maid_name').text('');
                        }

                        if (!manualMaidGender) {
                            isValid = false;
                            $('#manual_maid_gender').addClass('is-invalid');
                            $('#err_manual_maid_gender').text(recruitmentContractTranslations.genderRequired);
                        } else {
                            $('#manual_maid_gender').removeClass('is-invalid');
                            $('#err_manual_maid_gender').text('');
                        }

                        if (!manualMaidPassport) {
                            isValid = false;
                            $('#manual_maid_passport_num').addClass('is-invalid');
                            $('#err_manual_maid_passport_num').text(recruitmentContractTranslations.passportRequired);
                        } else if (isManualPassportDuplicate) {
                            isValid = false;
                            $('#manual_maid_passport_num').addClass('is-invalid');
                            $('#err_manual_maid_passport_num')
                                .removeClass('valid-feedback')
                                .addClass('invalid-feedback')
                                .text(recruitmentContractTranslations.passportAlreadyExists);
                        } else {
                            $('#manual_maid_passport_num').removeClass('is-invalid');
                            if (!$('#manual_maid_passport_num').hasClass('is-valid')) {
                                $('#err_manual_maid_passport_num').text('');
                            }
                        }

                        if (!manualMaidJobTitle) {
                            isValid = false;
                            $('#manual_maid_job_title_id').addClass('is-invalid');
                            $('#err_manual_maid_job_title_id').text(recruitmentContractTranslations.professionRequired);
                        } else {
                            $('#manual_maid_job_title_id').removeClass('is-invalid');
                            $('#err_manual_maid_job_title_id').text('');
                        }

                        if (!manualMaidNationality) {
                            isValid = false;
                            $('#manual_maid_nationality_id').addClass('is-invalid');
                            $('#err_manual_maid_nationality_id').text(recruitmentContractTranslations.nationalityRequired);
                        } else {
                            $('#manual_maid_nationality_id').removeClass('is-invalid');
                            $('#err_manual_maid_nationality_id').text('');
                        }

                        if (!manualMaidOffice) {
                            isValid = false;
                            $('#manual_maid_office_id').addClass('is-invalid');
                            $('#err_manual_maid_office_id').text(recruitmentContractTranslations.externalOfficeRequired);
                        } else {
                            $('#manual_maid_office_id').removeClass('is-invalid');
                            $('#err_manual_maid_office_id').text('');
                        }
                    } else if (maidSelectionMode === 'postponed') {
                        // التحقق من الجنسية
                        var requiredMaidNationality = $('#required_maid_nationality').val();
                        console.log('Validating required maid nationality:', requiredMaidNationality);
                        if (!requiredMaidNationality) {
                            isValid = false;
                            $('#required_maid_nationality').addClass('is-invalid');
                            $('#err_required_maid_nationality').text(
                                'يرجى اختيار الجنسية المطلوبة للعامل/ة');
                            console.log('Required maid nationality validation failed: empty');
                        } else {
                            $('#required_maid_nationality').removeClass('is-invalid');
                            $('#err_required_maid_nationality').text('');
                            console.log('Required maid nationality validation passed');
                        }

                        // التحقق من الدين
                        var requiredMaidReligion = $('#required_maid_religion').val();
                        console.log('Validating required maid religion:', requiredMaidReligion);
                        if (!requiredMaidReligion) {
                            isValid = false;
                            $('#required_maid_religion').addClass('is-invalid');
                            $('#err_required_maid_religion').text('يرجى اختيار الدين المطلوب للعامل/ة');
                            console.log('Required maid religion validation failed: empty');
                        } else {
                            $('#required_maid_religion').removeClass('is-invalid');
                            $('#err_required_maid_religion').text('');
                            console.log('Required maid religion validation passed');
                        }

                        // التحقق من الحد الأدنى للعمر
                        var requiredMaidAgeMin = $('#required_maid_age_min').val();
                        console.log('Validating required maid age min:', requiredMaidAgeMin);
                        if (!requiredMaidAgeMin) {
                            isValid = false;
                            $('#required_maid_age_min').addClass('is-invalid');
                            $('#err_required_maid_age_min').text('يرجى إدخال الحد الأدنى للعمر');
                            console.log('Required maid age min validation failed: empty');
                        } else {
                            $('#required_maid_age_min').removeClass('is-invalid');
                            $('#err_required_maid_age_min').text('');
                            console.log('Required maid age min validation passed');
                        }

                        // التحقق من الحد الأقصى للعمر
                        var requiredMaidAgeMax = $('#required_maid_age_max').val();
                        console.log('Validating required maid age max:', requiredMaidAgeMax);
                        if (!requiredMaidAgeMax) {
                            isValid = false;
                            $('#required_maid_age_max').addClass('is-invalid');
                            $('#err_required_maid_age_max').text('يرجى إدخال الحد الأقصى للعمر');
                            console.log('Required maid age max validation failed: empty');
                        } else {
                            $('#required_maid_age_max').removeClass('is-invalid');
                            $('#err_required_maid_age_max').text('');
                            console.log('Required maid age max validation passed');
                        }

                        // التحقق من الحد الأدنى للخبرة
                        var requiredMaidExperienceMin = $('#required_maid_experience_min').val();
                        console.log('Validating required maid experience min:', requiredMaidExperienceMin);
                        if (!requiredMaidExperienceMin) {
                            isValid = false;
                            $('#required_maid_experience_min').addClass('is-invalid');
                            $('#err_required_maid_experience_min').text('يرجى إدخال الحد الأدنى للخبرة');
                            console.log('Required maid experience min validation failed: empty');
                        } else {
                            $('#required_maid_experience_min').removeClass('is-invalid');
                            $('#err_required_maid_experience_min').text('');
                            console.log('Required maid experience min validation passed');
                        }
                    }

                } // إغلاق شرط isNewContractTab

                // التحقق من الحقول المطلوبة للعقد (في كلا التبويبين)
                var contractDate, msanedNumber, visaNumber;

                if (isNewContractTab) {
                    contractDate = $('#tab-new-pane #contract_date').val();
                    msanedNumber = $('#tab-new-pane #msaned_number').val();
                    visaNumber = $('#tab-new-pane #visa_number').val();
                } else {
                    contractDate = $('#tab-order-pane #contract_date').val();
                    msanedNumber = $('#tab-order-pane #msaned_number').val();
                    visaNumber = $('#tab-order-pane #visa_number').val();
                }

                console.log('Validating contract fields:', {
                    contractDate: contractDate,
                    msanedNumber: msanedNumber,
                    visaNumber: visaNumber
                });

                if (!contractDate) {
                    isValid = false;
                    if (isNewContractTab) {
                        $('#tab-new-pane #contract_date').addClass('is-invalid');
                        $('<div class="invalid-feedback">يرجى ادخال تاريخ العقد</div>').insertAfter(
                            '#tab-new-pane #contract_date');
                    } else {
                        $('#tab-order-pane #contract_date').addClass('is-invalid');
                        $('<div class="invalid-feedback">يرجى ادخال تاريخ العقد</div>').insertAfter(
                            '#tab-order-pane #contract_date');
                        isValid = true;
                    }
                    console.log('Contract date validation failed: empty');
                } else {
                    if (isNewContractTab) {
                        $('#tab-new-pane #contract_date').removeClass('is-invalid');
                        $('#tab-new-pane #contract_date').next('.invalid-feedback').remove();
                    } else {
                        $('#tab-order-pane #contract_date').removeClass('is-invalid');
                        $('#tab-order-pane #contract_date').next('.invalid-feedback').remove();
                    }
                    console.log('Contract date validation passed');
                }

                if (!msanedNumber) {
                    isValid = false;
                    if (isNewContractTab) {
                        $('#tab-new-pane #msaned_number').addClass('is-invalid');
                        $('<div class="invalid-feedback">يرجى ادخال رقم مساند</div>').insertAfter(
                            '#tab-new-pane #msaned_number');
                    } else {
                        $('#tab-order-pane #msaned_number').addClass('is-invalid');
                        $('<div class="invalid-feedback">يرجى ادخال رقم مساند</div>').insertAfter(
                            '#tab-order-pane #msaned_number');
                        isValid = true;
                    }
                    console.log('Msaned number validation failed: empty');
                } else {
                    if (isNewContractTab) {
                        $('#tab-new-pane #msaned_number').removeClass('is-invalid');
                        $('#tab-new-pane #msaned_number').next('.invalid-feedback').remove();
                    } else {
                        $('#tab-order-pane #msaned_number').removeClass('is-invalid');
                        $('#tab-order-pane #msaned_number').next('.invalid-feedback').remove();
                    }
                    console.log('Msaned number validation passed');
                }

                if (!visaNumber) {
                    isValid = false;
                    if (isNewContractTab) {
                        $('#tab-new-pane #visa_number').addClass('is-invalid');
                        $('<div class="invalid-feedback">يرجى ادخال رقم التأشيرة</div>').insertAfter(
                            '#tab-new-pane #visa_number');
                    } else {
                        $('#tab-order-pane #visa_number').addClass('is-invalid');
                        $('<div class="invalid-feedback">يرجى ادخال رقم التأشيرة</div>').insertAfter(
                            '#tab-order-pane #visa_number');
                        isValid = true;
                    }
                    console.log('Visa number validation failed: empty');
                } else {
                    if (isNewContractTab) {
                        $('#tab-new-pane #visa_number').removeClass('is-invalid');
                        $('#tab-new-pane #visa_number').next('.invalid-feedback').remove();
                    } else {
                        $('#tab-order-pane #visa_number').removeClass('is-invalid');
                        $('#tab-order-pane #visa_number').next('.invalid-feedback').remove();
                    }
                    console.log('Visa number validation passed');
                }

                // التحقق من المسوق
                var marketerId = '';
                if (isNewContractTab) {
                    marketerId = $('#tab-new-pane #marketer_id').val();
                } else {
                    marketerId = $('#tab-order-pane #marketer_id').val();
                }

                if (!marketerId) {
                    // المسوق إلزامي في التبويبين؛ لا استثناء لتبويب الطلب.
                    isValid = false;
                    var marketerPane = isNewContractTab ? '#tab-new-pane' : '#tab-order-pane';
                    $(marketerPane + ' #marketer_id').addClass('is-invalid');
                    $(marketerPane + ' #err_marketer_id').text('يرجى اختيار المسوق');
                    console.log('Marketer validation failed: empty');
                } else {
                    if (isNewContractTab) {
                        $('#tab-new-pane #marketer_id').removeClass('is-invalid');
                        $('#tab-new-pane #err_marketer_id').text('');
                    } else {
                        $('#tab-order-pane #marketer_id').removeClass('is-invalid');
                        $('#tab-order-pane #err_marketer_id').text('');
                    }
                    console.log('Marketer validation passed');
                }

                console.log('Validation completed. isValid:', isValid);
                console.log('All field values:', {
                    clientName: $('#new_client_name').val(),
                    clientPhone: $('#new_client_phone').val(),
                    clientNationalityNum: $('#new_client_national_num').val(),
                    clientBirthdate: $('#new_client_birthdate').val(),
                    clientPhone2: $('#new_client_phone2').val(),
                    clientAddress: $('#new_client_address').val(),
                    clientCity: $('#new_client_city').val(),
                    contractDate: $('#tab-new-pane #contract_date').val(),
                    msanedNumber: $('#tab-new-pane #msaned_number').val(),
                    visaNumber: $('#tab-new-pane #visa_number').val(),
                    clientNationalityId: $('#new_client_nationality_id').val(),
                    maidSelectionMode: maidSelectionMode
                });

                if (!isValid) {
                    console.log('Validation failed, stopping submission');
                    var $firstInvalid = $('#tab-new-pane .is-invalid').first();
                    if ($firstInvalid.length) {
                        var $scrollTarget = $firstInvalid.hasClass('select2-hidden-accessible') ?
                            $firstInvalid.next('.select2-container') :
                            $firstInvalid;

                        $('html, body').animate({
                            scrollTop: $scrollTarget.offset().top - 120
                        }, 400);
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'يرجى مراجعة البيانات',
                        text: 'توجد حقول مطلوبة أو غير مكتملة قبل حفظ العقد.',
                        confirmButtonText: 'حسناً'
                    });
                    return false;
                }

                console.log('Validation passed, preparing form data');

                // تجهيز البيانات للإرسال
                var formData = new FormData();
                var csrfToken = $('input[name=_token]').val();
                console.log('CSRF Token:', csrfToken);
                formData.append('_token', csrfToken);
                formData.append('contract_type', contractType);
                if (clientId && /^\d+$/.test(String(clientId))) {
                    formData.append('client_id', clientId);
                    formData.append('new_existing_client_id', clientId);
                }
                formData.append('maid_selection_mode', maidSelectionMode);
                formData.append('maid_id', maidSelectionMode === 'existing' && maidId ? maidId : '');

                console.log('Basic form data prepared:', {
                    contract_type: contractType,
                    client_type: isNewContractTab ? 'new_client' : 'ex_client',
                    client_id: clientId,
                    maid_id: maidSelectionMode === 'existing' ? maidId : '',
                    maid_selection_mode: maidSelectionMode
                });

                // أضف بيانات العميل حسب التبويب النشط
                if (isNewContractTab) {
                    formData.append('client_type', 'new_client');
                    formData.append('account_client_type', $('#tab-new-pane input[name="account_client_type"]:checked').val() || 'person');
                    formData.append('client_name', clientName);
                    formData.append('client_phone', clientPhone);
                    formData.append('client_email', clientEmail);
                    formData.append('client_nationality_id', $('#new_client_nationality_id').val() || '');
                    formData.append('new_client_national_num', clientNationalityNum);
                    formData.append('new_client_license_number', $('#new_client_license_number').val() || '');
                    formData.append('new_client_birthdate', $('#new_client_birthdate').val());
                    formData.append('new_client_phone2', $('#new_client_phone2').val());
                    formData.append('new_client_address', $('#new_client_address').val());
                    formData.append('new_client_city', $('#new_client_city').val());
                    formData.append('new_client_beds_num', $('#new_client_beds_num').val());
                    formData.append('new_client_family_num', $('#new_client_family_num').val());
                    formData.append('new_client_children', $('#new_client_children').val());
                    formData.append('new_client_job', $('#new_client_job').val());
                } else {
                    // للتبويب القديم، استخدم البيانات من الطلب المسبق
                    var selectedOrder = $('#order_id').val();
                    formData.append('order_id', selectedOrder);
                    formData.append('client_type', 'ex_client');
                    formData.append('account_client_type', $('#tab-order-pane input[name="account_client_type"]:checked').val() || 'person');
                    formData.append('client_national_num', $('#client_national_num').val() || '');
                    formData.append('client_license_number', $('#client_license_number').val() || '');
                    ['name', 'phone', 'email', 'nationality_id', 'birthdate'].forEach(function(field) {
                        formData.append('client_' + field, $('#tab-order-pane #client_' + field).val() || '');
                    });
                    ['phone2', 'address', 'city', 'beds_num', 'family_num', 'children', 'job'].forEach(function(field) {
                        formData.append('client_' + field, $('#tab-order-pane #' + field).val() || '');
                    });
                }

                console.log('Client data prepared:', {
                    isNewContractTab: isNewContractTab,
                    client_name: isNewContractTab ? clientName : 'From existing order',
                    client_phone: isNewContractTab ? clientPhone : 'From existing order',
                    client_email: isNewContractTab ? clientEmail : 'From existing order',
                    client_nationality_id: isNewContractTab ? $('#new_client_nationality_id').val() :
                        'From existing order',
                    client_nationality_num: isNewContractTab ? clientNationalityNum :
                        'From existing order',
                    client_birthdate: isNewContractTab ? $('#new_client_birthdate').val() :
                        'From existing order',
                    client_phone2: isNewContractTab ? $('#new_client_phone2').val() :
                        'From existing order',
                    client_address: isNewContractTab ? $('#new_client_address').val() :
                        'From existing order',
                    client_city: isNewContractTab ? $('#new_client_city').val() :
                        'From existing order'
                });

                // أضف بيانات العامل/ة حسب التبويب النشط
                if (isNewContractTab) {
                    var selectedMaidId = $('#new_new_maid_id').val();
                    if (maidSelectionMode === 'existing' && selectedMaidId) {
                        // إذا تم اختيار عامل/ة
                        formData.append('maid_name', $('#new_maid_name').val());
                        formData.append('maid_passport', $('#new_maid_passport').val());
                        formData.append('maid_religion', $('#new_maid_religion').val());
                        formData.append('maid_job', $('#new_maid_job').val());
                        formData.append('maid_nationality', $('#new_maid_nationality').val());
                        formData.append('maid_salary', $('#new_maid_salary').val());
                        formData.append('maid_experience_years', $('#new_maid_experience_years').val());
                        formData.append('maid_age', $('#new_maid_age').val());
                        formData.append('maid_selection_postponed', '0'); // تم اختيار عامل/ة
                    } else if (maidSelectionMode === 'manual') {
                        formData.append('maid_selection_postponed', '1');
                        formData.append('maid_name', '');
                        formData.append('maid_passport', '');
                        formData.append('maid_religion', '');
                        formData.append('maid_job', '');
                        formData.append('maid_nationality', '');
                        formData.append('maid_salary', '');
                        formData.append('maid_experience_years', '');
                        formData.append('maid_age', '');
                        formData.append('manual_maid_name', $('#manual_maid_name').val());
                        formData.append('manual_maid_gender', $('#manual_maid_gender').val() || '');
                        formData.append('manual_maid_passport_num', $('#manual_maid_passport_num').val());
                        formData.append('manual_maid_job_title_id', $('#manual_maid_job_title_id').val() || '');
                        formData.append('manual_maid_nationality_id', $('#manual_maid_nationality_id').val() || '');
                        formData.append('manual_maid_office_id', $('#manual_maid_office_id').val() || '');

                        var manualMaidAttachment = $('#manual_maid_attachment')[0];
                        if (manualMaidAttachment && manualMaidAttachment.files.length > 0) {
                            formData.append('manual_maid_attachment', manualMaidAttachment.files[0]);
                        }
                    } else {
                        // إذا تم تأجيل اختيار العامل/ة
                        formData.append('maid_selection_postponed', '1'); // تم تأجيل اختيار العامل/ة
                        formData.append('maid_name', '');
                        formData.append('maid_passport', '');
                        formData.append('maid_religion', '');
                        formData.append('maid_job', '');
                        formData.append('maid_nationality', '');
                        formData.append('maid_salary', '');
                        formData.append('maid_experience_years', '');
                        formData.append('maid_age', '');
                    }
                } else {
                    // للتبويب القديم، استخدم maid_id من الطلب المسبق
                    var selectedMaidId = $('#maid_id').val();
                    formData.append('maid_id', selectedMaidId);
                    formData.append('maid_selection_postponed', '0'); // لم يتم تأجيل الاختيار
                }

                console.log('Maid data prepared:', {
                    isNewContractTab: isNewContractTab,
                    maid_selection_mode: isNewContractTab ? maidSelectionMode : 'existing',
                    maid_selection_postponed: isNewContractTab ? (maidSelectionMode === 'existing' ? '0' : '1') : '0',
                    maid_name: isNewContractTab
                        ? (maidSelectionMode === 'existing'
                            ? $('#new_maid_name').val()
                            : (maidSelectionMode === 'manual' ? $('#manual_maid_name').val() : 'Postponed'))
                        : 'From existing order',
                    maid_passport: isNewContractTab
                        ? (maidSelectionMode === 'existing'
                            ? $('#new_maid_passport').val()
                            : (maidSelectionMode === 'manual' ? $('#manual_maid_passport_num').val() : 'Postponed'))
                        : 'From existing order'
                });

                // أضف بيانات العقد - تأكد من الحصول على القيم من التبويب الصحيح
                var contractDate, msanedNumber, visaNumber, contractDuration, tafwedNumber, visaMiladDate,
                    visaHijryDate;

                if (isNewContractTab) {
                    contractDate = $('#tab-new-pane #contract_date').val();
                    msanedNumber = $('#tab-new-pane #msaned_number').val();
                    visaNumber = $('#tab-new-pane #visa_number').val();
                    contractDuration = $('#tab-new-pane #contract_duration').val();
                    tafwedNumber = $('#tab-new-pane #tafwed_number').val();
                    visaMiladDate = $('#tab-new-pane #visa_milad_date').val();
                    visaHijryDate = $('#tab-new-pane #visa_hijry_date').val();
                } else {
                    contractDate = $('#tab-order-pane #contract_date').val();
                    msanedNumber = $('#tab-order-pane #msaned_number').val();
                    visaNumber = $('#tab-order-pane #visa_number').val();
                    contractDuration = $('#tab-order-pane #contract_duration').val();
                    tafwedNumber = $('#tab-order-pane #tafwed_number').val();
                    visaMiladDate = $('#tab-order-pane #visa_milad_date').val();
                    visaHijryDate = $('#tab-order-pane #visa_hijry_date').val();
                }

                console.log('Contract data being sent:', {
                    contract_date: contractDate,
                    msaned_number: msanedNumber,
                    visa_number: visaNumber,
                    contract_duration: contractDuration
                });

                formData.append('contract_date', contractDate || '');
                formData.append('msaned_number', msanedNumber || '');
                formData.append('visa_number', visaNumber || '');
                formData.append('contract_duration', contractDuration || '2');

                console.log('Contract data prepared:', {
                    isNewContractTab: isNewContractTab,
                    contract_date: contractDate || '',
                    msaned_number: msanedNumber || '',
                    visa_number: visaNumber || '',
                    contract_duration: contractDuration || '2'
                });

                // أضف الحقول الإضافية للعقد
                formData.append('tafwed_number', tafwedNumber || '');
                formData.append('visa_milad_date', visaMiladDate || '');
                formData.append('visa_hijry_date', visaHijryDate || '');
                formData.append('contract_note', $('#new_contract_note').val() || '');

                // إضافة المسوق
                var marketerId = '';
                if (isNewContractTab) {
                    marketerId = $('#tab-new-pane #marketer_id').val();
                } else {
                    marketerId = $('#tab-order-pane #marketer_id').val();
                }
                formData.append('marketer_id', marketerId || '');

                // إضافة الحقول المطلوبة للعامل/ة (إذا لم يتم اختيار عامل/ة)
                if ($('#new-no-maid-alert').is(':visible')) {
                    formData.append('required_maid_nationality', $('#required_maid_nationality').val() ||
                        '');
                    formData.append('required_maid_religion', $('#required_maid_religion').val() || '');
                    formData.append('required_maid_age_min', $('#required_maid_age_min').val() || '');
                    formData.append('required_maid_age_max', $('#required_maid_age_max').val() || '');
                    formData.append('required_maid_experience_min', $('#required_maid_experience_min')
                        .val() || '');
                    formData.append('required_maid_job_title', $('#required_maid_job_title').val() || '');
                    formData.append('required_maid_notes', $('#required_maid_notes').val() || '');
                }

                console.log('Additional contract data prepared:', {
                    isNewContractTab: isNewContractTab,
                    tafwed_number: tafwedNumber || '',
                    visa_milad_date: visaMiladDate || '',
                    visa_hijry_date: visaHijryDate || ''
                });
                // زر الحفظ
                var submitButton = $('#newContractSubmitBtn');
                var invoiceButton = $('#save-and-invoice');
                console.log('Submit button found:', submitButton.length);
                console.log('Submit button current state:', {
                    disabled: submitButton.prop('disabled'),
                    html: submitButton.html()
                });
                submitButton.prop('disabled', true).html(
                    '<i class="fa fa-spinner fa-spin me-2"></i>جاري الحفظ...');

                if (redirectToInvoice) {
                    invoiceButton.prop('disabled', true);
                }

                var actionUrl = $(this).attr('action');
                if (internalCompanyContract) {
                    formData.set('client_type', 'internal_company');
                    formData.set('company_profile_id', @json($internalCompany?->id));
                    ['client_id', 'new_existing_client_id', 'order_id'].forEach(function(key) { formData.delete(key); });
                }
                console.log('Sending AJAX request to:', actionUrl);
                console.log('Current window location:', window.location.href);
                console.log('Form data entries:');
                for (var pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                console.log('About to send AJAX request...');
                console.log('AJAX settings:', {
                    url: $(this).attr('action'),
                    type: 'POST',
                    processData: false,
                    contentType: false
                });

                // إرسال البيانات
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('AJAX success response:', response);
                        if (response.success) {
                            handleContractCreated(response, redirectToInvoice, invoiceCreateUrl);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء إنشاء العقد',
                                confirmButtonText: 'حسناً'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX error:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText,
                            responseJSON: xhr.responseJSON
                        });

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            showContractValidationErrors(xhr.responseJSON.errors, '#newContractForm');
                            return;
                        }

                        let errorMessage = contractRequestErrorMessage(xhr);
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: errorMessage,
                            confirmButtonText: 'حسناً'
                        });
                    },
                    complete: function() {
                        console.log('AJAX request completed');
                        submitButton.prop('disabled', false).html(
                            '<i class="fa-solid fa-floppy-disk me-2"></i>حفظ');
                        invoiceButton.prop('disabled', false);
                    }
                });
            });
        });
    </script>

    @include('admin.ingaz.RecruitmentContract.js')
@endsection
