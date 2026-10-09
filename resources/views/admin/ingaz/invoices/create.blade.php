@extends('admin.layout.indexs.index')

@section('page-title')
    إصدار فاتورة
@endsection

@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --invoice-primary: #0f766e;
            --invoice-primary-soft: #e7f6f3;
            --invoice-surface: #ffffff;
            --invoice-border: #e2e8f0;
            --invoice-text: #142033;
            --invoice-muted: #64748b;
            --invoice-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }

        .invoice-create-page {
            color: var(--invoice-text);
        }

        .page-toolbar,
        .editor-panel,
        .sidebar-panel {
            background: var(--invoice-surface);
            border: 1px solid var(--invoice-border);
            border-radius: 20px;
            box-shadow: var(--invoice-shadow);
        }

        .page-toolbar {
            padding: 18px 22px;
            margin-bottom: 16px;
        }

        .toolbar-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            background: var(--invoice-primary-soft);
            color: var(--invoice-primary);
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .toolbar-title {
            font-size: 24px;
            font-weight: 900;
            margin-bottom: 4px;
        }

        .toolbar-text {
            color: var(--invoice-muted);
            font-size: 13px;
            margin-bottom: 0;
        }

        .toolbar-btn {
            border-radius: 14px;
            font-weight: 800;
            padding-inline: 16px;
        }

        .editor-panel {
            padding: 18px;
        }

        .sidebar-panel {
            padding: 18px;
        }

        .panel-heading {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .panel-icon {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--invoice-primary-soft);
            color: var(--invoice-primary);
            font-size: 16px;
        }

        .panel-title {
            font-size: 17px;
            font-weight: 900;
            margin-bottom: 2px;
        }

        .panel-subtitle {
            color: var(--invoice-muted);
            font-size: 12px;
            margin: 0;
        }

        .mini-section {
            padding-top: 14px;
            margin-top: 14px;
            border-top: 1px solid #edf2f7;
        }

        .mini-section:first-of-type {
            border-top: 0;
            margin-top: 0;
            padding-top: 0;
        }

        .mini-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 900;
            color: #334155;
            margin-bottom: 12px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 800;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-control,
        .form-select {
            min-height: 44px;
            border-radius: 14px;
            border: 1px solid #dbe3ea;
            box-shadow: none;
            padding-inline: 12px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(15, 118, 110, 0.45);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.12);
        }

        .field-note {
            color: var(--invoice-muted);
            font-size: 11px;
            margin-top: 5px;
        }

        .invoice-issuance-field .form-control {
            background: #f8fafc;
            color: var(--invoice-text);
            font-weight: 900;
        }

        .invoice-issuance-field .form-control[type="date"] {
            background: #fff;
            direction: ltr;
            text-align: right;
        }

        .fixed-contract-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-bottom: 14px;
        }

        .fixed-contract-strip strong {
            font-size: 14px;
        }

        .fixed-contract-strip p {
            margin: 0;
            color: var(--invoice-muted);
            font-size: 12px;
        }

        .totals-box {
            padding: 12px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .total-field {
            background: #ffffff;
            font-weight: 900;
        }

        .sticky-sidebar {
            position: sticky;
            top: 20px;
        }

        .side-section+.side-section {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #edf2f7;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .meta-grid.is-empty {
            opacity: 0.75;
        }

        .meta-item {
            border-radius: 14px;
            border: 1px solid #edf2f7;
            background: #f8fafc;
            padding: 12px;
            min-height: 82px;
        }

        .meta-label {
            display: block;
            color: var(--invoice-muted);
            font-size: 11px;
            margin-bottom: 6px;
        }

        .meta-value {
            display: block;
            color: var(--invoice-text);
            font-size: 13px;
            font-weight: 900;
            line-height: 1.7;
            word-break: break-word;
        }

        .summary-total-card {
            border-radius: 18px;
            padding: 16px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            margin-bottom: 12px;
        }

        .summary-total-card span {
            display: block;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.72);
            margin-bottom: 6px;
        }

        .summary-total-card strong {
            display: block;
            font-size: 28px;
            line-height: 1.2;
        }

        .summary-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-size: 13px;
        }

        .summary-row span {
            color: var(--invoice-muted);
        }

        .summary-row strong {
            color: var(--invoice-text);
            font-weight: 900;
            text-align: left;
        }

        .line-breakdown {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #edf2f7;
        }

        .breakdown-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-size: 12px;
        }

        .breakdown-row span {
            color: var(--invoice-muted);
        }

        .breakdown-row strong {
            font-weight: 800;
            color: var(--invoice-text);
            text-align: left;
        }

        .action-stack .btn {
            border-radius: 14px;
            font-weight: 900;
            min-height: 44px;
        }

        .action-note {
            border-radius: 14px;
            background: #fff7ed;
            border: 1px solid #ffedd5;
            color: #9a3412;
            font-size: 12px;
            padding: 12px;
        }

        .post-save-actions {
            border-radius: 16px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 14px;
        }

        .post-save-actions h6 {
            color: #166534;
            font-size: 14px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        @media (max-width: 1199px) {
            .sticky-sidebar {
                position: static;
            }
        }

        @media (max-width: 767px) {

            .page-toolbar,
            .editor-panel,
            .sidebar-panel {
                border-radius: 16px;
                padding: 14px;
            }

            .toolbar-title {
                font-size: 20px;
            }

            .meta-grid {
                grid-template-columns: 1fr;
            }

            .fixed-contract-strip {
                flex-direction: column;
                align-items: flex-start;
            }

            .summary-total-card strong {
                font-size: 24px;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $selectedContractId = old('contract_id', $contractId ?? request('contract_id'));
        $selectedIngazDelegationId = old('ingaz_delegation_id', $ingazDelegationId ?? request('ingaz_delegation_id'));
        $selectedClientId = old('client_id', $clientId ?? request('client_id'));
        $contracts = $contracts ?? collect();
        $selectedContract = $selectedContract ?? null;
        $selectedIngazDelegation = $selectedIngazDelegation ?? null;
        $selectedClient = $selectedClient ?? null;
        $contractSearch = $contractSearch ?? request('contract_search');
        $contractSearchAttempted = $contractSearchAttempted ?? filled($contractSearch);
        $contractSearchMessage = $contractSearchMessage ?? null;
        $invoiceEligibleStatusLabels = $invoiceEligibleStatusLabels ?? collect();
        $isDelegationInvoice = (bool) $selectedIngazDelegation;
        $isGeneralInvoice = !$isDelegationInvoice && !$selectedContract && $selectedClientId;
        $invoiceMode = $isDelegationInvoice ? 'delegation' : ($isGeneralInvoice ? 'general' : 'contract');
        $selectedContractFromList =
            !$selectedContract && $selectedContractId ? $contracts->firstWhere('id', (int) $selectedContractId) : null;
        $currentContract = $selectedContract ?: $selectedContractFromList;
        $currentNationality = $isDelegationInvoice
            ? (optional($selectedIngazDelegation?->country)->title ?:
                (optional($selectedIngazDelegation?->country)->name_ar ?:
                    (optional($selectedIngazDelegation?->nationality)->sub_title ?:
                        (optional($selectedIngazDelegation?->nationality)->title ?: 'غير محدد'))))
            : (optional($currentContract?->maid?->nationality ?? $currentContract?->requiredMaidNationality)->sub_title ?:
                (optional($currentContract?->maid?->nationality ?? $currentContract?->requiredMaidNationality)->title ?:
                    'غير محدد'));
        $currentNationalityModel = $isDelegationInvoice
            ? $selectedIngazDelegation?->nationality
            : ($currentContract?->maid?->nationality ?? $currentContract?->requiredMaidNationality);
        $currentNationalityCosts = [
            'direct_recruitment_cost' => (float) ($currentNationalityModel?->direct_recruitment_cost ?? 0),
            'external_recruitment_cost' => (float) ($currentNationalityModel?->external_recruitment_cost ?? 0),
            'government_cost' => (float) ($currentNationalityModel?->government_cost ?? 0),
        ];
        $currentMusanedCommissionRate = (float) ($currentNationalityModel?->musaned_commission_rate
            ?? \App\Http\Controllers\ingaz\MusanedTransferController::DEFAULT_COMMISSION_RATE);
        $canEditInvoiceLineItems = \App\Support\InvoiceAuthorization::canEditLineItems();
        $canEditMusanedCommission = \App\Support\InvoiceAuthorization::canEditMusanedCommission();
        $currentNationalityHasCosts = collect($currentNationalityCosts)->contains(fn ($value) => (float) $value > 0);
        $currentContractStatus = $currentContract
            ? getEnumValue('contract_statuses', $currentContract->status) ?? ($currentContract->status ?? 'غير محدد')
            : ($isDelegationInvoice ? ($selectedIngazDelegation?->status_label ?? 'غير محدد') : ($isGeneralInvoice ? 'فاتورة عامة' : 'غير محدد'));
        $currentClient = $isDelegationInvoice ? $selectedIngazDelegation?->user : ($isGeneralInvoice ? $selectedClient : $currentContract?->user);
        $currentClientName = $currentClient?->name ?? 'غير محدد';
        $currentClientRawPhone = $currentClient?->phone;
        $currentClientPhone = $currentClientRawPhone && !str_starts_with($currentClientRawPhone, 'ingaz-delegation-')
            ? trim(($currentClient?->phone_code ?? '') . $currentClientRawPhone)
            : 'غير محدد';
        $currentClientIdentityLabel = $currentClient?->documentNumberLabel() ?? helperTrans('ingaz.national_num');
        $currentClientIdentity = $currentClient?->documentNumber() ?? 'غير محدد';
        $nextInvoiceNumber = $nextInvoiceNumber ?? 1;
        $invoiceIssuedAt = $invoiceIssuedAt ?? now();
        $selectedInvoiceDate = old('invoice_date', now()->toDateString());
        $canCreateInvoice = true;
    @endphp

    <div class="container-fluid py-3 invoice-create-page">
        <div class="page-toolbar">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <span class="toolbar-kicker">
                        <i class="fas fa-file-invoice-dollar"></i>
                        فاتورة جديدة
                    </span>
                    <div class="toolbar-title">إصدار فاتورة </div>

                </div>

                @if (\App\Support\InvoiceAuthorization::canView())
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary toolbar-btn">
                        <i class="fas fa-table-list me-1"></i>
                        جميع الفواتير
                    </a>
                </div>
                @endif
            </div>
        </div>

        @if (!$isDelegationInvoice && !$isGeneralInvoice)
            <div class="alert alert-info d-flex align-items-start gap-2 mb-3" role="alert">
                <i class="fas fa-circle-info mt-1"></i>
                <div>
                    <div class="fw-bold mb-1">مراحل العقود التي يمكن إصدار فاتورة لها</div>
                    <div>
                        يمكن إصدار الفاتورة للعقد الذي لا توجد له فاتورة سابقة ويكون في إحدى المراحل التالية:
                        {{ $invoiceEligibleStatusLabels->join('، ') }}.
                    </div>
                </div>
            </div>

            <div class="editor-panel mb-3">
                <div class="mini-section mb-0">
                    <div class="mini-title">
                        <i class="fas fa-search"></i>
                        البحث عن عقد حالي
                    </div>
                    <form method="GET" action="{{ route('invoices.create') }}" class="row g-2 align-items-end">
                        <div class="col-md-9">
                            <label class="form-label" for="contract_search">رقم العقد</label>
                            <input type="text" name="contract_search" id="contract_search" class="form-control"
                                value="{{ $contractSearch }}" placeholder="اكتب رقم العقد ثم اضغط بحث">
                            <div class="field-note">يتم البحث في العقود المؤهلة لإصدار فاتورة فقط، ولا يتم جلب كل العقود.</div>
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>
                                بحث
                            </button>
                        </div>
                    </form>

                    @if ($contractSearchMessage)
                        <div class="alert alert-warning mt-3 mb-0">
                            {{ $contractSearchMessage }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <form method="post" action="{{ $invoiceStoreUrl ?? route('invoices.store') }}" id="invoiceForm">
            @csrf

            <div class="row g-3 align-items-start">
                <div class="col-xl-8 order-2 order-xl-1">
                    <div class="editor-panel">
                        <div class="panel-heading">
                            <span class="panel-icon">
                                <i class="fas fa-pen-ruler"></i>
                            </span>
                            <div>
                                <div class="panel-title">بيانات الفاتورة</div>
                                <p class="panel-subtitle">أدخل القيم فقط، وسيتم تحديث الإجمالي والضريبة والمطلوب مباشرة.</p>
                            </div>
                        </div>

                        <div class="mini-section">
                            <div class="mini-title">
                                <i class="fas fa-link"></i>
                                {{ $isDelegationInvoice ? 'ربط تفويض إنجاز' : 'مصدر الفاتورة' }}
                            </div>

                            @if ($isDelegationInvoice)
                                <input type="hidden" name="ingaz_delegation_id" value="{{ $selectedIngazDelegation->id }}">

                                <div class="fixed-contract-strip">
                                    <div>
                                        <strong>تفويض إنجاز مرتبط مسبقاً: #{{ $selectedIngazDelegation->id }}</strong>
                                        <p>تم فتح الصفحة من التفويض، وسيتم إصدار الفاتورة والقيود الخاصة بالتفاويض.</p>
                                    </div>
                                    <span class="badge bg-light text-dark border">العميل: {{ $currentClientName }}</span>
                                </div>
                            @elseif (!$selectedContract)
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label" for="invoice_mode">نوع الفاتورة</label>
                                        <select id="invoice_mode" class="form-select">
                                            <option value="contract" {{ $invoiceMode === 'contract' ? 'selected' : '' }}>فاتورة مرتبطة بعقد</option>
                                            <option value="general" {{ $invoiceMode === 'general' ? 'selected' : '' }}>فاتورة عامة مرتبطة بعميل</option>
                                        </select>
                                        <div class="field-note">الفاتورة العامة تحتاج اختيار عميل فقط ولا ترتبط بعقد.</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="invoice-mode-section" data-mode-section="contract">
                                            @if ($contracts->isEmpty())
                                                <div class="alert alert-info mb-0">
                                                    اكتب رقم العقد في مربع البحث بالأعلى ثم اضغط بحث لإظهار العقد وإصدار الفاتورة.
                                                </div>
                                            @else
                                            <label class="form-label" for="contract_id">
                                            العقد
                                            <span class="text-danger">*</span>
                                            </label>
                                            <select name="contract_id" id="contract_id" class="form-select"
                                                @disabled(!$canCreateInvoice)>
                                                <option value="" disabled {{ $selectedContractId ? '' : 'selected' }}>
                                                    اختر العقد
                                                </option>
                                                @foreach ($contracts as $contract)
                                                    @php
                                                        $contractNationalityModel =
                                                            $contract->maid?->nationality ??
                                                            $contract->requiredMaidNationality;
                                                        $contractNationality =
                                                            optional(
                                                                $contractNationalityModel,
                                                            )->sub_title ?:
                                                            (optional(
                                                                $contractNationalityModel,
                                                            )->title ?:
                                                            'غير محدد');
                                                        $contractNationalityCosts = [
                                                            'direct_recruitment_cost' => (float) ($contractNationalityModel?->direct_recruitment_cost ?? 0),
                                                            'external_recruitment_cost' => (float) ($contractNationalityModel?->external_recruitment_cost ?? 0),
                                                            'government_cost' => (float) ($contractNationalityModel?->government_cost ?? 0),
                                                        ];
                                                        $contractNationalityHasCosts = collect($contractNationalityCosts)->contains(fn ($value) => (float) $value > 0);
                                                        $contractStatus =
                                                            getEnumValue('contract_statuses', $contract->status) ??
                                                            ($contract->status ?? 'غير محدد');
                                                        $contractPhone =
                                                            trim(
                                                                ($contract->user?->phone_code ?? '') .
                                                                    ($contract->user?->phone ?? ''),
                                                            ) ?:
                                                            'غير محدد';
                                                    @endphp
                                                    <option value="{{ $contract->id }}"
                                                        data-client-name="{{ e($contract->user?->name ?? 'غير محدد') }}"
                                                        data-client-phone="{{ e($contractPhone) }}"
                                                        data-client-identity="{{ e($contract->user?->documentNumber() ?? 'غير محدد') }}"
                                                        data-client-identity-label="{{ e($contract->user?->documentNumberLabel() ?? helperTrans('ingaz.national_num')) }}"
                                                        data-nationality="{{ e($contractNationality) }}"
                                                        data-status="{{ e($contractStatus) }}"
                                                        data-direct-cost="{{ $contractNationalityCosts['direct_recruitment_cost'] }}"
                                                        data-external-cost="{{ $contractNationalityCosts['external_recruitment_cost'] }}"
                                                        data-government-cost="{{ $contractNationalityCosts['government_cost'] }}"
                                                        data-musaned-commission-rate="{{ (float) ($contractNationalityModel?->musaned_commission_rate ?? \App\Http\Controllers\ingaz\MusanedTransferController::DEFAULT_COMMISSION_RATE) }}"
                                                        data-has-nationality-costs="{{ $contractNationalityHasCosts ? 1 : 0 }}"
                                                        {{ (string) $selectedContractId === (string) $contract->id ? 'selected' : '' }}>
                                                        {{ $contract->desc ?? 'العقد #' . $contract->id }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @endif
                                        </div>

                                        @if (!$canCreateInvoice)
                                            <div class="alert alert-warning mt-3 mb-0">
                                                لا توجد عقود متاحة حالياً لإصدار فاتورة جديدة.
                                            </div>
                                        @else
                                            <div class="field-note">اختر العقد لتحديث بيانات العميل في العمود الجانبي
                                                مباشرة.</div>
                                        @endif
                                    </div>
                                    <div class="col-12 invoice-mode-section" data-mode-section="general">
                                        <label class="form-label" for="client_id">
                                            العميل
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select name="client_id" id="client_id" class="form-select ajax-client-select" data-placeholder="ابحث باسم العميل أو الجوال أو الهوية">
                                            @if ($selectedClient)
                                                <option value="{{ $selectedClient->id }}" selected
                                                    data-name="{{ e($selectedClient->name ?? 'غير محدد') }}"
                                                    data-phone="{{ e(trim(($selectedClient->phone_code ?? '') . ($selectedClient->phone ?? '')) ?: 'غير محدد') }}"
                                                    data-identity="{{ e($selectedClient->documentNumber() ?? 'غير محدد') }}"
                                                    data-identity-label="{{ e($selectedClient->documentNumberLabel()) }}">
                                                    {{ $selectedClient->name }} - {{ $selectedClient->phone ?? 'بدون جوال' }}
                                                </option>
                                            @endif
                                        </select>
                                        <div class="field-note">ستظهر الفاتورة باسم هذا العميل في قائمة الفواتير وتفاصيل الطباعة.</div>
                                    </div>
                                    <div class="col-12 invoice-mode-section" data-mode-section="general">
                                        <label class="form-label" for="general_description">تفاصيل الفاتورة العامة</label>
                                        <textarea name="general_description" id="general_description" class="form-control" rows="3"
                                            placeholder="مثال: رسوم خدمة إضافية أو تسوية مالية">{{ old('general_description') }}</textarea>
                                    </div>
                                    <div class="col-md-6 invoice-mode-section" data-mode-section="general">
                                        <label class="form-label" for="general_direct_label">اسم البند الخاضع للضريبة</label>
                                        <input type="text" name="general_direct_label" id="general_direct_label"
                                            class="form-control general-label-input"
                                            value="{{ old('general_direct_label', 'خدمة عامة') }}">
                                    </div>
                                    <div class="col-md-6 invoice-mode-section" data-mode-section="general">
                                        <label class="form-label" for="general_ticket_label">اسم البند الإضافي الخاضع للضريبة</label>
                                        <input type="text" name="general_ticket_label" id="general_ticket_label"
                                            class="form-control general-label-input"
                                            value="{{ old('general_ticket_label', 'خدمة إضافية') }}">
                                    </div>
                                    <div class="col-md-6 invoice-mode-section" data-mode-section="general">
                                        <label class="form-label" for="general_external_label">اسم البند غير الخاضع</label>
                                        <input type="text" name="general_external_label" id="general_external_label"
                                            class="form-control general-label-input"
                                            value="{{ old('general_external_label', 'بند غير خاضع') }}">
                                    </div>
                                    <div class="col-md-6 invoice-mode-section" data-mode-section="general">
                                        <label class="form-label" for="general_government_label">اسم بند الرسوم</label>
                                        <input type="text" name="general_government_label" id="general_government_label"
                                            class="form-control general-label-input"
                                            value="{{ old('general_government_label', 'رسوم حكومية') }}">
                                    </div>
                                </div>
                            @else
                                <input type="hidden" name="contract_id" value="{{ $selectedContract->id }}">

                                <div class="fixed-contract-strip">
                                    <div>
                                        <strong>العقد مرتبط مسبقاً: #{{ $selectedContract->id }}</strong>
                                        <p>تم فتح الصفحة من داخل النظام بعقد محدد وجاهز لإصدار الفاتورة.</p>
                                    </div>
                                    <span class="badge bg-light text-dark border">العميل: {{ $currentClientName }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="mini-section">
                            <div class="mini-title">
                                <i class="fas fa-file-circle-check"></i>
                                بيانات الإصدار
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4 invoice-issuance-field">
                                    <label class="form-label" for="invoice_number_preview">رقم الفاتورة</label>
                                    <input type="text" id="invoice_number_preview" class="form-control"
                                        value="{{ $nextInvoiceNumber }}" readonly aria-readonly="true">
                                    <div class="field-note">رقم تسلسلي بعد آخر فاتورة، ويُثبت نهائياً عند الحفظ.</div>
                                </div>
                                <div class="col-md-4 invoice-issuance-field">
                                    <label class="form-label" for="invoice_date">
                                        تاريخ الفاتورة
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" name="invoice_date" id="invoice_date"
                                        class="form-control @error('invoice_date') is-invalid @enderror"
                                        value="{{ $selectedInvoiceDate }}" required>
                                    @error('invoice_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="field-note">يمكن تغيير تاريخ الفاتورة قبل الإصدار.</div>
                                </div>
                                <div class="col-md-4 invoice-issuance-field">
                                    <label class="form-label" for="invoice_issued_at_preview">تاريخ الإصدار</label>
                                    <input type="text" id="invoice_issued_at_preview" class="form-control"
                                        value="{{ $invoiceIssuedAt->format('d/m/Y - H:i') }}" readonly aria-readonly="true">
                                    <div class="field-note">وقت إنشاء الفاتورة في النظام.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mini-section">
                            <div class="mini-title">
                                <i class="fas fa-sack-dollar"></i>
                                بنود التكلفة
                            </div>
                            <div class="alert {{ $currentNationalityHasCosts ? 'alert-info' : 'alert-warning' }} py-2 mb-3" id="nationalityCostHint">
                                {{ $currentNationalityHasCosts
                                    ? 'تم تحميل تكاليف الاستقدام المحفوظة على الجنسية المحددة.'
                                    : 'لا توجد تكاليف استقدام محفوظة لهذه الجنسية. أدخل القيم هنا وسيتم حفظها على الجنسية عند إصدار الفاتورة.' }}
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label" for="insurance_company">شركة التأمين</label>
                                    <input type="text" name="insurance_company" id="insurance_company"
                                        class="form-control" value="{{ old('insurance_company') }}" placeholder="اختياري">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label" for="direct_recruitment_cost" id="directCostLabel">الاستقدام المباشر</label>
                                    <input type="number" step="0.01" min="0" name="direct_recruitment_cost"
                                        id="direct_recruitment_cost" class="form-control calc-input invoice-line-editable"
                                        {{ !$canEditInvoiceLineItems && !$isGeneralInvoice ? 'readonly' : '' }}
                                        value="{{ old('direct_recruitment_cost', $currentNationalityCosts['direct_recruitment_cost']) }}">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label" for="external_recruitment_cost" id="externalCostLabel">
                                        <span id="externalCostLabelText">الاستقدام الخارجي</span>
                                        @if (!$isGeneralInvoice)
                                            <span class="text-danger" id="externalCostRequiredMark">*</span>
                                        @else
                                            <span class="text-danger d-none" id="externalCostRequiredMark">*</span>
                                        @endif
                                    </label>
                                    <input type="number" step="0.01" min="{{ $isGeneralInvoice ? 0 : 0.01 }}" name="external_recruitment_cost"
                                        id="external_recruitment_cost" class="form-control calc-input invoice-line-editable"
                                        {{ !$canEditInvoiceLineItems && !$isGeneralInvoice ? 'readonly' : '' }}
                                        value="{{ old('external_recruitment_cost', $currentNationalityCosts['external_recruitment_cost']) }}"
                                        {{ !$isGeneralInvoice ? 'required' : '' }}>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label" for="government_cost" id="governmentCostLabel">تكاليف حكومية</label>
                                    <input type="number" step="0.01" min="0" name="government_cost"
                                        id="government_cost" class="form-control calc-input invoice-line-editable"
                                        {{ !$canEditInvoiceLineItems && !$isGeneralInvoice ? 'readonly' : '' }}
                                        value="{{ old('government_cost', $currentNationalityCosts['government_cost']) }}">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label" for="internal_ticket_cost" id="ticketCostLabel">التذكرة الداخلية</label>
                                    <input type="number" step="0.01" min="0" name="internal_ticket_cost"
                                        id="internal_ticket_cost" class="form-control calc-input invoice-line-editable"
                                        {{ !$canEditInvoiceLineItems && !$isGeneralInvoice ? 'readonly' : '' }}
                                        value="{{ old('internal_ticket_cost', 0) }}">
                                </div>
                            </div>
                        </div>

                        <div class="mini-section">
                            <div class="mini-title">
                                <i class="fas fa-hashtag"></i>
                                الكميات
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label" for="visa_recruited_count">عدد المستقدمين في
                                        التأشيرة</label>
                                    <input type="number" min="1" name="visa_recruited_count"
                                        id="visa_recruited_count" class="form-control calc-input invoice-line-editable"
                                        {{ !$canEditInvoiceLineItems && !$isGeneralInvoice ? 'readonly' : '' }}
                                        value="{{ old('visa_recruited_count', 1) }}">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label" for="direct_recruitment_count">عدد المباشر</label>
                                    <input type="number" min="1" name="direct_recruitment_count"
                                        id="direct_recruitment_count" class="form-control calc-input invoice-line-editable"
                                        {{ !$canEditInvoiceLineItems && !$isGeneralInvoice ? 'readonly' : '' }}
                                        value="{{ old('direct_recruitment_count', 1) }}">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label" for="external_recruitment_count">عدد الخارجي</label>
                                    <input type="number" min="1" name="external_recruitment_count"
                                        id="external_recruitment_count" class="form-control calc-input invoice-line-editable"
                                        {{ !$canEditInvoiceLineItems && !$isGeneralInvoice ? 'readonly' : '' }}
                                        value="{{ old('external_recruitment_count', 1) }}">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label" for="insured_count">عدد المؤمن عليهم</label>
                                    <input type="number" min="1" name="insured_count" id="insured_count"
                                        class="form-control calc-input invoice-line-editable" value="{{ old('insured_count', 1) }}"
                                        {{ !$canEditInvoiceLineItems && !$isGeneralInvoice ? 'readonly' : '' }}>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label" for="government_cost_count">عدد الحكومي</label>
                                    <input type="number" min="1" name="government_cost_count"
                                        id="government_cost_count" class="form-control calc-input invoice-line-editable"
                                        {{ !$canEditInvoiceLineItems && !$isGeneralInvoice ? 'readonly' : '' }}
                                        value="{{ old('government_cost_count', 1) }}">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label" for="internal_ticket_count">عدد التذاكر</label>
                                    <input type="number" min="1" name="internal_ticket_count"
                                        id="internal_ticket_count" class="form-control calc-input invoice-line-editable"
                                        {{ !$canEditInvoiceLineItems && !$isGeneralInvoice ? 'readonly' : '' }}
                                        value="{{ old('internal_ticket_count', 1) }}">
                                </div>
                            </div>
                        </div>

                        <div class="mini-section">
                            <div class="mini-title">
                                <i class="fas fa-calculator"></i>
                                الإجماليات
                            </div>

                            <div class="totals-box">
                                <div class="row g-3">
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label text-success" for="subtotal">المجموع</label>
                                        <input type="number" step="0.01" min="0" name="subtotal"
                                            id="subtotal" class="form-control total-field"
                                            value="{{ old('subtotal', 0) }}" readonly>
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label" for="discount">الخصم</label>
                                        <input type="number" step="0.01" min="0" name="discount"
                                            id="discount" class="form-control calc-input invoice-line-editable"
                                            {{ !$canEditInvoiceLineItems && !$isGeneralInvoice ? 'readonly' : '' }}
                                            value="{{ old('discount', 0) }}">
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label text-primary" for="vat_amount">الضريبة</label>
                                        <input type="number" step="0.01" min="0" name="vat_amount"
                                            id="vat_amount" class="form-control total-field"
                                            value="{{ old('vat_amount', 0) }}" readonly>
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label text-danger" for="total_due">المطلوب</label>
                                        <input type="number" step="0.01" min="0" name="total_due"
                                            id="total_due" class="form-control total-field"
                                            value="{{ old('total_due', 0) }}" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mini-section">
                            <div class="mini-title">
                                <i class="fas fa-receipt"></i>
                                هيئة الزكاة
                            </div>

                            <div class="form-check form-switch d-flex align-items-center gap-2">
                                <input type="hidden" name="send_to_zatca" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="send_to_zatca"
                                    name="send_to_zatca" value="1" {{ old('send_to_zatca', '1') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="send_to_zatca">
                                    إدراج الفاتورة ضمن فواتير الزكاة المعلقة
                                </label>
                            </div>
                            <div class="field-note">
                                عند تفعيل الخيار سيتم إنشاء فاتورة مبيعات مالية مرتبطة بهذه الفاتورة، وتظهر في صفحة فواتير المبيعات غير المرحلة للهيئة.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 order-1 order-xl-2">
                    <div class="sticky-sidebar">
                        <div class="sidebar-panel">
                            <div class="panel-heading">
                                <span class="panel-icon">
                                    <i class="fas fa-address-card"></i>
                                </span>
                                <div>
                                    <div class="panel-title">{{ $isDelegationInvoice ? 'بيانات التفويض والعميل' : 'بيانات العقد والعميل' }}</div>
                                </div>
                            </div>

                            @if(\App\Support\InvoiceSupplierWorkflow::enabled())
                            <div class="side-section" id="supplierInvoiceOptions">
                                @php
                                    $supplierDefaults = $currentContract ? $currentContract->buildExternalOfficeDispatchSourceData() : [];
                                    $supplierDefaultCost = $supplierDefaults['office_contract_cost_usd'] ?? null;
                                    $supplierCostSource = filled($currentContract?->maid?->supplier_cost_usd) ? 'السيرة الذاتية' : 'إعدادات المورد / المكتب';
                                @endphp
                                <label class="form-check-label"><input type="checkbox" name="create_supplier_invoice" value="1"> إنشاء فاتورة مورد مع فاتورة العميل</label>
                                <p class="text-muted small">لعقود الاستقدام فقط؛ يمكن إنشاؤها لاحقًا من العقد.</p>
                                <label class="form-label">إثبات تكلفة المورد</label>
                                <select name="supplier_recognition_mode" class="form-control"><option value="full">التكلفة كاملة</option><option value="partial">جزء من التكلفة</option></select>
                                <label class="form-label mt-2">إجمالي تكلفة المورد (دولار)</label>
                                <input type="number" class="form-control" name="supplier_total_cost_usd" value="{{ old('supplier_total_cost_usd', $supplierDefaultCost) }}" min="0.01" max="99999999.99" step="0.01" placeholder="أدخل تكلفة المورد" required>
                                @if(filled($supplierDefaultCost))
                                    <p class="text-muted small mt-2">التكلفة التلقائية: <strong>{{ number_format((float) $supplierDefaultCost, 2) }} دولار</strong> — المصدر: {{ $supplierCostSource }}{{ filled($supplierDefaults['office_name'] ?? null) ? ' (' . $supplierDefaults['office_name'] . ')' : '' }}. يمكنك تعديل القيمة لهذه الفاتورة.</p>
                                @else
                                    <p class="text-muted small mt-2">لا توجد تكلفة مسجلة في السيرة أو إعدادات المورد؛ أدخل التكلفة قبل إصدار فاتورة المورد.</p>
                                @endif
                                <label class="form-label mt-2">المبلغ الجزئي المراد إثباته (دولار)</label>
                                <input type="number" class="form-control" name="supplier_partial_cost_usd" min="0.01" max="99999999.99" step="0.01">
                                <label class="form-label mt-2">محطة الوصول</label>
                                <input class="form-control" name="supplier_arrival_station" maxlength="255" placeholder="اختياري؛ تُستخدم محطة العقد">
                            </div>
                            @endif
                            <div class="side-section">
                                <div class="meta-grid {{ $currentContract || $isDelegationInvoice || $isGeneralInvoice ? '' : 'is-empty' }}" id="contractPreview">
                                    <div class="meta-item">
                                        <span class="meta-label" id="previewReferenceLabel">{{ $isDelegationInvoice ? 'رقم التفويض' : ($isGeneralInvoice ? 'نوع الفاتورة' : 'رقم العقد') }}</span>
                                        <strong class="meta-value" id="previewContractNumber">
                                            {{ $isDelegationInvoice ? '#' . $selectedIngazDelegation->id : ($isGeneralInvoice ? 'فاتورة عامة' : ($currentContract ? '#' . $currentContract->id : '---')) }}
                                        </strong>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">اسم العميل</span>
                                        <strong class="meta-value"
                                            id="previewClientName">{{ $currentClientName }}</strong>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label" id="previewNationalityLabel">{{ $isDelegationInvoice ? 'الدولة' : ($isGeneralInvoice ? 'الارتباط' : 'الجنسية') }}</span>
                                        <strong class="meta-value"
                                            id="previewNationality">{{ $currentNationality }}</strong>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label" id="previewStatusLabel">{{ $isDelegationInvoice ? 'حالة التفويض' : ($isGeneralInvoice ? 'الحالة' : 'حالة العقد') }}</span>
                                        <strong class="meta-value"
                                            id="previewContractStatus">{{ $currentContractStatus }}</strong>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label" id="previewClientIdentityLabel">{{ $currentClientIdentityLabel }}</span>
                                        <strong class="meta-value"
                                            id="previewClientIdentity">{{ $currentClientIdentity }}</strong>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">رقم التواصل</span>
                                        <strong class="meta-value"
                                            id="previewClientPhone">{{ $currentClientPhone }}</strong>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">رقم الفاتورة</span>
                                        <strong class="meta-value" id="previewInvoiceNumber">#{{ $nextInvoiceNumber }}</strong>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">تاريخ الفاتورة</span>
                                        <strong class="meta-value" id="previewInvoiceDate">{{ \Carbon\Carbon::parse($selectedInvoiceDate)->format('d/m/Y') }}</strong>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">تاريخ الإصدار</span>
                                        <strong class="meta-value" id="previewInvoiceIssuedAt">{{ $invoiceIssuedAt->format('d/m/Y - H:i') }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="side-section">
                                <div class="summary-total-card">
                                    <span>المطلوب من العميل</span>
                                    <strong id="summaryGrandTotal">0.00 ريال</strong>
                                </div>

                                <div class="summary-list">
                                    <div class="summary-row">
                                        <span>المجموع الفرعي</span>
                                        <strong id="summarySubtotal">0.00 ريال</strong>
                                    </div>
                                    <div class="summary-row">
                                        <span>ضريبة القيمة المضافة</span>
                                        <strong id="summaryVat">0.00 ريال</strong>
                                    </div>
                                    <div class="summary-row">
                                        <span>الخصم</span>
                                        <strong id="summaryDiscount">0.00 ريال</strong>
                                    </div>
                                </div>

                                @if(\App\Support\InvoiceSupplierWorkflow::enabled())
                                <div class="musaned-summary mt-3">
                                    <div class="summary-row align-items-center gap-2">
                                        <label for="musaned_commission_amount" class="mb-0">التكاليف البنكية <small class="text-muted">(شاملة ضريبة القيمة المضافة)</small></label>
                                        <div class="input-group input-group-sm" style="max-width: 170px">
                                            <input type="number" class="form-control text-end" id="musaned_commission_amount"
                                                name="musaned_commission_amount" min="0" step="0.01"
                                                value="{{ old('musaned_commission_amount') }}"
                                                {{ !$canEditMusanedCommission ? 'readonly' : '' }}>
                                            <span class="input-group-text">ريال</span>
                                        </div>
                                    </div>
                                    <div class="summary-row"><span>الصافي المتوقع من مساند</span><strong id="summaryMusanedNet">0.00 ريال</strong></div>
                                </div>
                                @endif
                                <div class="line-breakdown">
                                    <div class="breakdown-row">
                                        <span id="summaryDirectLabel">المباشر</span>
                                        <strong id="summaryDirectLine">0 × 0.00 = 0.00 ريال</strong>
                                    </div>
                                    <div class="breakdown-row">
                                        <span id="summaryExternalLabel">الخارجي</span>
                                        <strong id="summaryExternalLine">0 × 0.00 = 0.00 ريال</strong>
                                    </div>
                                    <div class="breakdown-row">
                                        <span id="summaryGovernmentLabel">الحكومي</span>
                                        <strong id="summaryGovernmentLine">0 × 0.00 = 0.00 ريال</strong>
                                    </div>
                                    <div class="breakdown-row">
                                        <span id="summaryTicketLabel">التذاكر</span>
                                        <strong id="summaryTicketLine">0 × 0.00 = 0.00 ريال</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="side-section">
                                <div class="action-stack d-grid gap-2">
                                    <button type="submit" class="btn btn-primary" id="btnSubmit"
                                        @disabled(!$canCreateInvoice)>
                                        <i class="fas fa-save me-1"></i>
                                        حفظ وإصدار الفاتورة
                                    </button>
                                    @if (\App\Support\InvoiceAuthorization::canView())
                                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-list me-1"></i>
                                        الرجوع إلى جميع الفواتير
                                    </a>
                                    @endif
                                </div>

                                <div class="action-note mt-3">
                                    بعد الحفظ ستظهر أزرار العرض والطباعة هنا مباشرة، بدون الحاجة للنزول في الصفحة.
                                </div>

                                <div class="post-save-actions d-none mt-3" id="postSaveActions">
                                    <h6>
                                        <i class="fas fa-circle-check me-1"></i>
                                        تم إصدار الفاتورة
                                    </h6>
                                    <div class="d-grid gap-2">
                                        @if (\App\Support\InvoiceAuthorization::canView())
                                        <a href="#" class="btn btn-outline-primary" id="viewInvoiceBtn">
                                            <i class="fas fa-eye me-1"></i>
                                            عرض الفاتورة
                                        </a>
                                        @endif
                                        @if (\App\Support\InvoiceAuthorization::canPrint())
                                        <a href="#" class="btn btn-primary" id="printInvoiceBtn" target="_blank"
                                            rel="noopener">
                                            <i class="fas fa-print me-1"></i>
                                            طباعة الفاتورة
                                        </a>
                                        @endif
                                        <a href="#" class="btn btn-outline-dark d-none js-post-save-link"
                                            id="printInvoiceConstraintBtn" target="_blank" rel="noopener">
                                            <i class="fas fa-book-open me-1"></i>
                                            1. طباعة <span id="invoiceConstraintLabel">قيد المبيعات</span>
                                        </a>
                                        <a href="#" class="btn btn-outline-primary d-none js-post-save-link"
                                            id="printReceiptBondBtn" target="_blank" rel="noopener">
                                            <i class="fas fa-receipt me-1"></i>
                                            طباعة سند القبض
                                        </a>
                                        <a href="#" class="btn btn-outline-dark d-none js-post-save-link"
                                            id="printReceiptConstraintBtn" target="_blank" rel="noopener">
                                            <i class="fas fa-file-invoice-dollar me-1"></i>
                                            2. طباعة قيد تحصيل مساند
                                        </a>
                                        <a href="#" class="btn btn-outline-dark d-none js-post-save-link"
                                            id="printMusanedCommissionConstraintBtn" target="_blank" rel="noopener">
                                            <i class="fas fa-building-columns me-1"></i>
                                            3. طباعة قيد التكاليف البنكية
                                        </a>
                                        <a href="#" class="btn btn-outline-dark d-none js-post-save-link"
                                            id="printDelegationCostConstraintBtn" target="_blank" rel="noopener">
                                            <i class="fas fa-coins me-1"></i>
                                            طباعة قيد تكلفة إنجاز
                                        </a>
                                        <a href="{{ route('invoices.create') }}" class="btn btn-outline-success"
                                            id="addAnotherInvoiceBtn">
                                            <i class="fas fa-plus me-1"></i>
                                            إصدار فاتورة أخرى
                                        </a>
                                        @if (\App\Support\InvoiceAuthorization::canView())
                                        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary"
                                            id="allInvoicesBtn">
                                            <i class="fas fa-table-list me-1"></i>
                                            جميع الفواتير
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        $(function() {
            const $form = $('#invoiceForm');
            const $submitButton = $('#btnSubmit');
            const submitButtonHtml = $submitButton.html();

            function parseNumber(selector) {
                const value = parseFloat($(selector).val());
                return Number.isFinite(value) ? value : 0;
            }

            function formatMoney(value) {
                return `${Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                })} ريال`;
            }

            function formatInvoiceDate(value) {
                const parts = String(value || '').split('-');
                return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : '---';
            }

            function setPreviewValue(selector, value, fallback = 'غير محدد') {
                $(selector).text(value && String(value).trim() !== '' ? value : fallback);
            }

            function setCostValue(selector, value) {
                const parsed = parseFloat(value);
                $(selector).val(Number.isFinite(parsed) ? parsed.toFixed(2) : '0.00');
            }

            function updateNationalityCostHint(hasCosts) {
                const $hint = $('#nationalityCostHint');
                if (!$hint.length) {
                    return;
                }

                $hint
                    .toggleClass('alert-info', hasCosts)
                    .toggleClass('alert-warning', !hasCosts)
                    .text(hasCosts
                        ? 'تم تحميل تكاليف الاستقدام المحفوظة على الجنسية المحددة.'
                        : 'لا توجد تكاليف استقدام محفوظة لهذه الجنسية. أدخل القيم هنا وسيتم حفظها على الجنسية عند إصدار الفاتورة.');
            }

            function applyNationalityCosts($option) {
                const hasOption = $option && $option.length && $option.val();
                const hasCosts = hasOption && Number($option.attr('data-has-nationality-costs')) === 1;

                setCostValue('#direct_recruitment_cost', hasOption ? $option.attr('data-direct-cost') : 0);
                setCostValue('#external_recruitment_cost', hasOption ? $option.attr('data-external-cost') : 0);
                setCostValue('#government_cost', hasOption ? $option.attr('data-government-cost') : 0);
                const commissionRate = hasOption
                    ? Number($option.attr('data-musaned-commission-rate'))
                    : @json(\App\Http\Controllers\ingaz\MusanedTransferController::DEFAULT_COMMISSION_RATE);
                musanedCommissionRate = Number.isFinite(commissionRate)
                    ? commissionRate
                    : @json(\App\Http\Controllers\ingaz\MusanedTransferController::DEFAULT_COMMISSION_RATE);
                musanedCommissionDriver = 'rate';
                updateNationalityCostHint(Boolean(hasCosts));
            }

            function currentInvoiceMode() {
                return $('#invoice_mode').val() || '{{ $invoiceMode }}';
            }

            function updateModeSections() {
                const mode = currentInvoiceMode();
                $('.invoice-mode-section').each(function() {
                    $(this).toggleClass('d-none', $(this).data('mode-section') !== mode);
                });

                $('#contract_id').prop('disabled', mode !== 'contract');
                $('#client_id').prop('disabled', mode !== 'general');
                $('.general-label-input, #general_description').prop('disabled', mode !== 'general');
                $('#directCostLabel').text(mode === 'general' ? 'بند خدمة خاضع للضريبة' : 'الاستقدام المباشر');
                $('#externalCostLabelText').text(mode === 'general' ? 'بند خدمة غير خاضع' : 'الاستقدام الخارجي');
                $('#externalCostRequiredMark').toggleClass('d-none', mode === 'general');
                $('#external_recruitment_cost')
                    .prop('required', mode !== 'general')
                    .attr('min', mode === 'general' ? '0' : '0.01');
                $('#governmentCostLabel').text(mode === 'general' ? 'رسوم حكومية' : 'تكاليف حكومية');
                $('#ticketCostLabel').text(mode === 'general' ? 'بند إضافي خاضع للضريبة' : 'التذكرة الداخلية');
                $('.invoice-line-editable').prop('readonly', mode !== 'general' && !@json($canEditInvoiceLineItems));

                if (mode === 'contract') {
                    $('#client_id').val(null).trigger('change');
                } else {
                    $('#contract_id').val('').trigger('change');
                }

                updateContractPreview();
            }

            function generalLabel(selector, fallback) {
                const value = String($(selector).val() || '').trim();
                return value || fallback;
            }

            function updateGeneralClientPreview() {
                const data = $.fn.select2 && $('#client_id').hasClass('select2-hidden-accessible')
                    ? ($('#client_id').select2('data')[0] || {})
                    : {};
                const clientId = $('#client_id').val();
                const textParts = String(data.text || '').split(' - ');
                const clientName = data.name || textParts[0] || 'غير محدد';
                const clientPhone = data.phone || textParts[1] || 'غير محدد';
                const clientIdentity = data.national_num || data.identity || textParts[2] || 'غير محدد';
                const clientIdentityLabel = data.identity_label || data.identityLabel || 'رقم الهوية الوطنية';

                $('#contractPreview').toggleClass('is-empty', !clientId);
                setPreviewValue('#previewReferenceLabel', 'نوع الفاتورة');
                setPreviewValue('#previewContractNumber', clientId ? 'فاتورة عامة' : '---', '---');
                setPreviewValue('#previewClientName', clientName);
                setPreviewValue('#previewNationalityLabel', 'الارتباط');
                setPreviewValue('#previewNationality', 'عميل فقط');
                setPreviewValue('#previewStatusLabel', 'الحالة');
                setPreviewValue('#previewContractStatus', 'جاهزة للإصدار');
                setPreviewValue('#previewClientIdentityLabel', clientIdentityLabel);
                setPreviewValue('#previewClientIdentity', clientIdentity);
                setPreviewValue('#previewClientPhone', clientPhone);
                $('#nationalityCostHint')
                    .removeClass('alert-warning')
                    .addClass('alert-info')
                    .text('أدخل بنود الفاتورة العامة، وسيتم حفظها على العميل بدون ربطها بعقد.');
            }

            function updateContractPreview() {
                if (currentInvoiceMode() === 'general') {
                    updateGeneralClientPreview();
                    updateCalculation();
                    return;
                }

                const $select = $('#contract_id');

                if (!$select.length) {
                    return;
                }

                const $selectedOption = $select.find('option:selected');
                const contractId = $selectedOption.val();

                if (!contractId) {
                    $('#contractPreview').addClass('is-empty');
                    setPreviewValue('#previewContractNumber', '---', '---');
                    setPreviewValue('#previewClientName', '');
                    setPreviewValue('#previewNationality', '');
                    setPreviewValue('#previewContractStatus', '');
                    setPreviewValue('#previewClientIdentityLabel', 'رقم الهوية الوطنية');
                    setPreviewValue('#previewClientIdentity', '');
                    setPreviewValue('#previewClientPhone', '');
                    applyNationalityCosts(null);
                    updateCalculation();
                    return;
                }

                $('#contractPreview').removeClass('is-empty');
                setPreviewValue('#previewReferenceLabel', 'رقم العقد');
                setPreviewValue('#previewNationalityLabel', 'الجنسية');
                setPreviewValue('#previewStatusLabel', 'حالة العقد');
                setPreviewValue('#previewContractNumber', `#${contractId}`, '---');
                setPreviewValue('#previewClientName', $selectedOption.data('client-name'));
                setPreviewValue('#previewNationality', $selectedOption.data('nationality'));
                setPreviewValue('#previewContractStatus', $selectedOption.data('status'));
                setPreviewValue('#previewClientIdentityLabel', $selectedOption.data('client-identity-label') || 'رقم الهوية الوطنية');
                setPreviewValue('#previewClientIdentity', $selectedOption.data('client-identity'));
                setPreviewValue('#previewClientPhone', $selectedOption.data('client-phone'));
                applyNationalityCosts($selectedOption);
                updateCalculation();
            }

            let musanedCommissionRate = @json($currentMusanedCommissionRate);
            let musanedCommissionDriver = @json(old('musaned_commission_amount') !== null ? 'amount' : 'rate');

            function syncMusanedCommission(totalDue) {
                const amountInput = $('#musaned_commission_amount');
                if (!amountInput.length) return;

                let amount = Math.min(totalDue, Math.max(0, Number(amountInput.val()) || 0));

                if (musanedCommissionDriver !== 'amount') {
                    amount = Math.round(totalDue * musanedCommissionRate) / 100;
                    amountInput.val(amount.toFixed(2));
                }

                $('#summaryMusanedNet').text(formatMoney(Math.max(0, totalDue - amount)));
            }

            function updateCalculation() {
                const directCost = parseNumber('#direct_recruitment_cost');
                const externalCost = parseNumber('#external_recruitment_cost');
                const governmentCost = parseNumber('#government_cost');
                const ticketCost = parseNumber('#internal_ticket_cost');

                const directCount = parseNumber('#direct_recruitment_count');
                const externalCount = parseNumber('#external_recruitment_count');
                const governmentCount = parseNumber('#government_cost_count');
                const ticketCount = parseNumber('#internal_ticket_count');
                const discount = parseNumber('#discount');

                const directTotal = directCost * directCount;
                const externalTotal = externalCost * externalCount;
                const governmentTotal = governmentCost * governmentCount;
                const ticketTotal = ticketCost * ticketCount;

                const subtotal = directTotal + externalTotal + governmentTotal + ticketTotal;
                const taxableSubtotal = directTotal + ticketTotal;
                const vat = taxableSubtotal * 0.15;
                const totalDue = Math.max(0, subtotal + vat - discount);

                $('#subtotal').val(subtotal.toFixed(2));
                $('#vat_amount').val(vat.toFixed(2));
                $('#total_due').val(totalDue.toFixed(2));

                $('#summarySubtotal').text(formatMoney(subtotal));
                $('#summaryVat').text(formatMoney(vat));
                $('#summaryDiscount').text(formatMoney(discount));
                $('#summaryGrandTotal').text(formatMoney(totalDue));
                const musanedInvoice = @json(\App\Support\InvoiceSupplierWorkflow::enabled()) && ['contract', 'delegation'].includes(currentInvoiceMode());
                $('.musaned-summary').toggle(musanedInvoice);
                $('#musaned_commission_amount').prop('disabled', !musanedInvoice);
                syncMusanedCommission(totalDue);
                const supplierOptions = $('#supplierInvoiceOptions');
                supplierOptions.toggle(currentInvoiceMode() === 'contract');
                updateSupplierOptions();

                const isGeneral = currentInvoiceMode() === 'general';
                const directLabel = isGeneral ? generalLabel('#general_direct_label', 'خدمة عامة') : 'المباشر';
                const externalLabel = isGeneral ? generalLabel('#general_external_label', 'بند غير خاضع') : 'الخارجي';
                const governmentLabel = isGeneral ? generalLabel('#general_government_label', 'رسوم حكومية') : 'الحكومي';
                const ticketLabel = isGeneral ? generalLabel('#general_ticket_label', 'خدمة إضافية') : 'التذاكر';

                $('#summaryDirectLabel').text(directLabel);
                $('#summaryExternalLabel').text(externalLabel);
                $('#summaryGovernmentLabel').text(governmentLabel);
                $('#summaryTicketLabel').text(ticketLabel);

                $('#summaryDirectLine').text(
                    `${directCount} × ${directCost.toFixed(2)} = ${formatMoney(directTotal)}`);
                $('#summaryExternalLine').text(
                    `${externalCount} × ${externalCost.toFixed(2)} = ${formatMoney(externalTotal)}`
                );
                $('#summaryGovernmentLine').text(
                    `${governmentCount} × ${governmentCost.toFixed(2)} = ${formatMoney(governmentTotal)}`
                );
                $('#summaryTicketLine').text(
                    `${ticketCount} × ${ticketCost.toFixed(2)} = ${formatMoney(ticketTotal)}`);
            }

            function extractErrorMessage(xhr) {
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        return xhr.responseJSON.message;
                    }

                    if (xhr.responseJSON.errors) {
                        const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                        if (firstKey && xhr.responseJSON.errors[firstKey][0]) {
                            return xhr.responseJSON.errors[firstKey][0];
                        }
                    }
                }

                return 'حدث خطأ أثناء إرسال الفاتورة.';
            }

            function revealPostSaveActions(response) {
                $('#viewInvoiceBtn').attr('href', response.view_url || response.redirect || '#');
                $('#printInvoiceBtn').attr('href', response.print_url || '#');
                $('#printInvoiceConstraintBtn')
                    .attr('href', response.invoice_constraint_print_url || '#')
                    .toggleClass('d-none', !response.invoice_constraint_print_url);
                $('#invoiceConstraintLabel').text(response.invoice_constraint_label || 'قيد المبيعات');
                $('#printReceiptBondBtn')
                    .attr('href', response.receipt_bond_print_url || '#')
                    .toggleClass('d-none', !response.receipt_bond_print_url);
                $('#printReceiptConstraintBtn')
                    .attr('href', response.receipt_constraint_print_url || '#')
                    .toggleClass('d-none', !response.receipt_constraint_print_url);
                $('#printMusanedCommissionConstraintBtn')
                    .attr('href', response.musaned_commission_constraint_print_url || '#')
                    .toggleClass('d-none', !response.musaned_commission_constraint_print_url);
                $('#printDelegationCostConstraintBtn')
                    .attr('href', response.delegation_cost_constraint_print_url || '#')
                    .toggleClass('d-none', !response.delegation_cost_constraint_print_url);
                $('#addAnotherInvoiceBtn').attr('href', response.create_url || "{{ route('invoices.create') }}");
                $('#allInvoicesBtn').attr('href', response.index_url || "{{ route('invoices.index') }}");
                $('#postSaveActions').removeClass('d-none');
            }

            function updateSupplierOptions() {
                const panel = $('#supplierInvoiceOptions');
                if (!panel.length) return;
                const checkbox = panel.find('[name="create_supplier_invoice"]');
                const enabled = currentInvoiceMode() === 'contract' && checkbox.is(':checked');
                checkbox.prop('disabled', currentInvoiceMode() !== 'contract');
                panel.find(':input').not(checkbox).prop('disabled', !enabled);
                const partial = enabled && panel.find('[name="supplier_recognition_mode"]').val() === 'partial';
                panel.find('[name="supplier_partial_cost_usd"]').prop('disabled', !partial).prop('required', partial);
            }
            $('#supplierInvoiceOptions :input').on('change', updateSupplierOptions);
            $('#musaned_commission_amount').on('input change', function() {
                musanedCommissionDriver = 'amount';
                syncMusanedCommission(parseNumber('#total_due'));
            });
            $('.calc-input').on('input', updateCalculation);
            $('.general-label-input').on('input', updateCalculation);
            $('#invoice_date').on('change input', function() {
                $('#previewInvoiceDate').text(formatInvoiceDate($(this).val()));
            });
            $('#contract_id').on('change', updateContractPreview);
            $('#invoice_mode').on('change', updateModeSections);
            $('#client_id').on('change select2:select select2:clear', updateContractPreview);

            if ($.fn.select2) {
                $('.ajax-client-select').select2({
                    placeholder: $('.ajax-client-select').data('placeholder') || 'ابحث عن العميل...',
                    allowClear: true,
                    dir: 'rtl',
                    width: '100%',
                    minimumInputLength: 1,
                    ajax: {
                        url: "{{ route('invoices.searchClients') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function(data) {
                            return data;
                        },
                        cache: true
                    }
                });
            }

            updateModeSections();
            updateCalculation();

            $form.on('submit', function(e) {
                e.preventDefault();

                if ($submitButton.prop('disabled')) {
                    return;
                }

                if (currentInvoiceMode() === 'contract' && !$form.find('[name="contract_id"]').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ابحث عن العقد أولاً',
                        text: 'اكتب رقم العقد في مربع البحث واضغط بحث قبل إصدار الفاتورة.',
                    });
                    return;
                }

                $.ajax({
                    url: $form.attr('action'),
                    method: $form.attr('method') || 'POST',
                    data: $form.serialize(),
                    beforeSend: function() {
                        $submitButton.prop('disabled', true).html(`
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            جاري الحفظ...
                        `);
                    },
                    success: function(response) {
                        if (!response.success) {
                            $submitButton.prop('disabled', false).html(submitButtonHtml);

                            Swal.fire({
                                icon: 'error',
                                title: 'تعذر إصدار الفاتورة',
                                text: response.message ||
                                    'حدث خطأ أثناء إصدار الفاتورة.',
                            });
                            return;
                        }

                        if (response.financial_workspace_url) {
                            window.location.href = response.financial_workspace_url;
                            return;
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'تم إصدار الفاتورة',
                            text: response.message || 'تم إصدار الفاتورة بنجاح.',
                        });

                        $form.find(':input').prop('disabled', true);
                        $submitButton
                            .prop('disabled', true)
                            .removeClass('btn-primary')
                            .addClass('btn-success')
                            .html('<i class="fas fa-check me-1"></i> تم إصدار الفاتورة');

                        const issuedInvoiceNumber = response.invoice_number || response.data?.id;
                        if (issuedInvoiceNumber) {
                            $('#invoice_number_preview').val(issuedInvoiceNumber);
                            $('#previewInvoiceNumber').text(`#${issuedInvoiceNumber}`);
                        }

                        if (response.issued_at) {
                            $('#invoice_issued_at_preview').val(response.issued_at);
                            $('#previewInvoiceIssuedAt').text(response.issued_at);
                        }

                        revealPostSaveActions(response);
                    },
                    error: function(xhr) {
                        $submitButton.prop('disabled', false).html(submitButtonHtml);

                        Swal.fire({
                            icon: 'error',
                            title: 'تعذر إصدار الفاتورة',
                            text: extractErrorMessage(xhr),
                        });
                    },
                });
            });
        });
    </script>
@endsection
