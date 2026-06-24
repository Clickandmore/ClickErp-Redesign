@extends('admin.layout.indexs.index')

@section('page-title')
    تفاصيل طلب تمديد #{{ $extensionRequest->id }}
@endsection

@section('content')
    <div class="container-fluid py-3" style="max-width:760px;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex align-items-center gap-2 py-3">
                <i class="fas fa-calendar-plus text-primary fs-5"></i>
                <h5 class="mb-0">طلب تمديد عقد استقدام</h5>
                <div class="ms-auto">{!! $extensionRequest->status_badge !!}</div>
            </div>
            <div class="card-body">
                <dl class="row gy-2">
                    <dt class="col-sm-4">رقم الطلب</dt>
                    <dd class="col-sm-8">#{{ $extensionRequest->id }}</dd>

                    <dt class="col-sm-4">العقد</dt>
                    <dd class="col-sm-8">
                        <a href="{{ route('contracts.details', $extensionRequest->contract_id) }}" class="text-decoration-none">
                            #{{ $extensionRequest->contract_id }}
                            @if($extensionRequest->contract?->user)
                                – {{ $extensionRequest->contract->user->name }}
                            @endif
                        </a>
                    </dd>

                    @if($extensionRequest->contract?->maid)
                        <dt class="col-sm-4">العاملة</dt>
                        <dd class="col-sm-8">{{ $extensionRequest->contract->maid->name }}</dd>
                    @endif

                    @if($extensionRequest->contract?->info)
                        <dt class="col-sm-4">المدة الحالية</dt>
                        <dd class="col-sm-8">{{ $extensionRequest->contract->info->contract_duration ?? '—' }} سنة</dd>

                        <dt class="col-sm-4">تاريخ انتهاء الإنجاز الحالي</dt>
                        <dd class="col-sm-8">{{ $extensionRequest->contract->info->ingaz_to ?? '—' }}</dd>
                    @endif

                    <dt class="col-sm-4">مدة التمديد المطلوبة</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-info text-dark fs-6">
                            {{ $extensionRequest->extension_years }}
                            {{ $extensionRequest->extension_years == 1 ? 'سنة' : 'سنوات' }}
                        </span>
                    </dd>

                    <dt class="col-sm-4">سبب التمديد</dt>
                    <dd class="col-sm-8">{{ $extensionRequest->reason ?: '—' }}</dd>

                    <dt class="col-sm-4">مقدم الطلب</dt>
                    <dd class="col-sm-8">{{ $extensionRequest->requestedBy?->name ?? '—' }}</dd>

                    <dt class="col-sm-4">تاريخ الطلب</dt>
                    <dd class="col-sm-8">{{ $extensionRequest->created_at?->format('Y-m-d H:i') }}</dd>

                    @if($extensionRequest->status !== 'pending')
                        <dt class="col-sm-4">القرار</dt>
                        <dd class="col-sm-8">{!! $extensionRequest->status_badge !!}</dd>

                        <dt class="col-sm-4">ملاحظات الإدارة</dt>
                        <dd class="col-sm-8">{{ $extensionRequest->admin_notes ?: '—' }}</dd>

                        <dt class="col-sm-4">تمت المعالجة بواسطة</dt>
                        <dd class="col-sm-8">{{ $extensionRequest->processedBy?->name ?? '—' }}</dd>

                        <dt class="col-sm-4">تاريخ المعالجة</dt>
                        <dd class="col-sm-8">{{ $extensionRequest->processed_at?->format('Y-m-d H:i') }}</dd>
                    @endif
                </dl>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                @if($extensionRequest->status === 'pending' && @AdminGuardHasAnyPerm([\App\NewPermKeys::REC_CONTRACTS_EXTENSION_MANAGE, \App\NewPermKeys::REC_CONTRACTS_MANAGE]))
                    <button type="button" class="btn btn-success js-process-btn"
                            data-id="{{ $extensionRequest->id }}" data-action="approved"
                            data-years="{{ $extensionRequest->extension_years }}"
                            data-contract="{{ $extensionRequest->contract_id }}">
                        <i class="fas fa-check me-1"></i> قبول التمديد
                    </button>
                    <button type="button" class="btn btn-danger js-process-btn"
                            data-id="{{ $extensionRequest->id }}" data-action="rejected"
                            data-years="{{ $extensionRequest->extension_years }}"
                            data-contract="{{ $extensionRequest->contract_id }}">
                        <i class="fas fa-times me-1"></i> رفض التمديد
                    </button>
                @endif
                <a href="{{ route('contract-extension-requests.index') }}" class="btn btn-secondary ms-auto">
                    <i class="fas fa-arrow-right me-1"></i> رجوع
                </a>
            </div>
        </div>
    </div>

    {{-- مودال المعالجة --}}
    <div class="modal fade" id="processModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="processModalTitle">معالجة طلب التمديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="processModalInfo" class="alert alert-info mb-3"></div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ملاحظات الإدارة <small class="text-muted">(اختياري)</small></label>
                        <textarea id="adminNotesInput" class="form-control" rows="3" maxlength="1000"
                                  placeholder="يمكنك كتابة ملاحظة توضيحية..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" id="confirmProcessBtn" class="btn btn-primary">تأكيد</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
$(document).ready(function () {
    var currentRequestId = null;
    var currentAction    = null;

    $(document).on('click', '.js-process-btn', function () {
        currentRequestId = $(this).data('id');
        currentAction    = $(this).data('action');
        var years        = $(this).data('years');
        var contractId   = $(this).data('contract');
        var actionLabel  = currentAction === 'approved' ? 'قبول' : 'رفض';
        var alertClass   = currentAction === 'approved' ? 'alert-success' : 'alert-danger';

        $('#processModalTitle').text(actionLabel + ' طلب التمديد');
        $('#processModalInfo')
            .removeClass('alert-success alert-danger alert-info')
            .addClass(alertClass)
            .html('<strong>' + actionLabel + ' طلب تمديد العقد #' + contractId + '</strong><br>مدة التمديد: <strong>' + years + (years == 1 ? ' سنة' : ' سنوات') + '</strong>');
        $('#confirmProcessBtn')
            .removeClass('btn-success btn-danger btn-primary')
            .addClass(currentAction === 'approved' ? 'btn-success' : 'btn-danger')
            .text(actionLabel);
        $('#adminNotesInput').val('');
        new bootstrap.Modal(document.getElementById('processModal')).show();
    });

    $('#confirmProcessBtn').on('click', function () {
        if (!currentRequestId) return;
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: '/admin/contract-extension-requests/' + currentRequestId + '/process',
            type: 'POST',
            data: {
                _token:      $('meta[name="csrf-token"]').attr('content'),
                status:      currentAction,
                admin_notes: $('#adminNotesInput').val(),
            },
            success: function (res) {
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('processModal')).hide();
                    Swal.fire({ icon: 'success', title: 'تم بنجاح', text: res.message, confirmButtonText: 'حسناً' })
                        .then(function () { location.reload(); });
                } else {
                    Swal.fire('خطأ', res.message || 'حدث خطأ', 'error');
                }
            },
            error: function (xhr) {
                Swal.fire('خطأ', xhr.responseJSON?.message || 'حدث خطأ', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).text(currentAction === 'approved' ? 'قبول التمديد' : 'رفض التمديد');
            }
        });
    });
});
</script>
@endsection
