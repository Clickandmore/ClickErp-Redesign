@extends('admin.layout.indexs.index')

@section("style")
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/custom/status-badges.css">
    <style>
        .details-container {
            background: #f4f6fb;
            padding: 40px 20px 60px;
        }

        .details-content {
            max-width: 1200px;
            margin: 0 auto;
            direction: rtl;
        }

        .hero-card {
            background: linear-gradient(135deg, #0f172a, #1e40af);
            color: #fff;
            border-radius: 24px;
            padding: 32px;
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 25px 45px rgba(15, 23, 42, 0.35);
        }

        .hero-label {
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.8rem;
        }

        .hero-card h2 {
            margin: 10px 0 6px;
            font-size: 2rem;
            line-height: 1.2;
        }

        .hero-description {
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 16px;
        }

        .hero-meta {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            font-size: 0.95rem;
        }

        .meta-title {
            display: block;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 4px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 12px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.85rem;
            border: 1px solid transparent;
        }

        .status-pill--success {
            background: rgba(16, 185, 129, 0.18);
            border-color: rgba(16, 185, 129, 0.5);
            color: #4ade80;
            text-align: center;
        }

        .status-pill--danger {
            background: rgba(220, 38, 38, 0.18);
            border-color: rgba(220, 38, 38, 0.5);
            color: #f87171;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            background: transparent;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-action i {
            font-size: 1rem;
        }

        .btn-action:hover {
            border-color: rgba(255, 255, 255, 0.8);
            transform: translateY(-2px);
        }

        .btn-action--ghost {
            background: rgba(255, 255, 255, 0.15);
        }

        .info-grid {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .info-card {
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #1e293b;
            font-size: 1rem;
        }

        .section-title i {
            color: #2563eb;
            font-size: 1.2rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #f1f5f9;
            padding: 10px 0;
            font-size: 0.95rem;

            overflow-x: auto;
        }

        .info-row:last-of-type {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #475569;
            min-width: 140px;
        }

        .info-value {
            color: #0f172a;
            text-align: right;
        }

        .highlight {
            color: #1d4ed8;
            font-size: 1.1rem;
        }

        .info-empty {
            background: #fff8f4;
            border: 1px dashed #fb923c;
            padding: 12px;
            border-radius: 10px;
            color: #c2410c;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .operations-panel {
            margin-top: 35px;
            background: #fff;
            border-radius: 22px;
            padding: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 15px 30px rgba(15, 23, 42, 0.08);
        }

        .action-grid {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .action-card {
            border-radius: 16px;
            padding: 18px;
            border: 1px solid #dbeafe;
            background: #f8fafc;
            text-align: center;
            color: #0f172a;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .action-card i {
            font-size: 1.5rem;
        }

        .action-card:hover {
            border-color: #7c3aed;
            box-shadow: 0 15px 25px rgba(15, 23, 42, 0.16);
            transform: translateY(-3px);
        }

        .action-card--edit {
            border-color: #60a5fa;
        }

        .action-card--transfer {
            border-color: #22c55e;
        }

        .action-card--warning {
            border-color: #fb923c;
            background: #fff7ed;
            color: #b45309;
        }

        .action-card--danger {
            border-color: #ef4444;
            background: #fff1f2;
            color: #b91c1c;
        }

        .action-card.disabled {
            cursor: not-allowed;
            opacity: 0.7;
            box-shadow: none;
        }

        @media (max-width: 768px) {
            .hero-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .hero-actions {
                justify-content: flex-start;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endsection

@section('title')
    عرض تفاصيل عقد الملجأ
@endsection

@php
    $canEditShelterContract = @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_EDIT, \App\PermKeys::SHELTER_CONTRACT_CONTROL]);
    $canViewDeportationProcedures = @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_DEPORTATION_PROCEDURES_VIEW, \App\PermKeys::SHELTER_CONTRACT_DEPORTATION_STAGE_VIEW, \App\PermKeys::SHELTER_CONTRACT_DEPORTED_VIEW, \App\PermKeys::SHELTER_CONTRACT_VIEW, \App\PermKeys::SHELTER_CONTRACT_CONTROL]);
    $canManageDeportationProcedures = @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_DEPORTATION_PROCEDURES_EDIT, \App\PermKeys::SHELTER_CONTRACT_EDIT, \App\PermKeys::SHELTER_CONTRACT_CONTROL]);
    $canRemoveFromShelter = @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_REMOVE_FROM_SHELTER_EDIT, \App\PermKeys::SHELTER_CONTRACT_EDIT, \App\PermKeys::SHELTER_CONTRACT_CONTROL]);
    $canCreateTransferRequest = @AdminGuardHasAnyPerm([\App\PermKeys::SPONSOR_TRANSFER_CREATE, \App\PermKeys::SPONSOR_TRANSFER_CONTROL]);
    $backRoute = route('dashboard.index');

    if ($contract->deportationProcedure && $contract->deportationProcedure->status === 'completed' && @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_DEPORTED_VIEW, \App\PermKeys::SHELTER_CONTRACT_VIEW, \App\PermKeys::SHELTER_CONTRACT_CONTROL])) {
        $backRoute = route('ShelterContract.deported');
    } elseif (optional($contract->info)->wants_to_work === 'no' && @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_DEPORTATION_STAGE_VIEW, \App\PermKeys::SHELTER_CONTRACT_VIEW, \App\PermKeys::SHELTER_CONTRACT_CONTROL])) {
        $backRoute = route('ShelterContract.deportationStage');
    } elseif (optional($contract->info)->wants_to_work === 'yes' && @AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_AVAILABLE_FOR_TRANSFER_VIEW, \App\PermKeys::SHELTER_CONTRACT_VIEW, \App\PermKeys::SHELTER_CONTRACT_CONTROL])) {
        $backRoute = route('ShelterContract.availableForTransfer');
    } elseif (@AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_INSIDE_VIEW, \App\PermKeys::SHELTER_CONTRACT_VIEW, \App\PermKeys::SHELTER_CONTRACT_CONTROL])) {
        $backRoute = route('ShelterContract.index');
    }
@endphp

@section('content')
<div class="details-container">
    <div class="details-content">
        <div class="hero-card">
            <div>
                <p class="hero-label">تفاصيل عقد الملجأ</p>
                <h2>عقد رقم #{{ $contract->id }}</h2>
                <p class="hero-description">
                    @if($contract->user)
                        العميل: {{ $contract->user->name }}
                    @else
                        العميل غير موجود ضمن السجلات.
                    @endif
                </p>
                <div class="hero-meta">
                    <div>
                        <span class="meta-title">حالة الضمان</span>
                        <span class="status-pill {{ $warrantyStatus == '���.�� ���"���.����' ? 'status-pill--success' : 'status-pill--danger' }}">
                            {{ $warrantyStatus }}
                            @if($warrantyDays > 0)
                                ({{ $warrantyDays }} يوم)
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="meta-title">تاريخ الإنشاء</span>
                        <span>{{ $contract->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div>
                        <span class="meta-title">آخر تحديث</span>
                        <span>{{ $contract->updated_at->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            </div>
            <div class="hero-actions">
                <a href="{{ $backRoute }}" class="btn-action">
                    <i class="fas fa-arrow-right"></i>
                    <span>عودة</span>
                </a>
                @if ($canEditShelterContract)
                    <a href="{{ route('ShelterContract.edit', $contract->id) }}" class="btn-action">
                        <i class="fas fa-edit"></i>
                        <span>تعديل</span>
                    </a>
                @endif
                <button type="button" onclick="window.print()" class="btn-action btn-action--ghost">
                    <i class="fas fa-print"></i>
                    <span>طباعة</span>
                </button>
            </div>
        </div>

        @if($contract->hasEscapeReport())
            <div class="alert alert-danger mt-4">
                <div class="fw-bold mb-1"><i class="fas fa-running me-1"></i> يوجد بلاغ هروب على العاملة</div>
                <div>تم إخراجها من الإيواء، ولا يمكن بدء نقل الكفالة أو إجراءات الترحيل لهذا العقد.</div>
                @if($contract->info?->escape_report_note)
                    <div class="mt-2"><strong>الملاحظة:</strong> {{ $contract->info->escape_report_note }}</div>
                @endif
                @if(is_array($contract->info?->escape_report_attachment) && !empty($contract->info->escape_report_attachment['path']))
                    <a class="btn btn-sm btn-outline-danger mt-2"
                        href="{{ route('ShelterContract.escapeReport.attachment', $contract->id) }}">
                        <i class="fas fa-paperclip me-1"></i>
                        {{ $contract->info->escape_report_attachment['name'] ?? 'تحميل مرفق البلاغ' }}
                    </a>
                @endif
            </div>
        @endif

        <div class="info-grid">
            <section class="info-card contract-info">
                <div class="section-title">
                    <i class="fas fa-file-contract"></i>معلومات العقد
                </div>
                <div class="info-row">
                    <span class="info-label">رقم العقد</span>
                    <span class="info-value highlight">#{{ $contract->id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">حالة الضمان</span>
                    <span class="info-value">
                        <span class="status-pill {{ $warrantyStatus == 'ضمن الضمان' ? 'status-pill--success' : 'status-pill--danger' }}">
                            {{ $warrantyStatus }}
                            @if($warrantyDays > 0)
                                ({{ $warrantyDays }} يوم)
                            @endif
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">تاريخ الإنشاء</span>
                    <span class="info-value">{{ $contract->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">آخر تحديث</span>
                    <span class="info-value">{{ $contract->updated_at->format('Y-m-d H:i') }}</span>
                </div>
            </section>

            <section class="info-card client-info">
                <div class="section-title">
                    <i class="fas fa-user"></i>بيانات العميل
                </div>
                @if($contract->user)
                    <div class="info-row">
                        <span class="info-label">الاسم</span>
                        <span class="info-value">{{ $contract->user->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم الهوية</span>
                        <span class="info-value">{{ $contract->user?->documentNumber() ?? 'غير متوفر' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم الهاتف</span>
                        <span class="info-value">{{ $contract->user->phone ?? 'غير متوفر' }}</span>
                    </div>
                    @if($contract->user->email)
                    <div class="info-row">
                        <span class="info-label">البريد الإلكتروني</span>
                        <span class="info-value">{{ $contract->user->email }}</span>
                    </div>
                    @endif
                    @if($contract->user->city)
                    <div class="info-row">
                        <span class="info-label">المدينة</span>
                        <span class="info-value">{{ $contract->user->city }}</span>
                    </div>
                    @endif
                @else
                    <div class="info-empty">
                        <i class="fas fa-exclamation-triangle"></i> العميل غير متوفر في النظام.
                    </div>
                @endif
            </section>

            <section class="info-card maid-info">
                <div class="section-title">
                    <i class="fas fa-female"></i>بيانات الخادمة
                </div>
                @if($contract->maid)
                    <div class="info-row">
                        <span class="info-label">الاسم</span>
                        <span class="info-value">{{ $contract->maid->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم جواز السفر</span>
                        <span class="info-value">{{ $contract->maid->passport_num ?? 'غير متوفر' }}</span>
                    </div>
                    @if($contract->maid->nationality)
                    <div class="info-row">
                        <span class="info-label">الجنسية</span>
                        <span class="info-value">{{ $contract->maid->nationality->title ?? 'غير متوفر' }}</span>
                    </div>
                    @endif
                    @if($contract->maid->religion)
                    <div class="info-row">
                        <span class="info-label">الديانة</span>
                        <span class="info-value">{{ $contract->maid->religion->title ?? 'غير متوفر' }}</span>
                    </div>
                    @endif
                    @if($contract->maid->age)
                    <div class="info-row">
                        <span class="info-label">العمر</span>
                        <span class="info-value">{{ $contract->maid->age }} عام</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">نوع الوظيفة</span>
                        <span class="info-value">
                            @if($contract->maid->job_types && $contract->maid->job_types->count() > 0)
                                @foreach($contract->maid->job_types as $jobType)
                                    <span class="badge bg-secondary me-1">
                                        {{ $jobType->job_type->title ?? 'غير متوفر' }}
                                    </span>
                                @endforeach
                            @else
                                غير متوفر
                            @endif
                        </span>
                    </div>
                @else
                    <div class="info-empty">
                        <i class="fas fa-exclamation-triangle"></i> لا يوجد خادمة مربوطة بالعقد.
                    </div>
                @endif
            </section>

            <section class="info-card shelter-info">
                <div class="section-title">
                    <i class="fas fa-building"></i>بيانات الملجأ
                </div>
                @if($contract->info)
                    <div class="info-row">
                        <span class="info-label">اسم الملجأ</span>
                        <span class="info-value">
                            @if($contract->info->shelter)
                                {{ $contract->info->shelter->title ?? 'غير متوفر' }}
                            @else
                                غير متوفر
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">تاريخ الوصول للمملكة</span>
                        <span class="info-value">
                            {{ $contract->info->arrival_date ?? $contract->info->visa_milad_date ?? $contract->info->contract_date ?? 'غير متوفر' }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">تاريخ إدخالها للإيواء</span>
                        <span class="info-value">
                            {{ $contract->info->maid_date_back ?? 'غير متوفر' }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">التصنيفات السريعة</span>
                        <span class="info-value">
                            @php
                                $quickTags = $contract->info->shelter_quick_tags ?? [];
                                if (is_string($quickTags)) {
                                    $quickTags = json_decode($quickTags, true) ?: [];
                                }
                            @endphp
                            @if(!empty($quickTags))
                                {{ implode('، ', $quickTags) }}
                            @else
                                بدون تصنيف
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">مصدر الاستلام</span>
                        <span class="info-value">
                            @if($contract->info->receipt_source)
                                {{ __('shelter_worker_statuses.received_by.' . $contract->info->receipt_source) ?: $contract->info->receipt_source }}
                            @else
                                غير متوفر
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رغبة العمل</span>
                        <span class="info-value">
                            @if($contract->info->wants_to_work == 'yes')
                                <span class="badge bg-success">تفضل البقاء</span>
                            @elseif($contract->info->wants_to_work == 'no')
                                <span class="badge bg-warning">لا ترغب بالعمل</span>
                            @else
                                <span class="badge bg-secondary">غير محدد</span>
                            @endif
                        </span>
                    </div>
                    @if($contract->info->notes)
                    <div class="info-row">
                        <span class="info-label">ملاحظات</span>
                        <span class="info-value">{{ $contract->info->notes }}</span>
                    </div>
                    @endif
                @else
                    <div class="info-empty">
                        <i class="fas fa-exclamation-triangle"></i> لم يتم إدخال بيانات الملجأ.
                    </div>
                @endif
            </section>
        </div>

        <div class="operations-panel">
            <div class="section-title">
                <i class="fas fa-cogs"></i>خيارات إضافية
            </div>
            <div class="action-grid">
                @if ($canEditShelterContract)
                    <a href="{{ route('ShelterContract.edit', $contract->id) }}" class="action-card action-card--edit">
                        <i class="fas fa-edit"></i>
                        <span>تعديل بيانات العقد</span>
                    </a>
                @endif

                @if(!$contract->hasEscapeReport() && $contract->info && $contract->info->wants_to_work == 'yes' && $canCreateTransferRequest)
                    <a href="{{ route('SponsorshipTransfer.create', ['contract_id' => $contract->id, 'workflow_source' => $isRentalWorker ? 'rental' : 'standard']) }}" class="action-card action-card--transfer">
                        <i class="fas fa-exchange-alt"></i>
                        <span>بدء نقل الكفالة</span>
                    </a>
                @elseif(!$contract->hasEscapeReport() && $contract->info && $contract->info->wants_to_work == 'no')
                    @php
                        $deportationProcedure = $contract->deportationProcedure;
                    @endphp
                    @if(!$deportationProcedure && $canManageDeportationProcedures)
                        <button type="button" onclick="startDeportation({{ $contract->id }})" class="action-card action-card--warning">
                            <i class="fas fa-plane-departure"></i>
                            <span>إجراءات الترحيل</span>
                        </button>
                    @elseif($deportationProcedure && $deportationProcedure->status !== 'completed' && $canViewDeportationProcedures)
                        <a href="{{ route('ShelterContract.deportation.procedures', $contract->id) }}" class="action-card action-card--warning">
                            <i class="fas fa-tasks"></i>
                            <span>متابعة الترحيل</span>
                        </a>
                    @elseif($deportationProcedure && $canViewDeportationProcedures)
                        <span class="action-card action-card--warning disabled">
                            <i class="fas fa-check-circle"></i>
                            <span>تم إنهاء الترحيل</span>
                        </span>
                    @endif
                @endif

                @if ($canRemoveFromShelter)
                    <button onclick="leaveForVacation({{ $contract->id }})" type="button" class="action-card action-card--edit">
                        <i class="fas fa-plane-departure"></i>
                        <span>خروج العاملة في إجازة</span>
                    </button>
                    <button onclick="confirmBack({{ $contract->id }})" type="button" class="action-card action-card--danger">
                        <i class="fas fa-undo"></i>
                        <span>إرجاع العقد</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function leaveForVacation(id) {
    Swal.fire({
        title: 'تأكيد الإجازة',
        text: 'هل تريد إخراج العاملة من الإيواء بسبب الإجازة؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، خروج إجازة',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (!result.isConfirmed) return;

        $.ajax({
            url: '{{ route("ShelterContract.vacation", ":id") }}'.replace(':id', id),
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                Swal.fire('تم!', response.message, 'success').then(() => {
                    window.location.href = response.redirect || '{{ route("ShelterContract.outside") }}';
                });
            },
            error: function(xhr) {
                Swal.fire('خطأ!', xhr.responseJSON?.message || 'تعذر إخراج العاملة في إجازة.', 'error');
            }
        });
    });
}

function confirmBack(id) {
    Swal.fire({
        title: 'تأكيد',
        text: 'هل أنت متأكد أنك تريد إعادة العقد إلى مرحلة الملجأ؟ هذه الخطوة لا يمكن التراجع عنها.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'نعم، أعده',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("ShelterContract.back", ":id") }}'.replace(':id', id),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('تم!', response.message || 'تم إعادة العقد بنجاح إلى مرحلة الملجأ.', 'success')
                    .then(() => {
                        window.location.href = '{{ route("RecruitmentContract.index") }}?status=completed';
                    });
                },
                error: function(xhr) {
                    Swal.fire('حدث خطأ!', xhr.responseJSON?.message || 'تعذر إعادة العقد، حاول مرة أخرى.', 'error');
                }
            });
        }
    });
}

function startDeportation(id) {
    Swal.fire({
        title: 'تأكيد',
        text: 'هل ترغب ببدء إجراءات الترحيل؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff9800',
        cancelButtonColor: '#d33',
        confirmButtonText: 'نعم، ابدأ الإجراء',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("ShelterContract.deportation.start", ":id") }}'.replace(':id', id),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('تم!', response.message || 'تم إرسال طلب الترحيل بنجاح.', 'success')
                    .then(() => {
                        window.location.href = '{{ route("ShelterContract.deportation.procedures", ":id") }}'.replace(':id', id);
                    });
                },
                error: function(xhr) {
                    Swal.fire('حدث خطأ!', xhr.responseJSON?.message || 'تعذر بدء الترحيل، حاول مرة أخرى.', 'error');
                }
            });
        }
    });
}
</script>
@endsection
