@extends('admin.layout.indexs.index')

@section('title', 'تفاصيل فترة التجربة')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">تفاصيل فترة التجربة - عقد رقم {{ $contract->contract_number }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('RentContract.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-right"></i> العودة للقائمة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- معلومات العقد الأساسية -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-user"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">العميل</span>
                                    <span class="info-box-number">{{ $contract->user->name ?? 'غير محدد' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-female"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">العاملة</span>
                                    <span class="info-box-number">{{ $contract->maid->name ?? 'غير محدد' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الحالة الحالية -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h5><i class="icon fas fa-info-circle"></i> الحالة الحالية</h5>
                                <strong>{{ $contract->status_text }}</strong>
                                @if($contract->status === 'maid_delivered')
                                    <p class="mb-0 mt-2">العقد جاهز لبدء فترة التجربة. يمكنك الآن تأكيد بدء فترة التجربة.</p>
                                @elseif($contract->status === 'trial_started')
                                    <p class="mb-0 mt-2">
                                        بدأت فترة التجربة في:
                                        <strong>{{ $contract->trial_started_at ? $contract->trial_started_at->format('Y-m-d H:i') : 'غير محدد' }}</strong>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- تتبع مراحل العقد -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5>تتبع مراحل العقد</h5>
                            <div class="timeline">
                                <!-- بانتظار التوقيع -->
                                <div class="time-label">
                                    <span class="bg-{{ in_array($contract->status, ['waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active'], true) ? 'success' : 'secondary' }}">
                                        بانتظار التوقيع
                                    </span>
                                </div>
                                <div>
                                    <i class="fas fa-file-signature bg-{{ in_array($contract->status, ['waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active'], true) ? 'success' : 'secondary' }}"></i>
                                    <div class="timeline-item">
                                        <span class="time">
                                            <i class="fas fa-clock"></i> {{ optional($contract->contract_sent_at ?? $contract->created_at)->format('Y-m-d H:i') }}
                                        </span>
                                        <h3 class="timeline-header">
                                            العقد جاهز لتوقيع العميل
                                        </h3>
                                    </div>
                                </div>

                                <!-- توقيع العقد -->
                                <div class="time-label">
                                    <span class="bg-{{ $contract->contract_signed_at ? 'success' : 'secondary' }}">
                                        توقيع العقد
                                    </span>
                                </div>
                                <div>
                                    <i class="fas fa-signature bg-{{ $contract->contract_signed_at ? 'success' : 'secondary' }}"></i>
                                    <div class="timeline-item">
                                        <span class="time">
                                            @if($contract->contract_signed_at)
                                                <i class="fas fa-clock"></i> {{ $contract->contract_signed_at->format('Y-m-d H:i') }}
                                            @else
                                                لم يتم بعد
                                            @endif
                                        </span>
                                        <h3 class="timeline-header">
                                            @if($contract->contractSignedBy)
                                                تم التوقيع بواسطة {{ $contract->contractSignedBy->name }}
                                            @else
                                                توقيع العقد من العميل
                                            @endif
                                        </h3>
                                    </div>
                                </div>

                                <!-- تسليم العاملة -->
                                <div class="time-label">
                                    <span class="bg-{{ $contract->maid_delivered_at ? 'success' : 'secondary' }}">
                                        تسليم العاملة
                                    </span>
                                </div>
                                <div>
                                    <i class="fas fa-handshake bg-{{ $contract->maid_delivered_at ? 'success' : 'secondary' }}"></i>
                                    <div class="timeline-item">
                                        <span class="time">
                                            @if($contract->maid_delivered_at)
                                                <i class="fas fa-clock"></i> {{ $contract->maid_delivered_at->format('Y-m-d H:i') }}
                                            @else
                                                لم يتم بعد
                                            @endif
                                        </span>
                                        <h3 class="timeline-header">
                                            @if($contract->maidDeliveredBy)
                                                تم التسليم بواسطة {{ $contract->maidDeliveredBy->name }}
                                            @else
                                                تسليم العاملة للعميل
                                            @endif
                                        </h3>
                                    </div>
                                </div>

                                <!-- بدء التجربة -->
                                <div class="time-label">
                                    <span class="bg-{{ $contract->trial_started_at ? 'success' : 'warning' }}">
                                        بدء التجربة
                                    </span>
                                </div>
                                <div>
                                    <i class="fas fa-play-circle bg-{{ $contract->trial_started_at ? 'success' : 'warning' }}"></i>
                                    <div class="timeline-item">
                                        <span class="time">
                                            @if($contract->trial_started_at)
                                                <i class="fas fa-clock"></i> {{ $contract->trial_started_at->format('Y-m-d H:i') }}
                                            @else
                                                في الانتظار
                                            @endif
                                        </span>
                                        <h3 class="timeline-header">
                                            @if($contract->trial_started_at)
                                                بدأت فترة التجربة
                                            @else
                                                بدء فترة التجربة
                                            @endif
                                        </h3>
                                    </div>
                                </div>

                                <div>
                                    <i class="fas fa-clock bg-gray"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- إجراءات -->
                    @if($contract->canStartTrial())
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-warning">
                                <div class="card-header">
                                    <h3 class="card-title">بدء فترة التجربة</h3>
                                </div>
                                <div class="card-body">
                                    <p>تأكد من استكمال جميع الخطوات التالية قبل بدء فترة التجربة:</p>
                                    <ul class="list-group list-group-flush mb-3">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            تم توقيع العقد من العميل
                                            @if($contract->contract_signed_at)
                                                <span class="badge bg-success rounded-pill">مكتمل</span>
                                            @else
                                                <span class="badge bg-danger rounded-pill">غير مكتمل</span>
                                            @endif
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            تم تسليم العاملة للعميل
                                            @if($contract->maid_delivered_at)
                                                <span class="badge bg-success rounded-pill">مكتمل</span>
                                            @else
                                                <span class="badge bg-danger rounded-pill">غير مكتمل</span>
                                            @endif
                                        </li>
                                    </ul>

                                    @php
                                        $canStart = $contract->contract_signed_at && $contract->maid_delivered_at;
                                    @endphp

                                    <button type="button" class="btn btn-warning btn-lg"
                                            onclick="confirmStartTrial()"
                                            {{ !$canStart ? 'disabled' : '' }}>
                                        <i class="fas fa-play"></i>
                                        {{ $canStart ? 'بدء فترة التجربة' : 'يجب استكمال جميع الخطوات أولاً' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @elseif($contract->status === 'trial_started')
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-success">
                                <h5><i class="icon fas fa-check"></i> بدأت فترة التجربة!</h5>
                                <p class="mb-0">
                                    بدأت فترة التجربة بنجاح في {{ $contract->trial_started_at->format('Y-m-d H:i') }}.
                                    يمكنك الآن متابعة حالة العقد والعاملة.
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmStartTrial() {
    Swal.fire({
        title: 'تأكيد بدء التجربة',
        text: 'هل أنت متأكد من بدء فترة التجربة؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'نعم، ابدأ التجربة',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            startTrial();
        }
    });
}

function startTrial() {
    fetch(`{{ route('RentContract.startTrial', $contract->id) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(text);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'تم بنجاح!',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'موافق'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('خطأ!', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        let errorMessage = 'حدث خطأ غير متوقع';

        try {
            const errorData = JSON.parse(error.message);
            if (errorData.message) {
                errorMessage = errorData.message;
            }
        } catch (e) {
            // Keep default error message
        }

        Swal.fire('خطأ!', errorMessage, 'error');
    });
}
</script>
@endpush
