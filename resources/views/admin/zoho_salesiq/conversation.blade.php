@extends('admin.layout.indexs.index')

@section('page-title', 'تفاصيل محادثة Zoho SalesIQ')
@section('title', 'تفاصيل محادثة Zoho SalesIQ')

@push('style')
    <style>
        .zoho-panel {
            border-radius: 8px;
            border: 1px solid #e7edf3;
            box-shadow: 0 8px 22px rgba(21, 38, 60, 0.06);
        }

        .zoho-message {
            border-radius: 8px;
            border: 1px solid #e7edf3;
            background: #fff;
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
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-1">تفاصيل المحادثة</h4>
                <div class="text-muted">{{ $conversationId }}</div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('admin.zoho-salesiq.conversations.index') }}">
                    <i class="fa fa-arrow-right me-1"></i> الرجوع للمحادثات
                </a>
                <a class="btn btn-outline-primary" href="{{ route('admin.zoho-salesiq.visitors') }}">
                    <i class="fa fa-eye me-1"></i> الزوار
                </a>
            </div>
        </div>

        @if (!$isConfigured)
            <div class="alert alert-warning zoho-panel">
                <strong>بيانات الربط غير مكتملة.</strong>
                أضف المتغيرات التالية في ملف <code>.env</code> ثم نفذ <code>php artisan config:clear</code>:
                <div class="mt-2">
                    @foreach ($missingConfig as $key)
                        <code class="me-2">{{ $key }}</code>
                    @endforeach
                </div>
            </div>
        @elseif (!$result['ok'])
            <div class="alert alert-danger zoho-panel">
                تعذر جلب رسائل المحادثة من Zoho SalesIQ.
                @if (!empty($result['status_code']))
                    <span class="ms-2">HTTP {{ $result['status_code'] }}</span>
                @endif
                <div class="mt-1">{{ $result['error'] ?? '' }}</div>
            </div>
        @endif

        <div class="card zoho-panel">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">الرسائل</h5>
                    <span class="badge bg-primary">{{ number_format($messages->count()) }}</span>
                </div>

                @forelse ($messages as $message)
                    <div class="zoho-message p-3 mb-3">
                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                            <div>
                                <strong>{{ $message['sender'] }}</strong>
                                <span class="badge bg-light text-dark ms-1">{{ $message['type'] }}</span>
                            </div>
                            <small class="text-muted">{{ $message['time'] }}</small>
                        </div>
                        <div style="white-space: pre-line;">{{ $message['message'] }}</div>
                        <details class="mt-3">
                            <summary class="btn btn-sm btn-outline-secondary">Raw</summary>
                            <pre class="zoho-raw mt-2">{{ $message['raw'] }}</pre>
                        </details>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">لا توجد رسائل متاحة لهذه المحادثة.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
