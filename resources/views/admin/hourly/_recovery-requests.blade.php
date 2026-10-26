@php
    $recoveryStatuses = [
        'pending' => ['قيد المراجعة', 'rr-s-pending', 'fa-hourglass-half'],
        'approved' => ['تمت الموافقة', 'rr-s-approved', 'fa-circle-check'],
        'rejected' => ['مرفوض', 'rr-s-rejected', 'fa-circle-xmark'],
    ];
    $recoveryDate = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i') : '—';
@endphp
@foreach($recoveryOrders as $recoveryOrder)
@foreach(($recoveryOrder->booking_metadata['missed_visit_requests'] ?? []) as $recoveryId => $recoveryRequest)
@if(!isset($recoveryVisitId) || (int) $recoveryId === (int) $recoveryVisitId)
@php
    $isRefund = ($recoveryRequest['type'] ?? null) === 'refund';
    $isReplacement = ($recoveryRequest['type'] ?? null) === 'replacement';
    [$statusLabel, $statusClass, $statusIcon] = $recoveryStatuses[$recoveryRequest['status'] ?? 'pending'] ?? ['غير معروفة', 'rr-s-pending', 'fa-circle-question'];
    $isPending = ($recoveryRequest['status'] ?? null) === 'pending';
    // صفحة الطلبات تحسبها مسبقًا؛ في صفحة الطلب وبطاقة الزيارة تُحسب هنا لطلبات إعادة الجدولة المعلقة فقط.
    $slotCheck = isset($slotChecks) ? ($slotChecks[$recoveryOrder->id][$recoveryId] ?? null)
        : ($isPending ? app(\App\Services\Hourly\MissedVisitRecovery::class)->requestedSlotStatus($recoveryOrder, (int) $recoveryId) : null);
    // طلب البديلة: العاملات اللاتي تعذر حضورهن، والمتاحات لنفس الموعد لاختيار بديلة لكل منهن.
    $failedWorkers = collect(); $replacementCandidates = collect(); $replacementReady = false;
    if ($isReplacement) {
        $failedIds = array_map('intval', \App\Services\Hourly\ServiceIncident::state($recoveryOrder, (int) $recoveryId)['failed_worker_ids'] ?? []);
        $failedWorkers = \App\Models\ingaz\Maid::whereIn('id', $failedIds)->get(['id', 'name']);
        $replacementVisit = $recoveryOrder->visits()->find($recoveryId);
        if ($isPending && $replacementVisit) {
            $replacementReady = \App\Services\Hourly\MissedVisitRecovery::replacementEligible($replacementVisit, $recoveryOrder);
            if ($replacementReady) $replacementCandidates = app(\App\Services\Hourly\MissedVisitRecovery::class)->replacementCandidates($recoveryOrder, $replacementVisit);
        }
    }
@endphp
<article class="rr-card {{ $isPending ? 'is-pending' : '' }}">
    <header class="rr-head">
        <span class="rr-type-icon {{ $isRefund ? 'rr-t-refund' : 'rr-t-reschedule' }}" aria-hidden="true"><i class="fa {{ $isRefund ? 'fa-wallet' : ($isReplacement ? 'fa-people-arrows' : 'fa-calendar-days') }}"></i></span>
        <div class="rr-title">
            <h6>{{ $isRefund ? 'طلب إلغاء واسترداد للمحفظة' : ($isReplacement ? 'طلب عاملة بديلة لنفس الموعد' : 'طلب إعادة جدولة دون رسوم') }}</h6>
            <div class="rr-sub">
                <a href="{{ route('admin.hourly-orders.show', $recoveryOrder->id) }}" class="rr-order">الطلب {{ $recoveryOrder->order_number }}</a>
                <span>مرجع الزيارة #{{ $recoveryId }}</span>
                @if($recoveryOrder->user)<span><i class="fa fa-user" aria-hidden="true"></i> {{ $recoveryOrder->user->name }}</span>@endif
            </div>
        </div>
        <span class="rr-status {{ $statusClass }}"><i class="fa {{ $statusIcon }}" aria-hidden="true"></i> {{ $statusLabel }}</span>
    </header>

    <div class="rr-body">
        @if(($recoveryRequest['reason_code'] ?? null) === 'coverage_unavailable')
            <div class="rr-alert rr-alert-warn"><i class="fa fa-triangle-exclamation" aria-hidden="true"></i><span>تعذرت جدولة الزيارة بسبب عدم توفر التغطية. لا تعتمد التنفيذ قبل توفر التغطية. تُمدد صلاحية الموعد المعتمد دون دفع جديد، ويقتصر الاسترداد على المبلغ المدفوع لهذه الزيارة.</span></div>
        @endif

        <dl class="rr-facts">
            <div><dt><i class="fa fa-clock" aria-hidden="true"></i> تاريخ الطلب</dt><dd dir="ltr">{{ $recoveryDate($recoveryRequest['requested_at'] ?? null) }}</dd></div>
            @if(!$isRefund && !empty($recoveryRequest['requested_start']))
                <div><dt><i class="fa fa-calendar-check" aria-hidden="true"></i> الموعد الذي طلبه العميل</dt><dd dir="ltr">{{ $recoveryDate($recoveryRequest['requested_start']) }}</dd></div>
            @endif
            @if(isset($recoveryRequest['new_start']))
                <div><dt><i class="fa fa-calendar-day" aria-hidden="true"></i> الموعد الجديد</dt><dd dir="ltr">{{ $recoveryDate($recoveryRequest['new_start']) }}</dd></div>
            @endif
            @if($isReplacement && $failedWorkers->isNotEmpty())
                <div><dt><i class="fa fa-user-xmark" aria-hidden="true"></i> تعذر حضور</dt><dd>{{ $failedWorkers->pluck('name')->implode('، ') }}</dd></div>
            @endif
            @if(isset($recoveryRequest['refunded_amount']))
                <div><dt><i class="fa fa-coins" aria-hidden="true"></i> المسترد إلى المحفظة</dt><dd>{{ number_format($recoveryRequest['refunded_amount'], 2) }} ر.س</dd></div>
            @endif
        </dl>

        @if($slotCheck)
            @php
                [$checkClass, $checkIcon, $checkTitle, $checkText] = match ($slotCheck['state']) {
                    'available' => ['rr-slot-ok', 'fa-circle-check', 'الموعد المطلوب متاح ومناسب', 'لا يتعارض مع زيارات الطلب الأخرى، وتتوفر له عاملات بالعدد المطلوب في منطقة العميل. يمكن اعتماده الآن.'],
                    'past' => ['rr-slot-bad', 'fa-clock-rotate-left', 'الموعد المطلوب مضى', 'لا يمكن اعتماد موعد في الماضي. ارفض الطلب ليختار العميل موعدًا آخر.'],
                    'coverage' => ['rr-slot-bad', 'fa-map-location-dot', 'الخدمة غير متوفرة في منطقة العميل حاليًا', 'لا يمكن اعتماد أي موعد قبل إعادة توفير التغطية في منطقة العميل.'],
                    'ineligible' => ['rr-slot-bad', 'fa-ban', 'الزيارة لم تعد مؤهلة للمعالجة', 'تغيرت حالة الزيارة أو الدفع. حدّث الصفحة أو راجع الطلب.'],
                    default => ['rr-slot-bad', 'fa-circle-xmark', 'الموعد المطلوب غير متاح', 'يتعارض مع زيارة أخرى في الطلب، أو لا تتوفر عاملات كافية في هذا الوقت، أو لا يطابق مواعيد الباقة. ارفض الطلب مع توضيح السبب ليختار العميل موعدًا آخر.'],
                };
            @endphp
            <div class="rr-slot {{ $checkClass }}" role="status">
                <i class="fa {{ $checkIcon }}" aria-hidden="true"></i>
                <div>
                    <b>{{ $checkTitle }}</b>
                    <span>{{ $checkText }}</span>
                    @if($slotCheck['alternatives'])
                        <div class="rr-slot-alts"><small>مواعيد متاحة في اليوم نفسه يمكن اقتراحها على العميل:</small>@foreach($slotCheck['alternatives'] as $alternative)<span dir="ltr">{{ $alternative }}</span>@endforeach</div>
                    @endif
                </div>
            </div>
        @endif

        @if(!empty($recoveryRequest['note']))
            <blockquote class="rr-quote"><span><i class="fa fa-comment" aria-hidden="true"></i> ملاحظة العميل</span>{{ $recoveryRequest['note'] }}</blockquote>
        @endif

        @if($isPending && AdminGuardHasPerm('hourly_orders_create'))
            <form method="post" class="recovery-review-form rr-form" action="{{ route('admin.hourly-recovery.review', [$recoveryOrder->id, $recoveryId]) }}">
                @csrf
                <input type="hidden" name="request_id" value="{{ $recoveryRequest['id'] }}">
                @if($isReplacement)
                    @if(!$replacementReady)
                        <div class="rr-slot rr-slot-bad" role="status"><i class="fa fa-clock-rotate-left" aria-hidden="true"></i><div><b>فات وقت توفير بديلة</b><span>بقي على الزيارة أقل من {{ \App\Services\Hourly\MissedVisitRecovery::REPLACEMENT_LEAD_MINUTES }} دقيقة أو تغيرت حالتها. ارفض الطلب ليختار العميل إعادة الجدولة أو الإلغاء.</span></div></div>
                    @elseif($replacementCandidates->isEmpty())
                        <div class="rr-slot rr-slot-bad" role="status"><i class="fa fa-user-slash" aria-hidden="true"></i><div><b>لا توجد عاملة متاحة لنفس الموعد</b><span>لا تتوفر عاملة في فريق الزيارة بنفس مواصفات الخدمة خلال وقت الزيارة. ارفض الطلب مع توضيح السبب.</span></div></div>
                    @else
                        @foreach($failedWorkers as $failedWorker)
                            <label class="rr-label" for="rr-sub-{{ $recoveryId }}-{{ $failedWorker->id }}">بديلة عن {{ $failedWorker->name }}</label>
                            <select id="rr-sub-{{ $recoveryId }}-{{ $failedWorker->id }}" class="form-select mb-2" name="replacement_worker_ids[{{ $failedWorker->id }}]" required>
                                <option value="">اختر عاملة متاحة</option>
                                @foreach($replacementCandidates as $candidate)<option value="{{ $candidate->id }}">#{{ $candidate->id }} — {{ $candidate->name }}</option>@endforeach
                            </select>
                        @endforeach
                    @endif
                @endif
                <label class="rr-check"><input type="checkbox" name="verified_non_execution" value="1"><span>تحققت من عدم تنفيذ الخدمة من طرف الشركة، وأوافق على معالجة الطلب دون رسوم.</span></label>
                <label class="rr-label" for="rr-reason-{{ $recoveryOrder->id }}-{{ $recoveryId }}">سبب القرار <small>يظهر للعميل؛ لا تكتب تفاصيل صحية أو داخلية</small></label>
                <textarea id="rr-reason-{{ $recoveryOrder->id }}-{{ $recoveryId }}" class="form-control rr-textarea" name="reason" maxlength="2000" rows="2" required></textarea>
                <div class="rr-actions">
                    @if(!$isRefund || AdminGuardHasPerm('hourly_orders_collect'))
                        <button type="submit" name="decision" value="approve" class="btn btn-success" @if($slotCheck && $slotCheck['state'] !== 'available') disabled title="لا يمكن الاعتماد: الموعد المطلوب غير متاح" @elseif($isReplacement && (!$replacementReady || $replacementCandidates->isEmpty())) disabled title="لا توجد بديلة يمكن اعتمادها" @endif><i class="fa fa-check" aria-hidden="true"></i> اعتماد وتنفيذ</button>
                    @endif
                    <button type="submit" name="decision" value="reject" class="btn btn-outline-danger"><i class="fa fa-xmark" aria-hidden="true"></i> رفض مع ذكر السبب</button>
                </div>
                <div class="recovery-review-error alert alert-warning mt-2 mb-0" hidden role="alert"></div>
            </form>
        @elseif(!empty($recoveryRequest['review_reason']))
            <div class="rr-decision"><span><i class="fa fa-gavel" aria-hidden="true"></i> سبب القرار</span>{{ $recoveryRequest['review_reason'] }}</div>
        @endif
    </div>
</article>
@endif
@endforeach
@endforeach
@once
<style>
    .rr-card { --rr-brand: #1b6fb8; --rr-line: #e6edf5; }
    .rr-card { background: #fff; border: 1px solid var(--rr-line); border-radius: 14px; margin-bottom: 1rem; box-shadow: 0 2px 12px rgba(20, 80, 127, .05); overflow: hidden; }
    .rr-card.is-pending { border-inline-start: 4px solid #e0a526; }
    .rr-head { display: flex; align-items: center; gap: .85rem; padding: .9rem 1.1rem; border-bottom: 1px solid var(--rr-line); background: #fbfdff; }
    .rr-type-icon { flex: 0 0 42px; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; }
    .rr-t-reschedule { background: #e3f0fc; color: var(--rr-brand); }
    .rr-t-refund { background: #ede9fe; color: #5b21b6; }
    .rr-title { flex: 1; min-width: 0; }
    .rr-title h6 { margin: 0; font-size: .98rem; font-weight: 800; color: #1e293b; }
    .rr-sub { display: flex; flex-wrap: wrap; gap: .3rem .9rem; margin-top: .25rem; font-size: .8rem; color: #64748b; }
    .rr-order { font-weight: 700; color: var(--rr-brand); text-decoration: none; }
    .rr-order:hover { text-decoration: underline; }
    .rr-status { flex-shrink: 0; display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px; padding: .3rem .8rem; font-size: .78rem; font-weight: 700; white-space: nowrap; }
    .rr-s-pending { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
    .rr-s-approved { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .rr-s-rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

    .rr-body { padding: 1rem 1.1rem 1.1rem; }
    .rr-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: .6rem; margin: 0 0 .9rem; }
    .rr-facts > div { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 11px; padding: .55rem .8rem; }
    .rr-facts dt { font-size: .74rem; font-weight: 600; color: #64748b; margin-bottom: .15rem; }
    .rr-facts dd { margin: 0; font-weight: 800; color: #1e293b; text-align: start; }
    .rr-quote, .rr-decision { margin: 0 0 .9rem; padding: .65rem .9rem; border-radius: 11px; font-size: .88rem; color: #334155; line-height: 1.7; }
    .rr-quote { background: #f8fafc; border-inline-start: 3px solid #94a3b8; }
    .rr-decision { background: #f0f7ff; border-inline-start: 3px solid var(--rr-brand); margin-bottom: 0; }
    .rr-quote span, .rr-decision span { display: block; font-size: .74rem; font-weight: 700; color: #64748b; }
    .rr-alert { display: flex; gap: .6rem; align-items: flex-start; border-radius: 11px; padding: .65rem .85rem; font-size: .84rem; line-height: 1.7; margin-bottom: .9rem; }
    .rr-alert i { margin-top: .3rem; }
    .rr-alert-warn { background: #fffaf0; border: 1px solid #f3dfb2; color: #7c5300; }
    .rr-alert-info { background: #f0f7ff; border: 1px solid #d6e6f7; color: #1d4f7c; }

    .rr-slot { display: flex; gap: .75rem; align-items: flex-start; border-radius: 12px; padding: .75rem .9rem; margin-bottom: .9rem; font-size: .84rem; line-height: 1.7; }
    .rr-slot > i { font-size: 1.3rem; margin-top: .15rem; }
    .rr-slot b { display: block; font-size: .92rem; }
    .rr-slot-ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
    .rr-slot-bad { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .rr-slot-alts { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; margin-top: .45rem; }
    .rr-slot-alts small { width: 100%; color: #7f1d1d; }
    .rr-slot-alts span { background: #fff; border: 1px solid #fecaca; border-radius: 8px; padding: .05rem .55rem; font-weight: 700; color: #1e293b; }
    .rr-actions .btn:disabled { opacity: .45; cursor: not-allowed; }
    .rr-form { border-top: 1px dashed var(--rr-line); padding-top: .9rem; }
    .rr-check { display: flex; gap: .6rem; align-items: flex-start; background: #f8fafc; border: 1px solid #eef2f7; border-radius: 11px; padding: .65rem .85rem; font-size: .86rem; color: #334155; cursor: pointer; margin-bottom: .8rem; }
    .rr-check input { margin-top: .3rem; width: 17px; height: 17px; flex-shrink: 0; accent-color: var(--rr-brand); }
    .rr-label { display: block; font-size: .84rem; font-weight: 700; color: #1e293b; margin-bottom: .35rem; }
    .rr-label small { font-weight: 500; color: #94a3b8; margin-inline-start: .35rem; }
    .rr-textarea { border-radius: 11px; border-color: #d6e3f0; resize: vertical; }
    .rr-textarea:focus { border-color: #2794eb; box-shadow: 0 0 0 .2rem rgba(39, 148, 235, .15); }
    .rr-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .8rem; }
    .rr-actions .btn { border-radius: 10px; font-weight: 700; padding: .45rem 1.1rem; display: inline-flex; align-items: center; gap: .4rem; }
    @media (max-width: 575.98px) { .rr-head { flex-wrap: wrap; } .rr-status { order: -1; margin-inline-start: auto; } .rr-actions .btn { flex: 1; justify-content: center; } }
</style>
<script>
document.querySelectorAll('.recovery-review-form').forEach((form) => form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submitter = event.submitter;
    const errorBox = form.querySelector('.recovery-review-error');
    const data = new FormData(form);
    if (submitter?.name) data.set(submitter.name, submitter.value);
    submitter.disabled = true;
    errorBox.hidden = true;
    try {
        const response = await fetch(form.action, {method: 'POST', body: data, headers: {'Accept': 'application/json'}});
        const payload = await response.json();
        if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'تعذر تنفيذ القرار. حدّث الصفحة وحاول مجددًا.');
        window.location.reload();
    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.hidden = false;
        submitter.disabled = false;
    }
}));
</script>
@endonce
