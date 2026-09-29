@extends('admin.layout.indexs.index')

@section('page-title')
    تفاصيل عقد التأجير - العقد رقم #{{ $contract->contract_number ?? $contract->id }}
@endsection

@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .rental-show-page {
            background: #f4f7fb;
            border-radius: 24px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .page-back-toolbar {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 16px;
        }

        .page-back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 11px 18px;
            color: #334155;
            background: #fff;
            border: 1px solid #dbe4ef;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            transition: color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .page-back-button i {
            color: #2563eb;
            transition: transform 0.2s ease;
        }

        .page-back-button:hover {
            color: #1d4ed8;
            border-color: #93c5fd;
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.14);
        }

        .page-back-button:hover i {
            transform: translateX(3px);
        }

        .hero-card {
            background: linear-gradient(135deg, #0f766e 0%, #1d4ed8 52%, #1e293b 100%);
            color: #fff;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
            margin-bottom: 24px;
        }

        .hero-card h1 {
            margin: 6px 0 10px;
            font-size: 30px;
            font-weight: 800;
        }

        .hero-card p {
            margin: 0;
            color: rgba(255, 255, 255, 0.84);
            max-width: 760px;
        }

        .hero-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
        }

        .hero-meta-grid,
        .metric-grid,
        .workflow-grid {
            display: grid;
            gap: 16px;
        }

        .hero-meta-grid {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .hero-chip {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 18px;
            padding: 14px 16px;
        }

        .hero-chip .label {
            display: block;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.72);
            margin-bottom: 6px;
        }

        .hero-chip .value {
            font-size: 15px;
            font-weight: 700;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-pill.is-primary {
            background: rgba(191, 219, 254, 0.22);
            color: #dbeafe;
        }

        .status-pill.is-warning {
            background: rgba(254, 240, 138, 0.2);
            color: #fef08a;
        }

        .status-pill.is-success {
            background: rgba(187, 247, 208, 0.18);
            color: #bbf7d0;
        }

        .status-pill.is-danger {
            background: rgba(254, 202, 202, 0.18);
            color: #fecaca;
        }

        .status-pill.is-dark {
            background: rgba(226, 232, 240, 0.16);
            color: #e2e8f0;
        }

        .status-pill.is-secondary {
            background: rgba(226, 232, 240, 0.16);
            color: #f8fafc;
        }

        .metric-grid {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            margin-bottom: 24px;
        }

        .metric-card,
        .section-card {
            background: #fff;
            border: 1px solid #e7eef7;
            border-radius: 22px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.05);
        }

        .metric-card {
            padding: 18px 20px;
        }

        .metric-card .label {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .metric-card .value {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
            margin-bottom: 6px;
        }

        .metric-card .sub-value {
            color: #475569;
            font-size: 13px;
        }

        .section-card {
            padding: 22px;
            height: 100%;
        }

        .section-card.compact-card {
            height: auto;
            padding-top: 18px;
            padding-bottom: 18px;
        }

        .section-card.compact-card .section-header {
            margin-bottom: 12px;
        }

        .section-card.compact-card .detail-list {
            gap: 8px;
        }

        .section-card.compact-card .detail-list li {
            padding-bottom: 8px;
        }

        .page-section-divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 8px 0 -4px;
        }

        .page-section-divider::after {
            content: "";
            height: 1px;
            flex: 1;
            background: linear-gradient(90deg, transparent, #cbd5e1);
        }

        .page-section-divider .divider-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            color: #1d4ed8;
        }

        .page-section-divider h2 {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
        }

        .page-section-divider p {
            margin: 3px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: #0f172a;
            font-size: 19px;
            font-weight: 800;
        }

        .section-title i {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            font-size: 15px;
        }

        .section-subtitle {
            margin: -8px 0 16px;
            color: #64748b;
            font-size: 13px;
        }

        .profile-card {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 18px;
            padding-bottom: 18px;
            border-bottom: 1px solid #edf2f7;
        }

        .profile-avatar {
            width: 96px;
            height: 112px;
            border-radius: 20px;
            object-fit: cover;
            border: 4px solid #f8fafc;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
            background: #e2e8f0;
        }

        .profile-summary h3 {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 800;
            color: #111827;
        }

        .profile-summary .muted {
            display: block;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .detail-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 12px;
        }

        .detail-list li {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #e2e8f0;
        }

        .detail-list li:last-child {
            padding-bottom: 0;
            border-bottom: none;
        }

        .detail-list .label {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }

        .detail-list .value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            text-align: left;
        }

        .detail-list .value.highlight {
            color: #0f766e;
        }

        .workflow-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .workflow-step {
            position: relative;
            padding: 18px 16px;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            min-height: 190px;
        }

        .workflow-step:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 39px;
            left: -13px;
            width: 13px;
            height: 2px;
            background: #cbd5e1;
        }

        .workflow-step.done:not(:last-child)::after {
            background: #10b981;
        }

        .workflow-step.done {
            background: linear-gradient(180deg, #ecfdf5 0%, #f8fafc 100%);
            border-color: #bbf7d0;
        }

        .workflow-step.current {
            background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
            border-color: #fdba74;
            box-shadow: inset 0 0 0 1px rgba(249, 115, 22, 0.08);
        }

        .workflow-step.pending {
            opacity: 0.86;
        }

        .workflow-step .step-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #334155;
            margin-bottom: 14px;
            font-size: 16px;
        }

        .workflow-step .step-number {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 25px;
            height: 25px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid #cbd5e1;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
        }

        .workflow-step.done .step-icon {
            background: #10b981;
            color: #fff;
        }

        .workflow-step.current .step-icon {
            background: #f97316;
            color: #fff;
        }

        .workflow-step h4 {
            margin: 0 0 8px;
            font-size: 17px;
            font-weight: 800;
            color: #111827;
        }

        .workflow-step p {
            margin: 0 0 10px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }

        .workflow-step .step-date {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
        }

        .workflow-step .step-state {
            display: block;
            margin-bottom: 7px;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            padding: 13px 14px;
            border-bottom: 1px solid #edf2f7;
            vertical-align: top;
        }

        .history-table th {
            color: #475569;
            font-size: 12px;
            font-weight: 800;
            background: #f8fafc;
        }

        .history-table td {
            color: #0f172a;
            font-size: 13px;
            font-weight: 600;
        }

        .change-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 5px 10px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 800;
        }

        .notes-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 16px;
            color: #334155;
            line-height: 1.9;
            white-space: pre-line;
        }

        .term-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .term-tag {
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 700;
        }

        .empty-state {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 18px;
            padding: 22px;
            text-align: center;
            color: #64748b;
        }

        .attachment-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .attachment-item {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 15px;
            background: #f8fafc;
        }

        .activity-wrapper .border.rounded-3 {
            border-color: #edf2f7 !important;
        }

        .maid-select-hint {
            font-size: 13px;
            color: #64748b;
        }

        #changeMaidModal .select2-container {
            width: 100% !important;
        }

        #changeMaidModal .select2-container--default .select2-selection--single {
            min-height: 45px;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            display: flex;
            align-items: center;
        }

        #changeMaidModal .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #111827;
            line-height: 43px;
            padding-right: 14px;
            padding-left: 34px;
        }

        #changeMaidModal .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 43px;
            left: 8px;
            right: auto;
        }

        .select2-container--open {
            z-index: 1060;
        }

        .action-buttons {
            position: fixed;
            bottom: 28px;
            right: 28px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
        }

        .btn-icon {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-icon:hover {
            transform: translateY(-2px);
            color: #fff;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.24);
        }

        .btn-print {
            background: #0ea5e9;
        }

        .btn-edit {
            background: #f59e0b;
        }

        .btn-complete {
            background: #22c55e;
        }

        .btn-back {
            background: #64748b;
        }

        .btn-copy-payment {
            background: #2563eb;
        }

        .btn-delete {
            background: #ef4444;
        }

        @media (max-width: 991px) {
            .page-back-button {
                width: 100%;
                justify-content: center;
            }

            .hero-top,
            .section-header,
            .profile-card,
            .detail-list li {
                flex-direction: column;
            }

            .detail-list .value {
                text-align: right;
            }

            .action-buttons {
                position: static;
                flex-direction: row;
                flex-wrap: wrap;
                margin-top: 18px;
            }

            .workflow-grid {
                grid-template-columns: 1fr;
            }

            .workflow-step {
                min-height: auto;
            }

            .workflow-step:not(:last-child)::after {
                top: auto;
                bottom: -13px;
                right: 38px;
                left: auto;
                width: 2px;
                height: 13px;
            }
        }

        @media print {
            .action-buttons,
            .no-print {
                display: none !important;
            }

            .rental-show-page {
                background: #fff;
                padding: 0;
            }

            .hero-card,
            .metric-card,
            .section-card {
                box-shadow: none;
            }
        }
    </style>
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_VIEW))
        @php
            $statusClasses = [
                'new' => 'is-primary',
                'waiting_contract_send' => 'is-warning',
                'contract_sent' => 'is-warning',
                'contract_signed' => 'is-success',
                'maid_delivered' => 'is-primary',
                'active' => 'is-success',
                'completed' => 'is-secondary',
                'cancelled' => 'is-danger',
                'suspended' => 'is-dark',
                'frozen' => 'is-dark',
            ];

            $currentMaid = $contract->maid;
            $remainingDays = $contract->calculateRemainingDays();
            $userNationality = optional($contract->user?->nationality)->name ?? optional($contract->user?->nationality)->title ?? 'غير محدد';
            $maidNationality = optional($currentMaid?->nationality)->title ?? optional($currentMaid?->nationality)->name ?? 'غير محدد';
            $maidReligion = optional($currentMaid?->religion)->title ?? optional($currentMaid?->religion)->name ?? 'غير محدد';
            $additionalTerms = collect($contract->info?->additional_terms)->filter(fn($term) => filled($term))->values();
            $paymentTypes = [
                'cash' => 'نقداً',
                'bank_transfer' => 'تحويل بنكي',
                'mada' => 'مدى',
                'tabby' => 'تابي',
                'tamara' => 'تمارا',
                'moyasar' => 'ميسر: فيزا، مدى، ماستر كارد، أبل باي',
                'pay_later' => 'الدفع لاحقاً',
                'other' => 'أخرى',
            ];
            $paymentStatuses = [
                'paid' => 'تم الدفع',
                'partial' => 'مدفوع جزئياً',
                'pending' => 'معلق',
                'later' => 'الدفع لاحقاً',
                'bank_pending' => 'بانتظار التحويل',
            ];
            $warrantyStatuses = [
                'not_applicable' => 'غير قابل للتطبيق',
                'active' => 'نشط',
                'expired' => 'منتهي',
            ];
            $closureCompanyDue = (float) ($closureSummary['company_due_value'] ?? $closureSummary['calculated_company_due_value'] ?? 0);
            $closureClientRefund = (float) ($closureSummary['refund_value'] ?? $closureSummary['calculated_refund_value'] ?? 0);
            $closureAdjustments = $contract->financialAdjustments->whereIn('context', ['closure', 'manual_credit_note', 'closure_correction']);
            $showClosureSettlement = $closureCompanyDue > 0 || $closureClientRefund > 0;
            $isEarlyClosure = (bool) ($closureSummary['is_early_closure'] ?? false);
            $cancelledRemainingDays = (int) ($closureSummary['remaining_days_value'] ?? 0);
            $remainingMetricLabel = 'الأيام المتبقية';
            $remainingMetricValue = $remainingDays !== null ? $remainingDays : 0;
            $remainingMetricSubValue = $remainingDays !== null && $remainingDays > 0 ? 'يوم متبقٍ' : 'تم الوصول لنهاية المدة أو لم تحدد';

            if ($contract->status === 'cancelled' && $isEarlyClosure) {
                $remainingMetricLabel = 'حالة مدة العقد';
                $remainingMetricValue = $cancelledRemainingDays;
                $remainingMetricSubValue = $cancelledRemainingDays > 0
                    ? 'ألغي قبل نهاية مدته الأساسية'
                    : 'ألغي قبل اكتمال مدة العقد';
            }

            $isWorkflowClosed = in_array($contract->status, ['completed', 'cancelled'], true);
            $isWorkflowActivated = (bool) $contract->trial_started_at
                || in_array($contract->status, ['active', 'frozen', 'suspended', 'completed', 'cancelled'], true);
            $isWorkflowDelivered = (bool) $contract->maid_delivered_at
                || in_array($contract->status, ['maid_delivered', 'active', 'frozen', 'suspended', 'completed', 'cancelled'], true);
            $isWorkflowSigned = (bool) $contract->contract_signed_at
                || in_array($contract->status, ['contract_signed', 'maid_delivered', 'active', 'frozen', 'suspended', 'completed', 'cancelled'], true);
            $workflowStateLabel = static fn (string $state) => match ($state) {
                'done' => 'مكتملة',
                'current' => 'المرحلة الحالية',
                default => 'لم تبدأ',
            };

            $workflowSteps = [
                [
                    'title' => 'إنشاء العقد',
                    'icon' => 'fas fa-file-circle-check',
                    'description' => 'إنشاء سجل العقد واعتماد بيانات العميل والعاملة والباقة.',
                    'date' => optional($contract->legacy_created_at ?? $contract->contract_date ?? $contract->created_at)?->format('Y-m-d H:i'),
                    'state' => 'done',
                ],
                [
                    'title' => 'توقيع العميل',
                    'icon' => 'fas fa-signature',
                    'description' => 'اعتماد توقيع العميل على العقد.',
                    'date' => optional($contract->contract_signed_at)?->format('Y-m-d H:i'),
                    'state' => $isWorkflowSigned ? 'done' : 'current',
                ],
                [
                    'title' => 'تسليم العاملة',
                    'icon' => 'fas fa-handshake',
                    'description' => 'توثيق التسليم الفعلي للعاملة للعميل.',
                    'date' => optional($contract->maid_delivered_at)?->format('Y-m-d H:i'),
                    'state' => $isWorkflowDelivered ? 'done' : ($isWorkflowSigned ? 'current' : 'pending'),
                ],
                [
                    'title' => 'تفعيل العقد',
                    'icon' => 'fas fa-bolt',
                    'description' => 'دخول العقد مرحلة التشغيل وبدء احتساب مدة الخدمة.',
                    'date' => optional($contract->trial_started_at)?->format('Y-m-d H:i'),
                    'state' => $isWorkflowClosed
                        ? 'done'
                        : ($isWorkflowActivated || $isWorkflowDelivered ? 'current' : 'pending'),
                ],
                [
                    'title' => $contract->status === 'cancelled' ? 'إلغاء العقد' : 'إغلاق العقد',
                    'icon' => $contract->status === 'cancelled' ? 'fas fa-ban' : 'fas fa-flag-checkered',
                    'description' => $contract->status === 'cancelled'
                        ? 'إنهاء العقد قبل اكتمال مدته وتسجيل التسوية المرتبطة.'
                        : 'إنهاء مدة العقد وتسجيل التسوية والمستندات المالية.',
                    'date' => optional($contract->closed_at)?->format('Y-m-d H:i'),
                    'state' => $isWorkflowClosed ? 'done' : 'pending',
                ],
            ];

            $workflowSteps = collect($workflowSteps)->map(function (array $step, int $index) use ($workflowStateLabel) {
                $step['number'] = $index + 1;
                $step['state_label'] = $workflowStateLabel($step['state']);

                return $step;
            })->all();
        @endphp

        <div class="rental-show-page">
            <div class="page-back-toolbar no-print">
                <a href="{{ route('RentContract.index') }}" class="page-back-button" data-list-back
                    aria-label="العودة للصفحة السابقة">
                    <i class="fas fa-arrow-right"></i>
                    <span>العودة للصفحة السابقة</span>
                </a>
            </div>

            <div class="hero-card">
                <div class="hero-top">
                    <div>
                        <span class="hero-eyebrow">
                            <i class="fas fa-file-contract"></i>
                            عقد تأجير عاملة منزلية
                        </span>
                        <h1>العقد رقم {{ $contract->contract_number ?? $contract->id }}</h1>
                        <p>بيانات العقد، الفاتورة، سندات القبض، تسوية الإنهاء، والرصيد المحاسبي الفعلي في شاشة واحدة.</p>
                    </div>

                    <div class="d-flex flex-column align-items-end gap-2">
                        <span class="status-pill {{ $statusClasses[$contract->status] ?? 'is-secondary' }}">
                            <i class="fas fa-circle fs-10"></i>
                            {{ $contract->status_text }}
                        </span>

                        @if ($contract->status === 'completed' && $contract->closure_type === 'transferred')
                            <span class="status-pill is-warning">
                                <i class="fas fa-right-left"></i>
                                تم النقل
                            </span>
                        @elseif($contract->status === 'completed' && $contract->closure_type === 'available')
                            <span class="status-pill is-success">
                                <i class="fas fa-user-check"></i>
                                أُغلِق العقد وأصبحت العاملة متاحة
                            </span>
                        @elseif($contract->status === 'cancelled' && $isEarlyClosure)
                            <span class="status-pill is-danger">
                                <i class="fas fa-times-circle"></i>
                                ملغي لأنه لم يكمل مدة العقد
                            </span>
                        @elseif($contract->status === 'frozen')
                            <span class="status-pill is-warning">
                                <i class="fas fa-pause-circle"></i>
                                مجمد بسبب: {{ \Illuminate\Support\Str::limit($contract->freeze_reason ?: 'غير محدد', 60) }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="hero-meta-grid">
                    <div class="hero-chip">
                        <span class="label">تاريخ العقد</span>
                        <span class="value">{{ optional($contract->contract_date)->format('Y-m-d') ?? 'غير محدد' }}</span>
                    </div>
                    <div class="hero-chip">
                        <span class="label">العميل</span>
                        <span class="value">{{ $contract->user?->name ?? 'غير محدد' }}</span>
                    </div>
                    <div class="hero-chip">
                        <span class="label">العاملة الحالية</span>
                        <span class="value">{{ $currentMaid?->name ?? 'لم يتم اختيار عاملة' }}</span>
                    </div>
                    <div class="hero-chip">
                        <span class="label">المسوق</span>
                        <span class="value">{{ $contract->marketer?->name ?? 'غير محدد' }}</span>
                        @if(AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CHANGE_MARKETER))
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                data-bs-toggle="modal" data-bs-target="#changeMarketerModal">تغيير المسوق</button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="metric-grid">
                <div class="metric-card">
                    <div class="label">التكلفة الشهرية</div>
                    <div class="value">{{ number_format((float) ($contract->info->monthly_cost ?? 0), 2) }}</div>
                    <div class="sub-value">ريال سعودي</div>
                </div>
                <div class="metric-card">
                    <div class="label">مدة العقد</div>
                    <div class="value">{{ $contract->info?->package?->duration_label ?? ((int) ($contract->info->contract_duration_days ?? 0) . ' يوم') }}</div>
                    <div class="sub-value">{{ (int) ($contract->info->contract_duration_months ?? 0) }} شهر للحساب / {{ (int) ($contract->info->contract_duration_days ?? 0) }} يوم</div>
                </div>
                <div class="metric-card">
                    <div class="label">قيمة العقد</div>
                    <div class="value">{{ number_format((float) ($contract->info->contract_value ?? 0), 2) }}</div>
                    <div class="sub-value">إجمالي قيمة العقد</div>
                </div>
                <div class="metric-card">
                    <div class="label">{{ $remainingMetricLabel }}</div>
                    <div class="value">{{ $remainingMetricValue }}</div>
                    <div class="sub-value">{{ $remainingMetricSubValue }}</div>
                </div>
                <div class="metric-card">
                    <div class="label">صافي رصيد العقد</div>
                    <div class="value {{ $contractLedgerBalance > 0 ? 'text-danger' : ($contractLedgerBalance < 0 ? 'text-success' : '') }}">
                        {{ number_format(abs($contractLedgerBalance), 2) }}
                    </div>
                    <div class="sub-value">
                        {{ $contractLedgerBalance > 0 ? 'مطلوب من العميل' : ($contractLedgerBalance < 0 ? 'رصيد لصالح العميل' : 'مسوّى بالكامل') }}
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="page-section-divider">
                        <span class="divider-icon"><i class="fas fa-address-card"></i></span>
                        <div>
                            <h2>البيانات الأساسية والمالية</h2>
                            <p>بيانات العميل، الفوترة، الدفعات، ورصيد العقد.</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="section-card compact-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-user"></i>
                                بيانات العميل
                            </h3>
                            @if ($contract->user?->account_id && \Illuminate\Support\Facades\Route::has('finance.account-statement-clients.index'))
                                <a href="{{ route('finance.account-statement-clients.index', ['account_id' => $contract->user->account_id]) }}"
                                    class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                    <i class="fas fa-file-invoice-dollar me-1"></i> كشف حساب العميل
                                </a>
                            @endif
                        </div>

                        <ul class="detail-list">
                            <li>
                                <span class="label">الاسم</span>
                                <span class="value highlight">{{ $contract->user?->name ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">رقم الجوال</span>
                                <span class="value">{{ ($contract->user?->phone_code ?? '') . ($contract->user?->phone ?? '') ?: 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">البريد الإلكتروني</span>
                                <span class="value">{{ $contract->user?->email ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">{{ $contract->user?->documentNumberLabel() ?? 'رقم الهوية' }}</span>
                                <span class="value">{{ $contract->user?->documentNumber() ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">الجنسية</span>
                                <span class="value">{{ $userNationality }}</span>
                            </li>
                            @unless($contract->user?->isCompanyClient())
                            <li>
                                <span class="label">المهنة</span>
                                <span class="value">{{ $contract->user?->profession ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">الدخل الشهري</span>
                                <span class="value">{{ $contract->user?->monthly_income ? number_format($contract->user->monthly_income, 2) . ' ريال' : 'غير محدد' }}</span>
                            </li>
                            @endunless
                            <li>
                                <span class="label">المدينة</span>
                                <span class="value">{{ $contract->user?->city ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">العنوان</span>
                                <span class="value">{{ $contract->user?->address ?? 'غير محدد' }}</span>
                            </li>
                            @unless($contract->user?->isCompanyClient())
                            <li>
                                <span class="label">عدد أفراد الأسرة / الأطفال</span>
                                <span class="value">{{ $contract->user?->family_num ?? '-' }} / {{ $contract->user?->children ?? '-' }}</span>
                            </li>
                            @endunless
                        </ul>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-receipt"></i>
                                ملخص الفوترة والرصيد
                            </h3>
                        </div>

                        <ul class="detail-list mb-4">
                            <li>
                                <span class="label">الباقة</span>
                                <span class="value highlight">{{ $contract->info?->package?->title ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">نوع/مدة الباقة</span>
                                <span class="value">
                                    {{ $contract->info?->package?->rental_type_label ?? ($contract->info?->rental_type ?? 'غير محدد') }}
                                    /
                                    {{ $contract->info?->package?->duration_label ?? ((int) ($contract->info?->contract_duration_days ?? 0) . ' يوم') }}
                                </span>
                            </li>
                            <li>
                                <span class="label">قيمة الفاتورة الأساسية</span>
                                <span class="value">{{ number_format((float) ($contract->info?->contract_value ?? 0), 2) }} ريال</span>
                            </li>
                            <li>
                                <span class="label">المسدد المخصص للفاتورة</span>
                                <span class="value">{{ number_format((float) ($contract->financeInvoice?->paid_amount ?? $contract->info?->paid_amount ?? 0), 2) }} ريال</span>
                            </li>
                            <li>
                                <span class="label">متبقي الفاتورة الأصلية قبل الإشعارات</span>
                                <span class="value">{{ number_format((float) ($contract->financeInvoice?->remaining_amount ?? $contract->info?->remaining_amount ?? 0), 2) }} ريال</span>
                            </li>
                            @if($contract->extensions->isNotEmpty())
                                <li>
                                    <span class="label">إجمالي قيمة التمديدات</span>
                                    <span class="value">{{ number_format((float) $contract->extensions->sum('extension_value'), 2) }} ريال</span>
                                </li>
                            @endif
                            <li>
                                <span class="label">إجمالي سندات القبض المرتبطة بالعقد</span>
                                <span class="value highlight">{{ number_format((float) $receiptBondsTotal, 2) }} ريال</span>
                            </li>
                            <li>
                                <span class="label">صافي إشعارات العقد</span>
                                <span class="value">
                                    {{ number_format($closureIncome - $closureRefund, 2) }} ريال
                                    <small class="text-muted d-block">إشعارات مدينة {{ number_format($closureIncome, 2) }} - إشعارات دائنة {{ number_format($closureRefund, 2) }}</small>
                                </span>
                            </li>
                            <li>
                                <span class="label">صافي رصيد العقد بعد الإشعارات والسداد</span>
                                <span class="value {{ $contractLedgerBalance > 0 ? 'text-danger' : ($contractLedgerBalance < 0 ? 'text-success' : 'text-muted') }}">
                                    {{ number_format(abs($contractLedgerBalance), 2) }} ريال
                                    <small class="d-block">
                                        {{ $contractLedgerBalance > 0 ? 'مطلوب من العميل' : ($contractLedgerBalance < 0 ? 'رصيد دائن للعميل' : 'العقد مسوّى') }}
                                    </small>
                                    <small class="text-muted d-block">
                                        {{ ($contractLedgerBalanceFromJournal ?? false) ? 'محسوب من قيود العقد في دفتر الأستاذ' : 'محسوب من مستندات العقد - لا توجد قيود مرحّلة لهذا العقد' }}
                                        @if ($hasUnpostedReceivableAdjustments ?? false)
                                            <span class="text-warning d-block">يشمل إشعارات محفوظة بانتظار الترحيل المحاسبي</span>
                                        @endif
                                    </small>
                                </span>
                            </li>
                            <li>
                                <span class="label">رصيد حساب العميل بعد جميع القيود</span>
                                <span class="value {{ ($clientAccountBalance ?? 0) > 0 ? 'text-danger' : (($clientAccountBalance ?? 0) < 0 ? 'text-success' : 'text-muted') }}">
                                    {{ $clientAccountBalance === null ? 'لا يوجد حساب محاسبي' : number_format(abs($clientAccountBalance), 2) . ' ريال' }}
                                    @if ($clientAccountBalance !== null)
                                        <small class="d-block">
                                            {{ $clientAccountBalance > 0 ? 'مدين - مطلوب من العميل' : ($clientAccountBalance < 0 ? 'دائن - لصالح العميل' : 'متوازن') }}
                                            (يشمل القيود بانتظار الاعتماد)
                                        </small>
                                    @endif
                                </span>
                            </li>
                            @if ($clientAccountApprovedBalance !== null && $clientAccountApprovedBalance !== $clientAccountBalance)
                                <li>
                                    <span class="label">الرصيد المرحّل من القيود المعتمدة فقط</span>
                                    <span class="value {{ $clientAccountApprovedBalance > 0 ? 'text-danger' : ($clientAccountApprovedBalance < 0 ? 'text-success' : 'text-muted') }}">
                                        {{ number_format(abs($clientAccountApprovedBalance), 2) }} ريال
                                        <small class="d-block">
                                            {{ $clientAccountApprovedBalance > 0 ? 'مدين - مطلوب من العميل' : ($clientAccountApprovedBalance < 0 ? 'دائن - لصالح العميل' : 'متوازن') }}
                                        </small>
                                    </span>
                                </li>
                            @endif
                        </ul>

                        @if(!empty($paymentReceipts))
                            <div id="paymentReceiptsList" class="mb-4">
                                <div class="fw-bold mb-1">مرفقات إثبات الدفع</div>
                                <p class="small text-muted mb-2">
                                    هذه ملفات مرفقة فقط؛ الدفعات المحتسبة محاسبياً هي سندات القبض الظاهرة في السجل أدناه.
                                </p>
                                @foreach($paymentReceipts as $receipt)
                                    <div class="payment-receipt-item d-flex align-items-center justify-content-between gap-2 border rounded p-2 mb-2">
                                        <a href="{{ route('RentContract.attachments.view', [$contract->id, $receipt['key']]) }}" target="_blank" class="small">
                                            {{ $receipt['label'] }}
                                        </a>
                                        @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_EDIT))
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger delete-payment-receipt"
                                                data-delete-url="{{ route('RentContract.paymentReceipts.delete', [$contract->id, $receipt['key']]) }}"
                                            >
                                                حذف
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_EDIT))
                            <form method="POST" action="{{ route('RentContract.payment.update', $contract->id) }}" enctype="multipart/form-data" class="no-print border rounded-3 p-3 mb-4 bg-light">
                                @csrf
                                <div class="fw-bold mb-1">تسجيل دفعة جديدة</div>
                                <p class="small text-muted mb-3">
                                    المبلغ هنا دفعة جديدة وليس إجمالي المدفوع السابق. عند الحفظ سيُنشأ سند قبض وقيد مستقل ويُحدّث رصيد العميل.
                                </p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="show_payment_type" class="form-label">طريقة الدفع</label>
                                        <select name="payment_type" id="show_payment_type" class="form-select" required>
                                            @foreach($paymentTypes as $value => $label)
                                                @continue($value === 'pay_later')
                                                <option value="{{ $value }}" {{ ($contract->info?->payment_type ?? '') === $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="show_payment_amount" class="form-label">قيمة الدفعة الجديدة</label>
                                        <input type="number" id="show_payment_amount" name="payment_amount" class="form-control"
                                            step="0.01" min="0.01" value="{{ old('payment_amount') }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="show_receipt_account_id" class="form-label">البنك / حساب السداد</label>
                                        <select name="receipt_account_id" id="show_receipt_account_id" class="form-select" required>
                                            <option value="">اختر الحساب الذي سيصدر عليه سند القبض</option>
                                            @foreach($receiptAccounts as $account)
                                                <option value="{{ $account->id }}" @selected((string) old('receipt_account_id') === (string) $account->id)>
                                                    {{ $account->code }} - {{ $account->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($receiptAccounts->isEmpty())
                                            <small class="text-danger">لا توجد حسابات بنك أو صندوق أو محفظة صالحة في الدليل المحاسبي.</small>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label for="show_payment_date" class="form-label">تاريخ الدفعة</label>
                                        <input type="date" id="show_payment_date" name="payment_date" class="form-control"
                                            value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="show_payment_receipt" class="form-label">إرفاق إيصال دفع</label>
                                        <input type="file" id="show_payment_receipt" name="payment_receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">
                                    <i class="fas fa-receipt me-1"></i>
                                    تسجيل الدفعة وإنشاء سند قبض
                                </button>
                            </form>
                        @endif

                        <div class="fw-bold mb-2">سجل سندات القبض المرتبطة بالعقد</div>
                        @if ($receiptBonds->isEmpty())
                            <div class="empty-state">لا توجد سندات قبض مرتبطة بهذا العقد حتى الآن.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>السند</th>
                                            <th>التاريخ</th>
                                            <th>الحساب</th>
                                            <th>القيمة</th>
                                            <th class="text-end">المستندات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($receiptBonds as $bond)
                                            <tr>
                                                <td>#{{ $bond->code ?: $bond->id }}</td>
                                                <td>{{ $bond->date ?: '-' }}</td>
                                                <td>{{ $bond->debitAccount?->name ?: '-' }}</td>
                                                <td>{{ number_format((float) $bond->value, 2) }} ريال</td>
                                                <td class="text-end">
                                                    <a href="{{ route('finance.bonds-print.show', ['bonds_print' => $bond->id, 'type' => 'qabd']) }}"
                                                        target="_blank" class="btn btn-sm btn-outline-primary" title="طباعة سند القبض">
                                                        <i class="fas fa-receipt"></i>
                                                    </a>
                                                    @if ($bond->constraint_id)
                                                        <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $bond->constraint_id, 'autoprint' => 1]) }}"
                                                            target="_blank" class="btn btn-sm btn-outline-secondary" title="عرض قيد سند القبض">
                                                            <i class="fas fa-book-open"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-12">
                    <div class="page-section-divider">
                        <span class="divider-icon"><i class="fas fa-briefcase"></i></span>
                        <div>
                            <h2>بيانات العقد والتشغيل</h2>
                            <p>العاملة، مدة العقد، بيانات التشغيل، والمستندات المالية.</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-female"></i>
                                العاملة الحالية
                            </h3>
                            @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_EDIT) && !in_array($contract->status, ['completed', 'cancelled'], true))
                                <button type="button" class="btn btn-sm btn-warning text-dark no-print" data-bs-toggle="modal"
                                    data-bs-target="#changeMaidModal">
                                    <i class="fas fa-right-left me-1"></i>
                                    تغيير العاملة
                                </button>
                            @endif
                        </div>

                        @if ($currentMaid)
                            <div class="profile-card">
                                <img src="{{ $currentMaid->photo_url }}" alt="{{ $currentMaid->name }}" class="profile-avatar">
                                <div class="profile-summary">
                                    <h3>{{ $currentMaid->name }}</h3>
                                    <span class="muted">جواز السفر: {{ $currentMaid->passport_num ?? 'غير محدد' }}</span>
                                    <span class="muted">الجنسية: {{ $maidNationality }}</span>
                                    <span class="muted">الديانة: {{ $maidReligion }}</span>
                                    <span class="muted">الحالة الحالية: {{ $currentMaid->status ?? 'غير محدد' }}</span>
                                </div>
                            </div>

                            <ul class="detail-list">
                                <li>
                                    <span class="label">العمر</span>
                                    <span class="value">{{ $currentMaid->age ? $currentMaid->age . ' سنة' : 'غير محدد' }}</span>
                                </li>
                                <li>
                                    <span class="label">سنوات الخبرة</span>
                                    <span class="value">{{ $currentMaid->experience_years ? $currentMaid->experience_years . ' سنة' : 'غير محدد' }}</span>
                                </li>
                                <li>
                                    <span class="label">حالة الخبرة</span>
                                    <span class="value">{{ $currentMaid->experience_status ?? 'غير محدد' }}</span>
                                </li>
                                <li>
                                    <span class="label">الراتب المطلوب</span>
                                    <span class="value">{{ $currentMaid->salary ? number_format($currentMaid->salary, 2) . ' ريال' : 'غير محدد' }}</span>
                                </li>
                            </ul>
                        @else
                            <div class="empty-state">
                                لا توجد عاملة مرتبطة بهذا العقد حالياً.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-file-lines"></i>
                                تفاصيل العقد
                            </h3>
                        </div>

                        <ul class="detail-list">
                            <li>
                                <span class="label">رقم العقد</span>
                                <span class="value highlight">{{ $contract->contract_number ?? $contract->id }}</span>
                            </li>
                            <li>
                                <span class="label">تاريخ البداية</span>
                                <span class="value">{{ optional($contract->info?->ingaz_from)->format('Y-m-d') ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">تاريخ النهاية</span>
                                <span class="value">{{ optional($contract->info?->ingaz_to)->format('Y-m-d') ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">ساعات العمل يومياً</span>
                                <span class="value">{{ $contract->info?->working_hours_per_day ? $contract->info->working_hours_per_day . ' ساعة' : 'غير محدد' }}</span>
                            </li>
                            @if ((float) ($contract->info?->hourly_rate ?? 0) > 0)
                                <li>
                                    <span class="label">أجرة الساعة</span>
                                    <span class="value">{{ number_format((float) $contract->info->hourly_rate, 2) }} ريال</span>
                                </li>
                            @endif
                            @if ((float) ($contract->info?->monthly_salary ?? 0) > 0)
                                <li>
                                    <span class="label">الراتب الشهري</span>
                                    <span class="value">{{ number_format((float) $contract->info->monthly_salary, 2) }} ريال</span>
                                </li>
                            @endif
                            @if ((float) ($contract->info?->sponsorship_transfer_amount ?? 0) > 0)
                                <li>
                                    <span class="label">مبلغ نقل الخدمات</span>
                                    <span class="value">{{ number_format((float) $contract->info->sponsorship_transfer_amount, 2) }} ريال</span>
                                </li>
                            @endif
                            @if($contract->info?->sponsorship_transfer_total_amount || $contract->info?->sponsorship_transfer_payment_type || $contract->info?->sponsorship_transfer_payment_status)
                                @php
                                    $transferPaymentTypes = [
                                        'cash' => 'نقداً',
                                        'bank_transfer' => 'تحويل بنكي',
                                        'mada' => 'مدى',
                                        'tabby' => 'تابي',
                                        'tamara' => 'تمارا',
                                        'pay_later' => 'الدفع لاحقاً',
                                        'other' => 'أخرى',
                                    ];
                                    $transferPaymentStatuses = [
                                        'paid' => 'تم الدفع',
                                        'partial' => 'مدفوع جزئياً',
                                        'pending' => 'معلق',
                                        'later' => 'الدفع لاحقاً',
                                        'bank_pending' => 'بانتظار التحويل',
                                    ];
                                @endphp
                                <li>
                                    <span class="label">إجمالي نقل الخدمات</span>
                                    <span class="value">{{ number_format((float) $contract->info->sponsorship_transfer_total_amount, 2) }} ريال</span>
                                </li>
                                <li>
                                    <span class="label">مدفوع نقل الخدمات</span>
                                    <span class="value">{{ number_format((float) $contract->info->sponsorship_transfer_paid_amount, 2) }} ريال</span>
                                </li>
                                <li>
                                    <span class="label">متبقي نقل الخدمات</span>
                                    <span class="value">{{ number_format((float) $contract->info->sponsorship_transfer_remaining_amount, 2) }} ريال</span>
                                </li>
                                <li>
                                    <span class="label">طريقة دفع نقل الخدمات</span>
                                    <span class="value">{{ $transferPaymentTypes[$contract->info->sponsorship_transfer_payment_type] ?? 'غير محدد' }}</span>
                                </li>
                                <li>
                                    <span class="label">حالة دفع نقل الخدمات</span>
                                    <span class="value">{{ $transferPaymentStatuses[$contract->info->sponsorship_transfer_payment_status] ?? 'غير محدد' }}</span>
                                </li>
                                @if($contract->info->sponsorship_transfer_payment_receipt_url)
                                    <li>
                                        <span class="label">إيصال دفع نقل الخدمات</span>
                                        <span class="value">
                                            <a href="{{ $contract->info->sponsorship_transfer_payment_receipt_url }}" target="_blank">عرض الإيصال</a>
                                        </span>
                                    </li>
                                @endif
                            @endif
                            @if ((float) ($contract->info?->salary_after_sponsorship ?? 0) > 0)
                                <li>
                                    <span class="label">الراتب بعد نقل الخدمات</span>
                                    <span class="value">{{ number_format((float) $contract->info->salary_after_sponsorship, 2) }} ريال</span>
                                </li>
                            @endif
                            <li>
                                <span class="label">حالة الضمان</span>
                                <span class="value">{{ $warrantyStatuses[$contract->warranty_status] ?? ($contract->warranty_status ?: 'غير محدد') }}</span>
                            </li>
                            @if ($contract->status === 'frozen' || $contract->frozen_at)
                                <li>
                                    <span class="label">سبب التجميد</span>
                                    <span class="value">{{ $contract->freeze_reason ?: 'غير محدد' }}</span>
                                </li>
                                <li>
                                    <span class="label">تاريخ التجميد</span>
                                    <span class="value">{{ optional($contract->frozen_at)->format('Y-m-d H:i') ?? 'غير محدد' }}</span>
                                </li>
                                <li>
                                    <span class="label">تم التجميد بواسطة</span>
                                    <span class="value">{{ $contract->frozenBy?->name ?? 'غير محدد' }}</span>
                                </li>
                                @if ($contract->resumed_at)
                                    <li>
                                        <span class="label">آخر استئناف</span>
                                        <span class="value">{{ optional($contract->resumed_at)->format('Y-m-d H:i') ?? 'غير محدد' }}</span>
                                    </li>
                                @endif
                            @endif
                        </ul>
                    </div>
                </div>

                <div class="col-12">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-calendar-plus"></i>
                                سجل تمديدات العقد
                            </h3>
                            @if ($contract->extensions->isNotEmpty())
                                <span class="badge bg-info">{{ $contract->extensions->count() }} تمديد</span>
                            @endif
                        </div>

                        @if ($contract->extensions->isEmpty())
                            <div class="empty-state">لم يتم تمديد هذا العقد حتى الآن.</div>
                        @else
                            @php
                                $extensionValueTotal = $contract->extensions->sum(fn ($extension) => (float) ($extension->extension_value ?? 0));
                                $contractValueWithExtensions = (float) ($contract->info?->contract_value ?? 0) + $extensionValueTotal;
                            @endphp

                            <div class="metric-grid mb-4">
                                <div class="metric-card">
                                    <div class="label">قيمة العقد الأصلية</div>
                                    <div class="value">{{ number_format((float) ($contract->info?->contract_value ?? 0), 2) }}</div>
                                    <div class="sub-value">ريال</div>
                                </div>
                                <div class="metric-card">
                                    <div class="label">إجمالي قيمة التمديدات</div>
                                    <div class="value">{{ number_format($extensionValueTotal, 2) }}</div>
                                    <div class="sub-value">ريال</div>
                                </div>
                                <div class="metric-card">
                                    <div class="label">الإجمالي بعد التمديدات</div>
                                    <div class="value">{{ number_format($contractValueWithExtensions, 2) }}</div>
                                    <div class="sub-value">ريال</div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>المدة</th>
                                            <th>من</th>
                                            <th>إلى</th>
                                            <th>الخصم</th>
                                            <th>قيمة التمديد</th>
                                            <th>حالة الدفع</th>
                                            <th>سند القبض / البنك</th>
                                            <th>إثبات الدفع</th>
                                            <th>بواسطة</th>
                                            <th>تاريخ التسجيل</th>
                                            <th>ملاحظات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($contract->extensions->sortBy('start_date')->values() as $index => $extension)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    {{ $extension->extension_months ? $extension->extension_months . ' شهر / ' : '' }}
                                                    {{ $extension->extension_days ?? 0 }} يوم
                                                </td>
                                                <td>{{ optional($extension->start_date)->format('Y-m-d') ?? 'غير محدد' }}</td>
                                                <td>{{ optional($extension->end_date)->format('Y-m-d') ?? 'غير محدد' }}</td>
                                                <td>
                                                    @if ($extension->discount_label)
                                                        <span class="text-danger fw-bold">{{ $extension->discount_label }}</span>
                                                        @if ($extension->discount_notes)
                                                            <div class="small text-muted">{{ $extension->discount_notes }}</div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $extension->extension_value !== null ? number_format((float) $extension->extension_value, 2) . ' ريال' : 'غير مسجل' }}
                                                    @if ($extension->discount_label && $extension->gross_value)
                                                        <div class="small text-muted">
                                                            قبل الخصم: {{ number_format((float) $extension->gross_value, 2) }} ريال
                                                        </div>
                                                    @endif
                                                    <div class="mt-1">
                                                        @if (\App\Support\InvoiceAuthorization::canPrint())
                                                        <a href="{{ route('rental.extension-invoice.download', ['id' => $extension->id, 'autoprint' => 1]) }}"
                                                            target="_blank" rel="noopener" class="small">
                                                            فاتورة التمديد
                                                        </a>
                                                        @endif
                                                    </div>
                                                    @if ($extension->invoiceConstraint)
                                                        <div>
                                                            <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $extension->invoiceConstraint->id, 'autoprint' => 1]) }}"
                                                                target="_blank" rel="noopener" class="small">
                                                                قيد قيمة التمديد
                                                            </a>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>{{ $paymentStatuses[$extension->payment_status] ?? ($extension->payment_status ?: 'غير محدد') }}</td>
                                                <td>
                                                    @if ($extension->receiptBond)
                                                        <div class="mb-1">
                                                            <a href="{{ route('finance.bonds-print.show', ['bonds_print' => $extension->receiptBond->id, 'type' => 'qabd']) }}"
                                                                target="_blank" rel="noopener">
                                                                سند #{{ $extension->receiptBond->code ?: $extension->receiptBond->id }}
                                                            </a>
                                                        </div>
                                                        <small class="text-muted">
                                                            {{ $extension->receiptAccount?->name ?: '-' }}
                                                            @if ($extension->payment_amount)
                                                                — {{ number_format((float) $extension->payment_amount, 2) }} ريال
                                                            @endif
                                                        </small>
                                                        @if ($extension->receiptConstraint)
                                                            <div>
                                                                <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $extension->receiptConstraint->id, 'autoprint' => 1]) }}"
                                                                    target="_blank" rel="noopener" class="small">عرض القيد</a>
                                                            </div>
                                                        @endif
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($extension->payment_receipt_url)
                                                        <a href="{{ $extension->payment_receipt_url }}" target="_blank" rel="noopener">عرض الإيصال</a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>{{ $extension->createdBy?->name ?? 'غير محدد' }}</td>
                                                <td>{{ optional($extension->created_at)->format('Y-m-d H:i') ?? 'غير محدد' }}</td>
                                                <td>{{ $extension->notes ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-12">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-file-invoice-dollar"></i>
                                المستندات المالية
                            </h3>
                        </div>

                        @if ($contract->status === 'cancelled' && $isEarlyClosure)
                            <ul class="detail-list mb-4">
                                <li>
                                    <span class="label">سبب حالة العقد</span>
                                    <span class="value highlight">أُلغي العقد قبل اكتمال مدة العقد</span>
                                </li>
                                <li>
                                    <span class="label">تاريخ الإنهاء</span>
                                    <span class="value">{{ $closureSummary['closure_date'] ?? '-' }}</span>
                                </li>
                                <li>
                                    <span class="label">الأيام المتبقية عند الإنهاء</span>
                                    <span class="value">{{ (int) ($closureSummary['remaining_days_value'] ?? 0) }} يوم</span>
                                </li>
                            </ul>
                        @endif

                        @if ($showClosureSettlement)
                            <ul class="detail-list mb-4">
                                <li>
                                    <span class="label">نوع تسوية الإنهاء</span>
                                    <span class="value highlight">
                                        {{ $closureCompanyDue > 0 ? 'مستحق للشركة' : 'استرجاع للعميل' }}
                                    </span>
                                </li>
                                @if ($closureCompanyDue > 0)
                                    <li>
                                        <span class="label">مستحق الشركة</span>
                                        <span class="value">{{ number_format($closureCompanyDue, 2) }} ريال</span>
                                    </li>
                                    <li>
                                        <span class="label">أيام بعد انتهاء العقد</span>
                                        <span class="value">{{ (int) ($closureSummary['overdue_days'] ?? 0) }} يوم</span>
                                    </li>
                                @endif
                                @if ($closureClientRefund > 0)
                                    <li>
                                        <span class="label">باقي للعميل / استرجاع مقترح</span>
                                        <span class="value">{{ number_format($closureClientRefund, 2) }} ريال</span>
                                    </li>
                                    <li>
                                        <span class="label">الأيام المتبقية</span>
                                        <span class="value">{{ (int) ($closureSummary['remaining_days_value'] ?? 0) }} يوم</span>
                                    </li>
                                @endif
                                <li>
                                    <span class="label">تكلفة اليوم</span>
                                    <span class="value">{{ number_format((float) ($closureSummary['daily_rate'] ?? 0), 2) }} ريال</span>
                                </li>
                            </ul>

                        @endif

                        @if ($contract->financeInvoice)
                            <ul class="detail-list">
                                <li>
                                    <span class="label">رقم الفاتورة</span>
                                    <span class="value highlight">#{{ $contract->financeInvoice->id }}</span>
                                </li>
                                <li>
                                    <span class="label">قيمة الفاتورة الأساسية</span>
                                    <span class="value">{{ number_format((float) $contract->financeInvoice->total_due, 2) }} ريال</span>
                                </li>
                                <li>
                                    <span class="label">المسدد المخصص للفاتورة</span>
                                    <span class="value">{{ number_format((float) $contract->financeInvoice->paid_amount, 2) }} ريال</span>
                                </li>
                                <li>
                                    <span class="label">متبقي الفاتورة الأصلية قبل الإشعارات</span>
                                    <span class="value">{{ number_format((float) $contract->financeInvoice->remaining_amount, 2) }} ريال</span>
                                </li>
                                <li>
                                    <span class="label">حساب القبض</span>
                                    <span class="value">{{ $contract->financeInvoice->receiptAccount?->name ?? 'غير محدد' }}</span>
                                </li>
                            </ul>

                            <div class="d-flex flex-wrap gap-2 mt-3">
                                @if (\App\Support\InvoiceAuthorization::canPrint())
                                <a href="{{ route('rental.invoice.download', ['id' => $contract->financeInvoice->id, 'autoprint' => 1]) }}"
                                    target="_blank" class="btn btn-primary">
                                    <i class="fas fa-file-invoice me-1"></i>
                                    طباعة الفاتورة
                                </a>
                                @endif
                                @if (\App\Support\InvoiceAuthorization::canView())
                                <a href="{{ route('invoices.index') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-list me-1"></i>
                                    جميع الفواتير
                                </a>
                                @endif
                                @if (\App\Support\InvoiceAuthorization::canCreateCreditNotes())
                                    <a href="{{ route('rental-invoices.credit-notes.create', $contract->financeInvoice->id) }}"
                                        class="btn btn-outline-danger">
                                        <i class="fas fa-file-invoice-dollar me-1"></i>
                                        إشعار دائن دون إنهاء العقد
                                    </a>
                                @endif

                                @if ($contract->financeInvoice->receipt_bond_id)
                                    <a href="{{ route('finance.bonds-print.show', ['bonds_print' => $contract->financeInvoice->receipt_bond_id, 'type' => 'qabd']) }}"
                                        target="_blank" class="btn btn-outline-primary">
                                        <i class="fas fa-receipt me-1"></i>
                                        سند القبض الأساسي
                                    </a>
                                @endif

                                @if ($contract->financeInvoice->invoice_constraint_id)
                                    <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $contract->financeInvoice->invoice_constraint_id, 'autoprint' => 1]) }}"
                                        target="_blank" class="btn btn-outline-secondary">
                                        <i class="fas fa-book me-1"></i>
                                        قيد الفاتورة
                                    </a>
                                @endif

                                @if ($contract->financeInvoice->receipt_constraint_id)
                                    <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $contract->financeInvoice->receipt_constraint_id, 'autoprint' => 1]) }}"
                                        target="_blank" class="btn btn-outline-secondary">
                                        <i class="fas fa-book-open me-1"></i>
                                        قيد سند القبض
                                    </a>
                                @endif
                            </div>
                        @else
                            <div class="empty-state">
                                لم يتم إنشاء مستندات مالية لهذا العقد حتى الآن.
                            </div>
                        @endif

                        @if ($closureAdjustments->isNotEmpty())
                            <div class="mt-4">
                                <h4 class="h6 fw-bold mb-3">
                                    <i class="fas fa-scale-balanced me-1"></i>
                                    إشعارات وتسويات عقد التأجير
                                </h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>نوع التسوية</th>
                                                <th>القيمة</th>
                                                <th>الإشعار</th>
                                                <th>القيد المحاسبي</th>
                                                <th>حالة القيد</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($closureAdjustments as $adjustment)
                                                <tr>
                                                    <td>
                                                        {{ $adjustment->context === 'closure_correction' ? 'تصحيح تسوية الإنهاء' : ($adjustment->context === 'manual_credit_note' ? 'مرتجع مستقل' : ($adjustment->type === 'expense' ? 'إشعار دائن عند الإنهاء' : 'إشعار مدين عند الإنهاء')) }}
                                                    </td>
                                                    <td>{{ number_format((float) $adjustment->amount, 2) }} ريال</td>
                                                    <td>
                                                        @if (\App\Support\InvoiceAuthorization::canPrintCreditNotes())
                                                        <a href="{{ route('rental.credit-note.download', ['id' => $adjustment->id, 'autoprint' => 1]) }}"
                                                            target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-file-invoice-dollar me-1"></i>
                                                            {{ $adjustment->type === 'expense' ? 'الإشعار الدائن' : 'الإشعار المدين' }}
                                                        </a>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($adjustment->constraint_id)
                                                            <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $adjustment->constraint_id, 'autoprint' => 1]) }}"
                                                                target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                                                <i class="fas fa-book me-1"></i>
                                                                قيد #{{ $adjustment->constraint?->code ?: $adjustment->constraint_id }}
                                                            </a>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($adjustment->constraint_id)
                                                            <span class="badge bg-success">تم إنشاء القيد</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark">لم يُنشأ قيد</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        @if (!empty($contract->info?->moyasar_payment_url))
                            @if ((float) $contract->info->moyasar_refunded_amount > 0)
                                <div class="alert alert-warning mt-3">إجمالي المبالغ المستردة عبر ميسر: {{ number_format((float) $contract->info->moyasar_refunded_amount, 2) }} ريال. تم تسجيل سندات الصرف المرتبطة بها.</div>
                            @endif
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <button type="button"
                                    class="btn btn-outline-primary copy-payment-link-btn"
                                    data-payment-url="{{ $contract->info->moyasar_payment_url }}">
                                    <i class="fas fa-link me-1"></i>
                                    نسخ رابط الدفع
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-12">
                    <div class="page-section-divider">
                        <span class="divider-icon"><i class="fas fa-clock-rotate-left"></i></span>
                        <div>
                            <h2>السجلات والملاحظات</h2>
                            <p>تغييرات العاملة، المعلومات الإضافية، والمراجعة التاريخية للعقد.</p>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-right-left"></i>
                                سجل تبديل العاملات
                            </h3>
                            <span class="change-pill">
                                <i class="fas fa-clock-rotate-left"></i>
                                {{ $maidChangeHistory->count() }} عملية
                            </span>
                        </div>

                        @if ($maidChangeHistory->count())
                            <div class="table-responsive">
                                <table class="history-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>العاملة السابقة</th>
                                            <th>العاملة الحالية بعد التبديل</th>
                                            <th>السبب</th>
                                            <th>تم بواسطة</th>
                                            <th>التاريخ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($maidChangeHistory as $change)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $change['old_maid_name'] }}</td>
                                                <td>{{ $change['new_maid_name'] }}</td>
                                                <td>{{ $change['reason'] }}</td>
                                                <td>{{ $change['changed_by'] }}</td>
                                                <td>{{ optional($change['changed_at'])->format('Y-m-d H:i') ?? 'غير محدد' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state">
                                لم يتم تغيير العاملة في هذا العقد حتى الآن.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-circle-info"></i>
                                معلومات إضافية
                            </h3>
                        </div>

                        <ul class="detail-list">
                            <li>
                                <span class="label">المسوق</span>
                                <span class="value">{{ $contract->marketer?->name ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">رقم جوال المسوق</span>
                                <span class="value">{{ $contract->marketer?->phone ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">البريد الإلكتروني للمسوق</span>
                                <span class="value">{{ $contract->marketer?->email ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">تاريخ إنشاء العقد</span>
                                <span class="value">
                                    {{ optional($contract->legacy_created_at ?? $contract->contract_date ?? $contract->created_at)->format('Y-m-d H:i') ?? 'غير محدد' }}
                                </span>
                            </li>
                            @if ($contract->legacy_created_at)
                                <li>
                                    <span class="label">تاريخ استيراد السجل للنظام</span>
                                    <span class="value">{{ optional($contract->created_at)->format('Y-m-d H:i') ?? 'غير محدد' }}</span>
                                </li>
                            @endif
                            <li>
                                <span class="label">آخر تحديث</span>
                                <span class="value">{{ optional($contract->updated_at)->format('Y-m-d H:i') ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">بداية الضمان</span>
                                <span class="value">{{ optional($contract->warranty_start_date)->format('Y-m-d') ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">نهاية الضمان</span>
                                <span class="value">{{ optional($contract->warranty_end_date)->format('Y-m-d') ?? 'غير محدد' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-sticky-note"></i>
                                الملاحظات والبنود الإضافية
                            </h3>
                        </div>

                        <div class="notes-box">
                            {{ $contract->info?->notes ?: 'لا توجد ملاحظات مسجلة على العقد.' }}
                        </div>

                        @if ($additionalTerms->count())
                            <div class="term-tags">
                                @foreach ($additionalTerms as $term)
                                    <span class="term-tag">{{ $term }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-12">
                    <div class="page-section-divider">
                        <span class="divider-icon"><i class="fas fa-route"></i></span>
                        <div>
                            <h2>مسار العقد</h2>
                            <p>التسلسل التشغيلي للعقد من الإنشاء حتى الإغلاق.</p>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-route"></i>
                                مراحل العقد
                            </h3>
                            <span class="change-pill">
                                <i class="fas fa-location-dot"></i>
                                {{ $contract->status_text }}
                            </span>
                        </div>

                        <div class="workflow-grid">
                            @foreach ($workflowSteps as $step)
                                <div class="workflow-step {{ $step['state'] }}">
                                    <span class="step-number">{{ $step['number'] }}</span>
                                    <span class="step-icon">
                                        <i class="{{ $step['icon'] }}"></i>
                                    </span>
                                    <span class="step-state">{{ $step['state_label'] }}</span>
                                    <h4>{{ $step['title'] }}</h4>
                                    <p>{{ $step['description'] }}</p>
                                    @if (!empty($step['date']))
                                        <span class="step-date">
                                            <i class="fas fa-clock"></i>
                                            {{ $step['date'] }}
                                        </span>
                                    @elseif ($step['state'] === 'done')
                                        <span class="step-date">
                                            <i class="fas fa-circle-check"></i>
                                            مكتملة دون تاريخ موثق
                                        </span>
                                    @else
                                        <span class="step-date">
                                            <i class="fas fa-hourglass-half"></i>
                                            بانتظار التنفيذ
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="page-section-divider">
                        <span class="divider-icon"><i class="fas fa-paperclip"></i></span>
                        <div>
                            <h2>المرفقات والتنزيل</h2>
                            <p>عرض ملفات العقد وتنزيلها منفردة أو مجمعة.</p>
                        </div>
                    </div>
                </div>

                <div class="col-12" id="contract-attachments">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-paperclip"></i>
                                مرفقات العقد
                            </h3>
                            @if (!empty($contractAttachments))
                                <span class="change-pill">
                                    <i class="fas fa-files"></i>
                                    {{ count($contractAttachments) }} مرفق
                                </span>
                            @endif
                        </div>

                        @if (!empty($contractAttachments))
                            <form method="GET" action="{{ route('RentContract.attachments.download', $contract->id) }}" class="no-print">
                                <input type="hidden" name="selected_only" value="1">
                                <div class="attachment-list mb-3">
                                    @foreach ($contractAttachments as $attachment)
                                        <label class="attachment-item">
                                            <span class="d-flex align-items-center gap-2">
                                                <input type="checkbox" name="attachments[]" value="{{ $attachment['key'] }}" checked>
                                                <span class="fw-bold">{{ $attachment['label'] }}</span>
                                            </span>
                                            <span class="d-flex flex-wrap gap-2 mt-3">
                                                <a href="{{ $attachment['url'] }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-eye me-1"></i>
                                                    عرض
                                                </a>
                                                <a href="{{ $attachment['download_url'] }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download me-1"></i>
                                                    تنزيل الملف
                                                </a>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-download me-1"></i>
                                        تنزيل المرفقات المحددة
                                    </button>
                                    <a href="{{ route('RentContract.attachments.download', $contract->id) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-file-zipper me-1"></i>
                                        تنزيل جميع المرفقات
                                    </a>
                                </div>
                            </form>
                        @else
                            <div class="empty-state">
                                لا توجد مرفقات مسجلة على هذا العقد حتى الآن.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-12">
                    <div class="page-section-divider">
                        <span class="divider-icon"><i class="fas fa-history"></i></span>
                        <div>
                            <h2>السجل الزمني</h2>
                            <p>آخر قسم في الصفحة لمراجعة جميع العمليات والتغييرات على العقد.</p>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="section-card activity-wrapper">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-history"></i>
                                سجل النشاط
                            </h3>
                        </div>

                        @include('admin.partials.activity-feed', ['activities' => $activities])
                    </div>
                </div>
            </div>
        </div>

        @if(AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CHANGE_MARKETER))
            <div class="modal fade" id="changeMarketerModal" tabindex="-1" aria-labelledby="changeMarketerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form id="changeMarketerForm" class="modal-content" method="POST" action="{{ route('RentContract.changeMarketer', $contract->id) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="changeMarketerModalLabel">تغيير المسوق في عقد التأجير</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            <p>المسوق الحالي: {{ $contract->marketer?->name ?? 'غير محدد' }}</p>
                            <label for="new_marketer_id" class="form-label">المسوق الجديد</label>
                            <select id="new_marketer_id" name="marketer_id" class="form-select" required>
                                <option value="">اختر المسوق</option>
                                @foreach($marketers as $marketer)
                                    <option value="{{ $marketer->id }}" {{ (int) $contract->marketer_id === (int) $marketer->id ? 'disabled' : '' }}>{{ $marketer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">حفظ المسوق</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_EDIT) && !in_array($contract->status, ['completed', 'cancelled'], true))
            <div class="modal fade" id="changeMaidModal" tabindex="-1" aria-labelledby="changeMaidModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="changeMaidModalLabel">تغيير العاملة لعقد التأجير</h5>
                            <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form id="changeMaidForm">
                            <div class="modal-body">
                                <div class="alert alert-light border">
                                    <div class="fw-semibold mb-1">العاملة الحالية</div>
                                    <div>{{ $contract->maid?->name ?? 'لا توجد عاملة مرتبطة بالعقد' }}</div>
                                    <div class="maid-select-hint">ستظهر فقط عاملات التأجير المتاحات فعلياً وغير المرتبطات بحجز أو عقد نشط.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_maid_id" class="form-label">العاملة البديلة</label>
                                    <select name="new_maid_id" id="new_maid_id" class="form-select change-maid-select2"
                                        data-placeholder="ابحث باسم العاملة أو رقم الجواز أو الجنسية" required>
                                        <option value="">اختر العاملة المتاحة</option>
                                        @forelse ($availableReplacementMaids as $maid)
                                            <option value="{{ $maid->id }}">
                                                {{ $maid->name }} - {{ $maid->passport_num }} -
                                                {{ optional($maid->nationality)->title ?? optional($maid->nationality)->name ?? 'بدون جنسية' }}
                                            </option>
                                        @empty
                                            <option value="" disabled>لا توجد عاملات تأجير متاحات حالياً</option>
                                        @endforelse
                                    </select>
                                    <div class="invalid-feedback d-block d-none" data-field-error="new_maid_id"></div>
                                </div>

                                <div class="mb-0">
                                    <label for="change_reason" class="form-label">سبب التغيير</label>
                                    <textarea name="reason" id="change_reason" rows="4" class="form-control" maxlength="1000"
                                        placeholder="اكتب سبب تغيير العاملة" required></textarea>
                                    <div class="invalid-feedback d-block d-none" data-field-error="reason"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-warning text-dark" id="submitChangeMaidBtn"
                                    {{ $availableReplacementMaids->isEmpty() ? 'disabled' : '' }}>
                                    <i class="fas fa-save me-1"></i>
                                    حفظ التغيير
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="action-buttons no-print">
            <a href="{{ route('print.rental.contract', $contract->id) }}" class="btn-icon btn-print" title="طباعة العقد" target="_blank">
                <i class="fas fa-print"></i>
            </a>

            @if (!empty($contractAttachments))
                <a href="#contract-attachments" class="btn-icon btn-back" title="الانتقال إلى المرفقات">
                    <i class="fas fa-paperclip"></i>
                </a>
            @endif

            @if (!empty($contract->info?->moyasar_payment_url))
                <button type="button"
                    class="btn-icon btn-copy-payment copy-payment-link-btn"
                    title="نسخ رابط الدفع"
                    data-payment-url="{{ $contract->info->moyasar_payment_url }}">
                    <i class="fas fa-link"></i>
                </button>
            @endif

            @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_EDIT))
                <a href="{{ route('RentContract.edit', $contract->id) }}" class="btn-icon btn-edit" title="تعديل العقد">
                    <i class="fas fa-pen"></i>
                </a>
            @endif

            @if ($contract->canCloseOrExtend() && @AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL))
                <button type="button" class="btn-icon btn-complete" title="إنهاء العقد">
                    <i class="fas fa-check"></i>
                </button>
            @endif

            @if ($contract->canFreeze() && @AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL))
                <button type="button" class="btn-icon btn-back btn-freeze" title="تجميد العقد">
                    <i class="fas fa-pause"></i>
                </button>
            @endif

            @if ($contract->canResume() && @AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CONTROL))
                <button type="button" class="btn-icon btn-complete btn-resume" title="استئناف العقد">
                    <i class="fas fa-play"></i>
                </button>
            @endif

            <a href="{{ route('RentContract.index') }}" class="btn-icon btn-back" title="العودة للقائمة">
                <i class="fas fa-arrow-right"></i>
            </a>

            @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_DELETE))
                <button type="button" class="btn-icon btn-delete" title="حذف العقد">
                    <i class="fas fa-trash"></i>
                </button>
            @endif
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#changeMarketerForm').on('submit', function(event) {
                event.preventDefault();
                const form = $(this);
                const button = form.find('button[type="submit"]');
                button.prop('disabled', true);
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    headers: { 'Accept': 'application/json' },
                    success: function(response) {
                        Swal.fire({ icon: 'success', text: response.message, confirmButtonText: 'حسناً' })
                            .then(function() { window.location.reload(); });
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON || {};
                        const errors = response.errors ? Object.values(response.errors).flat().join('\n') : '';
                        Swal.fire({
                            icon: 'error',
                            text: errors || response.message || 'تعذر تغيير المسوق. يرجى التحقق من الاتصال والمحاولة مجدداً.',
                            confirmButtonText: 'حسناً'
                        });
                    },
                    complete: function() { button.prop('disabled', false); }
                });
            });

            function copyTextToClipboard(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    return navigator.clipboard.writeText(text);
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

                return Promise.resolve();
            }

            $(document).on('click', '.copy-payment-link-btn', function() {
                const paymentUrl = $(this).data('payment-url');

                if (!paymentUrl) {
                    Swal.fire({
                        icon: 'error',
                        title: 'تعذر النسخ',
                        text: 'لا يوجد رابط دفع محفوظ لهذا العقد.'
                    });
                    return;
                }

                copyTextToClipboard(paymentUrl).then(function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم النسخ',
                        text: 'تم نسخ رابط الدفع بنجاح.',
                        timer: 1600,
                        showConfirmButton: false
                    });
                }).catch(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'تعذر النسخ',
                        text: 'لم يتمكن المتصفح من نسخ رابط الدفع.'
                    });
                });
            });

            $(document).on('click', '.delete-payment-receipt', function() {
                const button = $(this);

                Swal.fire({
                    icon: 'warning',
                    title: 'حذف الإيصال',
                    text: 'هل تريد حذف هذا الإيصال؟',
                    showCancelButton: true,
                    confirmButtonText: 'نعم، حذف',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    button.prop('disabled', true);

                    $.ajax({
                        url: button.data('delete-url'),
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function(response) {
                            button.closest('.payment-receipt-item').remove();

                            if (!$('#paymentReceiptsList .payment-receipt-item').length) {
                                $('#paymentReceiptsList').remove();
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'تم الحذف',
                                text: response.message || 'تم حذف الإيصال بنجاح'
                            });
                        },
                        error: function(xhr) {
                            const message = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : 'حدث خطأ أثناء حذف الإيصال';

                            button.prop('disabled', false);

                            Swal.fire({
                                icon: 'error',
                                title: 'تعذر الحذف',
                                text: message
                            });
                        }
                    });
                });
            });

            function initChangeMaidSelect() {
                const replacementMaidSelect = $('#new_maid_id');

                if (!replacementMaidSelect.length || !$.fn.select2) {
                    return;
                }

                if (replacementMaidSelect.hasClass('select2-hidden-accessible')) {
                    replacementMaidSelect.select2('destroy');
                }

                replacementMaidSelect.select2({
                    dir: 'rtl',
                    width: '100%',
                    dropdownParent: $('#changeMaidModal'),
                    placeholder: replacementMaidSelect.data('placeholder'),
                    minimumResultsForSearch: 0,
                    language: {
                        noResults: function() {
                            return 'لا توجد نتائج مطابقة';
                        },
                        searching: function() {
                            return 'جاري البحث...';
                        }
                    }
                });
            }

            initChangeMaidSelect();

            $('#new_maid_id').on('select2:open', function() {
                setTimeout(function() {
                    $('.select2-container--open .select2-search__field').trigger('focus');
                }, 0);
            });

            $(document).on('click', '.btn-complete', function() {
                Swal.fire({
                    title: 'تأكيد إنهاء العقد',
                    html: '<div class="text-end">هل أنت متأكد من إنهاء هذا العقد؟</div>' +
                        '<div class="form-check mt-3 text-end">' +
                        '<input class="form-check-input" type="checkbox" id="sendClosureMessageFromShow" checked>' +
                        '<label class="form-check-label" for="sendClosureMessageFromShow">إرسال رسالة إنهاء العقد للعميل</label>' +
                        '</div>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#22c55e',
                    cancelButtonColor: '#dc3545',
                    confirmButtonText: 'نعم، إنهاء العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('RentContract.complete', $contract->id) }}",
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                send_closure_message: $('#sendClosureMessageFromShow').is(':checked') ? 1 : 0
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم بنجاح',
                                        text: response.message
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إنهاء العقد'
                                });
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.btn-delete', function() {
                Swal.fire({
                    title: 'تأكيد الحذف',
                    text: 'هل أنت متأكد من حذف هذا العقد؟ لا يمكن التراجع عن هذا الإجراء.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'نعم، احذف العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let form = $('<form>', {
                            method: 'POST',
                            action: "{{ route('RentContract.destroy', $contract->id) }}"
                        });

                        form.append($('<input>', {
                            type: 'hidden',
                            name: '_token',
                            value: '{{ csrf_token() }}'
                        }));

                        form.append($('<input>', {
                            type: 'hidden',
                            name: '_method',
                            value: 'DELETE'
                        }));

                        $('body').append(form);
                        form.submit();
                    }
                });
            });

            $(document).on('click', '.btn-freeze', function() {
                Swal.fire({
                    title: 'تجميد عقد الإيجار',
                    input: 'textarea',
                    inputLabel: 'سبب التجميد',
                    inputPlaceholder: 'اكتب سبب تجميد العقد',
                    inputAttributes: {
                        maxlength: 1000
                    },
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#64748b',
                    cancelButtonColor: '#dc3545',
                    confirmButtonText: 'تجميد العقد',
                    cancelButtonText: 'إلغاء',
                    inputValidator: (value) => {
                        if (!value || !value.trim()) {
                            return 'يرجى كتابة سبب التجميد';
                        }
                    }
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: "{{ route('RentContract.freeze', $contract->id) }}",
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            freeze_reason: result.value
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: response.success ? 'success' : 'error',
                                title: response.success ? 'تم بنجاح' : 'خطأ',
                                text: response.message
                            }).then(() => {
                                if (response.success) {
                                    location.reload();
                                }
                            });
                        },
                        error: function(xhr) {
                            const response = xhr.responseJSON || {};
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء تجميد العقد'
                            });
                        }
                    });
                });
            });

            $(document).on('click', '.btn-resume', function() {
                Swal.fire({
                    title: 'استئناف عقد الإيجار',
                    text: 'هل تريد استئناف هذا العقد؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#22c55e',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'نعم، استئناف العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: "{{ route('RentContract.resume', $contract->id) }}",
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: response.success ? 'success' : 'error',
                                title: response.success ? 'تم بنجاح' : 'خطأ',
                                text: response.message
                            }).then(() => {
                                if (response.success) {
                                    location.reload();
                                }
                            });
                        },
                        error: function(xhr) {
                            const response = xhr.responseJSON || {};
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء استئناف العقد'
                            });
                        }
                    });
                });
            });

            $('#changeMaidForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitButton = $('#submitChangeMaidBtn');
                const fieldErrors = form.find('[data-field-error]');

                fieldErrors.addClass('d-none').text('');
                submitButton.prop('disabled', true);

                $.ajax({
                    url: "{{ route('RentContract.changeMaid', $contract->id) }}",
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        new_maid_id: $('#new_maid_id').val(),
                        reason: $('#change_reason').val(),
                    },
                    success: function(response) {
                        if (!response.success) {
                            Swal.fire({
                                icon: 'error',
                                title: 'تعذر تغيير العاملة',
                                text: response.message || 'حدث خطأ غير متوقع',
                            });
                            return;
                        }

                        $('#changeMaidModal').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: 'تم الحفظ',
                            text: response.message,
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON || {};
                        const errors = response.errors || {};

                        Object.keys(errors).forEach(function(field) {
                            const errorTarget = form.find(`[data-field-error="${field}"]`);
                            if (errorTarget.length) {
                                errorTarget.removeClass('d-none').text(errors[field][0]);
                            }
                        });

                        Swal.fire({
                            icon: 'error',
                            title: 'تعذر تغيير العاملة',
                            text: response.message || 'حدث خطأ أثناء تنفيذ العملية',
                        });
                    },
                    complete: function() {
                        submitButton.prop('disabled', {{ $availableReplacementMaids->isEmpty() ? 'true' : 'false' }});
                    }
                });
            });

            $('#changeMaidModal').on('hidden.bs.modal', function() {
                const form = $('#changeMaidForm');
                form.trigger('reset');
                $('#new_maid_id').val('').trigger('change');
                form.find('[data-field-error]').addClass('d-none').text('');
                $('#submitChangeMaidBtn').prop('disabled', {{ $availableReplacementMaids->isEmpty() ? 'true' : 'false' }});
            });
        });
    </script>
@endsection
