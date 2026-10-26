@extends('admin.layout.indexs.index')
@section('page-title')
    إضافة عقد تأجير جديد
@endsection

@section('style')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <style>
        .rent-contract-create {
            --rent-primary: #0f6ec8;
            --rent-primary-dark: #0b4f94;
            --rent-primary-soft: #eff6ff;
            --rent-border: #d8e4f0;
            --rent-surface: #ffffff;
            --rent-surface-alt: #f6faff;
            --rent-text: #17324b;
            --rent-text-muted: #6b7b8d;
            --rent-shadow: 0 20px 50px rgba(15, 52, 94, 0.08);
        }

        .rent-contract-create.card {
            border: 0;
            border-radius: 24px;
            overflow: hidden;
            background: transparent;
            box-shadow: var(--rent-shadow);
        }

        .rent-contract-create .card-body {
            background: linear-gradient(180deg, #f4f8fc 0%, #fbfdff 160px, #ffffff 100%);
            padding: 1.75rem;
        }

        .rent-contract-create .contract-header {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--rent-primary-dark) 0%, var(--rent-primary) 55%, #38a3ff 100%);
            padding: 2rem 1.75rem;
            color: #fff;
        }

        .rent-contract-create .contract-header::before,
        .rent-contract-create .contract-header::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
            pointer-events: none;
        }

        .rent-contract-create .contract-header::before {
            width: 240px;
            height: 240px;
            top: -120px;
            right: -40px;
        }

        .rent-contract-create .contract-header::after {
            width: 170px;
            height: 170px;
            bottom: -85px;
            left: 8%;
        }

        .rent-contract-create .contract-header .text-center {
            position: relative;
            z-index: 1;
        }

        .rent-contract-create .contract-header h4 {
            font-size: 1.45rem;
            font-weight: 700;
        }

        .rent-contract-create .contract-header p {
            font-size: 0.95rem;
        }

        .rent-contract-create .selection-banner {
            border: 1px dashed #93c5fd;
            background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 100%);
            color: #1e3a8a;
            border-radius: 16px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 24px rgba(59, 130, 246, 0.08);
        }

        .rent-contract-create .selection-toolbar {
            background: var(--rent-surface);
            border: 1px solid var(--rent-border);
            border-radius: 20px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 14px 34px rgba(15, 52, 94, 0.06);
        }

        .rent-contract-create .selection-toolbar-note {
            margin: 0;
            color: var(--rent-text-muted);
            font-size: 0.92rem;
            line-height: 1.7;
        }

        .rent-contract-create .quick-actions {
            margin-top: 1.1rem;
            padding-top: 1rem;
            border-top: 1px dashed #d7e3ef;
        }

        .rent-contract-create .quick-create-client {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            border-radius: 12px;
            font-weight: 700;
        }

        .rent-contract-create .contract-form-grid > [class*="col-"] {
            display: flex;
        }

        .rent-contract-create .form-section {
            width: 100%;
            background: var(--rent-surface);
            border: 1px solid var(--rent-border);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 14px 30px rgba(15, 52, 94, 0.06);
        }

        .rent-contract-create .section-header {
            display: flex;
            align-items: flex-start;
            gap: 0.9rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e7eef6;
        }

        .rent-contract-create .section-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--rent-primary) 0%, #41a4ff 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
            box-shadow: 0 12px 22px rgba(15, 110, 200, 0.18);
        }

        .rent-contract-create .section-title {
            margin: 0 0 0.2rem;
            font-size: 1.08rem;
            font-weight: 700;
            color: var(--rent-text);
        }

        .rent-contract-create .section-subtitle {
            margin: 0;
            color: var(--rent-text-muted);
            font-size: 0.88rem;
            line-height: 1.6;
        }

        .rent-contract-create .form-section > .card-body {
            padding: 0.25rem 0 0;
            background: transparent;
        }

        .rent-contract-create .form-label {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--rent-text);
            margin-bottom: 0.45rem;
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }

        .rent-contract-create .form-control,
        .rent-contract-create .form-select {
            min-height: 46px;
            border: 1px solid var(--rent-border);
            border-radius: 12px;
            background: #fbfdff;
            color: var(--rent-text);
            padding: 0.7rem 0.85rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            box-shadow: none;
        }

        .rent-contract-create textarea.form-control {
            min-height: 120px;
        }

        .rent-contract-create .form-control:focus,
        .rent-contract-create .form-select:focus {
            border-color: var(--rent-primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(15, 110, 200, 0.12);
        }

        .rent-contract-create .form-control[readonly] {
            background: #f1f6fb;
            color: #5b6b7c;
        }

        .rent-contract-create .form-control:disabled,
        .rent-contract-create .form-select:disabled {
            background: #eef3f8;
            color: #7e8b98;
        }

        .rent-contract-create .text-muted,
        .rent-contract-create small.text-muted {
            color: var(--rent-text-muted) !important;
        }

        .rent-contract-create .invalid-feedback {
            font-size: 0.8rem;
        }

        .rent-contract-create .select2-selection.is-invalid {
            border-color: #dc3545 !important;
        }

        .rent-contract-create .btn {
            border-radius: 12px;
            font-weight: 600;
        }

        .rent-contract-create .select2-container {
            width: 100% !important;
        }

        .rent-contract-create .select2-container--default .select2-selection--single {
            height: 46px;
            border: 1px solid var(--rent-border);
            border-radius: 12px;
            background: #fbfdff;
            display: flex;
            align-items: center;
            padding: 0 0.9rem;
        }

        .rent-contract-create .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--rent-text);
            line-height: 44px;
            padding-left: 0;
            padding-right: 0;
        }

        .rent-contract-create .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px;
            left: 10px;
            right: auto;
        }

        .rent-contract-create .select2-container--default.select2-container--focus .select2-selection--single,
        .rent-contract-create .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--rent-primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(15, 110, 200, 0.12);
        }

        .rent-contract-create .select2-dropdown {
            border: 1px solid var(--rent-border);
            border-radius: 14px;
            box-shadow: 0 16px 36px rgba(15, 52, 94, 0.12);
            overflow: hidden;
        }

        .rent-contract-create .package-summary-card {
            border: 1px solid var(--rent-border);
            border-radius: 18px;
            background: linear-gradient(180deg, #ffffff 0%, #f6faff 100%);
            padding: 1rem;
            height: 100%;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }

        .rent-contract-create .package-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(135px, 1fr));
            gap: 0.85rem;
            margin-top: 1rem;
        }

        .rent-contract-create .package-stat {
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e6edf5;
            padding: 1rem 0.85rem;
            text-align: center;
        }

        .rent-contract-create .package-stat .value {
            display: block;
            font-weight: 700;
            color: #1f2937;
        }

        .rent-contract-create .package-stat .label {
            display: block;
            margin-top: .35rem;
            color: #6b7280;
            font-size: .82rem;
        }

        .rent-contract-create .payment-box {
            border: 1px solid var(--rent-border);
            border-radius: 18px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 1.15rem;
        }

        .rent-contract-create .moyasar-payment-methods {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            align-items: center;
        }

        .rent-contract-create .moyasar-payment-method {
            min-height: 30px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.55rem;
            border: 1px solid #d7e3ef;
            border-radius: 8px;
            background: #fff;
            color: var(--rent-text);
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1;
        }

        .rent-contract-create .moyasar-payment-method i {
            font-size: 1.05rem;
        }

        .rent-contract-create .moyasar-payment-method.visa i {
            color: #1a1f71;
        }

        .rent-contract-create .moyasar-payment-method.mastercard i {
            color: #eb001b;
        }

        .rent-contract-create .moyasar-payment-method.apple-pay i {
            color: #111827;
        }

        .rent-contract-create .moyasar-payment-method.mada .mada-mark {
            min-width: 26px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            background: #0f766e;
            color: #fff;
            font-size: 0.68rem;
            font-weight: 800;
        }

        .rent-contract-create .payment-summary {
            background: var(--rent-primary-soft);
            border: 1px solid #cddff6;
            border-radius: 14px;
            padding: 0.95rem 1rem;
        }

        .rent-contract-create .payment-summary strong {
            color: #0f172a;
        }

        .rent-contract-action-panel.card {
            border: 1px solid #d8e4f0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(15, 52, 94, 0.08);
        }

        .rent-contract-action-panel .card-body {
            padding: 1.25rem 1.5rem;
            background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
        }

        .rent-contract-action-panel h6 {
            margin: 0 0 0.25rem;
            font-size: 1rem;
            font-weight: 700;
            color: #17324b;
        }

        .rent-contract-action-panel p {
            margin: 0;
            color: #6b7b8d;
            font-size: 0.9rem;
        }

        .rent-contract-action-panel .btn-primary {
            border: 0;
            border-radius: 14px;
            padding: 0.95rem 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0f6ec8 0%, #2794eb 100%);
            box-shadow: 0 14px 24px rgba(15, 110, 200, 0.22);
        }

        .rent-contract-action-panel .btn-primary:hover,
        .rent-contract-action-panel .btn-primary:focus {
            background: linear-gradient(135deg, #0c5eaa 0%, #1d84d6 100%);
        }

        @media (max-width: 991.98px) {
            .rent-contract-create .contract-form-grid > [class*="col-"] {
                display: block;
            }
        }

        @media (max-width: 767.98px) {
            .rent-contract-create .card-body {
                padding: 1rem;
            }

            .rent-contract-create .contract-header {
                padding: 1.5rem 1rem;
            }

            .rent-contract-create .contract-header h4 {
                font-size: 1.2rem;
            }

            .rent-contract-create .form-section {
                padding: 1rem;
            }

            .rent-contract-create .section-header {
                gap: 0.7rem;
            }

            .rent-contract-create .section-icon {
                width: 40px;
                height: 40px;
                border-radius: 12px;
            }

            .rent-contract-action-panel .card-body {
                padding: 1rem;
            }

            .rent-contract-action-panel .btn-primary {
                width: 100%;
            }
        }

        /* نافذة نجاح إنشاء العقد: هوية كليك (كحلي وأخضر) */
        .ck-done.swal2-popup { width: min(560px, 94vw); padding: 0 0 1.25rem; border-radius: 18px; overflow: hidden; font-family: inherit; }
        .ck-done .swal2-icon { margin: 1.6rem auto .4rem; transform: scale(.82); }
        .ck-done .swal2-title { padding: .2rem 1.5rem 0; font-size: 1.45rem; font-weight: 700; color: #16233f; }
        .ck-done .swal2-html-container { margin: .6rem 1.5rem 0; padding: 0; text-align: right; overflow: visible; }
        .ck-done .swal2-actions { gap: .6rem; margin-top: 1.25rem; padding: 0 1.5rem; width: 100%; flex-wrap: nowrap; }
        .ck-done .swal2-actions button { flex: 1; margin: 0; min-height: 46px; border-radius: 11px; font-weight: 600; font-size: .95rem; display: inline-flex; align-items: center; justify-content: center; gap: .45rem; box-shadow: none !important; }
        .ck-done .ck-done-confirm { background: #16233f !important; color: #fff; border: 0; }
        .ck-done .ck-done-confirm:hover { background: #22345c !important; }
        .ck-done .ck-done-cancel { background: #fff !important; color: #16233f; border: 1px solid #d9e0ea !important; }
        .ck-done .ck-done-cancel:hover { background: #f4f6fa !important; }
        .ck-done-lead { margin: 0 0 1rem; text-align: center; color: #5f6c80; font-size: .95rem; line-height: 1.8; }
        .ck-done-chips { display: flex; justify-content: center; flex-wrap: wrap; gap: .5rem; margin-bottom: 1.1rem; }
        .ck-done-chip { display: inline-flex; align-items: center; gap: .4rem; padding: .3rem .8rem; border-radius: 999px; font-size: .82rem; font-weight: 600; background: #eef1f7; color: #16233f; }
        .ck-done-chip i { color: #7ab648; }
        .ck-pay { border: 1px solid #e3e8f0; border-radius: 14px; overflow: hidden; background: #fff; }
        .ck-pay-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .75rem 1rem; background: #f6f8fb; border-bottom: 1px solid #e3e8f0; }
        .ck-pay-title { display: flex; align-items: center; gap: .55rem; font-weight: 700; color: #16233f; font-size: .92rem; }
        .ck-pay-title .ck-pay-logo { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: #16233f; color: #a3d46f; font-size: .85rem; }
        .ck-pay-state { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .65rem; border-radius: 999px; font-size: .76rem; font-weight: 700; white-space: nowrap; }
        .ck-pay-state.is-sent { background: #f0f7e8; color: #4f7f2b; }
        .ck-pay-state.is-warn { background: #fdf6e7; color: #a26c0f; }
        .ck-pay-body { padding: .9rem 1rem 1rem; }
        .ck-pay-note { display: flex; gap: .5rem; align-items: flex-start; margin-bottom: .75rem; padding: .55rem .75rem; border-radius: 10px; font-size: .83rem; line-height: 1.7; background: #fdf6e7; color: #8a5a0b; }
        .ck-pay-link { display: flex; align-items: stretch; border: 1px solid #d9e0ea; border-radius: 11px; overflow: hidden; direction: ltr; }
        .ck-pay-link input { flex: 1; min-width: 0; border: 0; padding: .6rem .8rem; font-size: .85rem; color: #1d2839; background: #fbfcfe; font-family: ui-monospace, Consolas, monospace; outline: 0; }
        .ck-pay-link button, .ck-pay-link a { display: inline-flex; align-items: center; gap: .35rem; padding: 0 .9rem; border: 0; border-left: 1px solid #d9e0ea; background: #fff; color: #16233f; font-size: .84rem; font-weight: 600; text-decoration: none; cursor: pointer; white-space: nowrap; }
        .ck-pay-link button:hover, .ck-pay-link a:hover { background: #f0f7e8; color: #4f7f2b; }
        .ck-pay-link button.is-copied { background: #7ab648; color: #fff; }
        .ck-pay-hint { margin: .55rem 0 0; color: #7b879a; font-size: .78rem; }
        .ck-docs { margin-top: 1rem; font-size: .8rem; color: #7b879a; display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; justify-content: center; }
        .ck-docs span { padding: .15rem .55rem; border-radius: 6px; background: #f6f8fb; border: 1px solid #e3e8f0; color: #33465c; }
        @media (max-width: 480px) {
            .ck-done .swal2-actions { flex-direction: column; }
            .ck-done .swal2-actions button { width: 100%; }
            .ck-pay-link { flex-wrap: wrap; }
            .ck-pay-link input { flex-basis: 100%; border-bottom: 1px solid #d9e0ea; }
            .ck-pay-link button, .ck-pay-link a { flex: 1; justify-content: center; min-height: 40px; border-left: 0; }
        }
    </style>
@endsection

@section('content')
    @php
        $moyasarEnabled = (bool) ($moyasarEnabled ?? false);
        $rentContractPaymentTypes = ['cash', 'bank_transfer', 'mada', 'tabby', 'tamara', 'pay_later', 'other'];
        if ($moyasarEnabled) {
            $rentContractPaymentTypes[] = 'moyasar';
        }
        $selectedOrderPaymentMethod = optional($selectedOrder ?? null)->payment_method ?? '';
        $defaultSelectedPaymentType = in_array($selectedOrderPaymentMethod, $rentContractPaymentTypes, true)
            ? $selectedOrderPaymentMethod
            : '';

        $initialOrderId = (string) old('order_id', optional($selectedOrder ?? null)->id ?: request('order_id', ''));
        $initialClientId = (string) old('new_existing_client_id', optional($selectedOrder ?? null)->user_id ?: ($selectedClientId ?? ''));
        $initialMaidId = (string) old('maid_id', optional($selectedOrder ?? null)->maid_id ?: request('maid_id', ''));
        $initialPackageId = (string) old('package_id', data_get($selectedOrder ?? null, 'details.package_id', ''));
        $initialPaymentType = (string) old('payment_type', $defaultSelectedPaymentType);
        $initialPaymentStatus = (string) old('payment_status', '');
        $initialPaidAmount = (string) old('paid_amount', '');
        $saudiCountryId = optional($countries->firstWhere('code', 'SA'))->id;
        $isOrderLinked = (bool) filled(optional($selectedOrder ?? null)->id);
        $isClientAutoSelected = (bool) filled($selectedClientId ?? null);
    @endphp

    @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CREATE))
        <form id="rentalContractForm" method="post" action="{{ route('RentContract.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="contract_type" value="new_contract">
            <input type="hidden" name="client_type" value="new_client">
            <input type="hidden" name="order_id" value="{{ $initialOrderId }}">

            <div class="card mb-4 rent-contract-create">
                <div class="contract-header">
                    <div class="text-center">
                        <h4 class="mb-2">
                            <i class="fas fa-handshake me-2"></i>
                            إنشاء عقد تأجير جديد
                        </h4>
                        <p class="mb-0 opacity-75">قم بتعبئة البيانات المطلوبة لإنشاء عقد تأجير احترافي</p>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($selectedOrder) && $selectedOrder)
                        <div class="selection-banner">
                            <strong>تم فتح الصفحة من طلب إيجار رقم #{{ $selectedOrder->order_code ?? $selectedOrder->id }}</strong>
                            <div class="small mt-1">
                                العميل: {{ $selectedOrder->user?->name ?? 'غير محدد' }}
                                |
                                العاملة: {{ $selectedOrder->maid?->name ?? 'غير محدد' }}
                                @if($selectedOrder->details?->package)
                                    |
                                    الباقة: {{ $selectedOrder->details->package->title }}
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="selection-toolbar">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-9">
                                <label for="new_existing_client_id" class="form-label">اختر عميل
                                    <small class="text-muted">(اختياري)</small></label>
                                <select name="new_existing_client_id" id="new_existing_client_id"
                                        class="form-select ajax-client-select" style="width:100%"
                                        data-placeholder="ابحث بالاسم أو الجوال أو الهوية">
                                    <option value="" {{ !isset($selectedClient) || !$selectedClient ? 'selected' : '' }}></option>
                                    @if(isset($selectedClient) && $selectedClient)
                                        <option value="{{ $selectedClient->id }}"
                                                selected
                                                data-name="{{ $selectedClient->name }}"
                                                data-phone="{{ $selectedClient->phone }}"
                                                data-email="{{ $selectedClient->email }}"
                                                data-nationality="{{ $selectedClient->nationality_id }}"
                                                data-account-client-type="{{ $selectedClient->client_type ?? 'person' }}"
                                                data-document-label="{{ $selectedClient->documentNumberLabel() }}"
                                                data-tax-number="{{ $selectedClient->tax_number }}"
                                                data-license-number="{{ $selectedClient->license_number }}"
                                                data-national-num="{{ $selectedClient->documentNumber() }}"
                                                data-birthdate="{{ $selectedClient->birthdate }}"
                                                data-phone2="{{ $selectedClient->phone2 }}"
                                                data-address="{{ $selectedClient->address }}"
                                                data-city="{{ $selectedClient->city }}"
                                                data-beds-num="{{ $selectedClient->rooms_num }}"
                                                data-family-num="{{ $selectedClient->family_num }}"
                                                data-children="{{ $selectedClient->children }}"
                                                data-job="{{ $selectedClient->job ?: $selectedClient->profession }}">
                                            {{ $selectedClient->name }} - {{ $selectedClient->phone }} - {{ $selectedClient->searchableDocumentNumber() }}
                                        </option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <button type="button" id="use-direct-client-data"
                                        class="btn btn-outline-primary quick-create-client w-100"
                                        title="إدخال بيانات عميل جديد داخل نفس الشاشة">
                                    <i class="fas fa-user-plus"></i>
                                    عميل جديد من البيانات
                                </button>
                            </div>
                            <div class="col-12">
                                <label for="maid_id" class="form-label">اختر عاملة <span
                                        class="text-danger">*</span></label>
                                <select name="maid_id" id="maid_id" class="form-select select2" style="width:100%">
                                    <option value="" selected>اختر عاملة...</option>
                                    @if(isset($availableRentalMaids))
                                        @foreach($availableRentalMaids as $maid)
                                            <option value="{{$maid->id}}"
                                                    data-name="{{$maid->name}}"
                                                    data-passport="{{$maid->passport_num}}"
                                                    data-religion="{{$maid->religion ? $maid->religion->title : ''}}"
                                                    data-job="{{$maid->jobTitles && $maid->jobTitles->first() ? $maid->jobTitles->first()->title : ''}}"
                                                    data-nationality-id="{{ $maid->nationality_id }}"
                                                    data-nationality="{{$maid->nationality ? $maid->nationality->sub_title : ''}}"
                                                    data-salary="{{ $maid->resolved_salary ?? $maid->salary }}"
                                                    data-experience="{{$maid->experience_years}}"
                                                    data-request-type-label="{{$maid->request_type_label}}"
                                                    data-age="{{$maid->age}}">
                                                {{$maid->name}} - {{$maid->passport_num}}
                                                - {{$maid->nationality ? $maid->nationality->sub_title : ''}}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="quick-actions d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                            <p class="selection-toolbar-note">اختر عميلاً موجوداً لتعبئة بياناته تلقائياً، أو اضغط «عميل جديد من البيانات» وأدخل بياناته داخل نفس النموذج، ثم اختر العاملة.</p>
                        </div>
                    </div>

                    <div class="row g-4 contract-form-grid">
                        <!-- بيانات العميل والعاملة (تظهر بعد الاختيار) -->
                        <div class="col-12">
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
                                <div class="card-body">
                                    <div class="row">
                                    <div class="col-12 mb-2">
                                        <label class="form-label d-block">{{ helperTrans('ingaz.client_type') }} <span class="text-danger">*</span></label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input js-rent-account-client-type" type="radio"
                                                       name="account_client_type" id="rent_account_client_type_person"
                                                       value="person" checked>
                                                <label class="form-check-label" for="rent_account_client_type_person">شخص</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input js-rent-account-client-type" type="radio"
                                                       name="account_client_type" id="rent_account_client_type_company"
                                                       value="company">
                                                <label class="form-check-label" for="rent_account_client_type_company">شركة</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">اسم العميل <span class="text-danger">*</span></label>
                                        <input type="text" id="new_client_name" name="new_client_name"
                                               class="form-control" data-validation="required">
                                        <div class="invalid-feedback" id="err_new_client_name"></div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">رقم الجوال <span class="text-danger">*</span></label>
                                        <input type="tel" id="new_client_phone" name="new_client_phone"
                                               class="form-control" data-validation="required" dir="rtl">
                                        <div class="invalid-feedback" id="err_new_client_phone"></div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label for="client_nationality_id"
                                               class="form-label">{{helperTrans("ingaz.nationality_id")}} <span
                                                class="text-danger">*</span></label>
                                        <select id="client_nationality_id" name="client_nationality_id"
                                                class="form-select form-control select2" data-validation="required">
                                            <option value="">{{helperTrans('ingaz.choose')}}</option>
                                            @foreach($countries as $country)
                                                <option
                                                    value="{{$country->id}}" {{ (string) old('client_nationality_id', $saudiCountryId) === (string) $country->id ? 'selected' : '' }}>{{$country->name_ar}}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="err_client_nationality_id"></div>
                                    </div>
                                    <div class="col-md-6 mb-2 js-rent-client-document-field">
                                        <label class="form-label">رقم الهوية <span class="text-danger">*</span></label>
                                        <input type="text" id="new_client_national_num" name="new_client_national_num"
                                               class="form-control" data-validation="required">
                                        <div class="invalid-feedback" id="err_new_client_national_num"></div>
                                    </div>
                                    <div class="col-md-6 mb-2 js-rent-company-field d-none">
                                        <label class="form-label">{{ helperTrans('ingaz.license_number') }} <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="text" id="new_client_license_number" name="new_client_license_number"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">المدينة <span
                                                class="text-danger">*</span></label>
                                        <select id="new_client_city" name="new_client_city"
                                                class="form-select select2" data-validation="required"
                                                data-placeholder="اختر مدينة سعودية">
                                            <option value="">اختر المدينة</option>
                                            @foreach($saudiCities as $city)
                                                <option value="{{ $city->name_ar }}" {{ old('new_client_city') === $city->name_ar ? 'selected' : '' }}>
                                                    {{ $city->name_ar }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="err_new_client_city"></div>
                                        <div class="contract-city-actions mt-2">
                                            <a href="{{ route('cities.create') }}" target="_blank"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-plus-circle ms-1"></i>إضافة مدينة سعودية
                                            </a>
                                            <small class="text-muted">ابحث باسم المدينة أو أضفها إذا لم تكن موجودة.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">البريد الإلكتروني <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="email" id="new_client_email" name="new_client_email"
                                               class="form-control">
                                        <div class="invalid-feedback" id="err_new_client_email"></div>
                                    </div>
                                    <div class="col-md-6 mb-2 js-rent-person-field">
                                        <label class="form-label">تاريخ الميلاد <span class="text-danger">*</span></label>
                                        <input type="date" id="new_client_birthdate" name="new_client_birthdate"
                                               class="form-control" data-validation="required" max="{{ date('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">رقم جوال إضافي <span class="text-danger">*</span></label>
                                        <input type="tel" id="new_client_phone2" name="new_client_phone2"
                                               class="form-control" data-validation="required">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">العنوان التفصيلي <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="text" id="new_client_address" name="new_client_address"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2 js-rent-person-field">
                                        <label class="form-label">عدد الغرف <small class="text-muted">(اختياري)</small></label>
                                        <input type="text" id="new_client_beds_num" name="new_client_beds_num"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2 js-rent-person-field">
                                        <label class="form-label">عدد أفراد الأسرة <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="text" id="new_client_family_num" name="new_client_family_num"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2 js-rent-person-field">
                                        <label class="form-label">عدد الأطفال <small class="text-muted">(اختياري - تحت
                                                سن 12)</small></label>
                                        <input type="number" id="new_client_children" name="new_client_children"
                                               class="form-control" max="50">
                                    </div>
                                    <div class="col-md-6 mb-2 js-rent-person-field">
                                        <label class="form-label">المهنة <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="text" id="new_client_job" name="new_client_job"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2 js-rent-person-field">
                                        <label class="form-label">الدخل الشهري <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="number" id="monthly_income" name="monthly_income"
                                               class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                        <div class="col-12">
                            <div class="form-section">
                                <div class="section-header">
                                    <div class="section-icon">
                                        <i class="fas fa-female"></i>
                                    </div>
                                    <div>
                                        <h5 class="section-title">بيانات العاملة</h5>
                                        <p class="section-subtitle">البيانات الشخصية والمهنية</p>
                                    </div>
                                </div>
                                <div class="card-body" id="maid-section-card">
                                    <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                                        <input type="text" id="maid_name" name="maid_name" class="form-control" data-validation="required" required>
                                        <div class="invalid-feedback" id="err_maid_name"></div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">رقم الجواز <span class="text-danger">*</span></label>
                                        <input type="text" id="maid_passport" name="maid_passport" class="form-control" data-validation="required" required>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">الديانة</label>
                                        <input type="text" id="maid_religion" name="maid_religion" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">المهنة</label>
                                        <input type="text" id="maid_job" name="maid_job" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">نوع الطلب</label>
                                        <input type="text" id="maid_request_type" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">الجنسية</label>
                                        <input type="text" id="maid_nationality" name="maid_nationality"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">سنوات الخبرة</label>
                                        <input type="text" id="maid_experience_years" name="maid_experience_years"
                                               class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">العمر</label>
                                        <input type="text" id="maid_age" name="maid_age" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>

                        <div class="col-12">
                            <div class="form-section">
                                <div class="section-header">
                                    <div class="section-icon">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <div>
                                        <h5 class="section-title">الباقة والدفع</h5>
                                        <p class="section-subtitle">اختيار الباقة أولاً ثم تحديد طريقة الدفع وحالتها</p>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label for="package_id" class="form-label">اختر الباقة <span class="text-danger">*</span></label>
                                        <select name="package_id" id="package_id" class="form-select select2" data-validation="required" disabled>
                                            <option value="">اختر العاملة أولاً لعرض الباقات المناسبة</option>
                                            @foreach($packages ?? [] as $package)
                                                <option value="{{ $package->id }}"
                                                        data-nationality-id="{{ $package->nationality_id }}"
                                                        data-nationality-label="{{ $package->nationality?->sub_title ?? $package->nationality?->title ?? '' }}"
                                                        data-title="{{ $package->title }}"
                                                        data-days="{{ $package->days_count }}"
                                                        data-rental-type="{{ $package->rental_type ?? 'monthly' }}"
                                                        data-duration-count="{{ $package->duration_count ?: max(1, (int) ceil(($package->days_count ?: 30) / 30)) }}"
                                                        data-duration-unit="{{ $package->duration_unit ?? 'month' }}"
                                                        data-duration-label="{{ $package->duration_label }}"
                                                        data-working-hours="{{ $package->working_hours_per_day }}"
                                                        data-original-price="{{ $package->original_price }}"
                                                        data-price="{{ $package->original_price }}"
                                                        data-total="{{ $package->price_with_tax }}">
                                                    {{ $package->title }} - {{ $package->rental_type_label }} - {{ $package->duration_count ?: max(1, (int) ceil(($package->days_count ?: 30) / 30)) }} {{ $package->duration_unit_label }} - {{ number_format((float) $package->price_with_tax, 2) }} ريال شامل الضريبة
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-2">يتم تصفية الباقات حسب جنسية العاملة المختارة وإمكانات سير الإيجار.</small>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="package-summary-card" id="packageSummaryCard">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <h6 class="mb-1" id="packageSummaryTitle">لم يتم اختيار باقة بعد</h6>
                                                    <p class="text-muted mb-0" id="packageSummaryHint">اختر عاملة متاحة من سير الإيجار ثم اختر الباقة المناسبة لها.</p>
                                                </div>
                                                <i class="fas fa-receipt fs-3 text-primary opacity-50"></i>
                                            </div>
                                            <div class="package-summary-grid">
                                                <div class="package-stat">
                                                    <span class="value" id="packageSummaryDays">-</span>
                                                    <span class="label">مدة الباقة</span>
                                                </div>
                                                <div class="package-stat">
                                                    <span class="value" id="packageSummaryBeforeTax">-</span>
                                                    <span class="label">قبل الضريبة</span>
                                                </div>
                                                <div class="package-stat">
                                                    <span class="value" id="packageSummaryAfterTax">-</span>
                                                    <span class="label">شامل الضريبة</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="payment-box">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label for="monthly_salary" class="form-label">راتب العاملة الشهري <span class="text-danger">*</span></label>
                                                    <input type="number" id="monthly_salary" name="monthly_salary"
                                                           class="form-control" step="0.01" min="0.01"
                                                           value="{{ old('monthly_salary') }}" data-validation="required" required>
                                                    <div class="invalid-feedback" id="err_monthly_salary"></div>
                                                    <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small" role="alert">
                                                        <i class="fas fa-triangle-exclamation ms-1"></i>
                                                        الراتب معفى من الضريبة. أدخل الراتب الصحيح لأن الصفر يجعل الضريبة تُحتسب على كامل قيمة العقد.
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="payment_type" class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                                                    <select name="payment_type" id="payment_type" class="form-select" data-validation="required" disabled>
                                                        <option value="">اختر الطريقة</option>
                                                        <option value="cash">نقداً</option>
                                                        <option value="bank_transfer">تحويل بنكي</option>
                                                        <option value="mada">مدى</option>
                                                        <option value="tabby">تابي</option>
                                                        <option value="tamara">تمارا</option>
                                                        @if($moyasarEnabled)
                                                            <option value="moyasar">ميسر: فيزا، مدى، ماستر كارد، أبل باي</option>
                                                        @endif
                                                        <option value="pay_later">الدفع لاحقاً</option>
                                                        <option value="other">أخرى</option>
                                                    </select>
                                                    @if($moyasarEnabled)
                                                        <div id="moyasarPaymentIcons" class="moyasar-payment-methods d-none mt-2">
                                                            <span class="moyasar-payment-method visa" title="فيزا">
                                                                <i class="fab fa-cc-visa"></i>
                                                                <span>فيزا</span>
                                                            </span>
                                                            <span class="moyasar-payment-method mada" title="مدى">
                                                                <span class="mada-mark">مدى</span>
                                                                <span>مدى</span>
                                                            </span>
                                                            <span class="moyasar-payment-method mastercard" title="ماستر كارد">
                                                                <i class="fab fa-cc-mastercard"></i>
                                                                <span>ماستر كارد</span>
                                                            </span>
                                                            <span class="moyasar-payment-method apple-pay" title="أبل باي">
                                                                <i class="fab fa-apple-pay"></i>
                                                                <span>أبل باي</span>
                                                            </span>
                                                        </div>
                                                    @endif
                                                    {{-- تنبيه ميسر --}}
                                                    <div id="moyasarNotice" class="alert alert-info d-none mt-2 mb-0 py-2 px-3 small">
                                                        <i class="fas fa-paper-plane me-1"></i>
                                                        سيتم إنشاء رابط دفع تلقائياً وإرساله للعميل عبر <strong>SMS</strong> بعد حفظ العقد.
                                                        <br><small class="text-muted">تأكد من إدخال رقم جوال العميل بشكل صحيح.</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="payment_status" class="form-label">حالة الدفع <span class="text-danger">*</span></label>
                                                    <select name="payment_status" id="payment_status" class="form-select" data-validation="required" disabled>
                                                        <option value="">اختر الحالة</option>
                                                        <option value="paid">تم الدفع</option>
                                                        <option value="partial">مدفوع جزئياً</option>
                                                        <option value="pending">معلق</option>
                                                        <option value="later">الدفع لاحقاً</option>
                                                        <option value="bank_pending">بانتظار التحويل</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="payment_receipt" class="form-label">إيصال الدفع</label>
                                                    <input type="file" id="payment_receipt" name="payment_receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                                </div>
                                                <div class="col-md-4 d-none" id="paidAmountWrapper">
                                                    <label for="paid_amount" class="form-label">المبلغ المدفوع</label>
                                                    <input type="number" id="paid_amount" name="paid_amount" class="form-control" step="0.01" min="0">
                                                </div>
                                                <div class="col-md-4 d-none" id="receiptAccountWrapper">
                                                    <label for="receipt_account_id" class="form-label">البنك / حساب السداد <span class="text-danger">*</span></label>
                                                    <select id="receipt_account_id" name="receipt_account_id" class="form-select">
                                                        <option value="">اختر الحساب الذي سيصدر عليه سند القبض</option>
                                                        @foreach($receiptAccounts as $account)
                                                            <option value="{{ $account->id }}" @selected((string) old('receipt_account_id') === (string) $account->id)>
                                                                {{ $account->code }} - {{ $account->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if($receiptAccounts->isEmpty())
                                                        <small class="text-danger">لا توجد حسابات سداد صالحة في الدليل المحاسبي.</small>
                                                    @endif
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="remaining_amount" class="form-label">المبلغ المتبقي</label>
                                                    <input type="number" id="remaining_amount" name="remaining_amount" class="form-control" step="0.01" min="0" readonly>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="payment-summary h-100 d-flex flex-column justify-content-center">
                                                        <div>إجمالي العقد: <strong id="paymentTotalValue">0.00 ريال</strong></div>
                                                        <div class="mt-1">المتبقي بعد الدفع: <strong id="paymentRemainingText">0.00 ريال</strong></div>
                                                        <small class="text-muted mt-2">يمكن إدخال مبلغ جزئي عند اختيار حالة "مدفوع جزئياً".</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- بيانات العقد -->
                        <div class="col-12">
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
                                    <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">تاريخ العقد <span class="text-danger">*</span></label>
                                        <input type="date" id="contract_date" name="contract_date" class="form-control"
                                               value="{{ date('Y-m-d') }}" data-validation="required">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">تكلفة الفترة <span
                                                class="text-danger">*</span></label>
                                        <input type="number" id="monthly_cost" name="monthly_cost" class="form-control"
                                               step="0.01" data-validation="required">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">مدة العقد بالشهور للحساب <span
                                                class="text-danger">*</span></label>
                                        <input type="number" id="contract_duration_months"
                                               name="contract_duration_months" class="form-control"
                                               min="1" data-validation="required">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">مدة العقد بالأيام</label>
                                        <input type="number" id="contract_duration_days" name="contract_duration_days"
                                               class="form-control" readonly>
                                        <small class="text-muted">(يتم حسابها تلقائياً)</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">تكلفة العقد الإجمالية</label>
                                        <input type="number" id="total_contract_value" name="total_contract_value"
                                               class="form-control" readonly>
                                        <small class="text-muted">(يتم حسابها تلقائياً)</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">ساعات العمل اليومية</label>
                                        <input type="number" id="working_hours" name="working_hours"
                                               class="form-control" value="8" min="1" max="24">
                                        <small class="text-muted">(افتراضي: 8 ساعات)</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">الإنجاز من <span class="text-danger">*</span></label>
                                        <input type="date" id="ingaz_from" name="ingaz_from" class="form-control"
                                               value="{{ date('Y-m-d') }}" data-validation="required">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">الإنجاز إلى</label>
                                        <input type="date" id="ingaz_to" name="ingaz_to" class="form-control" readonly>
                                        <small class="text-muted">(يتم حسابها تلقائياً)</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">المسوق <span class="text-danger">*</span></label>
                                        <select name="marketer_id" id="marketer_id" class="form-select"
                                                data-validation="required" required>
                                            <option value="">اختر المسوق</option>
                                            {{-- بدون اختيار مسبق للمستخدم الحالي: المسوق اختيار صريح مطلوب --}}
                                            @foreach($marketers as $marketer)
                                                <option value="{{ $marketer->id }}"
                                                        {{ old('marketer_id') == $marketer->id ? 'selected' : '' }}>
                                                    {{ $marketer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="err_marketer_id"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">نوع التأجير <small
                                                class="text-muted">(اختياري)</small></label>
                                        <select name="rental_type" id="rental_type" class="form-select">
                                            <option value="">اختر نوع التأجير</option>
                                            <option value="daily">يومي</option>
                                            <option value="hourly">بالساعات</option>
                                            <option value="weekly">أسبوعي</option>
                                            <option value="monthly">شهري</option>
                                            <option value="yearly">سنوي</option>
                                        </select>
                                    </div>

                                    {{-- <div class="col-6">
                                        <label class="form-label">نقل الخدمات <small class="text-muted">(اختياري)</small></label>
                                        <select name="sponsorship_transfer" id="sponsorship_transfer" class="form-select">
                                            <option value="">اختر</option>
                                            <option value="yes">نعم</option>
                                            <option value="no">لا</option>
                                        </select>
                                    </div> --}}

                                    <div class="col-md-6">
                                        <label class="form-label">الأجر بالساعة <small
                                                class="text-muted">(اختياري)</small></label>
                                        <input type="number" id="hourly_rate" name="hourly_rate" class="form-control"
                                               step="0.01">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">ملاحظات <small
                                                class="text-muted">(اختياري)</small></label>
                                        <textarea id="notes" name="notes" class="form-control" rows="3"
                                                  placeholder="أي تفاصيل إضافية مرتبطة بالعقد أو الدفع أو الباقة"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- زر الحفظ -->
            <div class="col-12 mt-4">
                <div class="card rent-contract-action-panel">
                    <div class="card-body d-flex flex-column flex-lg-row align-items-center justify-content-between gap-3">
                        <div class="text-center text-lg-start">
                            <h6>مراجعة البيانات ثم حفظ العقد</h6>
                            <p>سيتم إنشاء عقد التأجير اعتماداً على البيانات المحددة أعلاه مع تفاصيل الباقة والدفع الحالية.</p>
                        </div>
                        <button class="btn btn-primary btn-lg px-5" type="submit" id="rentalContractSubmitBtn">
                            <i class="fa-solid fa-floppy-disk me-2"></i>
                            حفظ عقد التأجير
                        </button>
                    </div>
                </div>
            </div>
        </form>

    @else
        @include('components.no_permissions')
    @endif

@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            const toast = Swal.mixin({
                toast: true,
                position: 'top-start',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            });

            const initialState = {
                clientId: @json($initialClientId),
                maidId: @json($initialMaidId),
                packageId: @json($initialPackageId),
                paymentType: @json($initialPaymentType),
                paymentStatus: @json($initialPaymentStatus),
                paidAmount: @json($initialPaidAmount),
                orderLinked: @json($isOrderLinked),
                clientAutoSelected: @json($isClientAutoSelected),
            };
            const saudiCountryId = @json((string) $saudiCountryId);

            $('.select2').not('#new_existing_client_id').select2({
                placeholder: 'اختر...',
                allowClear: true,
                dir: 'rtl',
                width: '100%'
            });

            $('#new_existing_client_id').select2({
                placeholder: 'ابحث باسم العميل أو الجوال أو رقم الهوية...',
                allowClear: true,
                dir: 'rtl',
                width: '100%',
                minimumInputLength: 0,
                ajax: {
                    url: @json(route('RentContract.existing.clients')),
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            search: params.term || ''
                        };
                    },
                    processResults: function (response) {
                        return {
                            results: (response.clients || []).map(function (client) {
                                const documentNumber = client.document_number || client.national_num || client.tax_number || '';
                                return {
                                    id: client.id,
                                    text: [client.name, client.phone, documentNumber].filter(Boolean).join(' - '),
                                    client: client
                                };
                            })
                        };
                    },
                    cache: true
                }
            });

            function formatCurrency(value) {
                return `${(parseFloat(value) || 0).toFixed(2)} ريال`;
            }

            function normalizeText(value) {
                return String(value || '')
                    .trim()
                    .toLowerCase()
                    .replace(/[أإآ]/g, 'ا')
                    .replace(/ى/g, 'ي')
                    .replace(/ة/g, 'ه');
            }

            function formatDateForInput(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function addDaysToDate(dateString, days) {
                if (!dateString || !days) {
                    return '';
                }

                const parts = dateString.split('-').map(Number);
                if (parts.length !== 3) {
                    return '';
                }

                const date = new Date(parts[0], parts[1] - 1, parts[2]);
                date.setDate(date.getDate() + days);

                return formatDateForInput(date);
            }

            function resetPackageSummary(message = 'اختر عاملة متاحة من سير الإيجار ثم اختر الباقة المناسبة لها.') {
                $('#packageSummaryTitle').text('لم يتم اختيار باقة بعد');
                $('#packageSummaryHint').text(message);
                $('#packageSummaryDays').text('-');
                $('#packageSummaryBeforeTax').text('-');
                $('#packageSummaryAfterTax').text('-');
            }

            function isCompanyClientType(value) {
                return String(value || '').toLowerCase() === 'company';
            }

            function selectedRentClientDocumentNumber(selectedOption) {
                if (isCompanyClientType(selectedOption.data('account-client-type'))) {
                    return selectedOption.data('tax-number') || selectedOption.data('national-num') || '';
                }

                return selectedOption.data('national-num') || '';
            }

            function applyRentClientDocumentMode(selectedOption) {
                const isCompany = isCompanyClientType(selectedOption.data('account-client-type'));
                const documentLabel = selectedOption.data('document-label') || (isCompany ? @json(helperTrans('ingaz.tax_number')) : @json(helperTrans('ingaz.national_num')));
                const $documentInput = $('#new_client_national_num');
                const $documentWrapper = $documentInput.closest('.js-rent-client-document-field, .col-md-6, .col-lg-6');
                const $personFieldWrappers = $('#new_client_birthdate, #new_client_beds_num, #new_client_family_num, #new_client_children, #new_client_job, #monthly_income')
                    .map(function () {
                        return $(this).closest('.js-rent-person-field, .col-md-6, .col-lg-6')[0];
                    });
                const $companyFieldWrappers = $('#new_client_license_number')
                    .map(function () {
                        return $(this).closest('.js-rent-company-field, .col-md-6, .col-lg-6')[0];
                    });

                $documentWrapper.find('label').first().html(documentLabel + ' <span class="text-danger">*</span>');
                $personFieldWrappers.toggleClass('d-none', isCompany);
                $personFieldWrappers.find('input, select, textarea').prop('disabled', isCompany);
                $companyFieldWrappers.toggleClass('d-none', !isCompany);
                $companyFieldWrappers.find('input, select, textarea').prop('disabled', !isCompany);
                $('input[name="account_client_type"][value="' + (isCompany ? 'company' : 'person') + '"]').prop('checked', true);
                $documentInput.data('is-company-client', isCompany ? 1 : 0);
                $('#new_client_license_number').val(selectedOption.data('license-number') || '');

                if (isCompany) {
                    $personFieldWrappers.find('input, select, textarea').val('').removeClass('is-invalid');
                } else {
                    $companyFieldWrappers.find('input, select, textarea').val('').removeClass('is-invalid');
                }
            }

            function resetRentClientDocumentMode() {
                const $documentInput = $('#new_client_national_num');
                const $personFieldWrappers = $('#new_client_birthdate, #new_client_beds_num, #new_client_family_num, #new_client_children, #new_client_job, #monthly_income')
                    .map(function () {
                        return $(this).closest('.js-rent-person-field, .col-md-6, .col-lg-6')[0];
                    });
                const $companyFieldWrappers = $('#new_client_license_number')
                    .map(function () {
                        return $(this).closest('.js-rent-company-field, .col-md-6, .col-lg-6')[0];
                    });

                $documentInput.closest('.js-rent-client-document-field, .col-md-6, .col-lg-6')
                    .find('label').first().html(@json(helperTrans('ingaz.national_num')) + ' <span class="text-danger">*</span>');
                $personFieldWrappers.removeClass('d-none');
                $personFieldWrappers.find('input, select, textarea').prop('disabled', false);
                $companyFieldWrappers.addClass('d-none');
                $companyFieldWrappers.find('input, select, textarea').prop('disabled', true).val('');
                $('input[name="account_client_type"][value="person"]').prop('checked', true);
                $documentInput.data('is-company-client', 0);
            }

            function fillClientData(selectedOption) {
                $('#new_client_name').val(selectedOption.data('name') || '');
                $('#new_client_phone').val(selectedOption.data('phone') || '');
                $('#new_client_email').val(selectedOption.data('email') || '');
                $('#client_nationality_id').val(selectedOption.data('nationality') || '').trigger('change');
                $('#new_client_national_num').val(selectedRentClientDocumentNumber(selectedOption));
                $('#new_client_birthdate').val(selectedOption.data('birthdate') || '');
                $('#new_client_phone2').val(selectedOption.data('phone2') || '');
                $('#new_client_address').val(selectedOption.data('address') || '');
                $('#new_client_city').val(selectedOption.data('city') || '').trigger('change');
                $('#new_client_beds_num').val(selectedOption.data('beds-num') || '');
                $('#new_client_family_num').val(selectedOption.data('family-num') || '');
                $('#new_client_children').val(selectedOption.data('children') || '');
                $('#new_client_job').val(selectedOption.data('job') || '');
                applyRentClientDocumentMode(selectedOption);
            }

            function clearClientData() {
                $('#new_client_name, #new_client_phone, #new_client_email, #new_client_national_num, #new_client_license_number, #new_client_birthdate, #new_client_phone2, #new_client_address, #new_client_beds_num, #new_client_family_num, #new_client_children, #new_client_job, #monthly_income').val('');
                $('#new_client_city').val('').trigger('change');
                $('#client_nationality_id').val(saudiCountryId).trigger('change');
                resetRentClientDocumentMode();
            }

            function applyClientDataToOption($option, client) {
                const data = {
                    name: client.name || '',
                    phone: client.phone || '',
                    email: client.email || '',
                    nationality: client.nationality_id || '',
                    accountClientType: client.client_type || 'person',
                    documentLabel: client.document_label || '',
                    taxNumber: client.tax_number || '',
                    licenseNumber: client.license_number || '',
                    nationalNum: client.document_number || client.national_num || '',
                    birthdate: client.birthdate || '',
                    phone2: client.phone2 || '',
                    address: client.address || '',
                    city: client.city || '',
                    bedsNum: client.rooms_num || '',
                    familyNum: client.family_num || '',
                    children: client.children || '',
                    job: client.job || client.profession || ''
                };

                $option
                    .attr('data-name', data.name).data('name', data.name)
                    .attr('data-phone', data.phone).data('phone', data.phone)
                    .attr('data-email', data.email).data('email', data.email)
                    .attr('data-nationality', data.nationality).data('nationality', data.nationality)
                    .attr('data-account-client-type', data.accountClientType).data('account-client-type', data.accountClientType)
                    .attr('data-document-label', data.documentLabel).data('document-label', data.documentLabel)
                    .attr('data-tax-number', data.taxNumber).data('tax-number', data.taxNumber)
                    .attr('data-license-number', data.licenseNumber).data('license-number', data.licenseNumber)
                    .attr('data-national-num', data.nationalNum).data('national-num', data.nationalNum)
                    .attr('data-birthdate', data.birthdate).data('birthdate', data.birthdate)
                    .attr('data-phone2', data.phone2).data('phone2', data.phone2)
                    .attr('data-address', data.address).data('address', data.address)
                    .attr('data-city', data.city).data('city', data.city)
                    .attr('data-beds-num', data.bedsNum).data('beds-num', data.bedsNum)
                    .attr('data-family-num', data.familyNum).data('family-num', data.familyNum)
                    .attr('data-children', data.children).data('children', data.children)
                    .attr('data-job', data.job).data('job', data.job);
            }

            $('input[name="account_client_type"]').on('change', function () {
                const selectedClientType = $(this).val();
                applyRentClientDocumentMode({
                    data: function (key) {
                        if (key === 'account-client-type') {
                            return selectedClientType;
                        }

                        if (key === 'document-label') {
                            return selectedClientType === 'company'
                                ? @json(helperTrans('ingaz.tax_number'))
                                : @json(helperTrans('ingaz.national_num'));
                        }

                        return '';
                    }
                });
            });

            function fillMaidData(selectedOption) {
                $('#maid_name').val(selectedOption.data('name') || '');
                $('#maid_passport').val(selectedOption.data('passport') || '');
                $('#maid_religion').val(selectedOption.data('religion') || '');
                $('#maid_job').val(selectedOption.data('job') || '');
                $('#maid_request_type').val(selectedOption.data('request-type-label') || '');
                $('#maid_nationality').val(selectedOption.data('nationality') || '');
                $('#maid_experience_years').val(selectedOption.data('experience') || '');
                $('#maid_age').val(selectedOption.data('age') || '');

                if (!$('#monthly_salary').val() && selectedOption.data('salary')) {
                    $('#monthly_salary').val(selectedOption.data('salary'));
                }
            }

            function clearMaidData() {
                $('#maid_name, #maid_passport, #maid_religion, #maid_job, #maid_request_type, #maid_nationality, #maid_experience_years, #maid_age').val('');
            }

            function calculateIngazTo() {
                const ingazFrom = $('#ingaz_from').val();
                const days = parseInt($('#contract_duration_days').val(), 10) || 0;
                $('#ingaz_to').val(addDaysToDate(ingazFrom, days));
            }

            function calculateContractValue() {
                const monthlyCost = parseFloat($('#monthly_cost').val()) || 0;
                const months = parseInt($('#contract_duration_months').val(), 10) || 0;
                const totalValue = monthlyCost * months;
                $('#total_contract_value').val(totalValue ? totalValue.toFixed(2) : '');
                syncPaymentSummary();
            }

            function syncPaymentSummary() {
                const totalValue = parseFloat($('#total_contract_value').val()) || 0;
                const status = $('#payment_status').val();
                let paidAmount = parseFloat($('#paid_amount').val()) || 0;

                if (status === 'paid') {
                    paidAmount = totalValue;
                    $('#paid_amount').val(totalValue ? totalValue.toFixed(2) : '0.00');
                } else if (!['partial', 'paid'].includes(status)) {
                    paidAmount = 0;
                    $('#paid_amount').val('0.00');
                }

                paidAmount = Math.min(paidAmount, totalValue);
                const remainingAmount = Math.max(totalValue - paidAmount, 0);

                $('#remaining_amount').val(remainingAmount.toFixed(2));
                $('#paymentTotalValue').text(formatCurrency(totalValue));
                $('#paymentRemainingText').text(formatCurrency(remainingAmount));
            }

            function updatePaymentState() {
                const hasPackage = Boolean($('#package_id').val());
                $('#payment_type, #payment_status').prop('disabled', !hasPackage);

                if (!hasPackage) {
                    $('#payment_type').val('');
                    $('#payment_status').val('');
                    $('#payment_status option').prop('disabled', false);
                    $('#paidAmountWrapper').addClass('d-none');
                    $('#paid_amount').prop('readonly', false).val('0.00');
                    $('#remaining_amount').val('0.00');
                    $('#paymentTotalValue, #paymentRemainingText').text('0.00 ريال');
                    $('#receiptAccountWrapper').addClass('d-none');
                    $('#receipt_account_id').prop('required', false);
                    return;
                }

                enforcePaymentCompatibility();

                const paymentStatus = $('#payment_status').val();
                const hasRecordedPayment = ['paid', 'partial'].includes(paymentStatus);
                $('#receiptAccountWrapper').toggleClass('d-none', !hasRecordedPayment);
                $('#receipt_account_id').prop('required', hasRecordedPayment);
                if (paymentStatus === 'paid') {
                    $('#paidAmountWrapper').removeClass('d-none');
                    $('#paid_amount').prop('readonly', true);
                } else if (paymentStatus === 'partial') {
                    $('#paidAmountWrapper').removeClass('d-none');
                    $('#paid_amount').prop('readonly', false);
                } else {
                    $('#paidAmountWrapper').addClass('d-none');
                    $('#paid_amount').prop('readonly', false);
                }

                syncPaymentSummary();
            }

            function enforcePaymentCompatibility() {
                const paymentType = $('#payment_type').val();
                const $paymentStatus = $('#payment_status');

                $paymentStatus.find('option').prop('disabled', false);

                if (paymentType === 'moyasar') {
                    $paymentStatus.val('pending');
                    $paymentStatus.find('option').not('[value=""], [value="pending"]').prop('disabled', true);
                } else if (paymentType === 'pay_later') {
                    $paymentStatus.val('later');
                    $paymentStatus.find('option').not('[value=""], [value="later"]').prop('disabled', true);
                } else {
                    $paymentStatus.find('option[value="later"]').prop('disabled', true);

                    if (paymentType !== 'bank_transfer') {
                        $paymentStatus.find('option[value="bank_pending"]').prop('disabled', true);
                    }

                    if ($paymentStatus.val() === 'later' || ($paymentStatus.val() === 'bank_pending' && paymentType !== 'bank_transfer')) {
                        $paymentStatus.val('');
                    }
                }

                $('#moyasarNotice').toggleClass('d-none', paymentType !== 'moyasar');
                $('#moyasarPaymentIcons').toggleClass('d-none', paymentType !== 'moyasar');
            }

            function filterPackageOptions() {
                const maidId = $('#maid_id').val();
                const maidNationalityId = String($('#maid_id option:selected').data('nationality-id') || '');
                const maidNationalityLabel = normalizeText($('#maid_id option:selected').data('nationality') || '');
                const $packageSelect = $('#package_id');
                let matchedPackages = 0;

                $packageSelect.find('option').each(function () {
                    if (!this.value) {
                        return;
                    }

                    const packageNationalityId = String($(this).data('nationality-id') || '');
                    const packageNationalityLabel = normalizeText($(this).data('nationality-label') || '');
                    const packageTitle = normalizeText($(this).data('title') || '');
                    const matchesByNationality = !packageNationalityId || packageNationalityId === maidNationalityId;
                    const matchesByText = Boolean(maidNationalityLabel) && (
                        packageNationalityLabel === maidNationalityLabel ||
                        packageTitle.includes(maidNationalityLabel)
                    );
                    const matches = Boolean(maidId) && (matchesByNationality || matchesByText);

                    $(this).prop('hidden', !matches);
                    $(this).prop('disabled', !matches);

                    if (matches) {
                        matchedPackages++;
                    }
                });

                if (!maidId) {
                    $packageSelect.val('');
                    $packageSelect.prop('disabled', true);
                    $packageSelect.find('option:first').text('اختر العاملة أولاً لعرض الباقات المناسبة');
                    resetPackageSummary();
                    updatePaymentState();
                    return;
                }

                $packageSelect.prop('disabled', matchedPackages === 0);
                $packageSelect.find('option:first').text(
                    matchedPackages > 0 ? 'اختر الباقة المناسبة' : 'لا توجد باقات لهذه العاملة'
                );

                const currentValue = $packageSelect.val();
                if (currentValue) {
                    const $selectedOption = $packageSelect.find(`option[value="${currentValue}"]`);
                    if (!$selectedOption.length || $selectedOption.prop('disabled')) {
                        $packageSelect.val('').trigger('change');
                    }
                }

                if (matchedPackages === 0) {
                    resetPackageSummary('لا توجد باقات مرتبطة بجنسية العاملة المختارة حالياً.');
                }
            }

            function applyPackageData() {
                const selectedOption = $('#package_id').find('option:selected');
                const packageId = $('#package_id').val();

                if (!packageId) {
                    resetPackageSummary();
                    updatePaymentState();
                    return;
                }

                const days = parseInt(selectedOption.data('days'), 10) || 0;
                const rentalType = selectedOption.data('rental-type') || '';
                const durationUnit = selectedOption.data('duration-unit') || '';
                const durationLabel = selectedOption.data('duration-label') || '';
                const workingHours = parseInt(selectedOption.data('working-hours'), 10) || 0;
                const beforeTax = parseFloat(selectedOption.data('price')) || 0;
                const catalogTotal = parseFloat(selectedOption.data('total')) || beforeTax;
                const months = Math.max(1, Math.ceil(days / 30));
                const totalPrice = catalogTotal;
                const monthlyCost = (rentalType === 'monthly' || durationUnit === 'month') && months
                    ? (totalPrice / months)
                    : totalPrice;

                $('#packageSummaryTitle').text(selectedOption.data('title') || 'الباقة المختارة');
                $('#packageSummaryHint').text('تم اعتماد بيانات الباقة تلقائياً ويمكن تعديل تفاصيل العقد عند الحاجة.');
                $('#packageSummaryDays').text(durationLabel || (days ? `${days} يوم` : '-'));
                $('#packageSummaryBeforeTax').text(formatCurrency(beforeTax));
                $('#packageSummaryAfterTax').text(formatCurrency(totalPrice));

                $('#contract_duration_months').val(months);
                $('#contract_duration_days').val(days);
                $('#monthly_cost').val(monthlyCost ? monthlyCost.toFixed(2) : '');
                $('#total_contract_value').val(totalPrice ? totalPrice.toFixed(2) : '');
                $('#rental_type').val(rentalType).trigger('change');

                if (workingHours) {
                    $('#working_hours').val(workingHours);
                }

                calculateIngazTo();
                updatePaymentState();
            }

            $('#monthly_salary').on('input change', function () {
                if ($('#package_id').val()) {
                    applyPackageData();
                }
            });

            $('#new_existing_client_id').on('change', function () {
                const selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    fillClientData(selectedOption);
                } else {
                    clearClientData();
                }
            });

            $('#use-direct-client-data').on('click', function () {
                $('#new_existing_client_id').val(null).trigger('change');
                clearClientData();
                $('#new_client_name').trigger('focus');
            });

            $('#new_existing_client_id').on('select2:select', function (event) {
                const client = event.params.data.client || null;
                const selectedOption = $(this).find('option:selected');

                if (client && selectedOption.length) {
                    applyClientDataToOption(selectedOption, client);
                    fillClientData(selectedOption);
                }
            });

            $('#maid_id').on('change', function () {
                const selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    fillMaidData(selectedOption);
                } else {
                    clearMaidData();
                }

                filterPackageOptions();
            });

            $('#contract_duration_months').on('input', function () {
                const months = parseInt($(this).val(), 10) || 0;
                const days = months * 30;
                $('#contract_duration_days').val(days);
                calculateIngazTo();
                calculateContractValue();
            });

            $('#package_id').on('change', function () {
                applyPackageData();
            });

            $('#payment_status').on('change', function () {
                updatePaymentState();
            });

            $('#payment_type').on('change', function () {
                enforcePaymentCompatibility();
                updatePaymentState();
            });

            $('#paid_amount').on('input', function () {
                syncPaymentSummary();
            });

            $('#ingaz_from').on('change', calculateIngazTo);
            $('#monthly_cost').on('input', calculateContractValue);
            $('#total_contract_value').on('input', syncPaymentSummary);

            function escapeHtml(value) {
                return $('<div>').text(value || '').html();
            }

            function copyTextToClipboard(text, button) {
                const markCopied = function () {
                    if (button) {
                        const originalText = button.innerHTML;
                        button.innerHTML = '<i class="fas fa-check me-1"></i>تم النسخ';
                        setTimeout(function () {
                            button.innerHTML = originalText;
                        }, 1600);
                    }
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(markCopied);
                    return;
                }

                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                markCopied();
            }

            function buildPaymentLinkSuccessHtml(response) {
                const hasLink = !!response.moyasar_link;
                const sent = !!response.payment_link_sent;
                // حالة الرابط تظهر في بطاقته، فلا تتكرر في سطر الرسالة.
                const lead = hasLink ? 'تم إنشاء عقد التأجير وحفظه بنجاح.' : (response.message || 'تم إنشاء عقد التأجير بنجاح.');
                let html = '<div dir="rtl"><p class="ck-done-lead">' + escapeHtml(lead) + '</p>';

                if (response.contract_number) {
                    html += '<div class="ck-done-chips"><span class="ck-done-chip"><i class="fas fa-file-signature"></i>عقد رقم '
                        + escapeHtml(response.contract_number) + '</span></div>';
                }

                if (hasLink) {
                    html += '<div class="ck-pay">' +
                        '<div class="ck-pay-head">' +
                            '<span class="ck-pay-title"><span class="ck-pay-logo"><i class="fas fa-credit-card"></i></span>رابط الدفع الإلكتروني (ميسر)</span>' +
                            (sent
                                ? '<span class="ck-pay-state is-sent"><i class="fas fa-check-circle"></i>أُرسل للعميل عبر SMS</span>'
                                : '<span class="ck-pay-state is-warn"><i class="fas fa-exclamation-circle"></i>لم يُرسل</span>') +
                        '</div>' +
                        '<div class="ck-pay-body">' +
                            (!sent && response.payment_link_created
                                ? '<div class="ck-pay-note"><i class="fas fa-info-circle mt-1"></i><span>' + escapeHtml(response.payment_link_sms_error || 'تعذر إرسال رسالة رابط الدفع للعميل.') + '</span></div>'
                                : '') +
                            '<div class="ck-pay-link">' +
                                '<input type="text" id="createdPaymentLinkInput" value="' + escapeHtml(response.moyasar_link) + '" readonly aria-label="رابط الدفع">' +
                                '<a href="' + escapeHtml(response.moyasar_link) + '" target="_blank" rel="noopener" title="فتح صفحة الدفع"><i class="fas fa-external-link-alt"></i></a>' +
                                '<button type="button" id="copyCreatedPaymentLinkBtn"><i class="fas fa-copy"></i>نسخ</button>' +
                            '</div>' +
                            '<p class="ck-pay-hint">' + (sent
                                ? 'يمكنك إعادة إرسال الرابط عبر SMS أو واتساب من تفاصيل العقد.'
                                : 'انسخ الرابط وأرسله للعميل، أو أعد إرساله لاحقاً من تفاصيل العقد.') + '</p>' +
                        '</div>' +
                    '</div>';
                }

                const docs = ['العقد'];
                if (response.invoice_print_url) docs.push('الفاتورة');
                if (response.receipt_bond_print_url) docs.push('سند القبض');
                html += '<div class="ck-docs"><i class="fas fa-print"></i>طباعة المستندات تفتح: ' +
                    docs.map(function (doc) { return '<span>' + doc + '</span>'; }).join('') + '</div>';

                html += '</div>';

                return html;
            }

            function clearAjaxValidationErrors($form) {
                $form.find('.ajax-validation-feedback').remove();
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.select2-selection.is-invalid').removeClass('is-invalid');
            }

            function validationFieldSelector(field) {
                return '[name="' + String(field).replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"]';
            }

            function showFieldValidationError($form, field, message) {
                const $field = $form.find(validationFieldSelector(field)).first();

                if (!$field.length) {
                    return;
                }

                $field.addClass('is-invalid');

                const feedbackId = 'err_' + String(field).replace(/[^a-zA-Z0-9_-]/g, '_');
                let $feedback = $('#' + feedbackId);

                if (!$feedback.length) {
                    $feedback = $('<div class="invalid-feedback d-block ajax-validation-feedback"></div>');

                    if ($field.hasClass('select2-hidden-accessible')) {
                        $field.next('.select2-container').after($feedback);
                    } else {
                        $field.after($feedback);
                    }
                }

                if ($field.hasClass('select2-hidden-accessible')) {
                    $field.next('.select2-container').find('.select2-selection').addClass('is-invalid');
                    $feedback.addClass('d-block');
                }

                $feedback.text(message).show();
            }

            function collectValidationMessages(response) {
                const messages = [];

                if (response && response.errors) {
                    Object.keys(response.errors).forEach(function (field) {
                        const fieldMessages = Array.isArray(response.errors[field])
                            ? response.errors[field]
                            : [response.errors[field]];

                        fieldMessages.forEach(function (message) {
                            if (message) {
                                messages.push({
                                    field: field,
                                    message: String(message)
                                });
                            }
                        });
                    });
                }

                const genericLaravelMessage = 'The given data was invalid.';
                if (!messages.length && response && response.message && response.message !== genericLaravelMessage) {
                    messages.push({
                        field: null,
                        message: response.message
                    });
                }

                if (!messages.length) {
                    messages.push({
                        field: null,
                        message: 'لم يتم حفظ العقد لأن بعض البيانات غير صحيحة. راجع الحقول المطلوبة ثم حاول مرة أخرى.'
                    });
                }

                return messages;
            }

            function showAjaxValidationAlert(messages) {
                const messageList = messages
                    .map(function (item) {
                        return '<li>' + escapeHtml(item.message) + '</li>';
                    })
                    .join('');

                Swal.fire({
                    title: 'لم يتم حفظ العقد',
                    html: '<div class="text-end" dir="rtl">' +
                        '<p class="mb-2">سبب المشكلة:</p>' +
                        '<ul class="mb-0 pe-3">' + messageList + '</ul>' +
                        '</div>',
                    icon: 'error',
                    confirmButtonText: 'حسناً'
                });
            }

            $('#rentalContractForm').on('submit', function (e) {
                e.preventDefault();
                const $form = $(this);
                clearAjaxValidationErrors($form);

                if (!$('#new_existing_client_id').val() && !$('#new_client_name').val()) {
                    Swal.fire('خطأ', 'يرجى اختيار عميل أو تعبئة اسم العميل', 'error');
                    return;
                }

                if (!$('#maid_id').val()) {
                    Swal.fire('خطأ', 'يرجى اختيار عاملة', 'error');
                    return;
                }

                let isValid = true;
                let firstError = '';

                $('[data-validation="required"]').each(function () {
                    if ($(this).is(':disabled')) {
                        return;
                    }

                    if (!$(this).val()) {
                        $(this).addClass('is-invalid');
                        isValid = false;
                        if (!firstError) {
                            const label = $(this).closest('[class*="col-"]').find('label').first().text().replace('*', '').trim();
                            firstError = 'يرجى تعبئة حقل: ' + label;
                        }
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                const phone = $('#new_client_phone').val();
                if (phone && !/^(05|5)[0-9]{8}$/.test(phone)) {
                    $('#new_client_phone').addClass('is-invalid');
                    Swal.fire('خطأ', 'رقم الجوال يجب أن يبدأ بـ 05 ويكون 10 أرقام', 'error');
                    return;
                }

                const nationalNum = $('#new_client_national_num').val();
                const isCompanyClient = $('#new_client_national_num').data('is-company-client') == 1;
                if (!isCompanyClient && nationalNum && !/^[12][0-9]{9}$/.test(nationalNum)) {
                    $('#new_client_national_num').addClass('is-invalid');
                    Swal.fire('خطأ', 'رقم الهوية يجب أن يبدأ بـ 1 أو 2 ويكون 10 أرقام', 'error');
                    return;
                }

                const totalContractValue = parseFloat($('#total_contract_value').val()) || 0;
                const paidAmount = parseFloat($('#paid_amount').val()) || 0;
                const paymentStatus = $('#payment_status').val();
                const monthlySalary = parseFloat($('#monthly_salary').val()) || 0;

                if (monthlySalary <= 0) {
                    $('#monthly_salary').addClass('is-invalid').trigger('focus');
                    Swal.fire({
                        icon: 'warning',
                        title: 'يجب تأكيد الراتب',
                        text: 'أدخل راتباً شهرياً أكبر من صفر. الراتب معفى من الضريبة، وتركه صفراً يجعل الضريبة تُحتسب على كامل قيمة العقد.',
                        confirmButtonText: 'حسناً'
                    });
                    return;
                }

                if (paymentStatus === 'partial' && paidAmount <= 0) {
                    Swal.fire('خطأ', 'أدخل المبلغ المدفوع عند اختيار حالة مدفوع جزئياً', 'error');
                    return;
                }

                if (paymentStatus === 'partial' && paidAmount >= totalContractValue) {
                    Swal.fire('خطأ', 'إذا تم سداد كامل المبلغ اختر حالة تم الدفع بدلاً من مدفوع جزئياً', 'error');
                    return;
                }

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'بيانات ناقصة',
                        text: firstError,
                        confirmButtonText: 'حسناً'
                    });
                    return;
                }

                Swal.fire({
                    title: 'تأكيد الحفظ',
                    html: '<div dir="rtl">هل أنت متأكد من حفظ عقد التأجير؟<br>' +
                        '<strong>الراتب الشهري المعفى من الضريبة: ' + formatCurrency(monthlySalary) + '</strong></div>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'نعم، احفظ',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#rentalContractSubmitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...');

                        const formData = new FormData(this);
                        formData.set('remaining_amount', $('#remaining_amount').val() || '0');

                        $.ajax({
                            url: $(this).attr('action'),
                            method: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                $('#rentalContractSubmitBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>حفظ العقد');

                                if (response.success) {
                                    Swal.fire({
                                        title: 'تم بنجاح!',
                                        html: buildPaymentLinkSuccessHtml(response),
                                        icon: 'success',
                                        iconColor: '#7ab648',
                                        showCancelButton: true,
                                        buttonsStyling: false,
                                        reverseButtons: false,
                                        allowOutsideClick: false,
                                        customClass: {
                                            popup: 'ck-done',
                                            confirmButton: 'ck-done-confirm',
                                            cancelButton: 'ck-done-cancel'
                                        },
                                        confirmButtonText: '<i class="fas fa-print"></i> طباعة المستندات',
                                        cancelButtonText: '<i class="fas fa-list"></i> الذهاب للقائمة',
                                        didOpen: function () {
                                            const copyButton = document.getElementById('copyCreatedPaymentLinkBtn');
                                            const input = document.getElementById('createdPaymentLinkInput');

                                            if (copyButton && input) {
                                                copyButton.addEventListener('click', function () {
                                                    copyTextToClipboard(input.value, copyButton);
                                                    copyButton.classList.add('is-copied');
                                                    setTimeout(function () { copyButton.classList.remove('is-copied'); }, 1600);
                                                });
                                            }
                                        }
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            if (response.print_url) {
                                                window.open(response.print_url, '_blank');
                                            }
                                            if (response.invoice_print_url) {
                                                window.open(response.invoice_print_url, '_blank');
                                            }
                                            if (response.receipt_bond_print_url) {
                                                window.open(response.receipt_bond_print_url, '_blank');
                                            }
                                            setTimeout(() => {
                                                window.location.href = response.redirect_url;
                                            }, 1000);
                                        } else {
                                            window.location.href = response.redirect_url;
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'خطأ!',
                                        text: response.message || 'حدث خطأ غير متوقع',
                                        icon: 'error',
                                        confirmButtonText: 'حسناً'
                                    });
                                }
                            },
                            error: function (xhr) {
                                $('#rentalContractSubmitBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>حفظ العقد');

                                const response = xhr.responseJSON || {};
                                const validationMessages = collectValidationMessages(response);

                                if (response.errors) {
                                    Object.keys(response.errors).forEach(function (field) {
                                        const firstMessage = Array.isArray(response.errors[field])
                                            ? response.errors[field][0]
                                            : response.errors[field];

                                        showFieldValidationError($form, field, firstMessage);
                                    });
                                }

                                showAjaxValidationAlert(validationMessages);

                                const $firstInvalid = $form.find('.is-invalid:visible').first();
                                if ($firstInvalid.length) {
                                    $('html, body').animate({
                                        scrollTop: Math.max(0, $firstInvalid.offset().top - 120)
                                    }, 300);
                                }
                            }
                        });
                    }
                });
            });

            if (initialState.clientId) {
                $('#new_existing_client_id').val(initialState.clientId).trigger('change');
            }

            if (initialState.maidId) {
                $('#maid_id').val(initialState.maidId).trigger('change');
            } else {
                filterPackageOptions();
            }

            if (initialState.packageId) {
                $('#package_id').val(initialState.packageId).trigger('change');
            }

            if (initialState.paymentType) {
                $('#payment_type').val(initialState.paymentType);
            }

            if (initialState.paymentStatus) {
                $('#payment_status').val(initialState.paymentStatus);
            }

            if (initialState.paidAmount) {
                $('#paid_amount').val(initialState.paidAmount);
            }

            calculateIngazTo();
            calculateContractValue();
            updatePaymentState();

            if (initialState.orderLinked) {
                toast.fire({
                    icon: 'info',
                    title: 'تم تحميل بيانات الطلب تلقائياً'
                });
            } else if (initialState.clientAutoSelected && initialState.clientId) {
                toast.fire({
                    icon: 'info',
                    title: '{{ helperTrans("ingaz.client_selected_auto") }}'
                });
            }
        });
    </script>
@endsection
