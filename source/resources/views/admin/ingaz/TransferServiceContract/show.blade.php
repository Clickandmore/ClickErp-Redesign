@extends('admin.layout.indexs.index')

@section('title')
    عرض عقد نقل الخدمات #{{ $transferServiceContract->contract_number }}
@endsection

@section('style')
    <style>
        .contract-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #dee2e6;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            flex: 0 0 40%;
        }

        .info-value {
            color: #212529;
            flex: 1;
            text-align: left;
        }

        .status-timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline-item {
            position: relative;
            padding-left: 50px;
            margin-bottom: 20px;
        }

        .timeline-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        .timeline-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }

        .timeline-date {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .timeline-title {
            font-weight: 600;
            color: #212529;
            margin-bottom: 5px;
        }

        .timeline-description {
            color: #495057;
            font-size: 0.9rem;
        }

        .action-buttons {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">عرض عقد نقل الخدمات</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('TransferServiceContract.index') }}">عقود نقل الخدمات</a></li>
                        <li class="breadcrumb-item active">عرض العقد</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- رأس العقد -->
    <div class="contract-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-2">عقد نقل الخدمات #{{ $transferServiceContract->contract_number }}</h3>
                <p class="mb-0">تاريخ الإنشاء: {{ $transferServiceContract->created_at ? $transferServiceContract->created_at->format('Y-m-d H:i') : 'غير محدد' }}</p>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-{{ $transferServiceContract->status === 'active' ? 'success' : ($transferServiceContract->status === 'cancelled' ? 'danger' : 'primary') }} fs-6 p-2">
                    {{ $transferServiceContract->status_text }}
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- معلومات العقد الأساسية -->
        <div class="col-md-6">
            <div class="info-card">
                <h5 class="mb-3"><i class="fas fa-info-circle text-primary"></i> معلومات العقد</h5>
                <div class="info-item">
                    <span class="info-label">رقم العقد:</span>
                    <span class="info-value">{{ $transferServiceContract->contract_number }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">طلب النقل:</span>
                    <span class="info-value">
                        <a href="{{ route('SponsorshipTransfer.show', ['SponsorshipTransfer' => $transferServiceContract->sponsorship_transfer_id]) }}" class="text-primary">
                            #{{ $transferServiceContract->sponsorship_transfer_id }}
                        </a>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">العقد الأصلي:</span>
                    <span class="info-value">#{{ $transferServiceContract->contract_id }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">تاريخ بداية العقد:</span>
                    <span class="info-value">{{ $transferServiceContract->contract_start_date ? $transferServiceContract->contract_start_date->format('Y-m-d') : 'غير محدد' }}</span>
                </div>
                @if($transferServiceContract->contract_end_date)
                <div class="info-item">
                    <span class="info-label">تاريخ انتهاء العقد:</span>
                    <span class="info-value">{{ $transferServiceContract->contract_end_date ? $transferServiceContract->contract_end_date->format('Y-m-d') : 'غير محدد' }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- معلومات الأطراف -->
        <div class="col-md-6">
            <div class="info-card">
                <h5 class="mb-3"><i class="fas fa-users text-success"></i> أطراف العقد</h5>
                <div class="info-item">
                    <span class="info-label">الكفيل القديم:</span>
                    <span class="info-value">{{ $transferServiceContract->oldSponsor->name ?? 'غير محدد' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">الكفيل الجديد:</span>
                    <span class="info-value">{{ $transferServiceContract->newSponsor->name ?? 'غير محدد' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">العاملة:</span>
                    <span class="info-value">{{ $transferServiceContract->maid->name ?? 'غير محدد' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">الجنسية:</span>
                    <span class="info-value">{{ $transferServiceContract->maid->nationality->translations->first()->sub_title ?? 'غير محدد' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">رقم الجواز:</span>
                    <span class="info-value">{{ $transferServiceContract->maid->passport_num ?? 'غير محدد' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- البيانات المالية -->
        <div class="col-md-6">
            <div class="info-card">
                <h5 class="mb-3"><i class="fas fa-money-bill-wave text-warning"></i> البيانات المالية</h5>
                <div class="info-item">
                    <span class="info-label">قيمة العقد:</span>
                    <span class="info-value">{{ number_format($transferServiceContract->contract_value, 2) }} ريال</span>
                </div>
                <div class="info-item">
                    <span class="info-label">المبلغ المدفوع:</span>
                    <span class="info-value text-success">{{ number_format($transferServiceContract->paid_amount, 2) }} ريال</span>
                </div>
                <div class="info-item">
                    <span class="info-label">المبلغ المتبقي:</span>
                    <span class="info-value text-danger">{{ number_format($transferServiceContract->remaining_amount, 2) }} ريال</span>
                </div>
            </div>
        </div>

        <!-- بيانات التجربة -->
        <div class="col-md-6">
            <div class="info-card">
                <h5 class="mb-3"><i class="fas fa-calendar-alt text-info"></i> بيانات فترة التجربة</h5>
                <div class="info-item">
                    <span class="info-label">عدد أيام التجربة:</span>
                    <span class="info-value">{{ $transferServiceContract->trial_days }} يوم</span>
                </div>
                <div class="info-item">
                    <span class="info-label">تاريخ بداية التجربة:</span>
                    <span class="info-value">{{ $transferServiceContract->trial_start_date ? $transferServiceContract->trial_start_date->format('Y-m-d') : 'غير محدد' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">تاريخ انتهاء التجربة:</span>
                    <span class="info-value">
                        {{ $transferServiceContract->trial_start_date ? $transferServiceContract->trial_start_date->addDays($transferServiceContract->trial_days)->format('Y-m-d') : 'غير محدد' }}
                    </span>
                </div>
                @if($transferServiceContract->contract_duration)
                <div class="info-item">
                    <span class="info-label">مدة العقد:</span>
                    <span class="info-value">{{ $transferServiceContract->contract_duration }} يوم</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- الشروط والملاحظات -->
    @if($transferServiceContract->special_conditions || $transferServiceContract->notes)
    <div class="row">
        <div class="col-12">
            <div class="info-card">
                <h5 class="mb-3"><i class="fas fa-clipboard-list text-secondary"></i> الشروط والملاحظات</h5>

                @if($transferServiceContract->special_conditions)
                <div class="mb-3">
                    <h6 class="text-primary">الشروط الخاصة:</h6>
                    <p class="mb-0">{{ $transferServiceContract->special_conditions }}</p>
                </div>
                @endif

                @if($transferServiceContract->notes)
                <div class="mb-3">
                    <h6 class="text-primary">ملاحظات إضافية:</h6>
                    <p class="mb-0">{{ $transferServiceContract->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- الخط الزمني للعقد -->
    <div class="row">
        <div class="col-12">
            <div class="info-card">
                <h5 class="mb-4"><i class="fas fa-history text-primary"></i> الخط الزمني للعقد</h5>

                <div class="status-timeline">
                    <!-- إنشاء العقد -->
                    <div class="timeline-item">
                        <div class="timeline-icon bg-primary">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-date">{{ $transferServiceContract->created_at ? $transferServiceContract->created_at->format('Y-m-d H:i') : 'غير محدد' }}</div>
                            <div class="timeline-title">تم إنشاء العقد</div>
                            <div class="timeline-description">
                                تم إنشاء العقد بواسطة: {{ $transferServiceContract->createdBy->name ?? 'غير محدد' }}
                            </div>
                        </div>
                    </div>

                    <!-- إرسال العقد -->
                    @if($transferServiceContract->sent_at)
                    <div class="timeline-item">
                        <div class="timeline-icon bg-info">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-date">{{ $transferServiceContract->sent_at ? $transferServiceContract->sent_at->format('Y-m-d H:i') : 'غير محدد' }}</div>
                            <div class="timeline-title">تم إرسال العقد</div>
                            <div class="timeline-description">
                                تم الإرسال بواسطة: {{ $transferServiceContract->sentBy->name ?? 'غير محدد' }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- توقيع العقد -->
                    @if($transferServiceContract->signed_at)
                    <div class="timeline-item">
                        <div class="timeline-icon bg-success">
                            <i class="fas fa-signature"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-date">{{ $transferServiceContract->signed_at ? $transferServiceContract->signed_at->format('Y-m-d H:i') : 'غير محدد' }}</div>
                            <div class="timeline-title">تم توقيع العقد</div>
                            <div class="timeline-description">
                                تم التسجيل بواسطة: {{ $transferServiceContract->signedBy->name ?? 'غير محدد' }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- تفعيل العقد -->
                    @if($transferServiceContract->activated_at)
                    <div class="timeline-item">
                        <div class="timeline-icon bg-warning">
                            <i class="fas fa-play"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-date">{{ $transferServiceContract->activated_at->format('Y-m-d H:i') }}</div>
                            <div class="timeline-title">تم تفعيل العقد</div>
                            <div class="timeline-description">
                                تم التفعيل بواسطة: {{ $transferServiceContract->activatedBy->name ?? 'غير محدد' }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- إكمال العقد -->
                    @if($transferServiceContract->completed_at)
                    <div class="timeline-item">
                        <div class="timeline-icon bg-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-date">{{ $transferServiceContract->completed_at->format('Y-m-d H:i') }}</div>
                            <div class="timeline-title">تم إكمال العقد</div>
                            <div class="timeline-description">
                                تم الإكمال بواسطة: {{ $transferServiceContract->completedBy->name ?? 'غير محدد' }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- إلغاء العقد -->
                    @if($transferServiceContract->cancelled_at)
                    <div class="timeline-item">
                        <div class="timeline-icon bg-danger">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-date">{{ $transferServiceContract->cancelled_at->format('Y-m-d H:i') }}</div>
                            <div class="timeline-title">تم إلغاء العقد</div>
                            <div class="timeline-description">
                                تم الإلغاء بواسطة: {{ $transferServiceContract->cancelledBy->name ?? 'غير محدد' }}<br>
                                @if($transferServiceContract->cancellation_reason)
                                    <strong>السبب:</strong> {{ $transferServiceContract->cancellation_reason }}
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- أزرار العمليات -->
    <div class="action-buttons">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="{{ route('TransferServiceContract.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> العودة للقائمة
                </a>

                @if($transferServiceContract->status === 'draft')
                <a href="{{ route('TransferServiceContract.edit', $transferServiceContract->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> تحرير العقد
                </a>
                @endif
            </div>

            <div>
                <a href="{{ route('print.transfer-service-contract', $transferServiceContract->id) }}" target="_blank" class="btn btn-success">
                    <i class="fas fa-print"></i> طباعة العقد
                </a>

                @if($transferServiceContract->canBeCancelled())
                <button type="button" class="btn btn-danger" onclick="cancelContract({{ $transferServiceContract->id }})">
                    <i class="fas fa-ban"></i> إلغاء العقد
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
function cancelContract(contractId) {
    Swal.fire({
        title: 'تأكيد الإلغاء',
        text: 'هل أنت متأكد من إلغاء هذا العقد؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، ألغ العقد',
        cancelButtonText: 'إلغاء',
        input: 'textarea',
        inputPlaceholder: 'أدخل سبب الإلغاء...',
        inputValidator: (value) => {
            if (!value) {
                return 'يجب إدخال سبب الإلغاء!'
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/admin/TransferServiceContract/${contractId}`,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                    cancellation_reason: result.value
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'تم الإلغاء!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'حسناً'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('خطأ!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('خطأ!', 'حدث خطأ أثناء إلغاء العقد', 'error');
                }
            });
        }
    });
}
</script>
@endsection
