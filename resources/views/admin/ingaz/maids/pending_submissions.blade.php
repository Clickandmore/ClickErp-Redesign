@extends('admin.layout.indexs.index')

@section('page-title')
    سير ذاتية بانتظار المراجعة
@endsection

@section('content')
<div class="container-fluid py-3">

    {{-- رأس الصفحة --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-clock text-warning me-2"></i>
                طلبات المكاتب الخارجية — بانتظار المراجعة
            </h4>
            <p class="text-muted mb-0 small">
                تشمل السير الجديدة والتعديلات وطلبات التعليق وBack Out والسير المحذوفة.
            </p>
        </div>
        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
            {{ $pendingCount }} قيد الانتظار
        </span>
    </div>

    {{-- فلتر --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">بحث بالاسم / الجواز</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           value="{{ request('search') }}" placeholder="ابحث...">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">المكتب</label>
                    <select name="office_id" class="form-select form-select-sm" style="min-width:160px;">
                        <option value="">جميع المكاتب</option>
                        @foreach($offices as $office)
                            <option value="{{ $office->id }}" {{ request('office_id') == $office->id ? 'selected' : '' }}>
                                {{ $office->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm">
                        <i class="fas fa-search me-1"></i> بحث
                    </button>
                    @if(request()->hasAny(['search','office_id']))
                        <a href="{{ route('maids.pending-submissions') }}" class="btn btn-outline-secondary btn-sm ms-1">إلغاء</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- طلبات تغيير الحالة --}}
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-arrows-rotate text-primary me-2"></i>
            طلبات الإجراءات على السير
        </h5>
        <span class="badge bg-primary"><span id="statusRequestPendingCount">{{ $statusRequestCount }}</span> طلب</span>
    </div>
    <form id="statusRequestsBulkForm" action="{{ route('maids.status-change-requests.bulk-approve') }}"
        method="POST" class="card border-primary-subtle bg-primary-subtle mb-2">
        @csrf
        <div class="card-body py-2 d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <label class="mb-0 fw-semibold">
                <input type="checkbox" id="selectAllStatusRequests" class="form-check-input me-1">
                تحديد كل الطلبات الظاهرة
            </label>
            <button type="button" id="bulkApproveStatusRequests" class="btn btn-success btn-sm" disabled>
                <i class="fas fa-check-double me-1"></i>
                اعتماد وتنفيذ المحدد (<span class="js-selected-count">0</span>)
            </button>
        </div>
    </form>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:35px;"></th>
                            <th>#</th>
                            <th>العاملة</th>
                            <th>الجواز</th>
                            <th>المكتب</th>
                            <th>نوع الطلب</th>
                            <th>السبب</th>
                            <th>مرسل الطلب</th>
                            <th>تاريخ الطلب</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statusRequests as $statusRequest)
                            <tr id="status-request-row-{{ $statusRequest->id }}">
                                <td>
                                    <input type="checkbox" name="request_ids[]" value="{{ $statusRequest->id }}"
                                        class="form-check-input js-status-request-checkbox"
                                        form="statusRequestsBulkForm" aria-label="تحديد الطلب">
                                </td>
                                <td class="text-muted">{{ $statusRequest->id }}</td>
                                <td>
                                    <a href="{{ route('showMaidDetails', $statusRequest->maid_id) }}"
                                        class="fw-semibold text-decoration-none">
                                        {{ $statusRequest->maid?->name ?: '#' . $statusRequest->maid_id }}
                                    </a>
                                </td>
                                <td>{{ $statusRequest->maid?->passport_num ?: '—' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $statusRequest->office?->title ?: '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ in_array($statusRequest->type, ['backout', 'delete'], true) ? 'bg-danger' : 'bg-warning text-dark' }}">
                                        {{ $statusRequest->type_label }}
                                    </span>
                                </td>
                                <td style="min-width:220px;">
                                    <div class="small" style="white-space:pre-wrap;">{{ $statusRequest->reason }}</div>
                                </td>
                                <td>{{ $statusRequest->requestedBy?->name ?: $statusRequest->requestedBy?->username ?: '—' }}</td>
                                <td class="text-nowrap text-muted small">{{ $statusRequest->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-success js-approve-status-request"
                                            data-id="{{ $statusRequest->id }}"
                                            data-name="{{ $statusRequest->maid?->name }}"
                                            data-type="{{ $statusRequest->type_label }}"
                                            data-url="{{ route('maids.status-change-requests.approve', $statusRequest->id) }}">
                                            <i class="fas fa-check"></i> اعتماد
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger js-reject-status-request"
                                            data-id="{{ $statusRequest->id }}"
                                            data-name="{{ $statusRequest->maid?->name }}"
                                            data-url="{{ route('maids.status-change-requests.reject', $statusRequest->id) }}">
                                            <i class="fas fa-times"></i> رفض
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    لا توجد طلبات تعليق أو Back Out أو حذف قيد المراجعة
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($statusRequests->hasPages())
            <div class="card-footer bg-white">
                {{ $statusRequests->links() }}
            </div>
        @endif
    </div>

    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-file-circle-check text-warning me-2"></i>
            السير الذاتية الجديدة والمعدلة
        </h5>
        <span class="badge bg-warning text-dark"><span id="cvPendingCount">{{ $cvPendingCount }}</span> سيرة</span>
    </div>
    <form id="cvSubmissionsBulkForm" action="{{ route('maids.submissions.bulk-approve') }}"
        method="POST" class="card border-warning-subtle bg-warning-subtle mb-2">
        @csrf
        <div class="card-body py-2 d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <label class="mb-0 fw-semibold">
                <input type="checkbox" id="selectAllCvSubmissions" class="form-check-input me-1">
                تحديد كل السير الظاهرة
            </label>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-success btn-sm js-bulk-cv-action" disabled>
                    <i class="fas fa-check-double me-1"></i>
                    قبول المحدد (<span class="js-selected-count">0</span>)
                </button>
            </div>
        </div>
    </form>

    {{-- جدول السير الجديدة --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:35px;"></th>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>الجواز</th>
                            <th>الجنسية</th>
                            <th>المكتب</th>
                            <th>المهن</th>
                            <th>العمر</th>
                            <th>الخبرة</th>
                            <th>تاريخ الرفع</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($maids as $maid)
                            <tr id="maid-row-{{ $maid->id }}">
                                <td>
                                    <input type="checkbox" name="maid_ids[]" value="{{ $maid->id }}"
                                        class="form-check-input js-cv-submission-checkbox"
                                        form="cvSubmissionsBulkForm" aria-label="تحديد السيرة">
                                </td>
                                <td class="text-muted">{{ $maid->id }}</td>
                                <td>
                                    <a href="{{ route('showMaidDetails', $maid->id) }}" class="fw-semibold text-decoration-none">
                                        {{ $maid->name }}
                                    </a>
                                    @if($maid->submission_type === \App\Models\ingaz\Maid::SUBMISSION_TYPE_UPDATE)
                                        <span class="badge bg-info text-dark ms-1">تعديل سيرة</span>
                                    @else
                                        <span class="badge bg-success ms-1">سيرة جديدة</span>
                                    @endif
                                </td>
                                <td>{{ $maid->passport_num ?? '—' }}</td>
                                <td>{{ $maid->nationality?->sub_title ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $maid->office?->title ?? '—' }}
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    {{ $maid->jobTypes->pluck('title')->implode('، ') ?: '—' }}
                                </td>
                                <td>{{ $maid->age ? (int)$maid->age . ' سنة' : '—' }}</td>
                                <td>{{ $maid->experience_years ? $maid->experience_years . ' سنوات' : '—' }}</td>
                                <td class="text-nowrap text-muted small">{{ $maid->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="{{ route('showMaidDetails', $maid->id) }}"
                                           class="btn btn-sm btn-outline-secondary" title="عرض التفاصيل">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-success js-approve-btn"
                                                data-id="{{ $maid->id }}" data-name="{{ $maid->name }}"
                                                title="قبول">
                                            <i class="fas fa-check"></i> قبول
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger js-reject-btn"
                                                data-id="{{ $maid->id }}" data-name="{{ $maid->name }}"
                                                title="رفض">
                                            <i class="fas fa-times"></i> رفض
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-5">
                                    <i class="fas fa-check-circle text-success fs-2 d-block mb-2"></i>
                                    لا توجد سير ذاتية بانتظار المراجعة
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($maids->hasPages())
            <div class="card-footer bg-white">
                {{ $maids->links() }}
            </div>
        @endif
    </div>
</div>

{{-- مودال الرفض --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-times-circle me-2"></i>رفض السيرة الذاتية
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small mb-3" id="rejectMaidName"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">سبب الرفض <small class="text-muted">(اختياري)</small></label>
                    <textarea id="rejectionReasonInput" class="form-control" rows="3"
                              maxlength="500" placeholder="اكتب سبب الرفض لإبلاغ المكتب..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" id="confirmRejectBtn" class="btn btn-danger">
                    <i class="fas fa-times me-1"></i> تأكيد الرفض
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function () {
    var currentMaidId = null;

    // ===== قبول =====
    $(document).on('click', '.js-approve-btn', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            title: 'قبول السيرة الذاتية',
            html: 'هل تريد قبول سيرة <strong>' + $('<div>').text(name).html() + '</strong> وإضافتها للنظام؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'نعم، قبول',
            cancelButtonText: 'إلغاء',
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url:  '/admin/maids/' + id + '/approve-submission',
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res.success) {
                        $('#maid-row-' + id).fadeOut(300, function () { $(this).remove(); updateCounter(-1); updateSectionCounter('#cvPendingCount', -1); });
                        Swal.fire({ icon: 'success', title: 'تم القبول', text: res.message, timer: 2000, showConfirmButton: false });
                    } else {
                        Swal.fire('خطأ', res.message, 'error');
                    }
                },
                error: function (xhr) { Swal.fire('خطأ', xhr.responseJSON?.message || 'حدث خطأ', 'error'); }
            });
        });
    });

    // ===== رفض — فتح المودال =====
    $(document).on('click', '.js-reject-btn', function () {
        currentMaidId = $(this).data('id');
        var name = $(this).data('name');
        $('#rejectMaidName').text('سيتم رفض سيرة: ' + name);
        $('#rejectionReasonInput').val('');
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    });

    // ===== رفض — تأكيد =====
    $('#confirmRejectBtn').on('click', function () {
        if (!currentMaidId) return;
        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');

        $.ajax({
            url:  '/admin/maids/' + currentMaidId + '/reject-submission',
            type: 'POST',
            data: {
                _token:           $('meta[name="csrf-token"]').attr('content'),
                rejection_reason: $('#rejectionReasonInput').val(),
            },
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
                if (res.success) {
                    $('#maid-row-' + currentMaidId).fadeOut(300, function () { $(this).remove(); updateCounter(-1); updateSectionCounter('#cvPendingCount', -1); });
                    Swal.fire({ icon: 'success', title: 'تم الرفض', text: res.message, timer: 2000, showConfirmButton: false });
                } else {
                    Swal.fire('خطأ', res.message, 'error');
                }
            },
            error: function (xhr) { Swal.fire('خطأ', xhr.responseJSON?.message || 'حدث خطأ', 'error'); },
            complete: function () { $btn.prop('disabled', false).html('<i class="fas fa-times me-1"></i> تأكيد الرفض'); }
        });
    });

    function updateCounter(delta) {
        var badge = document.querySelector('.badge.bg-warning.fs-6');
        if (badge) {
            var num = parseInt(badge.textContent) + delta;
            badge.textContent = (num < 0 ? 0 : num) + ' قيد الانتظار';
        }
    }

    function updateSectionCounter(selector, delta) {
        var counter = document.querySelector(selector);
        if (!counter) return;
        var value = Math.max(0, (parseInt(counter.textContent) || 0) + delta);
        counter.textContent = value;
    }

    function setupBulkSelection(checkboxSelector, selectAllSelector, buttonSelector, formSelector) {
        var checkboxes = Array.from(document.querySelectorAll(checkboxSelector));
        var selectAll = document.querySelector(selectAllSelector);
        var buttons = Array.from(document.querySelectorAll(buttonSelector));
        var form = document.querySelector(formSelector);

        function update() {
            var count = checkboxes.filter(function (checkbox) { return checkbox.checked; }).length;
            buttons.forEach(function (button) {
                button.disabled = count === 0;
                var counter = button.querySelector('.js-selected-count');
                if (counter) counter.textContent = count;
            });
            if (form) {
                var counter = form.querySelector('.js-selected-count');
                if (counter) counter.textContent = count;
            }
            if (selectAll) {
                selectAll.checked = checkboxes.length > 0 && count === checkboxes.length;
                selectAll.indeterminate = count > 0 && count < checkboxes.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
                update();
            });
        }
        checkboxes.forEach(function (checkbox) { checkbox.addEventListener('change', update); });

        return {
            checkedIds: function () {
                return checkboxes.filter(function (checkbox) { return checkbox.checked; })
                    .map(function (checkbox) { return checkbox.value; });
            },
            update: update
        };
    }

    var statusBulk = setupBulkSelection(
        '.js-status-request-checkbox',
        '#selectAllStatusRequests',
        '#bulkApproveStatusRequests',
        '#statusRequestsBulkForm'
    );
    var cvBulk = setupBulkSelection(
        '.js-cv-submission-checkbox',
        '#selectAllCvSubmissions',
        '.js-bulk-cv-action',
        '#cvSubmissionsBulkForm'
    );

    $('#bulkApproveStatusRequests').on('click', function () {
        var ids = statusBulk.checkedIds();
        if (!ids.length) return;

        Swal.fire({
            title: 'اعتماد الطلبات المحددة',
            text: 'سيتم تنفيذ ' + ids.length + ' طلب فورًا.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'اعتماد وتنفيذ',
            cancelButtonText: 'إلغاء'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: $('#statusRequestsBulkForm').attr('action'),
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    request_ids: ids
                },
                success: function (res) {
                    ids.forEach(function (id) { $('#status-request-row-' + id).remove(); });
                    updateCounter(-res.processed_count);
                    updateSectionCounter('#statusRequestPendingCount', -res.processed_count);
                    Swal.fire('تم', res.message, 'success');
                },
                error: function (xhr) { Swal.fire('خطأ', xhr.responseJSON?.message || 'حدث خطأ', 'error'); }
            });
        });
    });

    $('.js-bulk-cv-action').on('click', function () {
        var ids = cvBulk.checkedIds();
        if (!ids.length) return;

        Swal.fire({
            title: 'قبول السير المحددة',
            text: 'عدد السير المحددة: ' + ids.length,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'قبول',
            cancelButtonText: 'إلغاء'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: $('#cvSubmissionsBulkForm').attr('action'),
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    maid_ids: ids
                },
                success: function (res) {
                    ids.forEach(function (id) { $('#maid-row-' + id).remove(); });
                    updateCounter(-res.processed_count);
                    updateSectionCounter('#cvPendingCount', -res.processed_count);
                    Swal.fire('تم', res.message, 'success');
                },
                error: function (xhr) { Swal.fire('خطأ', xhr.responseJSON?.message || 'حدث خطأ', 'error'); }
            });
        });
    });

    // ===== اعتماد طلب تعليق / Back Out =====
    $(document).on('click', '.js-approve-status-request', function () {
        var button = $(this);
        var id = button.data('id');
        var name = button.data('name');
        var type = button.data('type');

        Swal.fire({
            title: 'اعتماد الطلب',
            html: 'سيتم تنفيذ <strong>' + $('<div>').text(type).html() + '</strong> للعاملة <strong>'
                + $('<div>').text(name).html() + '</strong> فورًا.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'اعتماد وتنفيذ',
            cancelButtonText: 'إلغاء',
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: button.data('url'),
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    $('#status-request-row-' + id).fadeOut(300, function () { $(this).remove(); updateCounter(-1); updateSectionCounter('#statusRequestPendingCount', -1); });
                    Swal.fire({ icon: 'success', title: 'تم التنفيذ', text: res.message, timer: 2200, showConfirmButton: false });
                },
                error: function (xhr) {
                    Swal.fire('خطأ', xhr.responseJSON?.message || 'حدث خطأ', 'error');
                }
            });
        });
    });

    // ===== رفض طلب تعليق / Back Out =====
    $(document).on('click', '.js-reject-status-request', function () {
        var button = $(this);
        var id = button.data('id');
        var name = button.data('name');

        Swal.fire({
            title: 'رفض الطلب',
            text: 'لن تتغير حالة العاملة ' + (name || ''),
            input: 'textarea',
            inputLabel: 'سبب الرفض (اختياري)',
            inputPlaceholder: 'اكتب ملاحظة للمكتب...',
            inputAttributes: { maxlength: 1000 },
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'تأكيد الرفض',
            cancelButtonText: 'إلغاء',
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: button.data('url'),
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    review_note: result.value || ''
                },
                success: function (res) {
                    $('#status-request-row-' + id).fadeOut(300, function () { $(this).remove(); updateCounter(-1); updateSectionCounter('#statusRequestPendingCount', -1); });
                    Swal.fire({ icon: 'success', title: 'تم الرفض', text: res.message, timer: 2200, showConfirmButton: false });
                },
                error: function (xhr) {
                    Swal.fire('خطأ', xhr.responseJSON?.message || 'حدث خطأ', 'error');
                }
            });
        });
    });
});
</script>
@endsection
