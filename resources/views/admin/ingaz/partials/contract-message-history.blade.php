<div class="card shadow-sm my-4" id="contract-messages" dir="rtl">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-comments me-2"></i>سجل رسائل العقد <span class="badge bg-secondary">{{ $contractMessages->total() }}</span></h5></div>
    <div class="card-body">
        <p class="text-muted small">رسائل هذا العقد ومحاولات إرسالها، مرتبة من الأحدث. قبول المزود لا يؤكد التسليم.</p>
        @if ($contractMessages->isEmpty())
            <p class="text-muted mb-0">لا توجد رسائل مسجلة مرتبطة بهذا العقد.</p>
        @else
            <div class="table-responsive"><table class="table table-bordered align-middle">
                <thead><tr><th>التاريخ والموظف</th><th>القناة والمستلم</th><th>الرسالة</th><th>الحالة</th></tr></thead>
                <tbody>@foreach ($contractMessages as $entry)
                    <tr>
                        <td>{{ $entry->created_at?->format('Y-m-d H:i') }}<div class="small text-muted">{{ $entry->actor?->name ?: ($entry->admin_id ? 'موظف سابق' : 'النظام') }}</div></td>
                        <td>{{ \App\Models\OutboundMessageLog::CHANNELS[$entry->channel] ?? $entry->channel }}
                            <div>{{ $entry->recipient_name }}</div><bdi>{{ $entry->recipient }}</bdi>
                            <div class="small text-muted">{{ \App\Models\OutboundMessageLog::sourceLabel($entry->source) }}</div>
                        </td>
                        <td style="min-width:240px;max-width:600px;white-space:pre-wrap;overflow-wrap:anywhere">{{ $entry->message }}</td>
                        <td>{{ \App\Models\OutboundMessageLog::STATUSES[$entry->status] ?? $entry->status }}
                            @if ($entry->accepted_at)<div class="small">قبول: {{ $entry->accepted_at->format('Y-m-d H:i') }}</div>@endif
                            @if ($entry->delivered_at)<div class="small">تسليم: {{ $entry->delivered_at->format('Y-m-d H:i') }}</div>@endif
                            @if ($entry->read_at)<div class="small">قراءة: {{ $entry->read_at->format('Y-m-d H:i') }}</div>@endif
                            @if ($entry->error)<div class="small text-danger">{{ $entry->error }}</div>@endif
                        </td>
                    </tr>
                @endforeach</tbody>
            </table></div>
            {{ $contractMessages->links() }}
        @endif
    </div>
</div>
