@extends('admin.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/custom/status-badges.css">
@endsection
@section('page-title')
    {{ helperTrans('ingaz.contract details') }} - العقد رقم #{{ $contract->id }}
@endsection

@php
    $canShowDeliveryForm = in_array($contract->status, [\App\Enums\ingaz\ContractStatusEnum::Arrival->value, \App\Enums\ingaz\ContractStatusEnum::Completed->value], true);
    $deliveryFormUrl = route('contract_download_form.download', ['contract_id' => $contract->id]);
    $deliveryFormPdfUrl = route('contract_download_form.download', ['contract_id' => $contract->id, 'driver' => 'dompdf', 'download' => 1]);
    $statusTimelineItems = $statusTimeline['items'] ?? collect();
    $externalOfficeDispatch = $contract->externalOfficeDispatch;
    $externalOfficeDispatchState = $contract->getExternalOfficeDispatchState();
    $canManageExternalOfficeDispatch = $contract->isCurrentRecruitmentContract();
    $externalOfficeDispatchFormUrl = route('RecruitmentContract.externalOfficeDispatch.edit', $contract->id);
    $externalOfficeDispatchPrintUrl = $externalOfficeDispatch
        ? route('RecruitmentContract.externalOfficeDispatch.print', $contract->id)
        : null;
    $externalOfficeDispatchPdfUrl = $externalOfficeDispatch
        ? route('RecruitmentContract.externalOfficeDispatch.print', ['contract' => $contract->id, 'driver' => 'dompdf', 'download' => 1])
        : null;
    $clientName = optional($contract->user)->name;
    $contractCreatedAt = $contract->created_at ? \Carbon\Carbon::parse($contract->created_at) : null;
    $contractDate = $info->contract_date ? \Carbon\Carbon::parse($info->contract_date) : null;
    $contractExpiryDate = $contractDate
        ? $contractDate->copy()->addYears((int) ($info->contract_duration ?: 2))
        : null;
    $contractDurationLabel = match ((int) ($info->contract_duration ?? 0)) {
        1 => 'سنة واحدة',
        2 => 'سنتان',
        0 => 'غير محدد',
        default => ($info->contract_duration ?? 0) . ' سنوات',
    };
    $clientPhone = trim(implode(' ', array_filter([
        optional($contract->user)->phone_code,
        optional($contract->user)->phone,
    ]))) ?: 'غير محدد';
    $canSelectContractMaid = \AdminGuardHasAnyPerm([
        \App\NewPermKeys::REC_CONTRACTS_CREATE,
        \App\NewPermKeys::REC_CONTRACTS_UPDATE,
        \App\NewPermKeys::REC_CONTRACTS_MANAGE,
    ]);
@endphp

<style>
    /* تحسين المظهر العام */
    .contract-details-container {
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, 0.10), transparent 28%),
            linear-gradient(180deg, #f6f8fb 0%, #eef3f8 100%);
        border: 1px solid #d7e3ef;
        border-radius: 24px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .details-page-shell {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .contract-header {
        background: linear-gradient(135deg, #0f3d68 0%, #16608a 52%, #1d7cb0 100%);
        color: white;
        padding: 24px;
        border-radius: 20px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        flex-wrap: wrap;
        box-shadow: 0 18px 35px rgba(15, 61, 104, 0.22);
    }

    .contract-header-copy {
        max-width: 720px;
    }

    .contract-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 999px;
        padding: 6px 12px;
        margin-bottom: 12px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .contract-header h2 {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
    }

    .contract-header-subtitle {
        margin-top: 10px;
        font-size: 14px;
        line-height: 1.8;
        opacity: 0.92;
    }

    .contract-header-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: flex-end;
    }

    .contract-status,
    .header-stat {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 16px;
        padding: 12px 16px;
        min-width: 165px;
        backdrop-filter: blur(8px);
    }

    .header-stat-label {
        display: block;
        font-size: 12px;
        opacity: 0.75;
        margin-bottom: 6px;
    }

    .header-stat-value {
        font-size: 15px;
        font-weight: 600;
    }

    .page-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }

    .overview-card {
        background: #fff;
        border: 1px solid #dbe7f3;
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
    }

    .overview-label {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 8px;
        display: block;
    }

    .overview-value {
        font-size: 22px;
        line-height: 1.4;
        font-weight: 700;
        color: #0f172a;
    }

    .overview-meta {
        margin-top: 8px;
        font-size: 13px;
        color: #475569;
    }

    .info-card,
    .timeline-panel {
        background: white;
        border-radius: 18px;
        padding: 22px;
        margin-bottom: 20px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        border: 1px solid #dbe7f3;
        border-top: 4px solid #007bff;
        height: 100%;
    }

    .info-card.client-info {
        border-top-color: #28a745;
    }

    .info-card.maid-info {
        border-top-color: #f59e0b;
    }

    .info-card.contract-info {
        border-top-color: #0ea5e9;
    }

    .info-card.financial-info {
        border-top-color: #ef4444;
    }

    .card-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
    }

    .card-title i {
        margin-left: 10px;
        font-size: 20px;
    }

    .info-row {
        display: grid;
        grid-template-columns: minmax(110px, 170px) 1fr;
        gap: 16px;
        align-items: start;
        padding: 11px 0;
        border-bottom: 1px solid #edf2f7;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #475569;
    }

    .info-value {
        color: #111827;
        text-align: right;
        word-break: break-word;
    }

    .info-value.highlight {
        color: #007bff;
        font-weight: 600;
    }

    .info-value.success {
        color: #28a745;
        font-weight: 600;
    }

    .info-value.warning {
        color: #ffc107;
        font-weight: 600;
    }

    .info-value.danger {
        color: #dc3545;
        font-weight: 600;
    }

    /* تحسين أزرار الإجراءات */
    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 8px;
        flex-wrap: wrap;
    }

    .btn-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        text-decoration: none;
        color: white;
    }

    .btn-icon:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        color: white;
        text-decoration: none;
    }

    .btn-print {
        background: linear-gradient(135deg, #007bff, #0056b3);
    }

    .btn-invoice {
        background: linear-gradient(135deg, #7f24ff, #310076);
    }

    .btn-edit {
        background: linear-gradient(135deg, #28a745, #1e7e34);
    }

    .btn-back {
        background: linear-gradient(135deg, #6c757d, #545b62);
    }

    .btn-delete {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }

    .btn-change-maid {
        background: linear-gradient(135deg, #ffc107, #e0a800);
    }

    /* تحسين البادجات */
    .status-badge {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        font-weight: 600;
        border-radius: 25px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .status-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* تحسين الجداول */
    .info-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 640px;
    }

    .info-table th {
        background: #f8fafc;
        padding: 12px;
        text-align: right;
        font-weight: 600;
        color: #334155;
        border-bottom: 1px solid #dbe7f3;
    }

    .info-table td {
        padding: 12px;
        border-bottom: 1px solid #edf2f7;
    }

    .info-table tr:hover {
        background: #f8fbff;
    }

    .table-responsive {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
    }

    .attachment-upload-panel {
        background: #f8fbff;
        border: 1px solid #dbe7f3;
        border-radius: 14px;
        padding: 16px;
    }

    .attachment-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 12px;
    }

    .attachment-item {
        border: 1px solid #dbe7f3;
        border-radius: 12px;
        padding: 14px;
        background: #fff;
        min-height: 100%;
    }

    .attachment-item-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .attachment-meta {
        color: #64748b;
        font-size: 12px;
        margin-top: 8px;
        line-height: 1.8;
    }

    .contract-document-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 12px;
    }

    .contract-document-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px;
        border: 1px solid #dbe7f3;
        border-radius: 12px;
        color: #0f172a;
        background: #fff;
        text-decoration: none;
        min-height: 72px;
    }

    .contract-document-link:hover {
        border-color: #0d6efd;
        color: #0d6efd;
        text-decoration: none;
    }

    .contract-document-link i {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #eef6ff;
        color: #0d6efd;
        flex-shrink: 0;
    }

    .contract-document-link.has-items {
        align-items: flex-start;
    }

    .attachments-dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .attachment-summary-tile {
        background: #f8fbff;
        border: 1px solid #dbe7f3;
        border-radius: 14px;
        padding: 14px;
    }

    .attachment-summary-label {
        color: #64748b;
        display: block;
        font-size: 12px;
        margin-bottom: 6px;
    }

    .attachment-summary-value {
        color: #0f172a;
        font-size: 22px;
        font-weight: 800;
        line-height: 1.2;
    }

    .attachments-section-block {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px;
        margin-bottom: 18px;
        background: #fff;
    }

    .attachments-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .attachments-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #1f2937;
        font-size: 16px;
        font-weight: 800;
        margin: 0;
    }

    .attachment-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 12px;
    }

    .contract-document-items {
        display: flex;
        flex-direction: column;
        gap: 7px;
        margin-top: 10px;
    }

    .contract-document-items a {
        color: #0d6efd;
        font-size: 12px;
        text-decoration: none;
    }

    .contract-document-items a:hover {
        text-decoration: underline;
    }

    .contract-document-link .contract-document-items a i {
        width: auto;
        height: auto;
        display: inline;
        border-radius: 0;
        background: transparent;
        color: inherit;
    }

    /* تحسين Modal */
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #dee2e6;
        border-radius: 15px 15px 0 0;
    }

    .modal-title {
        color: #495057;
        font-weight: 600;
    }

    .modal-title i {
        margin-left: 8px;
        color: #007bff;
    }

    /* تحسين النماذج */
    .form-check {
        margin-bottom: 10px;
        padding: 10px;
        border-radius: 8px;
        transition: background-color 0.2s ease;
    }

    .form-check:hover {
        background-color: #f8f9fa;
    }

    .form-check-label {
        font-weight: 500;
        color: #495057;
        cursor: pointer;
        display: flex;
        align-items: center;
    }

    .form-check-label i {
        margin-left: 8px;
        font-size: 16px;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* تحسين التنبيهات */
    .alert {
        border-radius: 10px;
        border: none;
        padding: 15px 20px;
    }

    .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
    }

    .timeline-panel {
        margin-bottom: 24px;
        border-top-color: #1d4ed8;
    }

    .timeline-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .timeline-stat {
        background: linear-gradient(135deg, #163b65, #0d5f8f);
        color: white;
        border-radius: 14px;
        padding: 18px;
        box-shadow: 0 10px 22px rgba(13, 95, 143, 0.18);
    }

    .timeline-stat-label {
        font-size: 13px;
        opacity: 0.85;
        margin-bottom: 8px;
    }

    .timeline-stat-value {
        font-size: 20px;
        font-weight: 700;
        line-height: 1.5;
    }

    .timeline-stat-meta {
        font-size: 13px;
        opacity: 0.88;
        margin-top: 6px;
    }

    .contract-stage-timeline {
        position: relative;
        padding-right: 34px;
    }

    .contract-stage-timeline::before {
        content: '';
        position: absolute;
        top: 12px;
        bottom: 12px;
        right: 12px;
        width: 3px;
        background: linear-gradient(180deg, #0d6efd 0%, #71b7ff 100%);
        border-radius: 999px;
    }

    .contract-stage-item {
        position: relative;
        background: #f8fbff;
        border: 1px solid #dfeaf5;
        border-radius: 14px;
        padding: 16px 18px;
        margin-bottom: 14px;
    }

    .contract-stage-item::before {
        content: '';
        position: absolute;
        top: 22px;
        right: -28px;
        width: 18px;
        height: 18px;
        background: #0d6efd;
        border: 4px solid #d8e8ff;
        border-radius: 50%;
        box-shadow: 0 0 0 4px #f8fbff;
    }

    .contract-stage-item.current {
        background: #fff8ea;
        border-color: #ffd98a;
    }

    .contract-stage-item.current::before {
        background: #f59e0b;
        border-color: #ffe9bd;
    }

    .contract-stage-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .stage-order {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e3eeff;
        color: #0d4ea6;
        font-weight: 700;
    }

    .stage-name {
        font-size: 17px;
        font-weight: 700;
        color: #1f2937;
    }

    .stage-duration {
        background: #e8f1ff;
        color: #0d4ea6;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 13px;
        font-weight: 700;
    }

    .contract-stage-item.current .stage-duration {
        background: #fff0c7;
        color: #9a6700;
    }

    .stage-dates {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
    }

    .stage-date-box {
        background: white;
        border: 1px solid #edf2f7;
        border-radius: 10px;
        padding: 10px 12px;
    }

    .stage-date-label {
        display: block;
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 4px;
    }

    .stage-date-value {
        font-size: 14px;
        font-weight: 600;
        color: #212529;
    }

    .stage-notes {
        margin-top: 10px;
        background: white;
        border: 1px dashed #d0dbe7;
        border-radius: 10px;
        padding: 10px 12px;
        color: #495057;
        font-size: 13px;
    }

    .timeline-caption {
        color: #6c757d;
        font-size: 13px;
        margin-bottom: 18px;
    }

    /* تحسين الطباعة */
    @media print {

        .action-buttons,
        .modal {
            display: none !important;
        }

        .contract-details-container {
            background: white !important;
            box-shadow: none !important;
        }
    }

    @media (max-width: 767.98px) {
        .contract-details-container {
            padding: 16px;
            border-radius: 18px;
        }

        .contract-header {
            padding: 18px;
        }

        .contract-header h2 {
            font-size: 23px;
        }

        .contract-status,
        .header-stat {
            width: 100%;
            min-width: 0;
        }

        .page-overview {
            grid-template-columns: 1fr;
        }

        .info-row {
            grid-template-columns: 1fr;
            gap: 6px;
        }

        .contract-stage-timeline {
            padding-right: 26px;
        }

        .contract-stage-item::before {
            right: -22px;
        }
    }

    a.btn-edit {
        width: 60px !important;
        height: 60px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 20px !important;
        transition: all 0.3s ease !important;
        border: none !important;
        cursor: pointer !important;
        text-decoration: none !important;
        color: white !important;
    }
</style>

@section('content')
    @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW))

        <div class="contract-details-container" id="printDiv">
            <div class="details-page-shell">
                <!-- رأس العقد -->
                <div class="contract-header">
                    <div class="contract-header-copy">
                        <div class="contract-eyebrow">
                            <i class="fas fa-folder-open"></i>
                            ملف العقد
                        </div>
                        <h2><i class="fas fa-file-contract me-2"></i>تفاصيل العقد رقم #{{ $contract->id }}</h2>
                        <div class="contract-header-subtitle">
                            {{ $clientName ? 'متابعة شاملة لبيانات العميل والعاملة والمراحل الزمنية للعقد.' : 'عرض تفصيلي لبيانات العقد وحالته الحالية.' }}
                        </div>
                    </div>

                    <div class="contract-header-meta">
                        <div class="contract-status">
                            <span class="header-stat-label">الحالة الحالية</span>
                            <div class="header-stat-value">
                                <i class="fas fa-circle me-2"></i>
                                {{ getContractStatus($contract->status) }}
                            </div>
                        </div>
                        <div class="header-stat">
                            <span class="header-stat-label">تاريخ الإنشاء</span>
                            <div class="header-stat-value">{{ $contractCreatedAt ? $contractCreatedAt->format('Y-m-d H:i') : 'غير محدد' }}</div>
                        </div>
                    </div>
                </div>

                <div class="page-overview">
                    <div class="overview-card">
                        <span class="overview-label">العميل</span>
                        <div class="overview-value">{{ $clientName ?? 'غير محدد' }}</div>
                        <div class="overview-meta">{{ $clientPhone }}</div>
                    </div>

                    <div class="overview-card">
                        <span class="overview-label">إجمالي مدة العقد في النظام</span>
                        <div class="overview-value">{{ $durationSummary['total_label'] ?? 'غير محدد' }}</div>
                        <div class="overview-meta">حتى {{ $durationSummary['ended_at_label'] ?? 'غير محدد' }}</div>
                    </div>

                    <div class="overview-card">
                        <span class="overview-label">المرحلة الحالية</span>
                        <div class="overview-value">{{ $statusTimeline['current_stage_label'] ?? 'غير محدد' }}</div>
                        <div class="overview-meta">{{ $statusTimeline['current_stage_duration'] ?? 'لا توجد مدة محسوبة' }}</div>
                    </div>

                    <div class="overview-card">
                        <span class="overview-label">مدة التعاقد</span>
                        <div class="overview-value">{{ $contractDurationLabel }}</div>
                        <div class="overview-meta">ينتهي في {{ $contractExpiryDate ? $contractExpiryDate->format('Y-m-d') : 'غير محدد' }}</div>
                    </div>
                </div>

                <div class="row">
                <!-- معلومات العميل -->
                <div class="col-md-6 mb-4">
                    <div class="info-card client-info">
                        <div class="card-title">
                            <i class="fas fa-user text-success"></i>
                            معلومات العميل
                        </div>
                        <div class="info-row">
                            <span class="info-label">الاسم:</span>
                            <span class="info-value highlight">{{ $contract->user->name ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم الجوال:</span>
                            <span class="info-value">{{ $clientPhone }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ الميلاد:</span>
                            <span
                                class="info-value">{{ $contract->user->birthdate ? \Carbon\Carbon::parse($contract->user->birthdate)->format('Y-m-d') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">الجنسية:</span>
                            <span class="info-value">{{ $contract->user->nationality->title ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم الهوية:</span>
                            <span class="info-value">{{ $contract->user->national_num ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">البريد الإلكتروني:</span>
                            <span class="info-value">{{ $contract->user->email ?? 'غير محدد' }}</span>
                        </div>
                        @if (isset($contract->user->addresses[0]))
                            <div class="info-row">
                                <span class="info-label">العنوان:</span>
                                <span
                                    class="info-value">{{ $contract->user->addresses[0]->address . ' ' . $contract->user->addresses[0]->title }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- معلومات العاملة -->
                <div class="col-md-6 mb-4">
                    @php
                        $manualMaidInfo = $contract->info;
                        $hasManualMaidSummary = $manualMaidInfo && $manualMaidInfo->hasManualMaidSummary();
                    @endphp
                    <div class="info-card maid-info">
                        <div class="card-title">
                            <i class="fas fa-user-tie text-warning"></i>
                            معلومات العاملة
                        </div>
                        @if ($contract->maid)
                            <div class="info-row">
                                <span class="info-label">اسم العاملة:</span>
                                <span class="info-value highlight">{{ $contract->maid->name ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">رقم الجواز:</span>
                                <span class="info-value">{{ $contract->maid->passport_num ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الجنسية:</span>
                                <span
                                    class="info-value">{{ $contract->maid && $contract->maid->nationality ? $contract->maid->nationality->sub_title : 'غير محدد' }}</span>
                            </div>
                            @php
                                $office = $contract->maid?->office ?? $contract->maid?->recruitmentOffice;
                            @endphp
                            <div class="info-row">
                                <span class="info-label">المكتب الخارجي:</span>
                                <span
                                    class="info-value">{{ $office->title ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الدين:</span>
                                <span
                                    class="info-value">{{ $contract->maid && $contract->maid->religion ? $contract->maid->religion->title : 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">المهنة:</span>
                                <span class="info-value">
                                    @if ($contract->maid && $contract->maid->jobTitles && $contract->maid->jobTitles->count() > 0)
                                        {{ $contract->maid->jobTitles->first()->title }}
                                    @else
                                        غير محدد
                                    @endif
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">سنوات الخبرة:</span>
                                <span class="info-value">{{ $contract->maid->experience_years ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">حالة الخبرة:</span>
                                <span class="info-value">{{ $contract->maid->experience_status ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">العمر:</span>
                                <span class="info-value">{{ $contract->maid->age ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">السعر:</span>
                                <span class="info-value success">{{ $contract->maid->price ?? 'غير محدد' }} ريال</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الراتب:</span>
                                <span class="info-value success">{{ $contract->maid->salary ?? 'غير محدد' }} ريال</span>
                            </div>
                        @elseif ($hasManualMaidSummary)
                            <div class="alert alert-info mb-3">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div>
                                        <strong>بيانات عاملة مختصرة محفوظة</strong>
                                        <div class="text-muted small">تم إنشاء العقد بدون ربط عاملة فعلية حتى الآن.</div>
                                    </div>
                                    @if ($contract->status == 'pending_maid_selection' && $canSelectContractMaid)
                                        <a href="{{ route('RecruitmentContract.selectMaid', $contract->id) }}"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-user-plus me-1"></i>إضافة عاملة فعلية
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="info-row">
                                <span class="info-label">اسم العاملة:</span>
                                <span class="info-value highlight">{{ $manualMaidInfo->manual_maid_name ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">رقم الجواز:</span>
                                <span class="info-value">{{ $manualMaidInfo->manual_maid_passport_num ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الجنسية:</span>
                                <span class="info-value">{{ $manualMaidInfo->manualMaidNationality?->sub_title ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">المكتب الخارجي:</span>
                                <span class="info-value">{{ $manualMaidInfo->manualMaidOffice?->title ?? 'غير محدد' }}</span>
                            </div>
                            @if ($manualMaidInfo->manual_maid_attachment && showFile($manualMaidInfo->manual_maid_attachment))
                                <div class="info-row">
                                    <span class="info-label">المرفق:</span>
                                    <span class="info-value">
                                        <a href="{{ showFile($manualMaidInfo->manual_maid_attachment) }}"
                                            target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-paperclip me-1"></i>عرض المرفق
                                        </a>
                                    </span>
                                </div>
                            @endif
                        @else
                            <div class="alert alert-warning mb-0">
                                <div class="text-center">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                                    @if ($contract->status == 'pending_maid_selection' && $canSelectContractMaid)
                                        <h5 class="text-warning"><strong>في انتظار اختيار العاملة</strong></h5>
                                        <p class="text-muted mb-2">هذا العقد في مرحلة انتظار اختيار العاملة المناسبة</p>
                                        <a href="{{ route('RecruitmentContract.selectMaid', $contract->id) }}"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-user-plus me-1"></i>إضافة عاملة
                                        </a>
                                    @else
                                        <h5 class="text-danger"><strong>لا توجد عاملة مخصصة</strong></h5>
                                        <p class="text-muted mb-0">لم يتم تخصيص عاملة لهذا العقد بعد</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                </div>

                <div class="row">
                <!-- معلومات العقد الأساسية -->
                <div class="col-md-6 mb-4">
                    <div class="info-card contract-info">
                        <div class="card-title">
                            <i class="fas fa-calendar-alt text-info"></i>
                            معلومات العقد الأساسية
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم العقد:</span>
                            <span class="info-value highlight">#{{ $contract->id }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ العقد:</span>
                            <span class="info-value">{{ $contractDate ? $contractDate->format('Y-m-d') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم مساند:</span>
                            <span class="info-value">{{ $info->msaned_number ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم تفويض:</span>
                            <span class="info-value">{{ $info->tafwed_number ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم التأشيرة:</span>
                            <span class="info-value">{{ $info->visa_number ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ التأشيرة الميلادي:</span>
                            <span
                                class="info-value">{{ $info->visa_milad_date ? \Carbon\Carbon::parse($info->visa_milad_date)->format('Y-m-d') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ التأشيرة الهجري:</span>
                            <span
                                class="info-value">{{ $info->visa_hijry_date ? \Carbon\Carbon::parse($info->visa_hijry_date)->format('Y-m-d') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">مدة العقد:</span>
                            <span class="info-value">{{ $contractDurationLabel }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ انتهاء العقد:</span>
                            <span class="info-value danger">{{ $contractExpiryDate ? $contractExpiryDate->format('Y-m-d') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ إنشاء العقد:</span>
                            <span
                                class="info-value">{{ $contract->created_at ? \Carbon\Carbon::parse($contract->created_at)->format('Y-m-d H:i') : 'غير محدد' }}</span>
                        </div>

                        @if ($contract->status == 'completed')
                            <div class="info-row">
                                <span class="info-label">حالة الضمان:</span>
                                <span class="info-value">
                                    @php
                                        $warrantyStatus = $contract->warranty_status ?? 'not_applicable';
                                        $warrantyText = $contract->warranty_status_text ?? 'غير محدد';
                                        $remainingDays = $contract->warranty_remaining_days ?? 0;
                                    @endphp

                                    @switch($warrantyStatus)
                                        @case('active')
                                            <span class="badge bg-success">
                                                <i class="fas fa-shield-alt me-1"></i>
                                                نشط ({{ $remainingDays }} يوم متبقي)
                                            </span>
                                        @break

                                        @case('expired')
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times-circle me-1"></i>
                                                منتهي
                                            </span>
                                        @break

                                        @default
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-minus-circle me-1"></i>
                                                غير قابل للتطبيق
                                            </span>
                                    @endswitch
                                </span>
                            </div>

                            @if ($contract->warranty_start_date)
                                <div class="info-row">
                                    <span class="info-label">تاريخ بداية الضمان:</span>
                                    <span
                                        class="info-value">{{ \Carbon\Carbon::parse($contract->warranty_start_date)->format('Y-m-d H:i') }}</span>
                                </div>
                            @endif

                            @if ($contract->warranty_end_date)
                                <div class="info-row">
                                    <span class="info-label">تاريخ انتهاء الضمان:</span>
                                    <span
                                        class="info-value {{ $contract->warranty_status == 'expired' ? 'danger' : '' }}">
                                        {{ \Carbon\Carbon::parse($contract->warranty_end_date)->format('Y-m-d H:i') }}
                                    </span>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- معلومات مالية -->
                <div class="col-md-6 mb-4">
                    <div class="info-card financial-info">
                        <div class="card-title">
                            <i class="fas fa-money-bill-wave text-danger"></i>
                            المعلومات المالية
                        </div>
                        <div class="info-row">
                            <span class="info-label">عدد ساعات العمل:</span>
                            <span class="info-value">{{ $info->working_hours_num ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">مبلغ نقل الكفالة:</span>
                            <span class="info-value success">{{ $info->sponsorship_price ?? 'غير محدد' }} ريال</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">الراتب بعد الكفالة:</span>
                            <span class="info-value success">{{ $info->salary_after_sponsorship ?? 'غير محدد' }}
                                ريال</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">التكلفة الشهرية:</span>
                            <span class="info-value warning">{{ $info->monthly_cost ?? 'غير محدد' }} ريال</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">قيمة العقد:</span>
                            <span class="info-value highlight">{{ $info->contract_value ?? 'غير محدد' }} ريال</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">الانجاز من:</span>
                            <span class="info-value">{{ $info->ingaz_from ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">الانجاز إلى:</span>
                            <span class="info-value">{{ $info->ingaz_to ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">الكفيل السابق:</span>
                            <span class="info-value">{{ $info->prevSponsorship->title ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">مقر الإيواء:</span>
                            <span class="info-value">{{ $info->shelter->title ?? 'غير محدد' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($contract->recruitmentRefund)
                @php
                    $refund = $contract->recruitmentRefund;
                    $refundPartyLabel = match ($refund->cancelled_by) {
                        'client' => 'العميل',
                        'office' => 'المكتب',
                        'external_office' => 'الوكيل/المكتب الخارجي',
                        'none_before_transfer' => 'قبل تحويل مساند',
                        default => 'غير محدد',
                    };
                    $refundConstraint = $refund->refundConstraint;
                    $refundConstraintDetails = $refundConstraint?->constraintDetails ?? collect();
                    $refundDebtorTotal = $refundConstraintDetails->sum('debtor_value');
                    $refundCreditorTotal = $refundConstraintDetails->sum('creditor_value');
                @endphp

                <div class="row" id="contractRefundSection">
                    <div class="col-12 mb-4">
                        <div class="info-card financial-info">
                            <div class="card-title">
                                <i class="fas fa-rotate-left text-primary"></i>
                                تفاصيل استرداد العقد
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6 col-md-3">
                                    <div class="overview-card h-100">
                                        <span class="overview-label">قيمة العقد</span>
                                        <div class="overview-value">{{ number_format((float) $refund->contract_amount, 2) }}</div>
                                        <div class="overview-meta">ريال</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="overview-card h-100">
                                        <span class="overview-label">قيمة الخصم</span>
                                        <div class="overview-value text-danger">{{ number_format((float) $refund->penalty_amount, 2) }}</div>
                                        <div class="overview-meta">{{ number_format((float) $refund->penalty_percent, 2) }}%</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="overview-card h-100">
                                        <span class="overview-label">المدفوع للعميل</span>
                                        <div class="overview-value text-success">{{ number_format((float) $refund->refund_amount, 2) }}</div>
                                        <div class="overview-meta">ريال</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="overview-card h-100">
                                        <span class="overview-label">عدد الأيام</span>
                                        <div class="overview-value">{{ $refund->days_from_contract }}</div>
                                        <div class="overview-meta">من تاريخ العقد</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <span class="info-label">تاريخ الاسترداد:</span>
                                        <span class="info-value">{{ optional($refund->refund_date)->format('Y-m-d') }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">الطرف المتسبب:</span>
                                        <span class="info-value highlight">{{ $refundPartyLabel }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">سبب الاسترداد:</span>
                                        <span class="info-value">{{ $refund->reason ?: 'غير محدد' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-row">
                                        <span class="info-label">تم بواسطة:</span>
                                        <span class="info-value">{{ $refund->createdBy->name ?? 'غير محدد' }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">قيد الاسترداد:</span>
                                        <span class="info-value">
                                            @if ($refundConstraint)
                                                <a class="btn btn-sm btn-outline-primary"
                                                    href="{{ route('finance.add-constraint.show', ['add_constraint' => $refundConstraint->id]) }}"
                                                    target="_blank">
                                                    <i class="fas fa-book-open me-1"></i>
                                                    عرض القيد #{{ $refundConstraint->code ?? $refundConstraint->id }}
                                                </a>
                                            @else
                                                <span class="badge bg-warning text-dark">لا يوجد قيد مالي</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">تاريخ التسجيل:</span>
                                        <span class="info-value">{{ optional($refund->created_at)->format('Y-m-d H:i') }}</span>
                                    </div>
                                </div>
                            </div>

                            @if ($refundConstraintDetails->isNotEmpty())
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered table-sm align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>الحساب</th>
                                                <th>مدين</th>
                                                <th>دائن</th>
                                                <th>البيان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($refundConstraintDetails as $detail)
                                                <tr>
                                                    <td>
                                                        @if ($detail->account?->code)
                                                            <span class="text-muted">#{{ $detail->account->code }}</span>
                                                        @endif
                                                        {{ $detail->account->name ?? 'غير محدد' }}
                                                    </td>
                                                    <td class="text-success fw-semibold">{{ number_format((float) $detail->debtor_value, 2) }}</td>
                                                    <td class="text-danger fw-semibold">{{ number_format((float) $detail->creditor_value, 2) }}</td>
                                                    <td class="text-muted small">{{ $detail->statement }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th>الإجمالي</th>
                                                <th>{{ number_format((float) $refundDebtorTotal, 2) }}</th>
                                                <th>{{ number_format((float) $refundCreditorTotal, 2) }}</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- معلومات المسوق -->
            @if ($marketer)
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="info-card">
                            <div class="card-title">
                                <i class="fas fa-user-tie text-primary"></i>
                                معلومات المسوق
                            </div>
                            <div class="info-row">
                                <span class="info-label">اسم المسوق:</span>
                                <span class="info-value highlight">{{ $marketer->name }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">رقم الجوال:</span>
                                <span class="info-value">{{ $marketer->phone ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">البريد الإلكتروني:</span>
                                <span class="info-value">{{ $marketer->email ?? 'غير محدد' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- مستندات الطلب --}}
                    @if ($contract->signature)
                        <div class="col-md-6 mb-4">
                            <div class="info-card">
                                <div class="card-title">
                                    <i class="fas fa-file-pdf text-danger me-2"></i>
                                    مستندات الطلب
                                </div>

                                <div class="info-row d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="info-label">عرض الملف:</span>
                                        <span class="info-value highlight">
                                            {{-- {{ $document->file_name }} --}}
                                        </span>
                                    </div>

                                    <!-- Eye icon -->
                                    <a href="{{ showFile($contract->signature) }}" target="_blank"
                                        class="text-secondary fs-5" title="عرض الملف">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- معلومات الطلب المسبق -->
            @if ($contract->order)
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="info-card">
                            <div class="card-title">
                                <i class="fas fa-list-check text-secondary"></i>
                                معلومات الطلب المسبق
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">رقم الطلب:</span>
                                        <span class="info-value highlight">{{ $contract->order->order_code }}</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">تاريخ الطلب:</span>
                                        <span
                                            class="info-value">{{ $contract->order->created_at ? \Carbon\Carbon::parse($contract->order->created_at)->format('Y-m-d H:i') : 'غير محدد' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">حالة الطلب:</span>
                                        <span
                                            class="status-badge bg-{{ $contract->order->status == 'completed' ? 'success' : 'warning' }}">
                                            {{ $contract->order->status == 'completed' ? 'مكتمل' : 'قيد المعالجة' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">طريقة الدفع:</span>
                                        <span
                                            class="info-value">{{ $contract->order->payment_method ?? 'غير محدد' }}</span>
                                    </div>
                                </div>
                                @if ($contract->order->details && $contract->order->details->package)
                                    <div class="col-md-6 mb-3">
                                        <div class="info-row">
                                            <span class="info-label">الحزمة المختارة:</span>
                                            <span
                                                class="info-value highlight">{{ $contract->order->details->package->title ?? 'غير محدد' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="info-row">
                                            <span class="info-label">سعر الحزمة:</span>
                                            <span
                                                class="info-value success">{{ $contract->order->details->package->price ? number_format($contract->order->details->package->price, 2) . ' ريال' : 'غير محدد' }}</span>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-6 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">نوع العمل:</span>
                                        <span
                                            class="info-value">{{ $contract->order->jobType->title ?? 'غير محدد' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($canManageExternalOfficeDispatch || $externalOfficeDispatch)
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="info-card">
                            <div class="card-title">
                                <i class="fas fa-paper-plane text-primary"></i>
                                إرسالية المكتب الخارجي
                            </div>
                            <div class="info-row">
                                <span class="info-label">الحالة:</span>
                                <span class="info-value">
                                    <span
                                        class="badge bg-{{ $externalOfficeDispatchState['code'] === 'completed' ? 'success' : ($externalOfficeDispatchState['code'] === 'outdated' ? 'warning text-dark' : 'secondary') }}">
                                        {{ $externalOfficeDispatchState['label'] }}
                                    </span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">التفاصيل:</span>
                                <span class="info-value">{{ $externalOfficeDispatchState['message'] }}</span>
                            </div>
                            @if ($canManageExternalOfficeDispatch)
                                <div class="alert alert-warning mt-3 mb-0">
                                    يجب إتمام إرسالية المكتب الخارجي قبل وصول العقد أو إغلاقه.
                                </div>
                            @endif
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                @if ($canManageExternalOfficeDispatch)
                                    <a href="{{ $externalOfficeDispatchFormUrl }}" class="btn btn-outline-primary">
                                        <i class="fas fa-pen-to-square me-1"></i>
                                        إدارة الإرسالية
                                    </a>
                                @endif
                                @if ($externalOfficeDispatchPrintUrl)
                                    <a href="{{ $externalOfficeDispatchPrintUrl }}" class="btn btn-outline-secondary"
                                        target="_blank" rel="noopener">
                                        <i class="fas fa-eye me-1"></i>
                                        معاينة الإرسالية
                                    </a>
                                @endif
                                @if ($externalOfficeDispatchPdfUrl)
                                    <a href="{{ $externalOfficeDispatchPdfUrl }}" class="btn btn-primary"
                                        target="_blank" rel="noopener">
                                        <i class="fas fa-download me-1"></i>
                                        تنزيل PDF
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($canShowDeliveryForm)
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="info-card">
                            <div class="card-title">
                                <i class="fas fa-file-signature text-primary"></i>
                                نموذج تسليم العاملة
                            </div>
                            <div class="info-row">
                                <span class="info-label">حالة النموذج:</span>
                                <span class="info-value">يتم توليد النموذج تلقائياً من بيانات هذا العقد وبيانات الوصول.</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a href="{{ $deliveryFormUrl }}" class="btn btn-outline-primary"
                                    target="_blank" rel="noopener">
                                    <i class="fas fa-eye me-1"></i>
                                    معاينة النموذج
                                </a>
                                <a href="{{ $deliveryFormPdfUrl }}" class="btn btn-primary" target="_blank"
                                    rel="noopener">
                                    <i class="fas fa-download me-1"></i>
                                    تنزيل PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- سجل الحالات -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="info-card">
                        @if ($statusTimelineItems->count())
                            <div class="timeline-panel">
                                <div class="card-title">
                                    <i class="fas fa-stream text-primary"></i>
                                    المخطط الزمني لمراحل العقد
                                </div>

                                <div class="timeline-summary">
                                    <div class="timeline-stat">
                                        <div class="timeline-stat-label">بداية المسار</div>
                                        <div class="timeline-stat-value">{{ $statusTimeline['started_at_formatted'] ?? 'غير محدد' }}</div>
                                    </div>
                                    <div class="timeline-stat">
                                        <div class="timeline-stat-label">إجمالي المدة</div>
                                        <div class="timeline-stat-value">{{ $statusTimeline['total_duration'] ?? 'غير محدد' }}</div>
                                    </div>
                                    <div class="timeline-stat">
                                        <div class="timeline-stat-label">أطول مرحلة</div>
                                        <div class="timeline-stat-value">{{ $statusTimeline['longest_stage_label'] ?? 'غير محدد' }}</div>
                                        <div class="timeline-stat-meta">{{ $statusTimeline['longest_stage_duration'] ?? '' }}</div>
                                    </div>
                                    <div class="timeline-stat">
                                        <div class="timeline-stat-label">الحالة الحالية</div>
                                        <div class="timeline-stat-value">{{ $statusTimeline['current_stage_label'] ?? 'غير محدد' }}</div>
                                        <div class="timeline-stat-meta">{{ $statusTimeline['current_stage_duration'] ?? '' }}</div>
                                    </div>
                                </div>

                                <div class="timeline-caption">
                                    يتم احتساب مدة كل مرحلة من وقت تسجيل الحالة حتى الانتقال للحالة التالية، والحالة الحالية تُحسب حتى الآن إذا كانت ما زالت نشطة.
                                </div>

                                <div class="contract-stage-timeline">
                                    @foreach ($statusTimelineItems as $stage)
                                        <div class="contract-stage-item {{ $stage['is_current'] ? 'current' : '' }}">
                                            <div class="contract-stage-head">
                                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                                    <span class="stage-order">{{ $loop->iteration }}</span>
                                                    <div>
                                                        <div class="stage-name">{{ $stage['status_label'] }}</div>
                                                        @if ($stage['is_current'])
                                                            <small class="text-muted">الحالة الحالية</small>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="stage-duration">{{ $stage['duration_human'] }}</div>
                                            </div>

                                            <div class="stage-dates">
                                                <div class="stage-date-box">
                                                    <span class="stage-date-label">بداية المرحلة</span>
                                                    <span class="stage-date-value">{{ $stage['started_at_formatted'] }}</span>
                                                </div>
                                                <div class="stage-date-box">
                                                    <span class="stage-date-label">نهاية المرحلة</span>
                                                    <span class="stage-date-value">{{ $stage['ended_at_formatted'] }}</span>
                                                </div>
                                            </div>

                                            @if ($stage['notes'])
                                                <div class="stage-notes">
                                                    {!! nl2br(e($stage['notes'])) !!}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="card-title">
                            <i class="fas fa-history text-info"></i>
                            سجل حالات العقد
                        </div>
                        <div class="table-responsive">
                            <table class="info-table">
                                <thead>
                                    <tr>
                                        <th>الحالة</th>
                                        <th>التاريخ</th>
                                        <th>التفاصيل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($statusHistory as $log)
                                        <tr>
                                            <td>
                                                <span class="status-badge bg-info">
                                                    {{ $log['status_label'] }}
                                                </span>
                                            </td>
                                            <td>{{ $log['display_date'] }}</td>
                                            <td>{!! $log['notes'] ? nl2br(e($log['notes'])) : '<span class="text-muted">لا توجد تفاصيل</span>' !!}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">لا يوجد سجل حركات لهذا العقد</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ملاحظات العقد -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="info-card">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="card-title mb-0">
                                <i class="fas fa-sticky-note text-warning"></i>
                                ملاحظات العقد
                            </div>
                            @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE))
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#addContractNoteModal">
                                    <i class="fas fa-plus"></i>
                                    إضافة ملاحظة
                                </button>
                            @endif
                        </div>

                        @if ($contract->notes && $contract->notes->count())
                            <div class="table-responsive">
                                <table class="info-table">
                                    <thead>
                                        <tr>
                                            <th>الملاحظة</th>
                                            <th>المستخدم</th>
                                            <th>التاريخ</th>
                                        </tr>
                                    </thead>
                                    <tbody id="contractNotesTableBody">
                                        @foreach ($contract->notes as $note)
                                            <tr>
                                                <td>{!! nl2br(e($note->note)) !!}</td>
                                                <td>{{ $note->admin->name ?? 'غير محدد' }}</td>
                                                <td>{{ $note->created_at ? $note->created_at->format('Y-m-d H:i') : '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info mb-0" id="contractNotesEmpty">
                                لا توجد ملاحظات لهذا العقد بعد.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- مرفقات ومستندات العقد -->
            @php
                $documentCollection = collect($contractDocuments ?? []);
                $financialDocumentGroup = $documentCollection->firstWhere('key', 'financial-attachments');
                $financialDocuments = collect($financialDocumentGroup['items'] ?? []);
                $operationalDocuments = $documentCollection->reject(fn ($document) => ($document['key'] ?? null) === 'financial-attachments')->values();
                $uploadedAttachments = collect($contractAttachments ?? []);
                $uploadedAttachmentsByType = $uploadedAttachments->groupBy('type_label');
                $linkedDocumentsCount = $financialDocuments->count() + $operationalDocuments->count();
            @endphp

            <div class="row" id="contractAttachmentsSection">
                <div class="col-12 mb-4">
                    <div class="info-card">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                            <div class="card-title mb-0">
                                <i class="fas fa-paperclip text-primary"></i>
                                مرفقات ومستندات العقد
                            </div>
                            @if ($uploadedAttachments->isNotEmpty())
                                <a href="{{ route('RecruitmentContract.attachments.download', $contract->id) }}"
                                    class="btn btn-sm btn-primary">
                                    <i class="fas fa-file-zipper me-1"></i>
                                    تحميل كل المرفقات المرفوعة
                                </a>
                            @endif
                        </div>

                        <div class="attachments-dashboard">
                            <div class="attachment-summary-tile">
                                <span class="attachment-summary-label">مرفقات مرفوعة</span>
                                <div class="attachment-summary-value">{{ $uploadedAttachments->count() }}</div>
                            </div>
                            <div class="attachment-summary-tile">
                                <span class="attachment-summary-label">مستندات مالية وقيود</span>
                                <div class="attachment-summary-value">{{ $financialDocuments->count() }}</div>
                            </div>
                            <div class="attachment-summary-tile">
                                <span class="attachment-summary-label">مستندات تشغيلية</span>
                                <div class="attachment-summary-value">{{ $operationalDocuments->count() }}</div>
                            </div>
                            <div class="attachment-summary-tile">
                                <span class="attachment-summary-label">الإجمالي المعروض</span>
                                <div class="attachment-summary-value">{{ $uploadedAttachments->count() + $linkedDocumentsCount }}</div>
                            </div>
                        </div>

                        @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE))
                            <form method="POST" action="{{ route('RecruitmentContract.attachments.store', $contract->id) }}"
                                enctype="multipart/form-data" class="attachment-upload-panel mb-4">
                                @csrf
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label">نوع المرفق</label>
                                        <select name="attachment_type" class="form-control">
                                            <option value="other">أخرى</option>
                                            <option value="invoice">فاتورة</option>
                                            <option value="financial">معاملة مالية</option>
                                            <option value="operational">إجراء تشغيلي</option>
                                            <option value="contract">مستند عقد</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">إضافة مرفقات</label>
                                        <input type="file" name="attachments[]" class="form-control" multiple
                                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-upload me-1"></i>
                                            رفع المرفقات
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        <div class="attachments-section-block">
                            <div class="attachments-section-header">
                                <h5 class="attachments-section-title">
                                    <i class="fas fa-folder text-primary"></i>
                                    المرفقات المرفوعة
                                </h5>
                                @if ($uploadedAttachments->isNotEmpty())
                                    <span class="badge bg-primary">{{ $uploadedAttachments->count() }} ملف</span>
                                @endif
                            </div>

                            @if ($uploadedAttachments->isNotEmpty())
                                <form method="GET" action="{{ route('RecruitmentContract.attachments.download', $contract->id) }}">
                                    <input type="hidden" name="selected_only" value="1">

                                    @foreach ($uploadedAttachmentsByType as $typeLabel => $attachments)
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <span class="badge bg-secondary">{{ $typeLabel }}</span>
                                                <span class="text-muted small">{{ $attachments->count() }} ملف</span>
                                            </div>
                                            <div class="attachment-list">
                                                @foreach ($attachments as $attachment)
                                                    <div class="attachment-item">
                                                        <div class="attachment-item-header">
                                                            <label class="d-flex align-items-start gap-2 mb-0">
                                                                <input type="checkbox" name="attachments[]"
                                                                    value="{{ $attachment['key'] }}" checked>
                                                                <span>
                                                                    <strong>{{ $attachment['label'] }}</strong>
                                                                    <span class="text-muted d-block small mt-1">{{ $attachment['original_name'] }}</span>
                                                                </span>
                                                            </label>
                                                            @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE))
                                                                <button type="submit" form="deleteAttachment{{ $attachment['id'] }}"
                                                                    class="btn btn-sm btn-outline-danger" title="حذف المرفق">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                        <div class="attachment-meta">
                                                            <div>الحجم: {{ $attachment['size_label'] }}</div>
                                                            @if ($attachment['uploaded_by'])
                                                                <div>رفع بواسطة: {{ $attachment['uploaded_by'] }}</div>
                                                            @endif
                                                            @if ($attachment['created_at'])
                                                                <div>تاريخ الرفع: {{ $attachment['created_at'] }}</div>
                                                            @endif
                                                        </div>
                                                        <div class="attachment-actions">
                                                            <a href="{{ $attachment['url'] }}" target="_blank"
                                                                class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-download me-1"></i>
                                                                تحميل
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach

                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-download me-1"></i>
                                        تحميل المحدد
                                    </button>
                                </form>

                                @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE))
                                    @foreach ($uploadedAttachments as $attachment)
                                        <form id="deleteAttachment{{ $attachment['id'] }}" method="POST"
                                            action="{{ route('RecruitmentContract.attachments.delete', ['contractId' => $contract->id, 'attachment' => $attachment['id']]) }}"
                                            onsubmit="return confirm('هل تريد حذف هذا المرفق؟');" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endforeach
                                @endif
                            @else
                                <div class="alert alert-info mb-0">
                                    لا توجد مرفقات مرفوعة على هذا العقد حتى الآن.
                                </div>
                            @endif
                        </div>

                        <div class="attachments-section-block">
                            <div class="attachments-section-header">
                                <h5 class="attachments-section-title">
                                    <i class="fas fa-scale-balanced text-danger"></i>
                                    المستندات المالية والقيود
                                </h5>
                                @if ($financialDocuments->isNotEmpty())
                                    <span class="badge bg-danger">{{ $financialDocuments->count() }} مستند</span>
                                @endif
                            </div>

                            @if ($financialDocuments->isNotEmpty())
                                <div class="contract-document-list">
                                    @foreach ($financialDocuments as $item)
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener"
                                            class="contract-document-link">
                                            <i class="{{ $item['icon'] }}"></i>
                                            <span>
                                                <strong class="d-block">{{ $item['label'] }}</strong>
                                                <small class="text-muted">فاتورة / قيد مالي</small>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    لا توجد فاتورة أو قيود مالية مرتبطة بهذا العقد حالياً.
                                </div>
                            @endif
                        </div>

                        <div class="attachments-section-block mb-0">
                            <div class="attachments-section-header">
                                <h5 class="attachments-section-title">
                                    <i class="fas fa-briefcase text-info"></i>
                                    المستندات التشغيلية
                                </h5>
                                @if ($operationalDocuments->isNotEmpty())
                                    <span class="badge bg-info">{{ $operationalDocuments->count() }} مستند</span>
                                @endif
                            </div>

                            @if ($operationalDocuments->isNotEmpty())
                                <div class="contract-document-list">
                                    @foreach ($operationalDocuments as $document)
                                        <a href="{{ $document['url'] }}" target="_blank" rel="noopener"
                                            class="contract-document-link">
                                            <i class="{{ $document['icon'] }}"></i>
                                            <span>
                                                <strong class="d-block">{{ $document['label'] }}</strong>
                                                <small class="text-muted">{{ $document['category'] }}</small>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    لا توجد إجراءات تشغيلية مرتبطة بهذا العقد حالياً.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- أزرار الإجراءات -->
            <div class="action-buttons">
                @if ($canManageExternalOfficeDispatch)
                    <a href="{{ $externalOfficeDispatchFormUrl }}" class="btn-icon btn-edit"
                        title="إدارة إرسالية المكتب الخارجي">
                        <i class="fas fa-paper-plane"></i>
                    </a>
                @elseif ($externalOfficeDispatchPrintUrl)
                    <a href="{{ $externalOfficeDispatchPrintUrl }}" class="btn-icon btn-print"
                        title="عرض إرسالية المكتب الخارجي" target="_blank" rel="noopener">
                        <i class="fas fa-paper-plane"></i>
                    </a>
                @endif

                @if ($canShowDeliveryForm)
                    <a href="{{ $deliveryFormUrl }}" class="btn-icon btn-print" title="عرض نموذج تسليم العاملة"
                        target="_blank" rel="noopener">
                        <i class="fas fa-file-signature"></i>
                    </a>
                @endif

                @if ($contract->invoice)
                    <a href="{{ route('invoices.show', $contract->invoice->id) }}" class="btn-icon btn-invoice"
                        title="عرض الفاتورة" target="_blank">
                        <i class="fas fa-file-pdf"></i>
                    </a>
                @endif

                @if (!empty($contractAttachments))
                    <a href="{{ route('RecruitmentContract.attachments.download', $contract->id) }}"
                        class="btn-icon btn-print" title="تحميل مرفقات العقد">
                        <i class="fas fa-paperclip"></i>
                    </a>
                @endif

                @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT))
                    <a href="{{ route('RecruitmentContract.edit', $contract->id) }}" class="btn-icon btn-edit"
                        title="تعديل العقد">
                        <i class="fas fa-edit"></i>
                    </a>
                @endif

                @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE))
                    <button type="button" class="btn-icon btn-change-maid" onclick="openChangeMaidModal()"
                        title="تغيير العاملة">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                @endif

                <a href="{{ route('RecruitmentContract.index') }}" class="btn-icon btn-back" title="العودة للقائمة">
                    <i class="fas fa-arrow-right"></i>
                </a>

                @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE))
                    <button type="button" class="btn-icon btn-delete" onclick="deleteContract({{ $contract->id }})"
                        title="حذف العقد">
                        <i class="fas fa-trash"></i>
                    </button>
                @endif
            </div>
            </div>
        </div>

        @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE))
            <!-- دايلوج إضافة ملاحظة -->
            <div class="modal fade" id="addContractNoteModal" tabindex="-1" aria-labelledby="addContractNoteModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('RecruitmentContract.notes.store', $contract->id) }}" id="addContractNoteForm">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addContractNoteModalLabel">
                                    <i class="fas fa-sticky-note"></i>
                                    إضافة ملاحظة للعقد رقم #{{ $contract->id }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="contract_note" class="form-label">الملاحظة</label>
                                    <textarea class="form-control" id="contract_note" name="note" rows="4" maxlength="1000" required>{{ old('note') }}</textarea>
                                    @error('note')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    إلغاء
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    حفظ الملاحظة
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- دايلوج تغيير العاملة -->
        <div class="modal fade" id="changeMaidModal" tabindex="-1" aria-labelledby="changeMaidModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeMaidModalLabel">
                            <i class="fas fa-exchange-alt"></i>
                            تغيير العاملة في العقد رقم #{{ $contract->id }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- معلومات العاملة الحالية -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-user-tie"></i> العاملة الحالية:</h6>
                            <strong>{{ $contract->maid->name ?? 'غير محدد' }}</strong> -
                            {{ $contract->maid->passport_num ?? 'غير محدد' }}
                            ({{ $contract->maid && $contract->maid->nationality ? $contract->maid->nationality->sub_title : 'غير محدد' }})
                        </div>

                        <form id="changeMaidForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group mb-3">
                                        <label for="maid_search" class="form-label">
                                            <i class="fas fa-search"></i>
                                            البحث في العاملات
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="maid_search"
                                                placeholder="ابحث بالاسم أو رقم الجواز..."
                                                onkeyup="searchMaidsWithDelay(this.value)"
                                                onkeydown="handleSearchKeydown(event)">
                                            <button class="btn btn-outline-secondary" type="button"
                                                onclick="clearSearch()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">
                                            اكتب للبحث في العاملات المتاحات - اضغط Enter لفتح القائمة
                                        </small>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="new_maid_id" class="form-label">
                                            <i class="fas fa-user-plus"></i>
                                            اختيار العاملة الجديدة
                                        </label>
                                        <select class="form-control" id="new_maid_id" name="new_maid_id" required>
                                            <option value="">اختر العاملة الجديدة...</option>
                                        </select>
                                        <small class="form-text text-muted">
                                            سيتم تحميل العاملات المتاحات...
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-user-minus"></i>
                                            حالة العاملة السابقة
                                        </label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="old_maid_status"
                                                id="status_available" value="available" required>
                                            <label class="form-check-label" for="status_available">
                                                <i class="fas fa-check text-success"></i>
                                                متاحة
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="old_maid_status"
                                                id="status_unavailable" value="backout" required>
                                            <label class="form-check-label" for="status_unavailable">
                                                <i class="fas fa-ban text-danger"></i>
                                                باك اوت
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="reason" class="form-label">
                                    <i class="fas fa-comment"></i>
                                    سبب التغيير (اختياري)
                                </label>
                                <textarea class="form-control" id="reason" name="reason" rows="3"
                                    placeholder="اكتب سبب تغيير العاملة..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            إلغاء
                        </button>
                        <button type="button" class="btn btn-primary" onclick="submitChangeMaid()">
                            <i class="fas fa-save"></i>
                            حفظ التغيير
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif

@endsection

@section('js')
    <script>
        function printDiv() {
            var printContents = document.getElementById('printDiv').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }

        function deleteContract(contractId) {
            Swal.fire({
                title: 'تأكيد الحذف',
                text: 'هل أنت متأكد من حذف هذا العقد؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، احذف العقد',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/RecruitmentContract/${contractId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Content-Type': 'application/json',
                            },
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('نجاح!', data.message || 'تم حذف العقد بنجاح', 'success');
                                window.location.href = '{{ route('RecruitmentContract.index') }}';
                            } else {
                                Swal.fire('خطأ!', data.message || 'حدث خطأ أثناء حذف العقد', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('خطأ!', 'حدث خطأ أثناء حذف العقد', 'error');
                        });
                }
            });
        }

        // فتح دايلوج تغيير العاملة
        function openChangeMaidModal() {
            console.log('Opening change maid modal...');

            // فتح الدايلوج أولاً
            $('#changeMaidModal').modal('show');

            // تحميل العاملات المتاحات بعد فتح الدايلوج
            setTimeout(function() {
                loadAvailableMaids();
            }, 300);
        }

        // تحميل العاملات المتاحات
        function loadAvailableMaids(searchTerm = '') {
            console.log('Loading available maids...', {
                searchTerm
            });

            const url = '{{ route('RecruitmentContract.availableMaids') }}';
            const fullUrl = searchTerm ? `${url}?search=${encodeURIComponent(searchTerm)}` : url;
            console.log('Fetching from URL:', fullUrl);
            const select = document.getElementById('new_maid_id');
            select.innerHTML = '<option value="">جاري تحميل العاملات...</option>';

            // الحصول على CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(fullUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);

                    if (data.success) {
                        select.innerHTML = '<option value="">اختر العاملة الجديدة...</option>';

                        if (data.maids && data.maids.length > 0) {
                            data.maids.forEach(maid => {
                                const option = document.createElement('option');
                                option.value = maid.id;
                                option.textContent =
                                    `${maid.name} - ${maid.passport_num} (${maid.nationality})`;
                                // إضافة بيانات للبحث
                                option.setAttribute('data-name', maid.name);
                                option.setAttribute('data-passport', maid.passport_num);
                                option.setAttribute('data-nationality', maid.nationality);
                                select.appendChild(option);
                            });
                            console.log('Loaded ' + data.maids.length + ' maids');
                        } else {
                            select.innerHTML = '<option value="">لا توجد عاملات متاحات</option>';
                            console.log('No available maids found');
                        }
                    } else {
                        console.error('Error loading maids:', data.message);
                        select.innerHTML = '<option value="">خطأ في تحميل العاملات</option>';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    select.innerHTML = '<option value="">خطأ في الاتصال: ' + error.message + '</option>';
                });
        }

        // البحث مع تأخير لتجنب طلبات كثيرة
        function searchMaidsWithDelay(searchTerm) {
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(() => {
                loadAvailableMaids(searchTerm);
            }, 200); // تأخير 200 مللي ثانية
        }

        // إرسال نموذج تغيير العاملة
        function submitChangeMaid() {
            const form = document.getElementById('changeMaidForm');
            const formData = new FormData(form);

            // التحقق من البيانات
            if (!formData.get('new_maid_id')) {
                Swal.fire('خطأ!', 'يرجى اختيار العاملة الجديدة', 'error');
                return;
            }

            if (!formData.get('old_maid_status')) {
                Swal.fire('خطأ!', 'يرجى اختيار حالة العاملة السابقة', 'error');
                return;
            }

            // تأكيد العملية
            // if (!confirm('هل أنت متأكد من تغيير العاملة في هذا العقد؟')) {
            //     return;
            // }
            Swal.fire({
                title: 'تأكيد تغيير العاملة',
                text: 'هل أنت متأكد من تغيير العاملة في هذا الطلب؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، غير العاملة',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Submitting change maid form:', {
                        new_maid_id: formData.get('new_maid_id'),
                        old_maid_status: formData.get('old_maid_status'),
                        reason: formData.get('reason')
                    });

                    // إرسال البيانات
                    fetch('{{ route('RecruitmentContract.changeMaid', $contract->id) }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                            },
                        })
                        .then(response => {
                            console.log('Response status:', response.status);
                            return response.json();
                        })
                        .then(data => {
                            console.log('Response data:', data);

                            if (data.success) {
                                Swal.fire('نجاح!', data.message || 'تم تغيير العاملة بنجاح', 'success');
                                console.log('Change maid details:', data.data);
                                location.reload(); // إعادة تحميل الصفحة لتحديث البيانات
                            } else {
                                Swal.fire('خطأ!', data.message || 'حدث خطأ أثناء تغيير العاملة', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('خطأ!', 'حدث خطأ أثناء تغيير العاملة', 'error');
                        });
                }
            });
        }

        // فلترة خيارات العاملات في القائمة المنسدلة
        function filterMaidOptions(searchTerm) {
            console.log('Filtering maids with term:', searchTerm);

            const select = document.getElementById('new_maid_id');
            const options = Array.from(select.options);

            // إزالة رسالة "لا توجد نتائج" السابقة
            const noResultsOption = select.querySelector('option[disabled]');
            if (noResultsOption) {
                noResultsOption.remove();
            }

            // إذا كان البحث فارغاً، إظهار جميع الخيارات
            if (!searchTerm.trim()) {
                options.forEach(option => {
                    option.style.display = '';
                });
                return;
            }

            // إظهار/إخفاء الخيارات بناءً على البحث
            let visibleCount = 0;
            const search = searchTerm.toLowerCase().trim();

            options.forEach(option => {
                if (option.value === '') {
                    // إبقاء الخيار الافتراضي دائماً
                    option.style.display = '';
                } else {
                    const name = option.getAttribute('data-name')?.toLowerCase() || '';
                    const passport = option.getAttribute('data-passport')?.toLowerCase() || '';
                    const nationality = option.getAttribute('data-nationality')?.toLowerCase() || '';
                    const fullText = option.textContent.toLowerCase();

                    // البحث في جميع الحقول
                    const matches = name.includes(search) ||
                        passport.includes(search) ||
                        nationality.includes(search) ||
                        fullText.includes(search);

                    if (matches) {
                        option.style.display = '';
                        visibleCount++;
                    } else {
                        option.style.display = 'none';
                    }
                }
            });

            // إذا لم يتم العثور على نتائج، إظهار رسالة
            if (visibleCount === 0 && searchTerm.trim()) {
                const noResultsOption = document.createElement('option');
                noResultsOption.value = '';
                noResultsOption.textContent = 'لا توجد نتائج تطابق البحث';
                noResultsOption.disabled = true;
                select.appendChild(noResultsOption);
            }

            console.log('Visible options:', visibleCount);
        }

        // معالجة المفاتيح في حقل البحث
        function handleSearchKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const select = document.getElementById('new_maid_id');
                select.focus();
                select.click(); // فتح القائمة المنسدلة
            }
        }

        // مسح البحث
        function clearSearch() {
            document.getElementById('maid_search').value = '';
            loadAvailableMaids(''); // إعادة تحميل جميع العاملات المتاحات
        }
    </script>
@endsection
