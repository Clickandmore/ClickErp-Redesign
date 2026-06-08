@extends('admin.layout.indexs.index')

@section('title', 'تفاصيل فترة التجربة - طلب نقل كفالة #' . $transfer->id)

@section('page-title')
تفاصيل فترة التجربة
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('SponsorshipTransfer.index') }}">طلبات نقل الكفالة</a></li>
                        <li class="breadcrumb-item active">تفاصيل فترة التجربة</li>
                    </ol>
                </div>
                <h4 class="page-title">تفاصيل فترة التجربة</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock"></i>
                        تفاصيل فترة التجربة - طلب رقم #{{ $transfer->id }}
                    </h5>
                </div>
                <div class="card-body">
                    <!-- معلومات أساسية -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-card">
                                <h6 class="text-primary"><i class="fas fa-user"></i> معلومات العاملة</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>الاسم:</strong></td>
                                        <td>{{ $transfer->contract->maid->name ?? 'غير محدد' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>الجنسية:</strong></td>
                                        <td>{{ $transfer->contract->maid->nationality->name ?? 'غير محدد' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>رقم جواز السفر:</strong></td>
                                        <td>{{ $transfer->contract->maid->passport_number ?? 'غير محدد' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>المهنة:</strong></td>
                                        <td>{{ $transfer->contract->maid->job->name ?? 'عاملة منزلية' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-card">
                                <h6 class="text-success"><i class="fas fa-user-tie"></i> معلومات العميل الجديد</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>الاسم:</strong></td>
                                        <td>{{ $transfer->newSponsor->name ?? 'غير محدد' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ $transfer->newSponsor?->documentNumberLabel() ?? 'رقم الهوية' }}:</strong></td>
                                        <td>{{ $transfer->newSponsor?->documentNumber() ?? 'غير محدد' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>الهاتف:</strong></td>
                                        <td>{{ $transfer->newSponsor->phone ?? 'غير محدد' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>العنوان:</strong></td>
                                        <td>{{ $transfer->newSponsor->address ?? 'غير محدد' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- تفاصيل فترة التجربة -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h5 class="alert-heading"><i class="fas fa-info-circle"></i> تفاصيل فترة التجربة</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>مدة التجربة:</strong><br>
                                        <span class="badge bg-primary fs-6">{{ $transfer->trial_days }} يوماً</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>تاريخ بدء التجربة:</strong><br>
                                        <span class="text-success">{{ $transfer->trial_start_date ? $transfer->trial_start_date->format('Y-m-d') : 'سيتم تحديده عند البدء' }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>تاريخ انتهاء التجربة:</strong><br>
                                        <span class="text-warning">
                                            @if($transfer->trial_start_date)
                                                {{ $transfer->trial_start_date->addDays($transfer->trial_days)->format('Y-m-d') }}
                                            @else
                                                سيتم تحديده عند البدء
                                            @endif
                                        </span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>قيمة العقد:</strong><br>
                                        <span class="text-primary fw-bold">{{ number_format($transfer->contract_value, 2) }} ريال</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- شروط فترة التجربة -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> شروط وأحكام فترة التجربة</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> تبدأ فترة التجربة من تاريخ تسليم العاملة للعميل الجديد</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> مدة التجربة {{ $transfer->trial_days }} يوماً كاملة</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> يحق للعميل إرجاع العاملة خلال فترة التجربة دون إبداء أسباب</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> في حالة الإرجاع، تعود العاملة إلى الإيواء وتُرد المبالغ المدفوعة</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> في حالة النجاح، يتم تأكيد نقل الكفالة نهائياً</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> يجب على العميل التعامل مع العاملة وفقاً لأحكام الشريعة الإسلامية والأنظمة السعودية</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- معلومات الحالة الحالية -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-primary">
                                <h6><i class="fas fa-info-circle"></i> الحالة الحالية للطلب</h6>
                                <p class="mb-2"><strong>الحالة:</strong>
                                    <span class="badge bg-info">{{ $transfer->getStatusTextAttribute() }}</span>
                                </p>
                                <p class="mb-0"><strong>ملاحظة:</strong>
                                    @if($transfer->status === 'trial_started')
                                        تم تفعيل فترة التجربة بالفعل لهذا الطلب.
                                    @elseif($transfer->status !== 'maid_delivered')
                                        يجب إكمال الخطوات التالية قبل بدء التجربة:
                                        <ul class="mt-2 mb-0">
                                            @if($transfer->status === 'waiting_contract_send')
                                                <li>تسجيل استلام العاملة</li>
                                            @elseif($transfer->status === 'contract_sent')
                                                <li>تسجيل استلام العاملة</li>
                                            @elseif($transfer->status === 'contract_signed')
                                                <li>تسجيل استلام العاملة</li>
                                            @endif
                                        </ul>
                                    @else
                                        جميع الخطوات مكتملة. يمكن الآن بدء فترة التجربة.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- الإجراءات -->
                    <div class="row">
                        <div class="col-12">
                            <div class="text-center">
                                @if($transfer->status === 'maid_delivered')
                                    <button type="button" class="btn btn-success btn-lg me-2" onclick="confirmStartTrial()">
                                        <i class="fas fa-play"></i> تأكيد بدء فترة التجربة
                                    </button>
                                @elseif($transfer->status === 'trial_started')
                                    <a href="{{ route('SponsorshipTransfer.trial') }}" class="btn btn-success btn-lg me-2">
                                        <i class="fas fa-check-circle"></i> التجربة مفعلة بالفعل
                                    </a>
                                @else
                                    <button type="button" class="btn btn-warning btn-lg me-2" disabled title="يجب إكمال الخطوات السابقة أولاً">
                                        <i class="fas fa-exclamation-triangle"></i> لا يمكن بدء التجربة حالياً
                                    </button>
                                    <a href="{{ route('SponsorshipTransfer.index') }}" class="btn btn-primary btn-lg me-2">
                                        <i class="fas fa-arrow-left"></i> العودة لإكمال الخطوات
                                    </a>
                                @endif
                                <a href="{{ route('SponsorshipTransfer.index') }}" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-list"></i> العودة للقائمة
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmStartTrial() {
        Swal.fire({
            title: 'تأكيد بدء فترة التجربة',
            html: `
                <div class="text-start">
                    <p><strong>هل أنت متأكد من بدء فترة التجربة؟</strong></p>
                    <div class="alert alert-info">
                        <ul class="mb-0">
                            <li>ستبدأ فترة التجربة اعتباراً من اليوم</li>
                            <li>المدة: {{ $transfer->trial_days }} يوماً</li>
                            <li>تاريخ الانتهاء: ${new Date(Date.now() + {{ $transfer->trial_days }} * 24 * 60 * 60 * 1000).toLocaleDateString('ar-SA')}</li>
                        </ul>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، ابدأ التجربة',
            cancelButtonText: 'إلغاء',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch(`{{ route('SponsorshipTransfer.start-trial', $transfer->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        // محاولة قراءة رسالة الخطأ من الاستجابة
                        return response.json().then(errorData => {
                            throw new Error(errorData.message || 'حدث خطأ في الشبكة');
                        }).catch(() => {
                            throw new Error(`خطأ HTTP ${response.status}: ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'حدث خطأ غير معروف');
                    }
                    return data;
                })
                .catch(error => {
                    console.error('Error in startTrial:', error);
                    Swal.showValidationMessage(`${error.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'تم بنجاح!',
                    text: 'تم بدء فترة التجربة بنجاح',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '{{ route("SponsorshipTransfer.index") }}';
                });
            }
        });
    }
</script>
@endsection

@section('css')
<style>
    .info-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        margin-bottom: 20px;
    }

    .info-card h6 {
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #dee2e6;
    }

    .alert-info {
        border-left: 4px solid #17a2b8;
    }

    .card.border-warning {
        border-left: 4px solid #ffc107 !important;
    }
</style>
@endsection
