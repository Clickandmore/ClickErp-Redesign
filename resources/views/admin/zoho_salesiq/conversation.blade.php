@extends('admin.layout.indexs.index')

@section('page-title', 'تفاصيل محادثة Zoho SalesIQ')
@section('title', 'تفاصيل محادثة Zoho SalesIQ')

@push('style')
    <style>
        .zoho-detail-header {
            border: 1px solid #e7edf3;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 8px 22px rgba(21, 38, 60, 0.06);
            padding: 16px;
        }

        .zoho-status-badge {
            border-radius: 6px;
            font-size: 12px;
            padding: 7px 10px;
        }

        .zoho-status-open {
            background: #d8f3e6;
            color: #0f6b3d;
        }

        .zoho-status-closed {
            background: #eef2f7;
            color: #4b5563;
        }

        .zoho-status-missed {
            background: #ffe7d6;
            color: #9a3412;
        }

        .zoho-detail-grid {
            display: grid;
            grid-template-columns: minmax(270px, 330px) minmax(0, 1fr);
            gap: 16px;
            align-items: start;
        }

        .zoho-side-panel,
        .zoho-chat-panel {
            border: 1px solid #e7edf3;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 8px 22px rgba(21, 38, 60, 0.06);
        }

        .zoho-side-panel {
            position: sticky;
            top: 84px;
            overflow: hidden;
        }

        .zoho-side-head {
            background: #f8fafc;
            border-bottom: 1px solid #e7edf3;
            padding: 14px 16px;
        }

        .zoho-side-section {
            padding: 14px 16px;
            border-bottom: 1px solid #edf2f7;
        }

        .zoho-side-section:last-child {
            border-bottom: 0;
        }

        .zoho-info-row {
            display: grid;
            grid-template-columns: 92px minmax(0, 1fr);
            gap: 10px;
            margin-bottom: 10px;
            align-items: start;
        }

        .zoho-info-row:last-child {
            margin-bottom: 0;
        }

        .zoho-info-label {
            color: #64748b;
            font-size: 12px;
        }

        .zoho-info-value {
            color: #111827;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .zoho-chat-head {
            border-bottom: 1px solid #e7edf3;
            padding: 14px 16px;
            background: #fff;
            border-radius: 8px 8px 0 0;
        }

        .zoho-chat-body {
            min-height: 520px;
            max-height: calc(100vh - 280px);
            overflow: auto;
            padding: 18px;
            background:
                linear-gradient(rgba(255, 255, 255, 0.82), rgba(255, 255, 255, 0.82)),
                repeating-linear-gradient(135deg, #f7fafc 0, #f7fafc 14px, #f2f6fa 14px, #f2f6fa 28px);
        }

        .zoho-chat-row {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        .zoho-chat-row--visitor {
            justify-content: flex-end;
        }

        .zoho-chat-row--operator {
            justify-content: flex-start;
        }

        .zoho-chat-row--system {
            justify-content: center;
        }

        .zoho-avatar {
            width: 34px;
            height: 34px;
            min-width: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dbe5ef;
            background: #fff;
            color: #64748b;
            margin-top: 3px;
        }

        .zoho-bubble {
            max-width: min(720px, 72%);
            border: 1px solid #dbe5ef;
            border-radius: 8px;
            background: #fff;
            padding: 12px 14px;
            box-shadow: 0 4px 12px rgba(21, 38, 60, 0.05);
        }

        .zoho-chat-row--visitor .zoho-bubble {
            background: #f0fbf6;
            border-color: #b9e7d1;
        }

        .zoho-chat-row--operator .zoho-bubble {
            background: #f4f8ff;
            border-color: #c9ddf6;
        }

        .zoho-chat-row--system .zoho-bubble {
            background: #fff8e6;
            border-color: #f3d38a;
            max-width: min(620px, 82%);
            text-align: center;
        }

        .zoho-message-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 12px;
        }

        .zoho-message-sender {
            color: #111827;
            font-size: 13px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .zoho-message-text {
            color: #1f2937;
            line-height: 1.8;
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .zoho-type-badge {
            display: inline-flex;
            align-items: center;
            border: 1px solid #dbe5ef;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.72);
            color: #475569;
            font-size: 11px;
            padding: 2px 7px;
        }

        .zoho-empty-state {
            min-height: 360px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            text-align: center;
        }

        .zoho-raw {
            max-height: 260px;
            overflow: auto;
            direction: ltr;
            text-align: left;
            font-size: 12px;
            background: #111827;
            color: #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            margin: 10px 0 0;
        }

        .zoho-raw-toggle {
            cursor: pointer;
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }

        .zoho-copy-feedback {
            min-width: 72px;
            color: #15803d;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 991.98px) {
            .zoho-detail-grid {
                grid-template-columns: 1fr;
            }

            .zoho-side-panel {
                position: static;
            }

            .zoho-chat-body {
                max-height: none;
                min-height: 420px;
            }

            .zoho-bubble {
                max-width: 88%;
            }
        }

        @media (max-width: 575.98px) {
            .zoho-detail-header {
                padding: 14px;
            }

            .zoho-info-row {
                grid-template-columns: 1fr;
                gap: 2px;
            }

            .zoho-chat-body {
                padding: 12px;
            }

            .zoho-bubble {
                max-width: 100%;
            }

            .zoho-avatar {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $status = strtolower($conversation['status'] ?? '-');
        $statusClass = 'zoho-status-badge zoho-status-closed';

        if (str_contains($status, 'open') || str_contains($status, 'active')) {
            $statusClass = 'zoho-status-badge zoho-status-open';
        } elseif (str_contains($status, 'missed')) {
            $statusClass = 'zoho-status-badge zoho-status-missed';
        }
    @endphp

    <div class="container-fluid">
        <div class="zoho-detail-header mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                        <h4 class="mb-0">تفاصيل المحادثة</h4>
                        <span class="{{ $statusClass }}">{{ $conversation['status'] }}</span>
                    </div>
                    <div class="text-muted d-flex align-items-center flex-wrap gap-2">
                        <span dir="ltr">#{{ $conversationId }}</span>
                        <span id="zohoCopyFeedback" class="zoho-copy-feedback"></span>
                    </div>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-outline-secondary" href="{{ route('admin.zoho-salesiq.conversations.index') }}">
                        <i class="fa fa-arrow-right me-1"></i> الرجوع
                    </a>
                    <a class="btn btn-outline-primary" href="{{ request()->fullUrl() }}">
                        <i class="fa fa-refresh me-1"></i> تحديث
                    </a>
                    <button type="button" class="btn btn-outline-secondary" data-copy-conversation="{{ $conversationId }}">
                        <i class="fa fa-copy me-1"></i> نسخ
                    </button>
                    <a class="btn btn-outline-primary" href="{{ route('admin.zoho-salesiq.visitors') }}">
                        <i class="fa fa-eye me-1"></i> الزوار
                    </a>
                </div>
            </div>
        </div>

        @if (!$isConfigured)
            <div class="alert alert-warning">
                <strong>بيانات الربط غير مكتملة.</strong>
                أضف المتغيرات التالية في ملف <code>.env</code> ثم نفذ <code>php artisan config:clear</code>:
                <div class="mt-2">
                    @foreach ($missingConfig as $key)
                        <code class="me-2">{{ $key }}</code>
                    @endforeach
                </div>
            </div>
        @elseif (!$result['ok'])
            <div class="alert alert-danger">
                تعذر جلب رسائل المحادثة من Zoho SalesIQ.
                @if (!empty($result['status_code']))
                    <span class="ms-2">HTTP {{ $result['status_code'] }}</span>
                @endif
                <div class="mt-1">{{ $result['error'] ?? '' }}</div>
            </div>
        @endif

        <div class="zoho-detail-grid">
            <aside class="zoho-side-panel">
                <div class="zoho-side-head">
                    <div class="fw-bold">ملخص المحادثة</div>
                    <small class="text-muted">Zoho SalesIQ</small>
                </div>

                <div class="zoho-side-section">
                    <div class="fw-bold mb-3">الزائر</div>
                    <div class="zoho-info-row">
                        <div class="zoho-info-label">الاسم</div>
                        <div class="zoho-info-value">{{ $conversation['visitor'] }}</div>
                    </div>
                    <div class="zoho-info-row">
                        <div class="zoho-info-label">البريد</div>
                        <div class="zoho-info-value">{{ $conversation['email'] }}</div>
                    </div>
                    <div class="zoho-info-row">
                        <div class="zoho-info-label">الهاتف</div>
                        <div class="zoho-info-value">{{ $conversation['phone'] }}</div>
                    </div>
                </div>

                <div class="zoho-side-section">
                    <div class="fw-bold mb-3">بيانات التشغيل</div>
                    <div class="zoho-info-row">
                        <div class="zoho-info-label">الموظف</div>
                        <div class="zoho-info-value">{{ $conversation['operator'] }}</div>
                    </div>
                    <div class="zoho-info-row">
                        <div class="zoho-info-label">القسم</div>
                        <div class="zoho-info-value">{{ $conversation['department'] }}</div>
                    </div>
                    <div class="zoho-info-row">
                        <div class="zoho-info-label">الرسائل</div>
                        <div class="zoho-info-value">{{ number_format($conversation['message_count']) }}</div>
                    </div>
                    <div class="zoho-info-row">
                        <div class="zoho-info-label">آخر نشاط</div>
                        <div class="zoho-info-value">{{ $conversation['last_activity'] }}</div>
                    </div>
                </div>

                <div class="zoho-side-section">
                    <div class="fw-bold mb-3">التوقيت</div>
                    <div class="zoho-info-row">
                        <div class="zoho-info-label">البداية</div>
                        <div class="zoho-info-value">{{ $conversation['created_at'] }}</div>
                    </div>
                    <div class="zoho-info-row">
                        <div class="zoho-info-label">الإغلاق</div>
                        <div class="zoho-info-value">{{ $conversation['closed_at'] }}</div>
                    </div>
                </div>

                <div class="zoho-side-section">
                    <details>
                        <summary class="zoho-raw-toggle">Raw JSON</summary>
                        <pre class="zoho-raw">{{ $conversation['raw'] }}</pre>
                    </details>
                </div>
            </aside>

            <section class="zoho-chat-panel">
                <div class="zoho-chat-head">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div class="fw-bold">الرسائل</div>
                            <small class="text-muted">آخر نشاط: {{ $conversation['last_activity'] }}</small>
                        </div>
                        <span class="badge bg-primary">{{ number_format($messages->count()) }}</span>
                    </div>
                </div>

                <div class="zoho-chat-body">
                    @forelse ($messages as $message)
                        @php
                            $role = $message['role'] ?? 'operator';
                            $icon = $role === 'visitor' ? 'fa-user' : ($role === 'system' ? 'fa-info' : 'fa-headset');
                        @endphp

                        <div class="zoho-chat-row zoho-chat-row--{{ $role }}">
                            @if ($role !== 'visitor')
                                <span class="zoho-avatar"><i class="fa {{ $icon }}"></i></span>
                            @endif

                            <div class="zoho-bubble">
                                <div class="zoho-message-meta">
                                    <span class="zoho-message-sender">{{ $message['sender'] }}</span>
                                    <span dir="ltr">{{ $message['time'] }}</span>
                                </div>
                                <div class="mb-2">
                                    <span class="zoho-type-badge">{{ $message['type'] }}</span>
                                </div>
                                <div class="zoho-message-text">{{ $message['message'] }}</div>

                                <details>
                                    <summary class="zoho-raw-toggle">Raw</summary>
                                    <pre class="zoho-raw">{{ $message['raw'] }}</pre>
                                </details>
                            </div>

                            @if ($role === 'visitor')
                                <span class="zoho-avatar"><i class="fa {{ $icon }}"></i></span>
                            @endif
                        </div>
                    @empty
                        <div class="zoho-empty-state">لا توجد رسائل متاحة لهذه المحادثة.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const copyButton = document.querySelector('[data-copy-conversation]');
            const feedback = document.getElementById('zohoCopyFeedback');

            if (!copyButton || !feedback || !navigator.clipboard) {
                return;
            }

            copyButton.addEventListener('click', function () {
                navigator.clipboard.writeText(copyButton.dataset.copyConversation || '').then(function () {
                    feedback.textContent = 'تم النسخ';

                    window.setTimeout(function () {
                        feedback.textContent = '';
                    }, 1600);
                });
            });
        });
    </script>
@endpush
