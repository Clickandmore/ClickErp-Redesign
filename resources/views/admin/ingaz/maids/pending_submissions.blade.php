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
                سير ذاتية من المكاتب الخارجية — بانتظار المراجعة
            </h4>
            <p class="text-muted mb-0 small">
                هذه السير أرسلها مكتب خارجي وتحتاج إلى قبول أو رفض قبل ظهورها في النظام.
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
                            <option value="{{ $office->id }}" @selected(request('office_id') == $office->id)>
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

    {{-- الجدول --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
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
                                <td class="text-muted">{{ $maid->id }}</td>
                                <td>
                                    <a href="{{ route('maids.show', $maid->id) }}" class="fw-semibold text-decoration-none">
                                        {{ $maid->name }}
                                    </a>
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
                                        <a href="{{ route('maids.show', $maid->id) }}"
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
                                <td colspan="10" class="text-center text-muted py-5">
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
                        $('#maid-row-' + id).fadeOut(300, function () { $(this).remove(); updateCounter(-1); });
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
                    $('#maid-row-' + currentMaidId).fadeOut(300, function () { $(this).remove(); updateCounter(-1); });
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
});
</script>
@endsection
