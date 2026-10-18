@if(AdminGuardHasPerm('hourly_orders_create') && ($order->status==='active' || ($order->status==='pending_confirmation' && \App\Services\Hourly\ServiceIncident::recoverable($order,$visit))) && $visit->status==='scheduled' && $visit->scheduled_start_at?->isFuture() && app(\App\Services\Hourly\WorkerReplacement::class)->allowed($order) && $visit->workers->isNotEmpty())
@include('admin.hourly._action-panel-style')
<details class="ap is-replace" data-worker-replacement>
    <summary>
        <span class="ap-icon"><i class="fa fa-people-arrows"></i></span>
        <span class="ap-title"><b>استبدال عاملة لظرف طارئ</b><small>استبدال مباشر من الإدارة مع إبلاغ العميل</small></span>
        <span class="ap-chip">الزيارة {{ $visit->visit_number }}</span>
        <i class="fa fa-chevron-down ap-caret" aria-hidden="true"></i>
    </summary>
    <form class="ap-body" method="post" action="{{ route('admin.hourly-workers.replace',[$order->id,$visit->id]) }}">
        @csrf
        <p class="ap-intro"><i class="fa fa-circle-info"></i><span>اختيار العاملة غير مفعّل للعميل؛ تستطيع الإدارة استبدالها مباشرة. يُفحص توفر البديلة عند الحفظ، ويُرسل إشعار للعميل دون عرض السبب الداخلي. تبقى قيمة الزيارة وعدد العاملات كما هما.</span></p>
        <div class="ap-grid">
            <label class="ap-field"><span>العاملة المطلوب استبدالها</span>
                <select class="form-control" name="worker_id" required>
                    @foreach($visit->workers as $worker)<option value="{{ $worker->id }}">{{ $worker->name }}</option>@endforeach
                </select>
            </label>
            <label class="ap-field"><span>البديلة</span>
                <select class="form-control" name="replacement_worker_id" required>
                    <option value="">اختر البديلة — يُتحقق من توفرها قبل التنفيذ</option>
                    @foreach($workers as $worker)
                        @if(!$visit->workers->contains('id',$worker->id))
                            <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                        @endif
                    @endforeach
                </select>
            </label>
            <label class="ap-field is-wide"><span>السبب الداخلي <em>(لا يظهر للعميل، حتى 1000 حرف)</em></span>
                <textarea class="form-control" name="reason" maxlength="1000" required></textarea>
            </label>
        </div>
        <div class="ap-foot">
            <button class="ap-submit" type="submit"><i class="fa fa-user-check"></i> تأكيد الاستبدال وإبلاغ العميل</button>
        </div>
    </form>
</details>
@endif
