@extends('admin.layout.indexs.index')
@section('page-title','بلاغات تعذر تنفيذ الخدمة')
@push('css')
<style>
    /* بلاغات تعذّر تنفيذ الخدمة: التحقق من البلاغات ثم متابعة الزيارات المتأثرة حتى إغلاقها */
    .si { --brand: #2794eb; --brand-ink: #1b6fb8; --brand-deep: #14507f; --teal: #00ac9a; --ink: #16212e; --muted: #6b7a8c; --line: #e6ecf3; --soft: #f6f9fc; direction: rtl; color: var(--ink); }
    .si-hero { position: relative; overflow: hidden; border-radius: 18px; padding: 1.3rem 1.4rem; margin-bottom: 1.25rem; color: #fff; background: linear-gradient(105deg, var(--brand-deep) 0%, var(--brand-ink) 45%, var(--brand) 80%, var(--teal) 140%); box-shadow: 0 10px 30px rgba(20, 80, 127, .12); }
    .si-hero::after { content: ''; position: absolute; width: 220px; height: 220px; left: -60px; top: -110px; border-radius: 50%; border: 40px solid rgba(255, 255, 255, .07); }
    .si-hero h3 { position: relative; margin: 0; font-size: 1.3rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: .55rem; }
    .si-hero p { position: relative; margin: .35rem 0 0; font-size: .86rem; opacity: .9; max-width: 760px; }
    .si-flow { position: relative; display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-top: 1rem; }
    .si-flow span { display: inline-flex; align-items: center; gap: .4rem; background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .22); border-radius: 99px; padding: .3rem .75rem; font-size: .78rem; font-weight: 600; }
    .si-flow span b { width: 20px; height: 20px; border-radius: 50%; display: grid; place-items: center; background: #fff; color: var(--brand-ink); font-size: .7rem; }
    .si-flow > i { opacity: .7; font-size: .75rem; }

    .si-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .9rem; margin-bottom: 1.25rem; }
    .si-stat { position: relative; display: flex; align-items: center; gap: .8rem; padding: .95rem 1.1rem; background: #fff; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; box-shadow: 0 6px 18px rgba(20, 80, 127, .05); text-decoration: none; color: inherit; }
    .si-stat::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 4px; background: var(--tone); }
    .si-stat > i { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 12px; display: grid; place-items: center; color: var(--tone); background: var(--tint); }
    .si-stat strong { display: block; font-size: 1.4rem; line-height: 1.2; font-variant-numeric: tabular-nums; }
    .si-stat span { font-size: .8rem; color: var(--muted); }
    .t-amber { --tone: #e08a17; --tint: #fef5e7; } .t-green { --tone: #17996f; --tint: #e8f7f1; } .t-grey { --tone: #7b8a9a; --tint: #eef3f8; } .t-red { --tone: #e5484d; --tint: #fdeeee; } .t-violet { --tone: #6b63d6; --tint: #f0effc; }

    .si-section-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin: 1.5rem 0 .85rem; }
    .si-section-head h4 { margin: 0; font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }
    .si-section-head h4 i { color: var(--brand); }
    .si-section-head small { color: var(--muted); font-size: .8rem; }

    .si-card { background: #fff; border: 1px solid var(--line); border-inline-start: 4px solid var(--tone, #9fb0c2); border-radius: 14px; margin-bottom: .9rem; box-shadow: 0 4px 14px rgba(20, 80, 127, .05); }
    .si-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem 1rem; flex-wrap: wrap; padding: .95rem 1.15rem; }
    .si-card-title { display: flex; align-items: center; gap: .75rem; min-width: 0; }
    .si-card-icon { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 12px; display: grid; place-items: center; background: var(--tint, #eef3f8); color: var(--tone, #7b8a9a); font-size: 1.05rem; }
    .si-card-title b { display: block; font-size: .98rem; }
    .si-card-title b a { color: var(--brand-ink); text-decoration: none; }
    .si-card-title b a:hover { text-decoration: underline; }
    .si-meta { display: flex; flex-wrap: wrap; gap: .25rem .9rem; font-size: .78rem; color: var(--muted); }
    .si-meta i { color: #9fb0c2; margin-inline-end: .2rem; }
    .si-badge { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .7rem; border-radius: 99px; background: var(--tint); color: var(--tone); border: 1px solid transparent; font-size: .76rem; font-weight: 700; white-space: nowrap; }
    .si-card-body { padding: 0 1.15rem 1rem; }
    .si-note { margin: 0; padding: .75rem .9rem; border-radius: 10px; background: var(--soft); border: 1px solid var(--line); font-size: .86rem; line-height: 1.85; white-space: pre-wrap; }
    .si-note-label { display: block; margin-bottom: .3rem; font-size: .74rem; font-weight: 700; color: var(--muted); }
    .si-decision { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: .5rem 1rem; margin-top: .75rem; padding: .75rem .9rem; border-radius: 10px; border: 1px dashed var(--line); font-size: .84rem; }
    .si-decision span { display: block; font-size: .74rem; color: var(--muted); font-weight: 700; }

    .si-form { margin-top: .9rem; padding: 1rem; border-radius: 12px; background: #fbfcfe; border: 1px solid var(--line); }
    .si-form-title { display: flex; align-items: center; gap: .45rem; margin: 0 0 .8rem; font-size: .86rem; font-weight: 700; }
    .si-form-title i { color: var(--brand); }
    .si-grid { display: grid; grid-template-columns: minmax(200px, 260px) minmax(0, 1fr); gap: .8rem 1rem; }
    @media (max-width: 767px) { .si-grid { grid-template-columns: 1fr; } }
    .si-field { display: grid; gap: .35rem; margin: 0; }
    .si-field > span { font-size: .8rem; font-weight: 700; }
    .si-field > span em { font-style: normal; font-weight: 500; color: var(--muted); margin-inline-start: .3rem; }
    .si-field .form-control { min-height: 42px; border-radius: 10px; border-color: #dfe7ef; font-size: .88rem; }
    .si-field textarea.form-control { min-height: 42px; height: 42px; resize: vertical; }
    .si-check { display: flex; align-items: flex-start; gap: .5rem; margin: .8rem 0 0; font-size: .84rem; cursor: pointer; }
    .si-check input { margin-top: .3rem; accent-color: var(--brand-ink); width: 16px; height: 16px; }
    .si-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; margin-top: .9rem; padding-top: .8rem; border-top: 1px dashed var(--line); }
    .si-btn { display: inline-flex; align-items: center; gap: .4rem; height: 40px; padding: 0 1rem; border-radius: 10px; font-size: .84rem; font-weight: 700; border: 1px solid transparent; }
    .si-btn.is-approve { background: #17996f; color: #fff; }
    .si-btn.is-approve:hover { background: #0f7a5a; }
    .si-btn.is-reject { background: #fff; color: #b42a2e; border-color: #f7cdcf; }
    .si-btn.is-reject:hover { background: #fdeeee; }
    .si-btn.is-warn { background: #e08a17; color: #fff; }
    .si-btn.is-warn:hover { filter: brightness(.94); }
    .si-btn.is-ghost { background: #fff; color: #4a5a6b; border-color: var(--line); }
    .si-btn.is-ghost:hover { border-color: #9fb0c2; }
    .si-inline { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; }
    .si-inline .form-control { flex: 1 1 260px; min-height: 40px; border-radius: 10px; border-color: #dfe7ef; font-size: .86rem; }
    .si-info { display: flex; gap: .5rem; padding: .7rem .9rem; border-radius: 10px; background: #f0faf6; border: 1px solid #bfe9d9; color: #0f6b4f; font-size: .82rem; line-height: 1.8; }
    .si-empty { text-align: center; padding: 2.4rem 1rem; color: var(--muted); background: #fff; border: 1px dashed #cfd9e3; border-radius: 14px; }
    .si-empty i { display: block; font-size: 1.8rem; color: #9fd8c5; margin-bottom: .5rem; }
    .si-pager { margin-top: .5rem; }
</style>
@endpush
@section('content')
@php
    $statusMeta = ['pending' => ['بانتظار التحقق', 't-amber', 'fa-hourglass-half'], 'approved' => ['معتمد', 't-green', 'fa-circle-check'], 'rejected' => ['مرفوض', 't-grey', 'fa-circle-xmark']];
    $categoryMeta = ['illness' => ['مرض', 'fa-notes-medical'], 'accident' => ['حادث', 'fa-car-burst'], 'emergency' => ['ظرف طارئ', 'fa-triangle-exclamation']];
    $sources = ['worker' => 'العاملة', 'driver' => 'السائق', 'admin' => 'الإدارة'];
    $followMeta = [
        'awaiting_customer' => ['بانتظار اختيار العميل', 't-violet', 'fa-user-clock', 'متابعة مطلوبة: العميل يختار بديلة أو يطلب جدولة أو استردادًا.'],
        'awaiting_admin' => ['بانتظار الإدارة', 't-amber', 'fa-user-gear', 'استبدل العاملة من صفحة الطلب، أو انتظر طلب معالجة من العميل.'],
        'manual_review' => ['مراجعة يدوية', 't-red', 'fa-route', 'بدأ التنفيذ أو توجد حركة خروج؛ عالج العودة والتسوية أولًا.'],
    ];
    $canAct = AdminGuardHasPerm('hourly_orders_create');
@endphp
<div class="container-fluid si">
    @include('admin.hourly.messages')

    <header class="si-hero">
        <h3><i class="fa fa-notes-medical"></i> بلاغات تعذر تنفيذ الخدمة</h3>
        <p>تحقق من البلاغ قبل اعتماده. الاعتماد يسجل فترة عدم جاهزية للعاملة ويشمل زياراتها المتأثرة خلالها. تفاصيل السبب داخلية ولا ترسل للعميل.</p>
        <div class="si-flow" aria-label="مراحل البلاغ">
            <span><b>1</b> بلاغ من العاملة أو السائق</span><i class="fa fa-arrow-left"></i>
            <span><b>2</b> تحقق الإدارة واعتماد الفترة</span><i class="fa fa-arrow-left"></i>
            <span><b>3</b> معالجة الزيارات المتأثرة</span><i class="fa fa-arrow-left"></i>
            <span><b>4</b> إغلاق المتابعة بعد التسوية</span>
        </div>
    </header>

    <div class="si-stats">
        <a class="si-stat t-amber" href="#si-incidents"><i class="fa fa-hourglass-half"></i><div><strong>{{ (int) ($counts['pending'] ?? 0) }}</strong><span>بانتظار التحقق</span></div></a>
        <a class="si-stat t-red" href="#si-followups"><i class="fa fa-list-check"></i><div><strong>{{ $followups }}</strong><span>زيارات تحتاج متابعة</span></div></a>
        <div class="si-stat t-green"><i class="fa fa-circle-check"></i><div><strong>{{ (int) ($counts['approved'] ?? 0) }}</strong><span>بلاغات معتمدة</span></div></div>
        <div class="si-stat t-grey"><i class="fa fa-circle-xmark"></i><div><strong>{{ (int) ($counts['rejected'] ?? 0) }}</strong><span>بلاغات مرفوضة</span></div></div>
    </div>

    {{-- البلاغات --}}
    <div class="si-section-head" id="si-incidents">
        <h4><i class="fa fa-inbox"></i> البلاغات</h4>
        <small>البلاغات بانتظار التحقق تظهر أولًا</small>
    </div>
    @forelse($incidents as $incident)
        @php
            [$statusLabel, $tone, $statusIcon] = $statusMeta[$incident->status] ?? [$incident->status, 't-grey', 'fa-circle'];
            [$categoryLabel, $categoryIcon] = $categoryMeta[$incident->category] ?? [$incident->category, 'fa-circle-info'];
        @endphp
        <article class="si-card {{ $tone }}">
            <div class="si-card-head">
                <div class="si-card-title">
                    <span class="si-card-icon"><i class="fa {{ $categoryIcon }}"></i></span>
                    <div>
                        <b>بلاغ #{{ $incident->id }} — {{ $incident->worker_name ?: 'عاملة #'.$incident->maid_id }} · @if($incident->order_id)<a href="{{ route('admin.hourly-orders.show',$incident->order_id) }}">طلب {{ $incident->order_number }}</a>@else طلب غير متاح @endif</b>
                        <div class="si-meta">
                            <span><i class="fa fa-tag"></i>{{ $categoryLabel }}</span>
                            <span><i class="fa fa-user"></i>المصدر: {{ $sources[$incident->reporter_type] ?? $incident->reporter_type }}</span>
                            <span><i class="fa fa-calendar"></i>زيارة #{{ $incident->visit_id }}@if($incident->visit_start) · <bdi>{{ \Carbon\Carbon::parse($incident->visit_start)->format('Y-m-d H:i') }}</bdi>@endif</span>
                            <span dir="ltr"><i class="fa fa-clock"></i>{{ \Carbon\Carbon::parse($incident->created_at)->format('Y-m-d H:i') }}</span>
                        </div>
                    </div>
                </div>
                <span class="si-badge"><i class="fa {{ $statusIcon }}"></i> {{ $statusLabel }}</span>
            </div>
            <div class="si-card-body">
                <span class="si-note-label">تفاصيل البلاغ (داخلية)</span>
                <p class="si-note">{{ $incident->note }}</p>

                @if($incident->status === 'pending' && $canAct)
                    <form class="si-form" method="post" action="{{ route('admin.hourly-incidents.review',$incident->id) }}">
                        @csrf
                        <p class="si-form-title"><i class="fa fa-scale-balanced"></i> قرار الإدارة</p>
                        <div class="si-grid">
                            <label class="si-field"><span>العاملة غير متاحة من <em>(افتراضيًا: موعد الزيارة المبلَّغ عنها)</em></span>
                                <input type="datetime-local" class="form-control" name="unavailable_from" value="{{ $incident->visit_start ? \Carbon\Carbon::parse($incident->visit_start)->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i') }}">
                            </label>
                            <label class="si-field"><span>العاملة غير متاحة حتى <em>(مطلوب للاعتماد)</em></span>
                                <input type="datetime-local" class="form-control" name="unavailable_until" min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ $incident->visit_end && \Carbon\Carbon::parse($incident->visit_end)->isFuture() ? \Carbon\Carbon::parse($incident->visit_end)->format('Y-m-d\TH:i') : '' }}">
                            </label>
                            <label class="si-field"><span>سبب قرار الإدارة <em>(داخلي)</em></span>
                                <textarea class="form-control" name="reason" required maxlength="2000"></textarea>
                            </label>
                        </div>
                        <label class="si-check"><input type="checkbox" name="verified" value="1"> تحققت من البلاغ وأعتمد عدم الجاهزية للفترة المحددة.</label>
                        <div class="si-actions">
                            <button class="si-btn is-reject" name="decision" value="reject"><i class="fa fa-xmark"></i> رفض البلاغ</button>
                            <button class="si-btn is-approve" name="decision" value="approve"><i class="fa fa-check"></i> اعتماد وتفعيل المعالجة</button>
                        </div>
                    </form>
                @elseif($incident->status !== 'pending')
                    <div class="si-decision">
                        <div><span>قرار الإدارة</span>{{ $incident->review_reason ?: '—' }}</div>
                        @if($incident->unavailable_from ?? null)<div><span>بداية فترة عدم الجاهزية</span><bdi>{{ \Carbon\Carbon::parse($incident->unavailable_from)->format('Y-m-d H:i') }}</bdi></div>@endif
                        <div><span>نهاية فترة عدم الجاهزية</span><bdi>{{ $incident->unavailable_until ? \Carbon\Carbon::parse($incident->unavailable_until)->format('Y-m-d H:i') : '—' }}</bdi></div>
                        @if($incident->reviewed_at)<div><span>تاريخ المراجعة</span><bdi>{{ \Carbon\Carbon::parse($incident->reviewed_at)->format('Y-m-d H:i') }}</bdi></div>@endif
                    </div>
                @endif
            </div>
        </article>
    @empty
        <div class="si-empty"><i class="fa fa-circle-check"></i> لا توجد بلاغات تعذّر تنفيذ.</div>
    @endforelse
    <div class="si-pager">{{ $incidents->links() }}</div>

    {{-- الزيارات التي تنتظر المعالجة --}}
    <div class="si-section-head" id="si-followups">
        <h4><i class="fa fa-list-check"></i> زيارات تنتظر المعالجة والمتابعة</h4>
        <small>{{ $followups }} زيارة</small>
    </div>
    @php $shownFollowups = 0; @endphp
    @foreach($affected as $affectedOrder)
        @foreach(($affectedOrder->booking_metadata['service_disruptions']??[]) as $visitId=>$state)
            @if(in_array($state['status'],\App\Services\Hourly\ServiceIncident::WAITING,true))
                @php
                    $shownFollowups++;
                    [$followLabel, $followTone, $followIcon, $followHint] = $followMeta[$state['status']];
                    $followVisit = $affectedOrder->visits->firstWhere('id', (int) $visitId);
                @endphp
                <article class="si-card {{ $followTone }}">
                    <div class="si-card-head">
                        <div class="si-card-title">
                            <span class="si-card-icon"><i class="fa {{ $followIcon }}"></i></span>
                            <div>
                                <b><a href="{{ route('admin.hourly-orders.show',$affectedOrder->id) }}#visit-{{ $visitId }}">طلب {{ $affectedOrder->order_number }}</a> — زيارة {{ $followVisit?->visit_number ?? '' }} <small class="text-muted">(مرجع #{{ $visitId }})</small></b>
                                <div class="si-meta">
                                    @if($followVisit?->scheduled_start_at)<span dir="ltr"><i class="fa fa-calendar"></i>{{ $followVisit->scheduled_start_at->format('Y-m-d H:i') }}</span>@endif
                                    <span>{{ $followHint }}</span>
                                </div>
                            </div>
                        </div>
                        <span class="si-badge"><i class="fa {{ $followIcon }}"></i> {{ $followLabel }}</span>
                    </div>
                    @if($canAct)
                    <div class="si-card-body">
                        @if($state['status']==='manual_review')
                            @if(empty($state['return_recorded_at']))
                                <form class="si-form" method="post" action="{{ route('admin.hourly-incidents.return',[$affectedOrder->id,$visitId]) }}">
                                    @csrf
                                    <p class="si-form-title"><i class="fa fa-house-circle-check"></i> تأكيد عودة العاملة</p>
                                    <label class="si-check mt-0"><input type="checkbox" name="confirmed_return" value="1" required> تحققت من عودة العاملة فعليًا إلى الإيواء.</label>
                                    <div class="si-inline mt-2">
                                        <input class="form-control" name="reason" required maxlength="2000" placeholder="مرجع تأكيد العودة وسبب الإجراء">
                                        <button class="si-btn is-warn"><i class="fa fa-house-circle-check"></i> تسجيل العودة فقط دون إنهاء الزيارة أو تعديل المبالغ</button>
                                    </div>
                                </form>
                            @else
                                <p class="si-info"><i class="fa fa-circle-check mt-1"></i><span>سُجلت العودة: <bdi>{{ $state['return_recorded_at'] }}</bdi>. راجع الجزء المنفذ والمستحقات؛ الإلغاء بعد العودة يحتاج سببًا وصلاحية الاسترداد عند وجود مبلغ مدفوع.</span></p>
                            @endif
                        @endif
                        <form class="si-form" method="post" action="{{ route('admin.hourly-incidents.close',[$affectedOrder->id,$visitId]) }}">
                            @csrf
                            <p class="si-form-title"><i class="fa fa-flag-checkered"></i> إغلاق المتابعة</p>
                            <div class="si-inline">
                                <input name="reason" class="form-control" placeholder="سبب إغلاق المتابعة بعد تسوية التنفيذ والعودة" required maxlength="2000">
                                <button class="si-btn is-ghost"><i class="fa fa-flag-checkered"></i> إغلاق المتابعة بعد التسوية</button>
                            </div>
                        </form>
                    </div>
                    @endif
                </article>
            @endif
        @endforeach
    @endforeach
    @if(!$shownFollowups)
        <div class="si-empty"><i class="fa fa-circle-check"></i> لا توجد زيارات تنتظر المعالجة.</div>
    @endif
    <div class="si-pager">{{ $affected->links() }}</div>
</div>
@endsection
