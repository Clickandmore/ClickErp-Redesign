@extends('agent.layout')

@php
    $isAr = app()->getLocale() === 'ar';
    $status = \App\Support\AgentComplaintLabels::status($complaint->status);
    $priority = \App\Support\AgentComplaintLabels::priority($complaint->priority);
    $submissionMethod = \App\Enums\ingaz\ComplaintSubmissionMethodEnum::label($complaint->submission_method);
@endphp

@section('page-title', ($isAr ? 'شكوى' : 'Complaint') . ' #' . $complaint->id)

@push('style')
    <style>
        .agent-badge.success {
            background: #dcfce7;
            color: #166534;
        }

        .agent-badge.danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .agent-badge.muted {
            background: #e2e8f0;
            color: #334155;
        }

        .agent-flash {
            margin-bottom: 14px;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            font-size: 13px;
        }

        .agent-text-block {
            white-space: pre-line;
            line-height: 1.8;
            margin: 0;
        }

        .agent-attachments {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .agent-attachments a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid var(--agent-line);
            border-radius: 8px;
            background: #f8fafc;
            color: var(--agent-ink);
            font-size: 13px;
            text-decoration: none;
        }

        .agent-attachments a:hover {
            border-color: var(--agent-primary);
            color: var(--agent-primary-dark);
        }

        .agent-timeline {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .agent-timeline-item {
            border: 1px solid var(--agent-line);
            border-inline-start: 3px solid var(--agent-primary);
            border-radius: 8px;
            padding: 12px 14px;
            background: #f8fafc;
        }

        .agent-timeline-item header {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 12px;
            color: var(--agent-muted);
        }

        .agent-timeline-item strong {
            color: var(--agent-ink);
        }

        .agent-reply textarea {
            width: 100%;
            min-height: 110px;
            padding: 10px;
            border: 1px solid var(--agent-line);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
        }

        .agent-reply input[type="file"] {
            margin-top: 10px;
        }

        .agent-reply .actions {
            margin-top: 12px;
        }
    </style>
@endpush

@section('page-action')
    <a href="{{ route('agent.complaints.index') }}" class="agent-button">
        <i class="fa fa-list"></i>
        <span>{{ $isAr ? 'كل الشكاوى' : 'All Complaints' }}</span>
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="agent-flash">{{ session('success') }}</div>
    @endif

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ $complaint->subject ?: ($isAr ? 'شكوى' : 'Complaint') }} — #{{ $complaint->id }}</h3>
            <div style="display: flex; gap: 8px;">
                <span class="agent-badge {{ $status['class'] }}">{{ $isAr ? $status['ar'] : $status['en'] }}</span>
                <span class="agent-badge {{ $priority['class'] }}">{{ $isAr ? $priority['ar'] : $priority['en'] }}</span>
            </div>
        </div>

        <div class="agent-detail-grid">
            <div class="agent-detail-item">
                <span>{{ $isAr ? 'نوع الشكوى' : 'Complaint Type' }}</span>
                <strong>{{ $complaint->complaintType?->name ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>{{ $isAr ? 'تاريخ الإرسال' : 'Submitted At' }}</span>
                <strong>{{ $complaint->created_at?->format('Y-m-d H:i') ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>{{ $isAr ? 'مُقدّم الشكوى' : 'Complainant' }}</span>
                <strong>{{ $complaint->complainant_name ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>{{ $isAr ? 'رقم التواصل' : 'Contact Phone' }}</span>
                <strong>{{ $complaint->complainant_phone ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>{{ $isAr ? 'الطرف الآخر' : 'Other Party' }}</span>
                <strong>{{ $complaint->against_name ?: '-' }}</strong>
            </div>
            <div class="agent-detail-item">
                <span>{{ $isAr ? 'قناة التقديم' : 'Submission Channel' }}</span>
                <strong>{{ $submissionMethod ?: '-' }}</strong>
            </div>
            @if ($complaint->contract)
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'العقد المرتبط' : 'Linked Contract' }}</span>
                    <strong>
                        <a href="{{ route('agent.contracts.show', $complaint->contract->id) }}">
                            {{ $isAr ? 'عقد' : 'Contract' }} #{{ $complaint->contract->id }}
                        </a>
                    </strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'العاملة' : 'Worker' }}</span>
                    <strong>{{ $complaint->contract->maid?->name ?: '-' }}</strong>
                </div>
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'العميل' : 'Client' }}</span>
                    <strong>{{ $complaint->contract->user?->name ?: '-' }}</strong>
                </div>
            @elseif ($complaint->relation_reference)
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'المرجع' : 'Reference' }}</span>
                    <strong>{{ $complaint->relation_reference }}</strong>
                </div>
            @endif
            @if ($complaint->resolved_at)
                <div class="agent-detail-item">
                    <span>{{ $isAr ? 'تاريخ الحل' : 'Resolved At' }}</span>
                    <strong>{{ $complaint->resolved_at->format('Y-m-d H:i') }}</strong>
                </div>
            @endif
        </div>
    </section>

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ $isAr ? 'التفاصيل' : 'Details' }}</h3>
        </div>
        <p class="agent-text-block">{{ $complaint->details ?: $complaint->message ?: '-' }}</p>

        @if ($complaint->relation_notes)
            <div class="agent-section-header" style="margin-top: 16px;">
                <h3>{{ $isAr ? 'ملاحظات إضافية' : 'Additional Notes' }}</h3>
            </div>
            <p class="agent-text-block">{{ $complaint->relation_notes }}</p>
        @endif
    </section>

    @if ($complaint->response)
        <section class="agent-section">
            <div class="agent-section-header">
                <h3>{{ $isAr ? 'رد الإدارة' : 'Management Response' }}</h3>
            </div>
            <p class="agent-text-block">{{ $complaint->response }}</p>
        </section>
    @endif

    @if ($complaint->attachmentFiles->isNotEmpty())
        <section class="agent-section">
            <div class="agent-section-header">
                <h3>{{ $isAr ? 'المرفقات' : 'Attachments' }} ({{ $complaint->attachmentFiles->count() }})</h3>
            </div>
            <div class="agent-attachments">
                @foreach ($complaint->attachmentFiles as $attachment)
                    <a href="{{ showFile($attachment->path) }}" target="_blank" rel="noopener">
                        <i class="fa fa-paperclip"></i>
                        <span>{{ $attachment->display_name ?: $attachment->original_name ?: basename($attachment->path) }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="agent-section">
        <div class="agent-section-header">
            <h3>{{ $isAr ? 'سجل المتابعة' : 'Follow-up Log' }}</h3>
        </div>

        <div class="agent-timeline">
            @forelse ($complaint->comments->sortByDesc('id') as $comment)
                <div class="agent-timeline-item">
                    <header>
                        <strong>{{ $comment->admin?->name ?: ($isAr ? 'النظام' : 'System') }}</strong>
                        <span>{{ $comment->created_at?->format('Y-m-d H:i') }}</span>
                    </header>
                    <p class="agent-text-block">{{ $comment->comment }}</p>
                </div>
            @empty
                <p class="agent-empty">{{ $isAr ? 'لا توجد تحديثات حتى الآن.' : 'No updates yet.' }}</p>
            @endforelse
        </div>
    </section>

    <section class="agent-section agent-reply">
        <div class="agent-section-header">
            <h3>{{ $isAr ? 'إضافة رد' : 'Add a Reply' }}</h3>
        </div>

        <form action="{{ route('agent.complaints.comments.store', $complaint->id) }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            <textarea name="comment" required maxlength="10000"
                placeholder="{{ $isAr ? 'اكتب ردك أو أي مستجدات على الشكوى' : 'Write your reply or any updates on this complaint' }}">{{ old('comment') }}</textarea>
            <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
            <div class="actions">
                <button type="submit" class="agent-button">
                    <i class="fa fa-reply"></i>
                    <span>{{ $isAr ? 'إرسال الرد' : 'Send Reply' }}</span>
                </button>
            </div>
        </form>
    </section>
@endsection
