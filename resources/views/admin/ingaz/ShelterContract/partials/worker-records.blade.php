<section class="operations-panel" id="worker-trials">
    <h3 class="section-title"><i class="fas fa-exchange-alt"></i>تجارب العاملة وطلبات نقل الخدمات</h3>
    <div class="table-responsive mt-3">
        <table class="table table-bordered align-middle">
            <thead><tr><th>الطلب / العقد</th><th>الكفيل الجديد</th><th>بداية التجربة</th><th>المدة</th><th>الحالة</th><th>تاريخ الفشل</th><th>سبب الفشل</th></tr></thead>
            <tbody>
            @forelse($workerHistory['transfers'] as $transfer)
                <tr>
                    <td>#{{ $transfer->id }} / #{{ $transfer->contract_id }}</td>
                    <td>{{ $transfer->newSponsor?->name ?? 'غير مسجل' }}</td>
                    <td>{{ ($transfer->trial_started_at ?: $transfer->trial_start_date)?->format('Y-m-d') ?? 'لم تبدأ / غير مسجل' }}</td>
                    <td>{{ $transfer->trial_days ?? 'غير مسجل' }} @if($transfer->trial_days) يوم @endif</td>
                    <td><span class="badge {{ $transfer->status === 'failed' ? 'bg-danger' : 'bg-secondary' }}">{{ $transfer->status_text }}</span></td>
                    <td>{{ $transfer->failed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td style="white-space: pre-wrap">{{ $transfer->failure_reason ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">لا توجد تجارب أو طلبات نقل خدمات مسجلة للعاملة.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
<section class="operations-panel" id="worker-complaints">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3 class="section-title"><i class="fas fa-comment-dots"></i>شكاوى العاملة</h3>
        @if($workerHistory['open_complaints'] === 0 && @AdminGuardHasAnyPerm([\App\PermKeys::COMPLAINT_CREATE, \App\PermKeys::COMPLAINT_CONTROL]))
            <a href="{{ route('complaints.create', ['contract_id' => $contract->id]) }}" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة شكوى للعاملة</a>
        @endif
    </div>
    @forelse($workerHistory['complaints'] as $complaint)
        <article class="border rounded p-3 mt-3">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <strong>#{{ $complaint->id }} — {{ $complaint->subject ?: $complaint->complaintType?->name ?: 'شكوى' }}</strong>
                <span>{{ ['pending' => 'قيد الانتظار', 'in_progress' => 'قيد المعالجة', 'resolved' => 'تم الحل', 'closed' => 'مغلقة', 'rejected' => 'مرفوضة'][$complaint->status] ?? 'غير محدد' }}</span>
            </div>
            <small class="text-muted">{{ ($complaint->complaint_date ?: $complaint->created_at)?->format('Y-m-d') }} · {{ $complaint->complaintType?->name }} · الأولوية: {{ $complaint->priority_text }}</small>
            <p class="mt-2 mb-2" style="white-space: pre-wrap; overflow-wrap: anywhere">{{ $complaint->details ?: $complaint->message ?: 'لا توجد تفاصيل مسجلة.' }}</p>
            @if(@AdminGuardHasAnyPerm([\App\PermKeys::COMPLAINT_VIEW, \App\PermKeys::COMPLAINT_CONTROL]))
                <a href="{{ route('complaints.show', $complaint->id) }}">عرض الشكوى ومتابعتها</a>
            @endif
        </article>
    @empty
        <p class="text-muted mt-3 mb-0">لا توجد شكاوى مرتبطة بعقود العاملة أو طلباتها.</p>
    @endforelse
</section>
<section class="operations-panel" id="worker-timeline">
    <h3 class="section-title"><i class="fas fa-history"></i>سجل حركة العاملة منذ تسجيلها</h3>
    <p class="text-muted">من الأقدم إلى الأحدث، بحسب الأحداث المحفوظة في النظام والسجلات المتاحة لفرعك.</p>
    <ol class="worker-timeline">
        @forelse($workerHistory['events'] as $event)
            <li class="mb-3 pb-3 border-bottom">
                <time class="text-muted" dir="ltr">{{ $event['date']->format('Y-m-d H:i') }}</time>
                <div class="fw-bold mt-1">{{ $event['title'] }}</div>
                @if($event['details'])<div style="white-space: pre-wrap; overflow-wrap: anywhere">{{ $event['details'] }}</div>@endif
                @if($event['actor'])<small class="text-muted">بواسطة: {{ $event['actor'] }}</small>@endif
            </li>
        @empty
            <li class="text-muted">لا توجد حركة مؤرخة مسجلة.</li>
        @endforelse
    </ol>
</section>
