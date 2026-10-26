@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('ingaz.ticket_details') }} #{{ str_pad($complaint->id, 6, '0', STR_PAD_LEFT) }}
@endsection

@section('style')
    <link rel="stylesheet" href="{{ asset('admin/assets/plugins/select2/dist/css/select2.min.css') }}">
    @include('admin.ingaz.complaints.partials.related-record-styles')
    <style>
        /* Organized complaint details */
        .complaint-detail-page {
            --ink: #263248;
            --muted: #718096;
            --line: #e7ebf1;
            --surface: #ffffff;
            --canvas: #f7f8fb;
            color: var(--ink);
            padding-bottom: 30px;
        }

        .detail-toolbar,
        .detail-toolbar__actions,
        .detail-hero,
        .detail-hero__meta,
        .detail-hero__badges,
        .detail-section__header,
        .party-card__title,
        .party-card__meta,
        .relation-body,
        .organized-upload__head,
        .organized-attachment,
        .organized-attachment__main,
        .organized-comment,
        .organized-comment__head {
            align-items: center;
            display: flex;
        }

        .detail-toolbar {
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .detail-toolbar__actions { gap: 8px; }
        .detail-back-link { color: #5362c8; font-weight: 600; text-decoration: none; }

        .detail-hero {
            background: var(--surface);
            border: 1px solid var(--line);
            border-inline-start: 5px solid #667eea;
            border-radius: 14px;
            box-shadow: 0 7px 22px rgba(36, 48, 75, .06);
            justify-content: space-between;
            margin-bottom: 24px;
            padding: 24px 26px;
        }

        .detail-hero.status-accent-pending { border-inline-start-color: #e99a25; }
        .detail-hero.status-accent-in_progress { border-inline-start-color: #268bd2; }
        .detail-hero.status-accent-resolved { border-inline-start-color: #2e9d68; }
        .detail-hero.status-accent-closed { border-inline-start-color: #7b8493; }
        .detail-hero.status-accent-rejected { border-inline-start-color: #d94a5a; }
        .detail-ticket-number { color: #65718a; font-size: 13px; font-weight: 700; margin-bottom: 7px; }
        .detail-hero h2 { color: #202b3f; font-size: 24px; font-weight: 700; margin: 0 0 13px; }
        .detail-hero__meta { color: var(--muted); flex-wrap: wrap; font-size: 13px; gap: 10px 22px; }
        .detail-hero__meta span { align-items: center; display: inline-flex; gap: 7px; }
        .detail-hero__badges { flex-wrap: wrap; gap: 8px; justify-content: flex-end; }

        .detail-pill {
            align-items: center;
            border: 1px solid transparent;
            border-radius: 999px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
            padding: 7px 11px;
            white-space: nowrap;
        }

        .detail-pill .fa-circle { font-size: 6px; }
        .detail-pill.status-pending { background: #fff5df; border-color: #f3dcaa; color: #986416; }
        .detail-pill.status-in_progress { background: #e8f4fd; border-color: #c7e2f5; color: #176b9f; }
        .detail-pill.status-resolved { background: #e8f7ef; border-color: #c8ead8; color: #237a51; }
        .detail-pill.status-closed { background: #eef1f5; border-color: #dce1e8; color: #5f6978; }
        .detail-pill.status-rejected { background: #fdecef; border-color: #f5ccd3; color: #ad3445; }
        .detail-pill.priority-low { background: #edf4f8; color: #416f88; }
        .detail-pill.priority-medium { background: #fff7dc; color: #886810; }
        .detail-pill.priority-high { background: #fff0e4; color: #a85b20; }
        .detail-pill.priority-urgent { background: #fdecef; color: #ad3445; }

        .detail-section,
        .sidebar-card,
        .party-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 13px;
            box-shadow: 0 5px 17px rgba(36, 48, 75, .045);
        }

        .detail-section { margin-bottom: 20px; overflow: hidden; }
        .detail-section__header {
            background: #fbfcfe;
            border-bottom: 1px solid var(--line);
            gap: 11px;
            padding: 15px 19px;
        }
        .detail-section__header h5,
        .sidebar-card__header h5,
        .party-card h5 { color: #263248; font-size: 15px; font-weight: 700; margin: 0; }
        .detail-section__header small { color: var(--muted); display: block; margin-top: 3px; }
        .section-icon {
            align-items: center;
            border-radius: 9px;
            display: inline-flex;
            height: 38px;
            justify-content: center;
            width: 38px;
        }
        .section-icon--primary { background: #eef0ff; color: #5966cd; }
        .section-icon--info { background: #e8f4fd; color: #277eae; }
        .section-icon--warning { background: #fff5df; color: #b4771e; }
        .section-icon--success { background: #e8f7ef; color: #2e8c60; }
        .detail-section__body { padding: 20px; }

        .complaint-description {
            background: #f8fafc;
            border: 1px solid #e8edf3;
            border-radius: 10px;
            color: #344057;
            line-height: 1.95;
            min-height: 90px;
            padding: 17px;
            white-space: pre-line;
        }

        .detail-data-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 16px;
        }
        .detail-data-item { border: 1px solid #edf0f4; border-radius: 9px; padding: 12px 14px; }
        .detail-data-item span { color: var(--muted); display: block; font-size: 12px; margin-bottom: 4px; }
        .detail-data-item strong { color: #344057; font-size: 13px; }
        .last-response-box { background: #eef8f3; border-inline-start: 3px solid #36a26f; border-radius: 8px; margin-top: 16px; padding: 13px 15px; }
        .last-response-box > span { color: #287b55; font-size: 12px; font-weight: 700; }
        .last-response-box p { line-height: 1.7; margin: 7px 0 0; }

        .party-cards-grid { display: grid; gap: 16px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 20px; }
        .party-card { border-top: 3px solid #667eea; padding: 18px; }
        .party-card--against { border-top-color: #e99a25; }
        .party-card__title { gap: 11px; }
        .party-card__title > span { align-items: center; background: #eef0ff; border-radius: 10px; color: #5966cd; display: flex; height: 42px; justify-content: center; width: 42px; }
        .party-card--against .party-card__title > span { background: #fff5df; color: #a86e18; }
        .party-card__title small { color: var(--muted); font-size: 11px; }
        .party-card__name { color: #263248; font-size: 16px; font-weight: 700; margin: 17px 0 12px; }
        .party-card__meta { flex-wrap: wrap; gap: 8px 14px; }
        .party-card__meta a, .party-card__meta span { color: #65718a; font-size: 12px; text-decoration: none; }
        .party-card__meta i { margin-inline-end: 5px; }

        .relation-body { justify-content: space-between; }
        .relation-body > div { display: grid; gap: 4px; }
        .relation-type { color: var(--muted); font-size: 12px; }
        .relation-notes { background: #f8fafc; border-top: 1px solid var(--line); color: #5a667c; line-height: 1.7; padding: 13px 20px; }

        .organized-attachments { display: grid; gap: 10px; }
        .organized-attachment { border: 1px solid #e8ecf2; border-radius: 10px; justify-content: space-between; padding: 10px 12px; }
        .organized-attachment:hover { background: #fafbfe; border-color: #d9def0; }
        .organized-attachment__main { color: inherit; flex: 1; gap: 11px; min-width: 0; text-decoration: none; }
        .file-icon { align-items: center; background: #eef0ff; border-radius: 9px; color: #5966cd; display: flex; flex: 0 0 40px; height: 40px; justify-content: center; }
        .file-info { min-width: 0; }
        .file-info strong { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file-info small { color: var(--muted); display: block; margin-top: 4px; }
        .file-info small span { margin: 0 5px; }
        .organized-upload { background: #f8fafc; border: 1px dashed #cfd6e2; border-radius: 10px; margin-top: 16px; padding: 15px; }
        .organized-upload__head { justify-content: space-between; }
        .organized-upload__head small { color: var(--muted); display: block; font-size: 11px; margin-top: 3px; }
        .attachment-names-list { display: grid; gap: 8px; }
        .organized-empty { align-items: center; color: #8a94a5; display: flex; flex-direction: column; gap: 8px; justify-content: center; padding: 28px; }
        .organized-empty i { font-size: 28px; opacity: .55; }

        .organized-comments { display: grid; gap: 15px; }
        .organized-comment { align-items: flex-start; gap: 11px; }
        .comment-avatar { align-items: center; background: #eef0ff; border-radius: 50%; color: #5966cd; display: flex; flex: 0 0 38px; height: 38px; justify-content: center; }
        .organized-comment__content { background: #f8fafc; border: 1px solid #e8edf3; border-radius: 10px; flex: 1; padding: 13px 15px; }
        .organized-comment__head { justify-content: space-between; }
        .organized-comment__head small { color: var(--muted); display: block; font-size: 11px; margin-top: 2px; }
        .organized-comment__content p { line-height: 1.75; margin: 10px 0 0; }
        .organized-comment-form { border-top: 1px solid var(--line); margin-top: 20px; padding-top: 18px; }

        .detail-sidebar { display: grid; gap: 18px; position: sticky; top: 82px; }
        .sidebar-card { overflow: hidden; }
        .sidebar-card__header { background: #fbfcfe; border-bottom: 1px solid var(--line); padding: 15px 17px; }
        .sidebar-card__header h5 { align-items: center; display: flex; gap: 8px; }
        .sidebar-card__header h5 i { color: #667eea; }
        .sidebar-list { padding: 5px 17px; }
        .sidebar-list > div { align-items: center; border-bottom: 1px solid #f0f2f5; display: flex; justify-content: space-between; padding: 12px 0; }
        .sidebar-list > div:last-child { border-bottom: 0; }
        .sidebar-list > div > span { color: var(--muted); font-size: 12px; }
        .sidebar-list > div > strong:not(.detail-pill) { color: #354158; font-size: 12px; text-align: end; }
        .sidebar-actions { display: grid; gap: 8px; padding: 14px; }
        .sidebar-action { align-items: center; background: #f8fafc; border: 1px solid #e5e9ef; border-radius: 9px; color: #47536a; display: flex; gap: 10px; padding: 10px 12px; text-align: start; width: 100%; }
        .sidebar-action:hover { background: #eef0ff; border-color: #d9ddff; color: #5360c3; }
        .sidebar-action--success { background: #edf8f2; border-color: #d5eddf; color: #2a8057; }
        .sidebar-action--warning { background: #fff8e8; border-color: #f3e5bd; color: #946b18; }
        .sidebar-action--danger { background: #fdf0f0; border-color: #f2d4d4; color: #b23b3b; }

        .compact-timeline { padding: 16px 18px; }
        .compact-timeline__item { display: flex; gap: 12px; padding-bottom: 18px; position: relative; }
        .compact-timeline__item:not(:last-child)::before { background: #e1e5ec; content: ''; height: calc(100% - 7px); inset-inline-start: 5px; position: absolute; top: 13px; width: 1px; }
        .timeline-dot { background: #667eea; border: 3px solid #eef0ff; border-radius: 50%; flex: 0 0 11px; height: 11px; margin-top: 4px; position: relative; z-index: 1; }
        .timeline-dot--info { background: #268bd2; border-color: #e8f4fd; }
        .timeline-dot--success { background: #2e9d68; border-color: #e8f7ef; }
        .timeline-dot--muted { background: #8b95a5; border-color: #eef1f5; }
        .compact-timeline__item strong { display: block; font-size: 12px; }
        .compact-timeline__item small { color: var(--muted); display: block; font-size: 11px; margin-top: 4px; }

        @media (max-width: 1199.98px) {
            .detail-sidebar { position: static; }
        }

        @media (max-width: 767.98px) {
            .detail-toolbar, .detail-hero { align-items: flex-start; flex-direction: column; gap: 14px; }
            .detail-toolbar__actions { flex-wrap: wrap; }
            .detail-hero__badges { justify-content: flex-start; }
            .detail-hero { padding: 19px; }
            .detail-hero h2 { font-size: 20px; }
            .party-cards-grid, .detail-data-grid { grid-template-columns: 1fr; }
            .organized-upload__head { align-items: flex-start; flex-direction: column; gap: 12px; }
            .relation-body { align-items: flex-start; flex-direction: column; gap: 12px; }
        }

        @media print {
            .detail-toolbar, .detail-toolbar__actions, .quick-actions-organized, .organized-upload, .organized-comment-form, .delete-attachment-form, .deleteComment { display: none !important; }
            .detail-sidebar { position: static; }
            .detail-section, .sidebar-card, .party-card, .detail-hero { break-inside: avoid; box-shadow: none; }
        }
    </style>
@endsection

@section('content')
    @include('admin.ingaz.complaints.partials.show-organized')


    <!-- Modal تحويل التذكرة -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('ingaz.transfer_ticket') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="assignForm">
                        @csrf
                        <input type="hidden" name="complaint_id" value="{{ $complaint->id }}">

                        <div class="mb-3">
                            <label class="form-label">{{ __('ingaz.select_employee') }}</label>
                            <select name="assigned_to" class="form-select" required>
                                <option value="">{{ __('ingaz.select_employee') }}</option>
                                @foreach ($admins as $admin)
                                    <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @include('admin.ingaz.complaints.partials.notification-channels')
                        <div class="mb-3">
                            <label class="form-label">{{ __('ingaz.notes_optional') }}</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('buttons.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="saveAssign">{{ __('ingaz.transfer_ticket') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal تغيير الأولوية -->
    <div class="modal fade" id="priorityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('ingaz.change_priority') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="priorityForm">
                        @csrf
                        <input type="hidden" name="complaint_id" value="{{ $complaint->id }}">

                        <div class="mb-3">
                            <label class="form-label">{{ __('ingaz.new_priority') }}</label>
                            <select name="priority" class="form-select" required>
                                <option value="low" {{ $complaint->priority == 'low' ? 'selected' : '' }}>{{ __('ingaz.priority_low') }}
                                </option>
                                <option value="medium" {{ $complaint->priority == 'medium' ? 'selected' : '' }}>{{ __('ingaz.priority_medium') }}
                                </option>
                                <option value="high" {{ $complaint->priority == 'high' ? 'selected' : '' }}>{{ __('ingaz.priority_high') }}
                                </option>
                                <option value="urgent" {{ $complaint->priority == 'urgent' ? 'selected' : '' }}>{{ __('ingaz.priority_urgent') }}
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('ingaz.change_reason') }}</label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('buttons.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="savePriority">{{ __('buttons.save') }}</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="{{ asset('admin/assets/plugins/select2/dist/js/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            const complaintShowTranslations = {
                successTitle: @json(__('ingaz.success_title')),
                errorTitle: @json(__('ingaz.error_title')),
                genericError: @json(__('ingaz.unexpected_error')),
                successUpdateAdded: @json(__('ingaz.success_update_added')),
                updateAddError: @json(__('ingaz.update_add_error')),
                areYouSure: @json(__('ingaz.are_you_sure')),
                confirmMarkResolvedText: @json(__('ingaz.confirm_mark_resolved_text')),
                confirmMarkResolvedButton: @json(__('ingaz.confirm_mark_resolved_button')),
                confirmRejectTicketText: @json(__('ingaz.confirm_reject_ticket_text')),
                confirmRejectTicketButton: @json(__('ingaz.confirm_reject_ticket_button')),
                cancel: @json(__('buttons.cancel')),
                ticketAssignedSuccessfully: @json(__('ingaz.ticket_assigned_successfully')),
                ticketAssignError: @json(__('ingaz.ticket_assign_error')),
                priorityChangedSuccessfully: @json(__('ingaz.priority_changed_successfully')),
                priorityChangeError: @json(__('ingaz.priority_change_error')),
                cannotUndoAction: @json(__('ingaz.cannot_undo_action')),
                yesDelete: @json(__('ingaz.yes_delete')),
                ticketDeletedSuccessfully: @json(__('ingaz.ticket_deleted_successfully')),
                deleteError: @json(__('ingaz.delete_error')),
                statusUpdatedSuccessfully: @json(__('ingaz.status_updated_successfully_short')),
                deleteComment: @json(__('ingaz.delete_comment')),
                confirmDeleteComment: @json(__('ingaz.confirm_delete_this_attachment')),
                confirm: @json(__('ingaz.confirm')),
                ok: @json(__('ingaz.ok')),
                commentDeletedSuccessfully: @json(__('ingaz.comment_deleted_successfully')),
            };

            $('#complaintAttachmentInput').on('change', function() {
                const $container = $('#attachmentNamesFields').empty();
                $('#uploadAttachmentsButton').prop('disabled', !this.files || this.files.length === 0);
                Array.from(this.files || []).forEach(function(file, index) {
                    const $field = $('<div>', { class: 'attachment-name-field mb-2' });
                    $('<label>', {
                        class: 'form-label small fw-semibold mb-1',
                        text: file.name
                    }).appendTo($field);
                    $('<input>', {
                        type: 'text',
                        name: 'attachment_names[' + index + ']',
                        class: 'form-control form-control-sm',
                        maxlength: 255,
                        placeholder: 'اسم المرفق (اختياري)'
                    }).appendTo($field);
                    $container.append($field);
                });
            });

            // إضافة تعليق
            $('#addCommentForm').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    url: "{{ route('complaints.add-comment') }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            if (response.notification_results && Object.keys(response.notification_results).length) alert(Object.values(response.notification_results).join('\n'));
                            if (typeof successToster !== 'undefined') {
                                successToster(complaintShowTranslations.successTitle, complaintShowTranslations.successUpdateAdded);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.success(complaintShowTranslations.successUpdateAdded);
                            } else {
                                alert(complaintShowTranslations.successUpdateAdded);
                            }
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            if (typeof errorToster !== 'undefined') {
                                errorToster(complaintShowTranslations.errorTitle, response.message || complaintShowTranslations.genericError);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.error(response.message || complaintShowTranslations.genericError);
                            } else {
                                alert(response.message || complaintShowTranslations.genericError);
                            }
                        }
                    },
                    error: function() {
                        if (typeof errorToster !== 'undefined') {
                            errorToster(complaintShowTranslations.errorTitle, complaintShowTranslations.updateAddError);
                        } else if (typeof toastr !== 'undefined') {
                            toastr.error(complaintShowTranslations.updateAddError);
                        } else {
                            alert(complaintShowTranslations.updateAddError);
                        }
                    }
                });
            });

            // وضع علامة تم الحل
            $('#markResolved').click(function() {
                Swal.fire({
                    title: complaintShowTranslations.areYouSure,
                    text: complaintShowTranslations.confirmMarkResolvedText,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: complaintShowTranslations.confirmMarkResolvedButton,
                    cancelButtonText: complaintShowTranslations.cancel
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateStatus('resolved');
                    }
                });
            });

            // رفض التذكرة
            $('#rejectTicket').click(function() {
                Swal.fire({
                    title: complaintShowTranslations.areYouSure,
                    text: complaintShowTranslations.confirmRejectTicketText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: complaintShowTranslations.confirmRejectTicketButton,
                    cancelButtonText: complaintShowTranslations.cancel
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateStatus('rejected');
                    }
                });
            });

            // تحويل التذكرة
            $('.assign-ticket').click(function() {
                $('#assignModal').modal('show');
            });

            $('#saveAssign').click(function() {
                $.ajax({
                    url: "{{ route('complaints.assign') }}",
                    type: 'POST',
                    data: $('#assignForm').serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#assignModal').modal('hide');
                            if (response.notification_results && Object.keys(response.notification_results).length) {
                                alert(Object.values(response.notification_results).join('\n'));
                            }
                            if (typeof successToster !== 'undefined') {
                                successToster(complaintShowTranslations.successTitle, complaintShowTranslations.ticketAssignedSuccessfully);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.success(complaintShowTranslations.ticketAssignedSuccessfully);
                            } else {
                                alert(complaintShowTranslations.ticketAssignedSuccessfully);
                            }
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            if (typeof errorToster !== 'undefined') {
                                errorToster(complaintShowTranslations.errorTitle, response.message || complaintShowTranslations.genericError);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.error(response.message || complaintShowTranslations.genericError);
                            } else {
                                alert(response.message || complaintShowTranslations.genericError);
                            }
                        }
                    },
                    error: function() {
                        if (typeof errorToster !== 'undefined') {
                            errorToster(complaintShowTranslations.errorTitle, complaintShowTranslations.ticketAssignError);
                        } else if (typeof toastr !== 'undefined') {
                            toastr.error(complaintShowTranslations.ticketAssignError);
                        } else {
                            alert(complaintShowTranslations.ticketAssignError);
                        }
                    }
                });
            });

            // تغيير الأولوية
            $('#changePriority').click(function() {
                $('#priorityModal').modal('show');
            });

            $('#savePriority').click(function() {
                $.ajax({
                    url: "{{ route('complaints.change-priority') }}",
                    type: 'POST',
                    data: $('#priorityForm').serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#priorityModal').modal('hide');
                            if (typeof successToster !== 'undefined') {
                                successToster(complaintShowTranslations.successTitle, complaintShowTranslations.priorityChangedSuccessfully);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.success(complaintShowTranslations.priorityChangedSuccessfully);
                            } else {
                                alert(complaintShowTranslations.priorityChangedSuccessfully);
                            }
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            if (typeof errorToster !== 'undefined') {
                                errorToster(complaintShowTranslations.errorTitle, response.message || complaintShowTranslations.genericError);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.error(response.message || complaintShowTranslations.genericError);
                            } else {
                                alert(response.message || complaintShowTranslations.genericError);
                            }
                        }
                    },
                    error: function() {
                        if (typeof errorToster !== 'undefined') {
                            errorToster(complaintShowTranslations.errorTitle, complaintShowTranslations.priorityChangeError);
                        } else if (typeof toastr !== 'undefined') {
                            toastr.error(complaintShowTranslations.priorityChangeError);
                        } else {
                            alert(complaintShowTranslations.priorityChangeError);
                        }
                    }
                });
            });

            // حذف التذكرة
            $('#deleteTicket').click(function() {
                Swal.fire({
                    title: complaintShowTranslations.areYouSure,
                    text: complaintShowTranslations.cannotUndoAction,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: complaintShowTranslations.yesDelete,
                    cancelButtonText: complaintShowTranslations.cancel
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/complaints/{{ $complaint->id }}',
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    if (typeof successToster !== 'undefined') {
                                        successToster(complaintShowTranslations.successTitle,
                                            complaintShowTranslations.ticketDeletedSuccessfully);
                                    } else if (typeof toastr !== 'undefined') {
                                        toastr.success(complaintShowTranslations.ticketDeletedSuccessfully);
                                    } else {
                                        alert(complaintShowTranslations.ticketDeletedSuccessfully);
                                    }
                                    setTimeout(() => {
                                        window.location.href =
                                            '{{ route('complaints.index') }}';
                                    }, 1500);
                                } else {
                                    if (typeof errorToster !== 'undefined') {
                                        errorToster(complaintShowTranslations.errorTitle, response.message ||
                                            complaintShowTranslations.genericError);
                                    } else if (typeof toastr !== 'undefined') {
                                        toastr.error(response.message || complaintShowTranslations.genericError);
                                    } else {
                                        alert(response.message || complaintShowTranslations.genericError);
                                    }
                                }
                            },
                            error: function() {
                                if (typeof errorToster !== 'undefined') {
                                    errorToster(complaintShowTranslations.errorTitle, complaintShowTranslations.deleteError);
                                } else if (typeof toastr !== 'undefined') {
                                    toastr.error(complaintShowTranslations.deleteError);
                                } else {
                                    alert(complaintShowTranslations.deleteError);
                                }
                            }
                        });
                    }
                });
            });

            // تحديث الحالة
            function updateStatus(status) {
                $.ajax({
                    url: "{{ route('complaints.update-status') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        complaint_id: {{ $complaint->id }},
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            if (typeof successToster !== 'undefined') {
                                successToster(complaintShowTranslations.successTitle, complaintShowTranslations.statusUpdatedSuccessfully);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.success(complaintShowTranslations.statusUpdatedSuccessfully);
                            } else {
                                alert(complaintShowTranslations.statusUpdatedSuccessfully);
                            }
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            if (typeof errorToster !== 'undefined') {
                                errorToster(complaintShowTranslations.errorTitle, response.message || complaintShowTranslations.genericError);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.error(response.message || complaintShowTranslations.genericError);
                            } else {
                                alert(response.message || complaintShowTranslations.genericError);
                            }
                        }
                    },
                    error: function() {
                        if (typeof errorToster !== 'undefined') {
                            errorToster(complaintShowTranslations.errorTitle, complaintShowTranslations.genericError);
                        } else if (typeof toastr !== 'undefined') {
                            toastr.error(complaintShowTranslations.genericError);
                        } else {
                            alert(complaintShowTranslations.genericError);
                        }
                    }
                });
            }

            $('.delete-attachment-form').on('submit', function(e) {
                e.preventDefault();
                const form = this;

                Swal.fire({
                    title: complaintShowTranslations.areYouSure,
                    text: complaintShowTranslations.confirmDeleteComment,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: complaintShowTranslations.confirm,
                    cancelButtonText: complaintShowTranslations.cancel
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            $(document).on('click', '.deleteComment', function(e) {
                e.preventDefault();
                var commentId = $(this).data('comment-id');

                Swal.fire({
                    title: complaintShowTranslations.deleteComment,
                    text: complaintShowTranslations.areYouSure,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: complaintShowTranslations.confirm,
                    cancelButtonText: complaintShowTranslations.cancel
                }).then((result) => {
                    if (result.isConfirmed) {
                        const commentId = $(this).data('comment-id');
                        $.ajax({
                            url: '/admin/complaints/complaint-comment/' + commentId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.code == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: complaintShowTranslations.commentDeletedSuccessfully,
                                        confirmButtonText: complaintShowTranslations.ok
                                    });
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1000);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: complaintShowTranslations.deleteError,
                                        confirmButtonText: complaintShowTranslations.ok
                                    });

                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: complaintShowTranslations.deleteError,
                                    confirmButtonText: complaintShowTranslations.ok
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
