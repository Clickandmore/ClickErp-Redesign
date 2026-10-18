@if(in_array($order->status,['active','pending_confirmation'],true)
    && ($order->status!=='pending_confirmation' || !$order->expires_at?->isPast())
    && $visit->status==='scheduled' && $visit->scheduled_start_at?->isFuture()
    && !$visit->is_free && (int)$visit->workers_count<20)
    <a class="hr-btn secondary sm" href="{{ route('web.hourly.order',['id'=>$order->id,'add_worker_visit'=>$visit->id]) }}#hourly-customer-add-worker"><i class="fa-light fa-user-plus"></i> إضافة عاملة</a>
@endif
