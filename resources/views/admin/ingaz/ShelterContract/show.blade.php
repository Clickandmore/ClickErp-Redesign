@extends('admin.layout.indexs.index')

@section("style")
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/custom/status-badges.css">
    <link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">
    <style>
        /* تنسيق قسم الإجراءات */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            background: #f8f9fa;
            cursor: pointer;
        }

        .action-item i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .action-item span {
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
        }

        /* ألوان الإجراءات */
        .action-item.edit {
            color: #007bff;
        }

        .action-item.edit:hover {
            background: #e7f1ff;
            border-color: #007bff;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        .action-item.transfer {
            color: #28a745;
        }

        .action-item.transfer:hover {
            background: #e6f4ea;
            border-color: #28a745;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .action-item.danger {
            color: #dc3545;
            background: #f8f9fa;
            border: none;
        }

        .action-item.danger:hover {
            background: #ffeef0;
            border-color: #dc3545;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        /* تحسين مظهر الأزرار */
        button.action-item {
            width: 100%;
            font-family: inherit;
        }

        /* للشاشات الصغيرة */
        @media (max-width: 768px) {
            .action-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .action-item {
                padding: 20px 15px;
            }

            .action-item i {
                font-size: 2rem;
            }
        }
    </style>
@endsection

@section('title')
    تفاصيل العقد داخل الإيواء
@endsection

@section('content')
<div class="details-container">
    <!-- Header مع معلومات أساسية -->
    <div class="details-header">
        <h3>
            <i class="fas fa-home me-2"></i>
            تفاصيل العقد رقم: {{ $contract->id }}
        </h3>
        <div class="status-info">
            <span class="badge bg-info">داخل الإيواء</span>
        </div>
    </div>

    <!-- أزرار الإجراءات -->
    <div class="action-buttons">
        <a href="{{ route('ShelterContract.index') }}" class="btn-icon btn-back" title="العودة">
            <i class="fas fa-arrow-right"></i>
        </a>
        <a href="{{ route('ShelterContract.edit', $contract->id) }}" class="btn-icon btn-edit" title="تحرير">
            <i class="fas fa-edit"></i>
        </a>
        <button onclick="window.print()" class="btn-icon btn-print" title="طباعة">
            <i class="fas fa-print"></i>
        </button>
    </div>

    <div class="row mt-4">
        <!-- معلومات العقد الأساسية -->
        <div class="col-lg-6 col-md-12">
            <div class="info-card contract-info">
                <div class="card-title">
                    <i class="fas fa-file-contract"></i>معلومات العقد
                </div>
                <div class="info-row">
                    <span class="info-label">رقم العقد:</span>
                    <span class="info-value highlight">#{{ $contract->id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">حالة الضمان:</span>
                    <span class="info-value">
                        <span class="details-badge {{ $warrantyStatus == 'ضمن الضمان' ? 'bg-success' : 'bg-danger' }}">
                            {{ $warrantyStatus }}
                            @if($warrantyDays > 0)
                                ({{ $warrantyDays }} يوم)
                            @endif
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">تاريخ الإنشاء:</span>
                    <span class="info-value">{{ $contract->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">آخر تحديث:</span>
                    <span class="info-value">{{ $contract->updated_at->format('Y-m-d H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- معلومات العميل -->
        <div class="col-lg-6 col-md-12">
            <div class="info-card client-info">
                <div class="card-title">
                    <i class="fas fa-user"></i>معلومات العميل
                </div>
                @if($contract->user)
                    <div class="info-row">
                        <span class="info-label">الاسم:</span>
                        <span class="info-value">{{ $contract->user->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم الهوية:</span>
                        <span class="info-value">{{ $contract->user->national_num ?? 'غير محدد' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم الجوال:</span>
                        <span class="info-value">{{ $contract->user->phone ?? 'غير محدد' }}</span>
                    </div>
                    @if($contract->user->email)
                    <div class="info-row">
                        <span class="info-label">البريد الإلكتروني:</span>
                        <span class="info-value">{{ $contract->user->email }}</span>
                    </div>
                    @endif
                    @if($contract->user->city)
                    <div class="info-row">
                        <span class="info-label">المدينة:</span>
                        <span class="info-value">{{ $contract->user->city }}</span>
                    </div>
                    @endif
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>معلومات العميل غير متاحة
                    </div>
                @endif
            </div>
        </div>

        <!-- معلومات العاملة -->
        <div class="col-lg-6 col-md-12">
            <div class="info-card maid-info">
                <div class="card-title">
                    <i class="fas fa-female"></i>معلومات العاملة
                </div>
                @if($contract->maid)
                    <div class="info-row">
                        <span class="info-label">الاسم:</span>
                        <span class="info-value">{{ $contract->maid->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم الجواز:</span>
                        <span class="info-value">{{ $contract->maid->passport_num ?? 'غير محدد' }}</span>
                    </div>
                    @if($contract->maid->nationality)
                    <div class="info-row">
                        <span class="info-label">الجنسية:</span>
                        <span class="info-value">{{ $contract->maid->nationality->title ?? 'غير محدد' }}</span>
                    </div>
                    @endif
                    @if($contract->maid->religion)
                    <div class="info-row">
                        <span class="info-label">الديانة:</span>
                        <span class="info-value">{{ $contract->maid->religion->title ?? 'غير محدد' }}</span>
                    </div>
                    @endif
                    @if($contract->maid->age)
                    <div class="info-row">
                        <span class="info-label">العمر:</span>
                        <span class="info-value">{{ $contract->maid->age }} سنة</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">المهنة:</span>
                        <span class="info-value">
                            @if($contract->maid->job_types && $contract->maid->job_types->count() > 0)
                                @foreach($contract->maid->job_types as $jobType)
                                    <span class="badge bg-secondary me-1">
                                        {{ $jobType->job_type->title ?? 'غير محدد' }}
                                    </span>
                                @endforeach
                            @else
                                غير محدد
                            @endif
                        </span>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>معلومات العاملة غير متاحة
                    </div>
                @endif
            </div>
        </div>

        <!-- معلومات الإيواء -->
        <div class="col-lg-6 col-md-12">
            <div class="info-card shelter-info">
                <div class="card-title">
                    <i class="fas fa-building"></i>معلومات الإيواء
                </div>
                @if($contract->info)
                    <div class="info-row">
                        <span class="info-label">مكان الإيواء:</span>
                        <span class="info-value">
                            @if($contract->info->shelter)
                                {{ $contract->info->shelter->title ?? 'غير محدد' }}
                            @else
                                غير محدد
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">تاريخ الوصول:</span>
                        <span class="info-value">
                            {{ $contract->info->visa_milad_date ?? $contract->info->contract_date ?? 'غير محدد' }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">تاريخ العودة:</span>
                        <span class="info-value">
                            {{ $contract->info->maid_date_back ?? 'غير محدد' }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">مصدر الاستلام:</span>
                        <span class="info-value">
                            @if($contract->info->receipt_source)
                                {{ __('shelter_worker_statuses.received_by.' . $contract->info->receipt_source) ?: $contract->info->receipt_source }}
                            @else
                                غير محدد
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رغبة العمل:</span>
                        <span class="info-value">
                            @if($contract->info->wants_to_work == 'yes')
                                <span class="badge bg-success">ترغب بالعمل</span>
                            @elseif($contract->info->wants_to_work == 'no')
                                <span class="badge bg-warning">لا ترغب بالعمل</span>
                            @else
                                <span class="badge bg-secondary">غير محدد</span>
                            @endif
                        </span>
                    </div>
                    @if($contract->info->notes)
                    <div class="info-row">
                        <span class="info-label">ملاحظات:</span>
                        <span class="info-value">{{ $contract->info->notes }}</span>
                    </div>
                    @endif
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>معلومات الإيواء غير متاحة
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- الإجراءات المتاحة -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="info-card">
                <div class="card-title">
                    <i class="fas fa-cogs"></i>الإجراءات المتاحة
                </div>
                <div class="action-grid">
                    <a href="{{ route('ShelterContract.edit', $contract->id) }}" class="action-item edit">
                        <i class="fas fa-edit"></i>
                        <span>تحرير البيانات</span>
                    </a>

                    @if($contract->info && $contract->info->wants_to_work == 'yes')
                    <a href="{{ route('SponsorshipTransfer.create', ['contract_id' => $contract->id]) }}" class="action-item transfer">
                        <i class="fas fa-exchange-alt"></i>
                        <span>إضافة طلب نقل كفالة</span>
                    </a>
                    @elseif($contract->info && $contract->info->wants_to_work == 'no')
                        @php
                            $deportationProcedure = $contract->deportationProcedure;
                        @endphp
                        @if(!$deportationProcedure)
                            <button onclick="startDeportation({{ $contract->id }})" class="action-item" style="background: #ff9800; color: white;">
                                <i class="fas fa-plane-departure"></i>
                                <span>بدء إجراءات الترحيل</span>
                            </button>
                        @elseif($deportationProcedure->status !== 'completed')
                            <a href="{{ route('ShelterContract.deportation.procedures', $contract->id) }}" class="action-item" style="background: #ff9800; color: white;">
                                <i class="fas fa-tasks"></i>
                                <span>متابعة إجراءات الترحيل</span>
                            </a>
                        @else
                            <span class="action-item disabled" style="background: #4caf50; color: white; cursor: not-allowed;">
                                <i class="fas fa-check-circle"></i>
                                <span>تمت إجراءات الترحيل</span>
                            </span>
                        @endif
                    @endif

                    <button onclick="confirmBack({{ $contract->id }})" class="action-item danger">
                        <i class="fas fa-undo"></i>
                        <span>حذف من الإيواء</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmBack(id) {
    Swal.fire({
        title: 'تأكيد',
        text: 'هل أنت متأكد من حذف العقد من الإيواء؟ سيعود العقد إلى العقود المكتملة',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'نعم، حذف من الإيواء',
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
                    Swal.fire('نجح!', response.message || 'تم حذف العقد من الإيواء بنجاح', 'success')
                    .then(() => {
                        window.location.href = '{{ route("RecruitmentContract.index") }}?status=completed';
                    });
                },
                error: function(xhr) {
                    Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء حذف العقد من الإيواء', 'error');
                }
            });
        }
    });
}

function startDeportation(id) {
    Swal.fire({
        title: 'تأكيد',
        text: 'هل أنت متأكد من بدء إجراءات الترحيل للعاملة؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff9800',
        cancelButtonColor: '#d33',
        confirmButtonText: 'نعم، بدء الإجراءات',
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
                    Swal.fire('نجح!', response.message || 'تم بدء إجراءات الترحيل بنجاح', 'success')
                    .then(() => {
                        window.location.href = '{{ route("ShelterContract.deportation.procedures", ":id") }}'.replace(':id', id);
                    });
                },
                error: function(xhr) {
                    Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء بدء إجراءات الترحيل', 'error');
                }
            });
        }
    });
}
</script>
@endsection
