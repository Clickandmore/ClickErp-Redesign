<section class="detail-section" id="complaintHistory">
    <div class="detail-section__header"><span class="section-icon"><i class="fas fa-history"></i></span><div><h5>سجل أحداث الشكوى</h5><small>من أضاف الشكوى وما تم عليها من تحديثات</small></div></div>
    <div class="detail-section__body">
        <div class="detail-data-grid mb-4 p-3 bg-light rounded">
            <div class="detail-data-item"><span>أضاف الشكوى</span><strong>{{ $historySummary['creator'] }}</strong></div>
            <div class="detail-data-item"><span>تاريخ الإضافة</span><strong>{{ $complaint->created_at->format('Y-m-d H:i') }}</strong></div>
            <div class="detail-data-item"><span>{{ $historySummary['duration_label'] }}</span><strong>{{ $historySummary['duration'] }}</strong></div>
            <div class="detail-data-item"><span>آخر تحديث</span><strong>{{ $complaint->updated_at->format('Y-m-d H:i') }}</strong></div>
        </div>
        @forelse ($history as $event)
            @php($entry = \App\Services\Complaints\ComplaintHistory::present($event))
            <article class="border-bottom pb-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <strong>{{ $entry['title'] }}</strong>
                    <small class="text-muted">{{ $event->created_at->format('Y-m-d H:i') }} · {{ $event->created_at->locale('ar')->diffForHumans() }}</small>
                </div>
                <div class="text-muted small mt-1"><i class="far fa-user me-1"></i>{{ \App\Services\Complaints\ComplaintHistory::actor($event) }}</div>
                @foreach ($entry['changes'] as $change)
                    <div class="mt-2" style="white-space: pre-line; overflow-wrap: anywhere"><strong>{{ $change['label'] }}:</strong>
                        @if ($change['has_before'])<span class="text-muted">{{ $change['before'] }}</span> <span class="text-muted">←</span> @endif
                        <span>{{ $change['removed'] ? 'تمت الإزالة' : $change['after'] }}</span>
                    </div>
                @endforeach
                @if ($event->properties->get('notes'))<p class="mt-2 mb-0">{{ $event->properties->get('notes') }}</p>@endif
                @foreach ($event->properties->get('notification_results', []) as $channel => $result)
                    <div class="mt-2">{{ ['database' => 'إشعار', 'sms' => 'رسالة نصية', 'email' => 'بريد إلكتروني'][$channel] ?? 'إخطار' }}: {{ $result }}</div>
                @endforeach
            </article>
        @empty
            <p class="text-muted mb-0">لا توجد تحديثات محفوظة لهذه الشكوى.</p>
        @endforelse
        {{ $history->fragment('complaintHistory')->links() }}
    </div>
</section>
