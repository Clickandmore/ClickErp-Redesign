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
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        }

        .workflow-step {
            position: relative;
            padding: 18px;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            min-height: 170px;
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
            padding: 12px;
            background: #f8fafc;
        }

        .activity-wrapper .border.rounded-3 {
            border-color: #edf2f7 !important;
        }

        .maid-select-hint {
            font-size: 13px;
            color: #64748b;
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

        .btn-delete {
            background: #ef4444;
        }

        @media (max-width: 991px) {
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

            $workflowSteps = [
                [
                    'title' => 'بانتظار التوقيع',
                    'icon' => 'fas fa-file-signature',
                    'description' => 'تم إنشاء العقد وهو الآن جاهز لتوقيع العميل دون مرحلة إرسال مستقلة.',
                    'date' => optional($contract->contract_sent_at ?? $contract->created_at)?->format('Y-m-d H:i'),
                    'state' => in_array($contract->status, ['waiting_contract_send', 'contract_sent'], true)
                        ? 'current'
                        : ((bool) $contract->contract_signed_at || in_array($contract->status, ['contract_signed', 'maid_delivered', 'active', 'completed'], true) ? 'done' : 'pending'),
                ],
                [
                    'title' => 'تم توقيع العقد',
                    'icon' => 'fas fa-signature',
                    'description' => 'اعتماد توقيع العميل على العقد.',
                    'date' => optional($contract->contract_signed_at)?->format('Y-m-d H:i'),
                    'state' => $contract->contract_signed_at
                        ? 'done'
                        : ($contract->status === 'contract_signed' ? 'current' : 'pending'),
                ],
                [
                    'title' => 'تم تسليم العاملة',
                    'icon' => 'fas fa-handshake',
                    'description' => 'توثيق التسليم الفعلي للعاملة للعميل.',
                    'date' => optional($contract->maid_delivered_at)?->format('Y-m-d H:i'),
                    'state' => $contract->maid_delivered_at
                        ? 'done'
                        : ($contract->status === 'maid_delivered' ? 'current' : 'pending'),
                ],
                [
                    'title' => 'العقد نشط',
                    'icon' => 'fas fa-bolt',
                    'description' => 'العقد مفعل والخدمة دخلت مرحلة التشغيل.',
                    'date' => optional($contract->trial_started_at)?->format('Y-m-d H:i'),
                    'state' => $contract->status === 'active'
                        ? 'current'
                        : (in_array($contract->status, ['completed'], true) ? 'done' : 'pending'),
                ],
            ];
        @endphp

        <div class="rental-show-page">
            <div class="hero-card">
                <div class="hero-top">
                    <div>
                        <span class="hero-eyebrow">
                            <i class="fas fa-file-contract"></i>
                            عقد تأجير عاملة منزلية
                        </span>
                        <h1>العقد رقم {{ $contract->contract_number ?? $contract->id }}</h1>
                        <p>عرض منظم لبيانات عقد الإيجار، حالة التنفيذ، وسجل تبديل العاملات المرتبط بهذا العقد.</p>
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
                    <div class="label">الأيام المتبقية</div>
                    <div class="value">{{ $remainingDays !== null ? $remainingDays : 0 }}</div>
                    <div class="sub-value">{{ $remainingDays !== null && $remainingDays > 0 ? 'يوم متبقٍ' : 'تم الوصول لنهاية المدة أو لم تحدد' }}</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-6">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-user"></i>
                                بيانات العميل
                            </h3>
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
                                <span class="label">رقم الهوية</span>
                                <span class="value">{{ $contract->user?->national_num ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">الجنسية</span>
                                <span class="value">{{ $userNationality }}</span>
                            </li>
                            <li>
                                <span class="label">المهنة</span>
                                <span class="value">{{ $contract->user?->profession ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">الدخل الشهري</span>
                                <span class="value">{{ $contract->user?->monthly_income ? number_format($contract->user->monthly_income, 2) . ' ريال' : 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">المدينة</span>
                                <span class="value">{{ $contract->user?->city ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">العنوان</span>
                                <span class="value">{{ $contract->user?->address ?? 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">عدد أفراد الأسرة / الأطفال</span>
                                <span class="value">{{ $contract->user?->family_num ?? '-' }} / {{ $contract->user?->children ?? '-' }}</span>
                            </li>
                        </ul>
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
                            <li>
                                <span class="label">أجرة الساعة</span>
                                <span class="value">{{ $contract->info?->hourly_rate ? number_format($contract->info->hourly_rate, 2) . ' ريال' : 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">الراتب الشهري</span>
                                <span class="value">{{ $contract->info?->monthly_salary ? number_format($contract->info->monthly_salary, 2) . ' ريال' : 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">مبلغ نقل الكفالة</span>
                                <span class="value">{{ $contract->info?->sponsorship_transfer_amount ? number_format($contract->info->sponsorship_transfer_amount, 2) . ' ريال' : 'غير محدد' }}</span>
                            </li>
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
                                    <span class="label">إجمالي نقل الكفالة</span>
                                    <span class="value">{{ number_format((float) $contract->info->sponsorship_transfer_total_amount, 2) }} ريال</span>
                                </li>
                                <li>
                                    <span class="label">مدفوع نقل الكفالة</span>
                                    <span class="value">{{ number_format((float) $contract->info->sponsorship_transfer_paid_amount, 2) }} ريال</span>
                                </li>
                                <li>
                                    <span class="label">متبقي نقل الكفالة</span>
                                    <span class="value">{{ number_format((float) $contract->info->sponsorship_transfer_remaining_amount, 2) }} ريال</span>
                                </li>
                                <li>
                                    <span class="label">طريقة دفع نقل الكفالة</span>
                                    <span class="value">{{ $transferPaymentTypes[$contract->info->sponsorship_transfer_payment_type] ?? 'غير محدد' }}</span>
                                </li>
                                <li>
                                    <span class="label">حالة دفع نقل الكفالة</span>
                                    <span class="value">{{ $transferPaymentStatuses[$contract->info->sponsorship_transfer_payment_status] ?? 'غير محدد' }}</span>
                                </li>
                                @if($contract->info->sponsorship_transfer_payment_receipt_url)
                                    <li>
                                        <span class="label">إيصال دفع نقل الكفالة</span>
                                        <span class="value">
                                            <a href="{{ $contract->info->sponsorship_transfer_payment_receipt_url }}" target="_blank">عرض الإيصال</a>
                                        </span>
                                    </li>
                                @endif
                            @endif
                            <li>
                                <span class="label">الراتب بعد نقل الكفالة</span>
                                <span class="value">{{ $contract->info?->salary_after_sponsorship ? number_format($contract->info->salary_after_sponsorship, 2) . ' ريال' : 'غير محدد' }}</span>
                            </li>
                            <li>
                                <span class="label">حالة الضمان</span>
                                <span class="value">{{ $contract->warranty_status ?? 'غير محدد' }}</span>
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

                <div class="col-xl-6">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-file-invoice-dollar"></i>
                                المستندات المالية
                            </h3>
                        </div>

                        @if ($contract->financeInvoice)
                            <ul class="detail-list">
                                <li>
                                    <span class="label">رقم الفاتورة</span>
                                    <span class="value highlight">#{{ $contract->financeInvoice->id }}</span>
                                </li>
                                <li>
                                    <span class="label">إجمالي الفاتورة</span>
                                    <span class="value">{{ number_format((float) $contract->financeInvoice->total_due, 2) }} ريال</span>
                                </li>
                                <li>
                                    <span class="label">المبلغ المسدد</span>
                                    <span class="value">{{ number_format((float) $contract->financeInvoice->paid_amount, 2) }} ريال</span>
                                </li>
                                <li>
                                    <span class="label">المتبقي</span>
                                    <span class="value">{{ number_format((float) $contract->financeInvoice->remaining_amount, 2) }} ريال</span>
                                </li>
                                <li>
                                    <span class="label">حساب القبض</span>
                                    <span class="value">{{ $contract->financeInvoice->receiptAccount?->name ?? 'غير محدد' }}</span>
                                </li>
                            </ul>

                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a href="{{ route('rental.invoice.download', ['id' => $contract->financeInvoice->id, 'autoprint' => 1]) }}"
                                    target="_blank" class="btn btn-primary">
                                    <i class="fas fa-file-invoice me-1"></i>
                                    طباعة الفاتورة
                                </a>

                                @if ($contract->financeInvoice->receipt_bond_id)
                                    <a href="{{ route('finance.bonds-print.show', ['bonds_print' => $contract->financeInvoice->receipt_bond_id, 'type' => 'qabd']) }}"
                                        target="_blank" class="btn btn-outline-primary">
                                        <i class="fas fa-receipt me-1"></i>
                                        طباعة سند القبض
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
                    </div>
                </div>

                <div class="col-12">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-paperclip"></i>
                                مرفقات العقد
                            </h3>
                            @if (!empty($contractAttachments))
                                <a href="{{ route('RentContract.attachments.download', $contract->id) }}"
                                    class="btn btn-sm btn-primary no-print">
                                    <i class="fas fa-file-zipper me-1"></i>
                                    تحميل الكل
                                </a>
                            @endif
                        </div>

                        @if (!empty($contractAttachments))
                            <form method="GET" action="{{ route('RentContract.attachments.download', $contract->id) }}" class="no-print">
                                <input type="hidden" name="selected_only" value="1">
                                <div class="attachment-list mb-3">
                                    @foreach ($contractAttachments as $attachment)
                                        <label class="attachment-item">
                                            <input type="checkbox" name="attachments[]" value="{{ $attachment['key'] }}" checked>
                                            <span class="fw-bold">{{ $attachment['label'] }}</span>
                                            <span class="d-flex flex-wrap gap-2 mt-2">
                                                <a href="{{ $attachment['url'] }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-eye me-1"></i>
                                                    عرض
                                                </a>
                                                <a href="{{ $attachment['download_url'] }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download me-1"></i>
                                                    تحميل منفرد
                                                </a>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-download me-1"></i>
                                    تحميل المحدد
                                </button>
                            </form>
                        @else
                            <div class="empty-state">
                                لا توجد مرفقات مسجلة على هذا العقد حتى الآن.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title">
                                <i class="fas fa-route"></i>
                                مراحل العقد
                            </h3>
                        </div>
                        <p class="section-subtitle">تم إلغاء مرحلة إرسال العقد. أول مرحلة تشغيلية الآن هي انتظار توقيع العميل.</p>

                        <div class="workflow-grid">
                            @foreach ($workflowSteps as $step)
                                <div class="workflow-step {{ $step['state'] }}">
                                    <span class="step-icon">
                                        <i class="{{ $step['icon'] }}"></i>
                                    </span>
                                    <h4>{{ $step['title'] }}</h4>
                                    <p>{{ $step['description'] }}</p>
                                    @if (!empty($step['date']))
                                        <span class="step-date">
                                            <i class="fas fa-clock"></i>
                                            {{ $step['date'] }}
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
                                <span class="label">أنشئ في</span>
                                <span class="value">{{ optional($contract->created_at)->format('Y-m-d H:i') ?? 'غير محدد' }}</span>
                            </li>
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
                                    <select name="new_maid_id" id="new_maid_id" class="form-select" required>
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
                <a href="{{ route('RentContract.attachments.download', $contract->id) }}" class="btn-icon btn-back" title="تحميل كل المرفقات">
                    <i class="fas fa-paperclip"></i>
                </a>
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
            $(document).on('click', '.btn-complete', function() {
                Swal.fire({
                    title: 'تأكيد إنهاء العقد',
                    text: 'هل أنت متأكد من إنهاء هذا العقد؟',
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
                                _token: '{{ csrf_token() }}'
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
                form.find('[data-field-error]').addClass('d-none').text('');
                $('#submitChangeMaidBtn').prop('disabled', {{ $availableReplacementMaids->isEmpty() ? 'true' : 'false' }});
            });
        });
    </script>
@endsection
