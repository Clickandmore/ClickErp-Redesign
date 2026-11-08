@extends('admin.layout.indexs.index')
@section('page-title', 'تفاصيل مشاركة السير')
@section('content')
@php
    $labels = ['draft' => 'معاينة — لم ترسل', 'queued' => 'بانتظار التنفيذ', 'processing' => 'جارٍ الإرسال',
        'accepted' => 'قبل المزود الإرسال', 'partial' => 'إرسال جزئي', 'failed' => 'فشل الإرسال',
        'needs_review' => 'تحتاج مراجعة النتيجة مع المزود', 'pending' => 'بانتظار الإرسال', 'sending' => 'جارٍ الإرسال',
        'unknown' => 'نتيجة غير مؤكدة — راجع منصة واتساب', 'sent' => 'أرسلت', 'delivered' => 'تم التسليم', 'read' => 'تمت القراءة'];
@endphp
<div class="container-fluid py-4" dir="rtl">
    <h3>مشاركة السير #{{ $share->id }}</h3>
    <div class="card card-body mb-3">
        <p>الموظف: {{ $share->admin_name }} — العميل: {{ $share->client_name }} — <bdi>{{ $share->phone }}</bdi></p>
        <p>تاريخ الطلب: {{ $share->created_at }} — تاريخ تأكيد الإرسال: {{ $share->confirmed_at ?: 'لم يؤكد بعد' }}</p>
        <p>الحالة: <strong>{{ $labels[$share->status] ?? $share->status }}</strong></p>
        <p class="text-muted">قبول المزود للرسالة لا يؤكد تسليمها. يظهر التسليم والقراءة عند ورود تحديث من المزود.</p>
        <pre class="border rounded p-3" style="white-space:pre-wrap;font:inherit">{{ $share->message }}</pre>
    </div>
    <div class="table-responsive"><table class="table table-bordered">
        <thead><tr><th>الرسالة / السيرة المرسلة</th><th>الحالة</th><th>المحاولات</th><th>التفاصيل</th></tr></thead>
        <tbody>@foreach ($share->messages as $message)
            <tr><td>
                @if ($message->file_path)
                    <a href="{{ route('maid-cv-shares.download', $message->id) }}" target="_blank" rel="noopener">{{ $message->snapshot['name'] }} — {{ $message->snapshot['reference'] }}</a>
                    @if (isset($message->snapshot['cv_type']))<span class="badge bg-secondary">{{ $message->snapshot['cv_type'] === 'rental' ? 'إيجار' : 'استقدام' }}</span>@endif
                @else رسالة البيانات المجمعة @endif
            </td><td>{{ $labels[$message->status] ?? $message->status }}
                @if ($message->accepted_at)<div class="small">قبول: {{ $message->accepted_at }}</div>@endif
                @if ($message->delivered_at)<div class="small">تسليم: {{ $message->delivered_at }}</div>@endif
                @if ($message->read_at)<div class="small">قراءة: {{ $message->read_at }}</div>@endif
            </td><td>{{ $message->attempts }}</td><td>{{ $message->error }}<div class="small text-muted">{{ $message->provider_message_id }}</div></td></tr>
        @endforeach</tbody>
    </table></div>
    @if ((int) $share->admin_id === (int) auth('admin')->id())
        @if ($share->status === 'draft')
            <form method="POST" action="{{ route('maid-cv-shares.confirm', $share->id) }}" onsubmit="this.querySelector('button').disabled=true">@csrf
                <button class="btn btn-success">تأكيد وإرسال عبر واتساب</button>
            </form>
        @elseif (in_array($share->status, ['failed', 'partial'], true))
            <form method="POST" action="{{ route('maid-cv-shares.retry', $share->id) }}" onsubmit="this.querySelector('button').disabled=true">@csrf
                <button class="btn btn-warning">إعادة محاولة الرسائل التي فشلت فقط</button>
            </form>
        @endif
    @endif
    <a class="btn btn-outline-secondary mt-3" href="{{ route('maid-cv-shares.index', ['client_id' => $share->client_id]) }}">تاريخ مشاركات العميل</a>
    @if (in_array($share->status, ['queued', 'processing'], true))
        <script>setTimeout(() => window.location.reload(), 10000);</script>
    @endif
</div>
@endsection
