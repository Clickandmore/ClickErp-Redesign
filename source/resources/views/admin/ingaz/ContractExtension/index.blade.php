@extends('admin.layout.indexs.index')

@section('page-title')
    طلبات تمديد عقود الاستقدام
@endsection

@section('content')
    <div class="container-fluid py-3">

        {{-- بطاقات الإحصاء --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <div class="fs-2 fw-bold text-primary">{{ $stats['total'] }}</div>
                        <div class="text-muted small">إجمالي الطلبات</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <div class="fs-2 fw-bold text-warning">{{ $stats['pending'] }}</div>
                        <div class="text-muted small">قيد الانتظار</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <div class="fs-2 fw-bold text-success">{{ $stats['approved'] }}</div>
                        <div class="text-muted small">مقبولة</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body py-3">
                        <div class="fs-2 fw-bold text-danger">{{ $stats['rejected'] }}</div>
                        <div class="text-muted small">مرفوضة</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- فلتر الحالة --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-2">
                <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
                    <label class="form-label mb-0 fw-semibold">تصفية حسب الحالة:</label>
                    <select name="status" class="form-select form-select-sm" style="width:180px;" onchange="this.form.submit()">
                        <option value="">جميع الطلبات</option>
                        <option value="pending"  @selected(request('status') === 'pending')>قيد الانتظار</option>
                        <option value="approved" @selected(request('status') === 'approved')>مقبولة</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>مرفوضة</option>
                    </select>
                    @if(request('status'))
                        <a href="{{ route('contract-extension-requests.index') }}" class="btn btn-sm btn-outline-secondary">إلغاء الفلتر</a>
                    @endif
                </form>
            </div>
        </div>

        {{-- الجدول --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>رقم العقد</th>
                                <th>العميل</th>
                                <th>العاملة</th>
                                <th>سنوات التمديد</th>
                                <th>مقدم الطلب</th>
                                <th>تاريخ الطلب</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($extensionRequests as $req)
                                <tr>
                                    <td class="text-muted">{{ $req->id }}</td>
                                    <td>
                                        @if($req->contract)
                                            <a href="{{ route('contracts.details', $req->contract_id) }}" class="fw-semibold text-decoration-none">
                                                #{{ $req->contract_id }}
                                            </a>
                                        @else
                                            <span class="text-muted">#{{ $req->contract_id }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $req->contract?->user?->name ?? '—' }}</td>
                                    <td>{{ $req->contract?->maid?->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            {{ $req->extension_years }} {{ $req->extension_years == 1 ? 'سنة' : 'سنوات' }}
                                        </span>
                                    </td>
                                    <td>{{ $req->requestedBy?->name ?? '—' }}</td>
                                    <td class="text-nowrap">{{ $req->created_at?->format('Y-m-d') }}</td>
                                    <td>{!! $req->status_badge !!}</td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <a href="{{ route('contract-extension-requests.show', $req->id) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($req->status === 'pending' && @AdminGuardHasAnyPerm([\App\NewPermKeys::REC_CONTRACTS_EXTENSION_MANAGE, \App\NewPermKeys::REC_CONTRACTS_MANAGE]))
                                                <button type="button" class="btn btn-sm btn-success js-process-btn"
                                                        data-id="{{ $req->id }}"
                                                        data-action="approved"
                                                        data-years="{{ $req->extension_years }}"
                                                        data-contract="{{ $req->contract_id }}">
                                                    <i class="fas fa-check"></i> قبول
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger js-process-btn"
                                                        data-id="{{ $req->id }}"
                                                        data-action="rejected"
                                                        data-years="{{ $req->extension_years }}"
                                                        data-contract="{{ $req->contract_id }}">
                                                    <i class="fas fa-times"></i> رفض
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">لا توجد طلبات تمديد</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($extensionRequests->hasPages())
                <div class="card-footer bg-white">
                    {{ $extensionRequests->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- مودال معالجة الطلب --}}
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
                                  placeholder="يمكنك كتابة ملاحظة توضيحية للقرار..."></textarea>
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

        var actionLabel = currentAction === 'approved' ? 'قبول' : 'رفض';
        var alertClass  = currentAction === 'approved' ? 'alert-success' : 'alert-danger';

        $('#processModalTitle').text(actionLabel + ' طلب التمديد');
        $('#processModalInfo')
            .removeClass('alert-success alert-danger alert-info')
            .addClass(alertClass)
            .html(
                '<strong>' + actionLabel + ' طلب تمديد العقد #' + contractId + '</strong><br>' +
                'مدة التمديد: <strong>' + years + (years == 1 ? ' سنة' : ' سنوات') + '</strong>'
            );
        $('#confirmProcessBtn')
            .removeClass('btn-success btn-danger')
            .addClass(currentAction === 'approved' ? 'btn-success' : 'btn-danger')
            .text(actionLabel);
        $('#adminNotesInput').val('');

        var modal = new bootstrap.Modal(document.getElementById('processModal'));
        modal.show();
    });

    $('#confirmProcessBtn').on('click', function () {
        if (!currentRequestId) return;

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

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
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: res.message,
                        confirmButtonText: 'حسناً'
                    }).then(function () { location.reload(); });
                } else {
                    Swal.fire('خطأ', res.message || 'حدث خطأ', 'error');
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message || 'حدث خطأ أثناء معالجة الطلب';
                Swal.fire('خطأ', msg, 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).text(currentAction === 'approved' ? 'قبول' : 'رفض');
            }
        });
    });
});
</script>
@endsection
