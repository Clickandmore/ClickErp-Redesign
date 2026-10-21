@if(in_array($order->status,['active','pending_confirmation'],true)
    && ($order->status!=='pending_confirmation' || !$order->expires_at?->isPast())
    && $visit->status==='scheduled' && $visit->scheduled_start_at?->isFuture()
    && !$visit->is_free && (int)$visit->workers_count<20)
    <button type="button" class="hr-btn secondary sm" data-add-worker="{{ $visit->id }}" data-visit-label="الزيارة {{ $visit->visit_number }} · {{ $visit->scheduled_start_at->translatedFormat('l d/m') }} · {{ $visit->scheduled_start_at->format('H:i') }}"><i class="fa-light fa-user-plus"></i> إضافة عاملة</button>
@endif
