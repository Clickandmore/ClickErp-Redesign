@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('ingaz.ticket_details') }} #{{ str_pad($complaint->id, 6, '0', STR_PAD_LEFT) }}
@endsection

@section('style')
    <style>
        /* تصميم صفحة التفاصيل */
        .ticket-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .ticket-number-large {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .ticket-meta-header {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .ticket-meta-header .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ticket-meta-header .meta-item i {
            font-size: 18px;
            opacity: 0.8;
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            padding-left: 70px;
            margin-bottom: 30px;
        }

        .timeline-icon {
            position: absolute;
            left: 20px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            border: 3px solid #667eea;
            z-index: 1;
        }

        .timeline-icon.success {
            border-color: #28a745;
        }

        .timeline-icon.warning {
            border-color: #ffc107;
        }

        .timeline-icon.danger {
            border-color: #dc3545;
        }

        .timeline-icon.info {
            border-color: #17a2b8;
        }

        .timeline-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .timeline-title {
            font-weight: 600;
            color: #495057;
        }

        .timeline-time {
            font-size: 12px;
            color: #6c757d;
        }

        /* معلومات الشكوى */
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .info-card h5 {
            color: #495057;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
        }

        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            flex: 0 0 150px;
            font-weight: 600;
            color: #6c757d;
        }

        .info-value {
            flex: 1;
            color: #495057;
        }

        /* نموذج التحديث */
        .update-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        /* المرفقات */
        .attachment-item {
            display: inline-block;
            margin: 5px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s;
        }

        .attachment-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .attachment-item i {
            margin-left: 5px;
        }

        /* الأولوية والحالة */
        .priority-badge-large {
            padding: 8px 20px;
            font-size: 14px;
            border-radius: 20px;
            font-weight: 600;
        }

        .status-badge-large {
            padding: 8px 20px;
            font-size: 14px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* أزرار الإجراءات */
        .action-buttons-top {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        /* التعليقات */
        .comment-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 3px solid #667eea;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .comment-author {
            font-weight: 600;
            color: #495057;
        }

        .comment-time {
            font-size: 12px;
            color: #6c757d;
        }

        .comment-body {
            color: #495057;
            line-height: 1.6;
        }

        /* ==============================================
                       Comment Section - Arabic RTL - Attractive Design
                       ============================================== */

        .comment-section {
            direction: rtl;
            font-family: 'Tajawal', 'Cairo', system-ui, sans-serif;
            color: #2d3748;
        }

        .comment-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 24px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.25s ease;
        }

        .comment-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .comment-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #edf2f7;
        }

        .comment-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: bold;
        }

        .author-name {
            font-weight: 600;
            color: #1e293b;
        }

        .comment-time {
            font-size: 0.875rem;
            color: #64748b;
        }

        .comment-body {
            padding: 20px;
            line-height: 1.7;
            font-size: 1.05rem;
            color: #334155;
        }

        .comment-attachments {
            padding: 0 20px 20px;
            border-top: 1px solid #edf2f7;
        }

        /* للـ empty state */
        .no-comments {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
            font-size: 1.1rem;
        }

        .no-comments i {
            font-size: 3.5rem;
            opacity: 0.4;
            margin-bottom: 16px;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="ticket-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="ticket-number-large">
                        {{ __('ingaz.ticket_label') }} #{{ str_pad($complaint->id, 6, '0', STR_PAD_LEFT) }}
                    </div>
                    <h3 class="mb-3">{{ $complaint->subject ?? __('ingaz.no_subject') }}</h3>
                    <div class="ticket-meta-header">
                        <div class="meta-item">
                            <i class="fas fa-user"></i>
                            <span>{{ $complaint->complainant_name ?? __('ingaz.not_specified') }}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-phone"></i>
                            <span>{{ $complaint->complainant_phone ?? __('ingaz.not_specified') }}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <span>{{ $complaint->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                        @if ($complaint->assignedTo)
                            <div class="meta-item">
                                <i class="fas fa-user-tie"></i>
                                <span>{{ $complaint->assignedTo->name }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="mb-3">
                        @php
                            $priorityBadgeHtml = $complaint->priorityBadge ?? '';
                            $statusBadgeHtml = $complaint->statusBadge ?? '';

                            $priorityHasTag =
                                is_string($priorityBadgeHtml) &&
                                (strpos($priorityBadgeHtml, '<') !== false ||
                                    strpos($priorityBadgeHtml, '&lt;') !== false);
                            $statusHasTag =
                                is_string($statusBadgeHtml) &&
                                (strpos($statusBadgeHtml, '<') !== false || strpos($statusBadgeHtml, '&lt;') !== false);

                            if ($priorityHasTag) {
                                $priorityBadgeHtml = html_entity_decode($priorityBadgeHtml);
                            }
                            if ($statusHasTag) {
                                $statusBadgeHtml = html_entity_decode($statusBadgeHtml);
                            }

                            if (!$priorityHasTag) {
                                $priorityBadgeHtml =
                                    '<span class="' .
                                    e($complaint->priorityBadge ?? 'badge text-bg-light text-dark') .
                                    ' priority-badge-large">' .
                                    e($complaint->priorityText ?? ($complaint->priority ?? __('ingaz.not_specified'))) .
                                    '</span>';
                            }

                            if (!$statusHasTag) {
                                $statusTextMap = [
                                    'pending' => __('ingaz.complaint_status_pending'),
                                    'in_progress' => __('ingaz.complaint_status_in_progress'),
                                    'resolved' => __('ingaz.complaint_status_resolved'),
                                    'closed' => __('ingaz.complaint_status_closed'),
                                    'rejected' => __('ingaz.complaint_status_rejected'),
                                ];
                                $statusClassMap = [
                                    'pending' => 'text-bg-warning',
                                    'in_progress' => 'text-bg-info',
                                    'resolved' => 'text-bg-success',
                                    'closed' => 'text-bg-secondary',
                                    'rejected' => 'text-bg-danger',
                                ];
                                $statusKey = $complaint->status ?? '';
                                $statusText = $statusTextMap[$statusKey] ?? $statusKey;
                                $statusClass = $statusClassMap[$statusKey] ?? 'text-bg-light text-dark';

                                $statusBadgeHtml =
                                    '<span class="badge ' .
                                    e($statusClass) .
                                    ' status-badge-large">' .
                                    e($statusText) .
                                    '</span>';
                            }
                        @endphp
                        {!! $priorityBadgeHtml !!}
                        {!! $statusBadgeHtml !!}
                    </div>
                    <div class="action-buttons-top">
                        @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_EDIT))
                            <a class="btn btn-light" href="{{ route('complaints.edit', $complaint->id) }}">
                                <i class="fas fa-edit me-2"></i>{{ __('buttons.edit') }}
                            </a>
                        @endif

                        @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_DELETE))
                            <button class="btn btn-light text-danger" id="deleteTicket">
                                <i class="fas fa-trash me-2"></i>{{ __('ingaz.delete') }}
                            </button>
                        @endif

                        <button class="btn btn-light" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>{{ __('ingaz.print') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- العمود الأيسر - معلومات الشكوى -->
            <div class="col-md-8">
                <!-- تفاصيل الشكوى -->
                <div class="info-card">
                    <h5><i class="fas fa-info-circle me-2"></i>{{ __('ingaz.complaint_details') }}</h5>

                    <div class="info-row">
                        <div class="info-label">{{ __('ingaz.complaint_type') }}:</div>
                        <div class="info-value">
                            {{ $complaint->complaintType->name ?? __('ingaz.not_specified') }}
                        </div>
                    </div>

                    @if ($complaint->contract)
                        <div class="info-row">
                            <div class="info-label">عقد الإيواء:</div>
                            <div class="info-value">
                                <a href="{{ route('ShelterContract.show', $complaint->contract->id) }}">
                                    #{{ $complaint->contract->id }}
                                </a>
                            </div>
                        </div>
                    @endif

                    <div class="info-row">
                        <div class="info-label">{{ __('ingaz.description') }}:</div>
                        <div class="info-value">
                            {{ $complaint->details ?? __('ingaz.no_description') }}
                        </div>
                    </div>

                    @if ($complaint->maid_passport || $complaint->maid_nationality_id)
                        <div class="info-row">
                            <div class="info-label">{{ __('ingaz.worker_info') }}:</div>
                            <div class="info-value">
                                @if ($complaint->maid_passport)
                                    {{ __('ingaz.passport') }}: {{ $complaint->maid_passport }}
                                @endif
                                @if ($complaint->maidNationality)
                                    - {{ __('ingaz.nationality') }}: {{ $complaint->maidNationality->title }}
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($complaint->attachments && count($complaint->attachments) > 0)
                        <div class="info-row">
                            <div class="info-label">{{ __('ingaz.attachments') }}:</div>
                            <div class="info-value">
                                @foreach ($complaint->attachments as $attachment)
                                    <a href="{{ Storage::url($attachment) }}" target="_blank" class="attachment-item">
                                        <i class="fas fa-paperclip"></i>
                                        {{ __('ingaz.attachment_number', ['number' => $loop->iteration]) }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Timeline التحديثات -->
                <div class="info-card">
                    <h5><i class="fas fa-history me-2"></i>{{ __('ingaz.updates_log') }}</h5>

                    <div class="timeline">
                        <!-- إنشاء التذكرة -->
                        <div class="timeline-item">
                            <div class="timeline-icon"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <div class="timeline-title">{{ __('ingaz.ticket_created') }}</div>
                                    <div class="timeline-time">{{ $complaint->created_at->diffForHumans() }}</div>
                                </div>
                                <div>
                                    {{ __('ingaz.ticket_created_by', ['name' => $complaint->user->name ?? __('ingaz.system')]) }}
                                </div>
                            </div>
                        </div>

                        @if (isset($complaint->updates) && count($complaint->updates) > 0)
                            @foreach ($complaint->updates as $update)
                                <div class="timeline-item">
                                    <div class="timeline-icon {{ $update->type_class }}"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <div class="timeline-title">{{ $update->title }}</div>
                                            <div class="timeline-time">{{ $update->created_at->diffForHumans() }}</div>
                                        </div>
                                        <div>
                                            {{ $update->description }}
                                            @if ($update->admin)
                                                <br><small class="text-muted">{{ __('ingaz.by') }}: {{ $update->admin->name }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        @if ($complaint->resolved_at)
                            <div class="timeline-item">
                                <div class="timeline-icon success"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <div class="timeline-title">{{ __('ingaz.complaint_resolved') }}</div>
                                        <div class="timeline-time">{{ $complaint->resolved_at->diffForHumans() }}</div>
                                    </div>
                                    <div>
                                        {{ __('ingaz.complaint_resolved_by', ['name' => $complaint->resolvedBy->name ?? __('ingaz.not_specified')]) }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- التعليقات والردود -->
                <div class="info-card">
                    <h5><i class="fas fa-comments me-2"></i>{{ __('ingaz.comments_and_replies') }}</h5>

                    <div id="commentsSection" class="comment-section">
                        @if (isset($complaint->comments) && count($complaint->comments) > 0)
                            @foreach ($complaint->comments as $comment)
                                <div class="comment-card">
                                    <div class="comment-header">
                                        <div class="comment-author">
                                            <div class="author-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div class="author-name">{{ $comment->admin->name ?? __('ingaz.not_specified') }}</div>
                                                <div class="comment-time">{{ $comment->created_at->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                        @if (auth('admin')->id() == $comment->added_by)
                                            <div class="">
                                                <button class="btn btn-danger deleteComment"
                                                    data-comment-id="{{ $comment->id }}">
                                                    {{ __('ingaz.delete_comment') }}
                                                </button>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="comment-body">
                                        {!! nl2br(e($comment->comment)) !!}
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="no-comments">
                                <i class="far fa-comment-dots"></i>
                                <p>{{ __('ingaz.no_comments_yet') }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- نموذج إضافة تعليق -->
                    @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_RESPONSE))
                        <div class="update-form">
                            <h6 class="mb-3">{{ __('ingaz.add_comment_or_update') }}</h6>
                            <form id="addCommentForm">
                                @csrf
                                <input type="hidden" name="complaint_id" value="{{ $complaint->id }}">

                                <div class="mb-3">
                                    <textarea name="comment" class="form-control" rows="3" placeholder="{{ __('ingaz.write_comment_here') }}" required></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('ingaz.status_change_optional') }}</label>
                                        <select name="status" class="form-select">
                                            <option value="">{{ __('ingaz.no_change') }}</option>
                                            <option value="pending">{{ __('ingaz.complaint_status_in_progress') }}</option>
                                            <option value="resolved">{{ __('ingaz.complaint_status_resolved') }}</option>
                                            {{-- <option value="closed">إغلاق التذكرة</option> --}}
                                            <option value="rejected">{{ __('ingaz.complaint_status_rejected') }}</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('ingaz.assign_to_another_employee') }}</label>
                                        <select name="assigned_to" class="form-select">
                                            <option value="">{{ __('ingaz.no_change') }}</option>
                                            @foreach ($admins as $admin)
                                                <option value="{{ $admin->id }}"
                                                    {{ $complaint->assigned_to == $admin->id ? 'selected' : '' }}>
                                                    {{ $admin->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>{{ __('ingaz.send_update') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <!-- العمود الأيمن - معلومات جانبية -->
            <div class="col-md-4">
                <!-- معلومات العميل -->
                <div class="info-card">
                    <h5><i class="fas fa-user me-2"></i>{{ __('ingaz.complainant_info') }}</h5>

                    <div class="info-row">
                        <div class="info-label">{{ __('ingaz.name') }}:</div>
                        <div class="info-value">{{ $complaint->complainant_name ?? __('ingaz.not_specified') }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">{{ __('ingaz.phone') }}:</div>
                        <div class="info-value">
                            @if ($complaint->complainant_phone)
                                <a href="tel:{{ $complaint->complainant_phone }}">{{ $complaint->complainant_phone }}</a>
                            @else
                                {{ __('ingaz.not_specified') }}
                            @endif
                        </div>
                    </div>

                    @if ($complaint->user)
                        <div class="info-row">
                            <div class="info-label">{{ __('ingaz.registered_client') }}:</div>
                            <div class="info-value">
                                <a href="{{ route('clients.show', $complaint->user_id) }}">
                                    {{ $complaint->user->name }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- معلومات التذكرة -->
                <div class="info-card">
                    <h5><i class="fas fa-ticket-alt me-2"></i>{{ __('ingaz.ticket_info') }}</h5>

                    <div class="info-row">
                        <div class="info-label">{{ __('ingaz.ticket_number') }}:</div>
                        <div class="info-value">#{{ str_pad($complaint->id, 6, '0', STR_PAD_LEFT) }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">{{ __('ingaz.created_date') }}:</div>
                        <div class="info-value">{{ $complaint->created_at->format('Y-m-d H:i') }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">{{ __('ingaz.last_updated_at') }}:</div>
                        <div class="info-value">{{ $complaint->updated_at->format('Y-m-d H:i') }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">{{ __('ingaz.status') }}:</div>
                        <div class="info-value">{!! $statusBadgeHtml ?? '' !!}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">{{ __('ingaz.priority') }}:</div>
                        <div class="info-value">{!! $priorityBadgeHtml ?? '' !!}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">{{ __('ingaz.assigned_employee') }}:</div>
                        <div class="info-value">
                            {{ $complaint->assignedTo->name ?? __('ingaz.not_specified') }}
                        </div>
                    </div>
                </div>

                <!-- الإجراءات السريعة -->
                <div class="info-card">
                    <h5><i class="fas fa-bolt me-2"></i>{{ __('ingaz.quick_actions') }}</h5>

                    <div class="d-grid gap-2">
                        @if ($complaint->status != 'resolved')
                            <button class="btn btn-success" id="markResolved">
                                <i class="fas fa-check me-2"></i>{{ __('ingaz.mark_resolved') }}
                            </button>
                        @endif

                        @if ($complaint->status != 'closed')
                            <button class="btn btn-secondary" id="closeTicket">
                                <i class="fas fa-times me-2"></i>{{ __('ingaz.close_ticket') }}
                            </button>
                        @endif

                        <button class="btn btn-info" id="assignTicket">
                            <i class="fas fa-user-tag me-2"></i>{{ __('ingaz.assign_to_another_employee') }}
                        </button>

                        <button class="btn btn-warning" id="changePriority">
                            <i class="fas fa-exclamation-triangle me-2"></i>{{ __('ingaz.change_priority') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                confirmCloseTicketText: @json(__('ingaz.confirm_close_ticket_text')),
                confirmCloseTicketButton: @json(__('ingaz.confirm_close_ticket_button')),
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

            // إضافة تعليق
            $('#addCommentForm').submit(function(e) {
                e.preventDefault();

                $.ajax({
                    url: "{{ route('complaints.add-comment') }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
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

            // إغلاق التذكرة
            $('#closeTicket').click(function() {
                Swal.fire({
                    title: complaintShowTranslations.areYouSure,
                    text: complaintShowTranslations.confirmCloseTicketText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: complaintShowTranslations.confirmCloseTicketButton,
                    cancelButtonText: complaintShowTranslations.cancel
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateStatus('closed');
                    }
                });
            });

            // تحويل التذكرة
            $('#assignTicket').click(function() {
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
