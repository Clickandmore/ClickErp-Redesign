@extends('admin.layout.indexs.index')

@section('page-title')
    تفاصيل طلب تمديد #{{ $extensionRequest->id }}
@endsection

@section('content')
    <div class="container-fluid py-3" style="max-width:820px;">
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

        {{-- قسم الملاحظات --}}
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white d-flex align-items-center gap-2 py-3">
                <i class="fas fa-sticky-note text-warning fs-5"></i>
                <h5 class="mb-0">ملاحظات الطلب</h5>
                <span class="badge bg-secondary ms-1" id="notesCount">{{ $extensionRequest->notes->count() }}</span>
            </div>

            {{-- قائمة الملاحظات --}}
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="notesList">
                    @forelse($extensionRequest->notes as $note)
                        <li class="list-group-item d-flex gap-3 align-items-start py-3" id="note-{{ $note->id }}">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                     style="width:38px;height:38px;">
                                    <i class="fas fa-user text-primary small"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <span class="fw-semibold small">{{ $note->admin?->name ?? 'غير محدد' }}</span>
                                    <span class="text-muted small text-nowrap">{{ $note->created_at->format('Y-m-d H:i') }}</span>
                                </div>
                                <p class="mb-0 mt-1 text-dark" style="white-space:pre-wrap">{{ $note->note }}</p>
                            </div>
                            @if(@AdminGuardHasAnyPerm([\App\NewPermKeys::REC_CONTRACTS_EXTENSION_MANAGE, \App\NewPermKeys::REC_CONTRACTS_MANAGE]))
                                <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0 js-delete-note"
                                        data-id="{{ $note->id }}" title="حذف الملاحظة">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4" id="emptyNotesMsg">
                            <i class="fas fa-comment-slash fs-4 d-block mb-2 opacity-50"></i>
                            لا توجد ملاحظات بعد
                        </li>
                    @endforelse
                </ul>
            </div>

            {{-- نموذج إضافة ملاحظة --}}
            @if(@AdminGuardHasAnyPerm([\App\NewPermKeys::REC_CONTRACTS_EXTENSION_VIEW, \App\NewPermKeys::REC_CONTRACTS_EXTENSION_MANAGE, \App\NewPermKeys::REC_CONTRACTS_MANAGE]))
                <div class="card-footer bg-white">
                    <div class="d-flex gap-2 align-items-start">
                        <textarea id="newNoteText" class="form-control" rows="2"
                                  placeholder="اكتب ملاحظة..." maxlength="1000"
                                  style="resize:none;"></textarea>
                        <button type="button" id="submitNoteBtn" class="btn btn-primary flex-shrink-0"
                                style="min-width:90px;">
                            <i class="fas fa-paper-plane me-1"></i> إرسال
                        </button>
                    </div>
                    <div class="d-flex justify-content-end mt-1">
                        <small class="text-muted"><span id="noteCharCount">0</span>/1000</small>
                    </div>
                </div>
            @endif
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

    // ===========================================================
    // الملاحظات
    // ===========================================================
    var storeNoteUrl  = '{{ route('contract-extension-requests.notes.store', $extensionRequest->id) }}';
    var deleteNoteUrl = '/admin/contract-extension-requests/{{ $extensionRequest->id }}/notes/';
    var canManage     = {{ @AdminGuardHasAnyPerm([\App\NewPermKeys::REC_CONTRACTS_EXTENSION_MANAGE, \App\NewPermKeys::REC_CONTRACTS_MANAGE]) ? 'true' : 'false' }};

    // عداد الأحرف
    $('#newNoteText').on('input', function () {
        $('#noteCharCount').text($(this).val().length);
    });

    // إرسال ملاحظة جديدة
    $('#submitNoteBtn').on('click', function () {
        var text = $('#newNoteText').val().trim();
        if (!text) {
            $('#newNoteText').addClass('is-invalid').focus();
            return;
        }
        $('#newNoteText').removeClass('is-invalid');

        var $btn = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url:  storeNoteUrl,
            type: 'POST',
            data: { _token: $('meta[name="csrf-token"]').attr('content'), note: text },
            success: function (res) {
                if (!res.success) { Swal.fire('خطأ', res.message || 'حدث خطأ', 'error'); return; }

                // إزالة رسالة "لا توجد ملاحظات"
                $('#emptyNotesMsg').remove();

                // بناء عنصر الملاحظة الجديدة
                var deleteBtn = canManage
                    ? '<button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0 js-delete-note" data-id="' + res.note.id + '" title="حذف الملاحظة"><i class="fas fa-trash-alt"></i></button>'
                    : '';

                var html = '<li class="list-group-item d-flex gap-3 align-items-start py-3" id="note-' + res.note.id + '">' +
                    '<div class="flex-shrink-0"><div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="fas fa-user text-primary small"></i></div></div>' +
                    '<div class="flex-grow-1">' +
                    '<div class="d-flex justify-content-between align-items-start gap-2">' +
                    '<span class="fw-semibold small">' + $('<div>').text(res.note.admin_name).html() + '</span>' +
                    '<span class="text-muted small text-nowrap">' + res.note.created_at + '</span>' +
                    '</div>' +
                    '<p class="mb-0 mt-1 text-dark" style="white-space:pre-wrap">' + $('<div>').text(res.note.note).html() + '</p>' +
                    '</div>' +
                    deleteBtn +
                    '</li>';

                $('#notesList').prepend(html);
                $('#notesCount').text(parseInt($('#notesCount').text() || '0') + 1);
                $('#newNoteText').val('');
                $('#noteCharCount').text('0');
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.errors?.note?.[0] || xhr.responseJSON?.message || 'حدث خطأ';
                Swal.fire('خطأ', msg, 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> إرسال');
            }
        });
    });

    // إرسال بـ Ctrl+Enter
    $('#newNoteText').on('keydown', function (e) {
        if (e.ctrlKey && e.key === 'Enter') { $('#submitNoteBtn').trigger('click'); }
    });

    // حذف ملاحظة
    $(document).on('click', '.js-delete-note', function () {
        var noteId = $(this).data('id');
        Swal.fire({
            title: 'حذف الملاحظة',
            text: 'هل أنت متأكد من حذف هذه الملاحظة؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url:  deleteNoteUrl + noteId,
                type: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res.success) {
                        $('#note-' + noteId).fadeOut(300, function () {
                            $(this).remove();
                            var count = parseInt($('#notesCount').text() || '1') - 1;
                            $('#notesCount').text(count);
                            if (count === 0) {
                                $('#notesList').append('<li class="list-group-item text-center text-muted py-4" id="emptyNotesMsg"><i class="fas fa-comment-slash fs-4 d-block mb-2 opacity-50"></i>لا توجد ملاحظات بعد</li>');
                            }
                        });
                    } else {
                        Swal.fire('خطأ', res.message, 'error');
                    }
                },
                error: function () { Swal.fire('خطأ', 'حدث خطأ أثناء الحذف', 'error'); }
            });
        });
    });
});
</script>
@endsection
