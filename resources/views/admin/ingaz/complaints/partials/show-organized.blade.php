@php
    $statusLabels = [
        'pending' => __('ingaz.complaint_status_pending'),
        'in_progress' => __('ingaz.complaint_status_in_progress'),
        'resolved' => __('ingaz.complaint_status_resolved'),
        'closed' => __('ingaz.complaint_status_closed'),
        'rejected' => __('ingaz.complaint_status_rejected'),
    ];
    $priorityLabels = [
        'low' => __('ingaz.priority_low'),
        'medium' => __('ingaz.priority_medium'),
        'high' => __('ingaz.priority_high'),
        'urgent' => __('ingaz.priority_urgent'),
    ];
    $statusKey = $complaint->status ?: 'pending';
    $priorityKey = $complaint->priority ?: 'medium';
    $relatedRecord = $relatedRecord ?? null;
    // قناة التقديم: enum معروف، أو نص حر قادم من الاستيراد
    $submissionMethod = \App\Enums\ingaz\ComplaintSubmissionMethodEnum::tryFrom((string) $complaint->submission_method);
    $submissionMethodLabel = \App\Enums\ingaz\ComplaintSubmissionMethodEnum::label($complaint->submission_method);
    // عقد الإيواء يُعرف من حالته، أما بقية عقود هذا الجدول فهي عقود استقدام.
    $contractUrl = $complaint->contract
        ? ($complaint->contract->status === 'shelter'
            ? route('ShelterContract.show', $complaint->contract->id)
            : route('contracts.details', $complaint->contract->id))
        : null;
@endphp

<div class="complaint-detail-page">
    <div class="detail-toolbar">
        <div>
            <a href="{{ route('complaints.index') }}" class="detail-back-link">
                <i class="fas fa-arrow-right"></i> الشكاوى
            </a>
            <span class="text-muted mx-2">/</span>
            <span class="text-muted">#{{ str_pad($complaint->id, 6, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="detail-toolbar__actions">
            @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_EDIT))
                <a class="btn btn-primary btn-sm" href="{{ route('complaints.edit', $complaint->id) }}">
                    <i class="fas fa-edit me-1"></i>{{ __('buttons.edit') }}
                </a>
            @endif
            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.print()">
                <i class="fas fa-print me-1"></i>{{ __('ingaz.print') }}
            </button>
            @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_DELETE))
                <button class="btn btn-outline-danger btn-sm" type="button" id="deleteTicket">
                    <i class="fas fa-trash me-1"></i>{{ __('ingaz.delete') }}
                </button>
            @endif
        </div>
    </div>

    <section class="detail-hero status-accent-{{ $statusKey }}">
        <div class="detail-hero__main">
            <div class="detail-ticket-number">{{ __('ingaz.ticket_label') }} #{{ str_pad($complaint->id, 6, '0', STR_PAD_LEFT) }}</div>
            <h2>{{ $complaint->subject ?: __('ingaz.no_subject') }}</h2>
            <div class="detail-hero__meta">
                <span><i class="far fa-user"></i>{{ $complaint->complainant_name ?: __('ingaz.not_specified') }}</span>
                <span><i class="far fa-calendar"></i>{{ $complaint->created_at->format('Y-m-d H:i') }}</span>
                <span><i class="fas fa-user-tie"></i>{{ $complaint->assignedTo?->name ?: __('ingaz.not_specified') }}</span>
            </div>
        </div>
        <div class="detail-hero__badges">
            <span class="detail-pill status-{{ $statusKey }}">
                <i class="fas fa-circle"></i>{{ $statusLabels[$statusKey] ?? $statusKey }}
            </span>
            <span class="detail-pill priority-{{ $priorityKey }}">
                <i class="fas fa-flag"></i>{{ $priorityLabels[$priorityKey] ?? $priorityKey }}
            </span>
        </div>
    </section>

    @include('admin.ingaz.complaints.partials.management')

    <div class="row g-4 align-items-start">
        <main class="col-xl-8">
            <section class="detail-section">
                <div class="detail-section__header">
                    <span class="section-icon section-icon--primary"><i class="fas fa-align-right"></i></span>
                    <div>
                        <h5>محتوى الشكوى</h5>
                        <small>وصف الشكوى والبيانات المرتبطة بها</small>
                    </div>
                </div>
                <div class="detail-section__body">
                    <div class="complaint-description">{{ $complaint->details ?: $complaint->message ?: __('ingaz.no_description') }}</div>

                    <div class="detail-data-grid">
                        <div class="detail-data-item">
                            <span>نوع الشكوى</span>
                            <strong>{{ $complaint->complaintType?->name ?: __('ingaz.not_specified') }}</strong>
                        </div>
                        <div class="detail-data-item">
                            <span>طريقة التقديم</span>
                            <strong>
                                @if ($submissionMethodLabel)
                                    <span class="badge bg-{{ $submissionMethod?->color() ?: 'secondary' }}">
                                        <i class="fas {{ $submissionMethod?->icon() ?: 'fa-circle-info' }} me-1"></i>{{ $submissionMethodLabel }}
                                    </span>
                                @else
                                    {{ __('ingaz.not_specified') }}
                                @endif
                            </strong>
                        </div>
                    </div>

                    @if ($complaint->last_response)
                        <div class="last-response-box">
                            <span><i class="fas fa-reply me-1"></i>آخر رد</span>
                            <p>{{ $complaint->last_response }}</p>
                        </div>
                    @endif
                </div>
            </section>

            @if ($maidParty)
                <section class="detail-section">
                    <div class="detail-section__header"><span class="section-icon"><i class="fas fa-user"></i></span><h5>بيانات العاملة</h5></div>
                    <div class="detail-section__body detail-data-grid">
                        <div class="detail-data-item"><span>العاملة</span><strong>{{ $maidParty['name'] }}</strong></div>
                        <div class="detail-data-item"><span>الوكيل الخارجي</span><strong>{{ $maidParty['office'] ?: 'غير محدد' }}</strong></div>
                        <div class="detail-data-item"><span>جواز العاملة</span><strong>{{ $maidParty['passport'] ?: 'غير محدد' }}</strong></div>
                        <div class="detail-data-item"><span>الجنسية</span><strong>{{ $maidParty['nationality'] ?: 'غير محدد' }}</strong></div>
                    </div>
                </section>
            @endif

            <div class="party-cards-grid">
                <section class="party-card party-card--complainant">
                    <div class="party-card__title">
                        <span><i class="fas fa-user"></i></span>
                        <div><small>الطرف الأول</small><h5>مقدم الشكوى</h5></div>
                    </div>
                    <div class="party-card__name">{{ $complaint->complainant_name ?: __('ingaz.not_specified') }}</div>
                    <div class="party-card__meta">
                        @if ($complaint->complainant_phone)
                            <a href="tel:{{ $complaint->complainant_phone }}"><i class="fas fa-phone"></i>{{ $complaint->complainant_phone }}</a>
                        @else
                            <span><i class="fas fa-phone"></i>{{ __('ingaz.not_specified') }}</span>
                        @endif
                        @if ($complaint->user)
                            <a href="{{ route('clients.show', $complaint->user_id) }}"><i class="fas fa-external-link-alt"></i>ملف العميل</a>
                        @endif
                    </div>
                </section>

                <section class="party-card party-card--against">
                    <div class="party-card__title">
                        <span><i class="fas fa-user-shield"></i></span>
                        <div><small>الطرف الثاني</small><h5>المشتكى عليه</h5></div>
                    </div>
                    <div class="party-card__name">{{ $againstParty['name'] ?: __('ingaz.not_specified') }}</div>
                    <div class="party-card__meta">
                        <span><i class="fas fa-tag"></i>{{ $complaint->against_type ?: $complaint->complaintType?->against_type ?: __('ingaz.not_specified') }}</span>
                        @if ($againstParty['phone'])
                            <a href="tel:{{ $againstParty['phone'] }}"><i class="fas fa-phone"></i>{{ $againstParty['phone'] }}</a>
                        @endif
                    </div>
                </section>
            </div>

            @if ($relatedRecord)
                <section class="detail-section relation-section">
                    <div class="detail-section__header">
                        <span class="section-icon section-icon--info"><i class="fas fa-link"></i></span>
                        <div><h5>الربط والمرجع</h5><small>السجل المرتبط بهذه الشكوى</small></div>
                    </div>
                    <div class="detail-section__body relation-body">
                        <div>
                            <span class="relation-type">نوع السجل المرتبط</span>
                            <span class="relation-badge relation-badge--{{ $relatedRecord['type'] ?? 'reference' }}">
                                <i class="fas {{ $relatedRecord['icon'] ?? 'fa-link' }}"></i>
                                <span>{{ $relatedRecord['label'] }}</span>
                                @if ($relatedRecord['reference'] !== '')
                                    <span class="relation-badge__ref">{{ $relatedRecord['reference'] }}</span>
                                @endif
                            </span>
                        </div>
                        @if ($relatedRecord['url'])
                            <a href="{{ $relatedRecord['url'] }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i>{{ $relatedRecord['action_label'] ?: 'عرض المرتبط' }}
                            </a>
                        @endif
                    </div>
                    @if ($complaint->relation_notes)
                        <div class="relation-notes">{{ $complaint->relation_notes }}</div>
                    @endif
                </section>
            @endif

            @if ($complaint->contract)
                @php
                    $contract = $complaint->contract;
                    $contractInfo = $contract->info;
                    $contractStatus = \App\Enums\ingaz\ContractStatusEnum::tryFrom((string) $contract->status);
                    $contractDate = $contractInfo?->contract_date ?: $contract->contract_date;
                    $notSet = __('ingaz.not_specified');
                @endphp
                <section class="detail-section contract-section">
                    <div class="detail-section__header">
                        <span class="section-icon section-icon--info"><i class="fas fa-file-contract"></i></span>
                        <div>
                            <h5>تفاصيل العقد المرتبط</h5>
                            <small>عقد #{{ $contract->id }}@if ($contractStatus) — {{ $contractStatus->lang() }}@endif</small>
                        </div>
                    </div>
                    <div class="detail-section__body">
                        <div class="detail-data-grid">
                            <div class="detail-data-item">
                                <span>رقم العقد</span>
                                <strong>#{{ $contract->id }}</strong>
                            </div>
                            <div class="detail-data-item">
                                <span>حالة العقد</span>
                                <strong>
                                    <span class="badge bg-{{ $contractStatus?->color() ?: 'secondary' }}">
                                        {{ $contractStatus?->lang() ?: ($contract->status ?: $notSet) }}
                                    </span>
                                </strong>
                            </div>
                            <div class="detail-data-item">
                                <span>تاريخ العقد</span>
                                <strong>{{ $contractDate ? \Carbon\Carbon::parse($contractDate)->format('Y-m-d') : $notSet }}</strong>
                            </div>
                            <div class="detail-data-item">
                                <span>العميل</span>
                                <strong>{{ $contract->user?->name ?: $notSet }}</strong>
                            </div>
                            <div class="detail-data-item">
                                <span>جوال العميل</span>
                                <strong>
                                    @if ($contract->user?->phone)
                                        <a href="tel:{{ $contract->user->phone }}">{{ $contract->user->phone }}</a>
                                    @else
                                        {{ $notSet }}
                                    @endif
                                </strong>
                            </div>
                            <div class="detail-data-item">
                                <span>العاملة</span>
                                <strong>{{ $contract->maid?->name ?: $notSet }}</strong>
                            </div>
                            <div class="detail-data-item">
                                <span>جواز العاملة</span>
                                <strong>{{ $contract->maid?->passport_num ?: $notSet }}</strong>
                            </div>
                            <div class="detail-data-item">
                                <span>جنسية العاملة</span>
                                <strong>{{ $contract->maid?->nationality?->title ?: $contract->requiredMaidNationality?->title ?: $notSet }}</strong>
                            </div>
                            <div class="detail-data-item">
                                <span>المهنة</span>
                                <strong>{{ $contract->requiredMaidJobTitle?->title ?: $notSet }}</strong>
                            </div>
                            <div class="detail-data-item">
                                <span>الوكيل الخارجي</span>
                                <strong>{{ $complaint->external_office?->title ?: $notSet }}</strong>
                            </div>
                            <div class="detail-data-item">
                                <span>رقم التأشيرة</span>
                                <strong>{{ $contractInfo?->visa_number ?: $contract->visa_number ?: $notSet }}</strong>
                            </div>
                            <div class="detail-data-item">
                                <span>رقم مساند</span>
                                <strong>{{ $contractInfo?->msaned_number ?: $contract->msaned_number ?: $notSet }}</strong>
                            </div>
                            <div class="detail-data-item">
                                <span>قيمة العقد</span>
                                <strong>{{ $contractInfo?->contract_value ? number_format((float) $contractInfo->contract_value, 2) . ' ' . __('ingaz.sar') : $notSet }}</strong>
                            </div>
                            <div class="detail-data-item">
                                <span>مدة العقد</span>
                                <strong>{{ $contractInfo?->contract_duration ? $contractInfo->contract_duration . ' سنة' : $notSet }}</strong>
                            </div>
                            <div class="detail-data-item">
                                <span>حالة الضمان</span>
                                <strong>
                                    @if ($contract->warranty_end_date)
                                        {{ \Carbon\Carbon::parse($contract->warranty_end_date)->isFuture() ? 'ساري حتى' : 'منتهٍ في' }}
                                        {{ \Carbon\Carbon::parse($contract->warranty_end_date)->format('Y-m-d') }}
                                    @else
                                        {{ $notSet }}
                                    @endif
                                </strong>
                            </div>
                            <div class="detail-data-item">
                                <span>المسوّق</span>
                                <strong>{{ $contract->marketer?->name ?: $notSet }}</strong>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="{{ $contractUrl }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i>فتح صفحة العقد
                            </a>
                        </div>
                    </div>
                </section>
            @endif

            <section class="detail-section" id="complaintAttachments">
                <div class="detail-section__header">
                    <span class="section-icon section-icon--warning"><i class="fas fa-paperclip"></i></span>
                    <div><h5>المرفقات</h5><small>{{ $complaint->attachmentFiles->count() }} مرفق</small></div>
                </div>
                <div class="detail-section__body">
                    @if ($complaint->attachmentFiles->isNotEmpty())
                        <div class="organized-attachments">
                            @foreach ($complaint->attachmentFiles as $attachment)
                                @php
                                    $extension = strtolower(pathinfo($attachment->original_name ?: $attachment->path, PATHINFO_EXTENSION));
                                    $attachmentIcon = in_array($extension, ['jpg', 'jpeg', 'png']) ? 'fa-file-image' : ($extension === 'pdf' ? 'fa-file-pdf' : 'fa-file');
                                @endphp
                                <article class="organized-attachment">
                                    <a href="{{ showFile($attachment->path) }}" target="_blank" class="organized-attachment__main">
                                        <span class="file-icon"><i class="fas {{ $attachmentIcon }}"></i></span>
                                        <span class="file-info">
                                            <strong>{{ $attachment->display_name ?: $attachment->original_name ?: 'مرفق ' . $loop->iteration }}</strong>
                                            <small>
                                                {{ $attachment->addedBy?->name ?: 'النظام' }}
                                                <span>•</span>{{ $attachment->created_at->format('Y-m-d H:i') }}
                                            </small>
                                        </span>
                                    </a>
                                    @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_EDIT))
                                        <form method="POST" action="{{ route('complaints.attachments.destroy', [$complaint->id, $attachment->id]) }}" class="delete-attachment-form">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-light text-danger" type="submit" title="حذف"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="organized-empty"><i class="far fa-folder-open"></i><span>لا توجد مرفقات حتى الآن</span></div>
                    @endif

                    @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_EDIT))
                        <form method="POST" enctype="multipart/form-data" action="{{ route('complaints.attachments.store', $complaint->id) }}" class="organized-upload">
                            @csrf
                            <div class="organized-upload__head">
                                <div><strong>إضافة مرفقات</strong><small>صور، PDF، Word أو Excel — حتى 5MB للملف</small></div>
                                <label for="complaintAttachmentInput" class="btn btn-outline-primary btn-sm"><i class="fas fa-plus me-1"></i>اختيار الملفات</label>
                            </div>
                            <input type="file" name="attachments[]" id="complaintAttachmentInput" class="d-none" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" multiple required>
                            <div id="attachmentNamesFields" class="attachment-names-list"></div>
                            @error('attachments')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                            @error('attachments.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                            <button type="submit" class="btn btn-primary btn-sm mt-3" id="uploadAttachmentsButton" disabled><i class="fas fa-upload me-1"></i>رفع المرفقات</button>
                        </form>
                    @endif
                </div>
            </section>

            @include('admin.ingaz.complaints.partials.history')
            <section class="detail-section conversation-section">
                <div class="detail-section__header">
                    <span class="section-icon section-icon--success"><i class="fas fa-comments"></i></span>
                    <div><h5>{{ __('ingaz.comments_and_replies') }}</h5><small>{{ $complaint->comments->count() }} تحديث</small></div>
                </div>
                <div class="detail-section__body">
                    <div class="organized-comments">
                        @forelse ($complaint->comments as $comment)
                            <article class="organized-comment">
                                <div class="comment-avatar"><i class="fas fa-user"></i></div>
                                <div class="organized-comment__content">
                                    <div class="organized-comment__head">
                                        <div><strong>{{ $comment->admin?->name ?: __('ingaz.not_specified') }}</strong><small>{{ $comment->created_at->diffForHumans() }}</small></div>
                                        @if (auth('admin')->id() == $comment->added_by || AdminGuardHasPerm(\App\PermKeys::COMPLAINT_CONTROL))
                                            <button class="btn btn-sm btn-light text-danger deleteComment" data-comment-id="{{ $comment->id }}"><i class="fas fa-trash"></i></button>
                                        @endif
                                    </div>
                                    <p>{!! nl2br(e($comment->comment)) !!}</p>
                                </div>
                            </article>
                        @empty
                            <div class="organized-empty"><i class="far fa-comment-dots"></i><span>{{ __('ingaz.no_comments_yet') }}</span></div>
                        @endforelse
                    </div>

                    @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_RESPONSE))
                        <form id="addCommentForm" class="organized-comment-form">
                            @csrf
                            <input type="hidden" name="complaint_id" value="{{ $complaint->id }}">
                            <label class="form-label fw-semibold">إضافة رد أو تحديث</label>
                            <textarea name="comment" class="form-control" rows="3" placeholder="{{ __('ingaz.write_comment_here') }}" required></textarea>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label small">تغيير الحالة (اختياري)</label>
                                    <select name="status" class="form-select">
                                        <option value="">{{ __('ingaz.no_change') }}</option>
                                        <option value="pending">{{ __('ingaz.complaint_status_pending') }}</option>
                                        <option value="in_progress">{{ __('ingaz.complaint_status_in_progress') }}</option>
                                        <option value="resolved">{{ __('ingaz.complaint_status_resolved') }}</option>
                                        <option value="rejected">{{ __('ingaz.complaint_status_rejected') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">إسناد إلى (اختياري)</label>
                                    <select name="assigned_to" class="form-select">
                                        <option value="">{{ __('ingaz.no_change') }}</option>
                                        @foreach ($admins as $admin)
                                            <option value="{{ $admin->id }}" {{ $complaint->assigned_to == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @include('admin.ingaz.complaints.partials.notification-channels')
                            <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-paper-plane me-1"></i>{{ __('ingaz.send_update') }}</button>
                        </form>
                    @endif
                </div>
            </section>
        </main>

        <aside class="col-xl-4">
            <div class="detail-sidebar">
                <section class="sidebar-card">
                    <div class="sidebar-card__header"><h5><i class="fas fa-ticket-alt"></i>بيانات التذكرة</h5></div>
                    <div class="sidebar-list">
                        <div><span>الحالة</span><strong class="detail-pill status-{{ $statusKey }}">{{ $statusLabels[$statusKey] ?? $statusKey }}</strong></div>
                        <div><span>الأولوية</span><strong class="detail-pill priority-{{ $priorityKey }}">{{ $priorityLabels[$priorityKey] ?? $priorityKey }}</strong></div>
                        <div><span>الموعد المستهدف</span><strong class="{{ $complaint->due_at?->isPast() && !in_array($complaint->status, ['resolved', 'closed', 'rejected']) ? 'text-danger' : '' }}">{{ $complaint->due_at?->format('Y-m-d H:i') ?: 'غير محدد' }}</strong></div>
                        <div><span>الموظف المختص</span><strong>{{ $complaint->assignedTo?->name ?: __('ingaz.not_specified') }}</strong></div>
                        <div><span>تاريخ الإنشاء</span><strong>{{ $complaint->created_at->format('Y-m-d H:i') }}</strong></div>
                        <div><span>آخر تحديث</span><strong>{{ $complaint->updated_at->format('Y-m-d H:i') }}</strong></div>
                    </div>
                </section>

                @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_EDIT))
                    <section class="sidebar-card quick-actions-organized">
                        <div class="sidebar-card__header"><h5><i class="fas fa-bolt"></i>{{ __('ingaz.quick_actions') }}</h5></div>
                        <div class="sidebar-actions">
                            @if (!in_array($complaint->status, ['resolved', 'closed']))
                                <button class="sidebar-action sidebar-action--success" id="markResolved"><i class="fas fa-check-circle"></i><span>{{ __('ingaz.mark_resolved') }}</span></button>
                            @endif
                            @if ($complaint->status !== 'closed')
                                <button class="sidebar-action" id="closeTicket"><i class="fas fa-lock"></i><span>{{ __('ingaz.close_ticket') }}</span></button>
                            @endif
                            <button class="sidebar-action assign-ticket" id="assignTicket"><i class="fas fa-user-tag"></i><span>{{ __('ingaz.assign_to_another_employee') }}</span></button>
                            <button class="sidebar-action sidebar-action--warning" id="changePriority"><i class="fas fa-flag"></i><span>{{ __('ingaz.change_priority') }}</span></button>
                        </div>
                    </section>
                @endif

                <section class="sidebar-card">
                    <div class="sidebar-card__header"><h5><i class="fas fa-history"></i>التسلسل الزمني</h5></div>
                    <div class="compact-timeline">
                        <div class="compact-timeline__item">
                            <span class="timeline-dot"></span>
                            <div><strong>{{ __('ingaz.ticket_created') }}</strong><small>{{ $complaint->created_at->format('Y-m-d H:i') }}</small></div>
                        </div>
                        @if ($complaint->complaint_date)
                            <div class="compact-timeline__item">
                                <span class="timeline-dot timeline-dot--info"></span>
                                <div><strong>تاريخ الشكوى الأصلي</strong><small>{{ $complaint->complaint_date->format('Y-m-d') }}</small></div>
                            </div>
                        @endif
                        @if ($complaint->resolved_at)
                            <div class="compact-timeline__item">
                                <span class="timeline-dot timeline-dot--success"></span>
                                <div><strong>{{ __('ingaz.complaint_resolved') }}</strong><small>{{ $complaint->resolved_at->format('Y-m-d H:i') }} — {{ $complaint->resolvedBy?->name ?: __('ingaz.not_specified') }}</small></div>
                            </div>
                        @endif
                        <div class="compact-timeline__item">
                            <span class="timeline-dot timeline-dot--muted"></span>
                            <div><strong>آخر تحديث</strong><small>{{ $complaint->updated_at->format('Y-m-d H:i') }}</small></div>
                        </div>
                    </div>
                </section>
            </div>
        </aside>
    </div>
</div>
