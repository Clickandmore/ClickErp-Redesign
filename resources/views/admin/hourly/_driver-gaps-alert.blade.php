{{-- زيارات اليوم المتوقفة على تعيين سائق (DriverGaps): المتأخرة أولًا بالأحمر، والقادمة مع السائق المقترح وإسناده بضغطة. يتطلب: $driverGaps --}}
@if(($driverGaps ?? collect())->isNotEmpty())
@php
    $dgLate = $driverGaps->filter(fn ($row) => $row['late_minutes'] > 0)->count();
    $dgCanAssign = AdminGuardHasPerm('hourly_orders_create');
    $dgSuggest = app(\App\Services\Hourly\NearestDriverAssignment::class);
@endphp
<style>
    .dg { --dg-red:#c2312f; --dg-red-bg:#fff1f1; --dg-amber:#b45309; --dg-amber-bg:#fff8eb; --dg-line:#eef1f5;
          border:1px solid #f4c7c3; border-radius:18px; background:linear-gradient(135deg,#fff6f5 0%,#fff 55%); padding:16px 18px; margin-bottom:18px; box-shadow:0 6px 22px rgba(194,49,47,.07); }
    .dg-head { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .dg-icon { position:relative; width:46px; height:46px; flex:0 0 46px; border-radius:14px; display:grid; place-items:center; color:#fff; font-size:1.15rem; background:linear-gradient(135deg,#ef4444,#b91c1c); box-shadow:0 6px 14px rgba(185,28,28,.25); }
    .dg-icon.is-calm { background:linear-gradient(135deg,#f59e0b,#d97706); box-shadow:0 6px 14px rgba(217,119,6,.22); }
    .dg-pulse { position:absolute; inset:-4px; border-radius:17px; border:2px solid rgba(239,68,68,.45); animation:dg-pulse 1.8s ease-out infinite; }
    @keyframes dg-pulse { 0% { transform:scale(.92); opacity:1; } 100% { transform:scale(1.18); opacity:0; } }
    @media (prefers-reduced-motion: reduce) { .dg-pulse { animation:none; } }
    .dg h5 { margin:0; font-size:1.02rem; font-weight:800; color:#7f1d1d; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .dg-count { display:inline-grid; place-items:center; min-width:26px; height:26px; padding:0 .45rem; border-radius:999px; background:#b91c1c; color:#fff; font-size:.82rem; }
    .dg-head p { margin:3px 0 0; color:#78716c; font-size:.83rem; }
    .dg-toggle { margin-inline-start:auto; border-radius:10px; font-weight:700; }
    .dg-list { display:grid; gap:10px; margin-top:14px; }
    .dg-row { display:grid; grid-template-columns:auto minmax(150px,1fr) minmax(170px,1.1fr) minmax(220px,1.5fr) auto; align-items:center; gap:14px;
              background:#fff; border:1px solid var(--dg-line); border-inline-start:4px solid var(--dg-amber); border-radius:14px; padding:11px 14px; }
    .dg-row.is-late { border-inline-start-color:var(--dg-red); background:linear-gradient(90deg,var(--dg-red-bg),#fff 40%); }
    .dg-time { text-align:center; min-width:74px; }
    .dg-time b { display:block; font-size:1.15rem; font-weight:800; color:#0f172a; font-variant-numeric:tabular-nums; }
    .dg-chip { display:inline-flex; align-items:center; gap:.3rem; margin-top:3px; padding:.12rem .5rem; border-radius:999px; font-size:.72rem; font-weight:800; white-space:nowrap; }
    .dg-chip.is-late { background:#fee2e2; color:var(--dg-red); }
    .dg-chip.is-soon { background:var(--dg-amber-bg); color:var(--dg-amber); }
    .dg-order a { font-weight:800; color:#1b6fb8; text-decoration:none; }
    .dg-order a:hover { text-decoration:underline; }
    .dg-meta { display:block; color:#64748b; font-size:.8rem; margin-top:2px; }
    .dg-legs { display:flex; flex-wrap:wrap; gap:.35rem; }
    .dg-leg { display:inline-flex; align-items:center; gap:.3rem; padding:.18rem .55rem; border-radius:8px; background:#f1f5f9; color:#334155; font-size:.76rem; font-weight:700; }
    .dg-leg.is-missing { background:#fee2e2; color:#991b1b; }
    .dg-suggest { font-size:.8rem; color:#245a86; }
    .dg-suggest b { color:#0f3d63; }
    .dg-suggest.is-none { color:#b42a2e; }
    .dg-actions { display:flex; gap:.4rem; flex-wrap:wrap; justify-content:flex-end; }
    .dg-actions form { margin:0; }
    .dg-actions .btn { border-radius:9px; font-weight:700; white-space:nowrap; }
    @media (max-width: 1199.98px) { .dg-row { grid-template-columns:auto 1fr 1fr; } .dg-suggest-cell, .dg-actions { grid-column:1 / -1; } .dg-actions { justify-content:flex-start; } }
    @media (max-width: 575.98px) { .dg { padding:14px; } .dg-toggle { width:100%; margin:0; } .dg-row { grid-template-columns:1fr; } .dg-time { text-align:start; } }
</style>
<section class="dg" role="region" aria-label="زيارات اليوم بدون سائق">
    <div class="dg-head">
        <span class="dg-icon {{ $dgLate ? '' : 'is-calm' }}" aria-hidden="true">@if($dgLate)<span class="dg-pulse"></span>@endif<i class="fa fa-van-shuttle"></i></span>
        <div>
            <h5>زيارات اليوم بدون سائق <span class="dg-count">{{ $driverGaps->count() }}</span>
                @if($dgLate)<span class="dg-chip is-late"><i class="fa fa-triangle-exclamation"></i> {{ $dgLate }} متأخرة</span>@endif
            </h5>
            <p>عاملات هذه الزيارات يحتجن سائقًا ولم يُعيَّن بعد؛ لن تبدأ الزيارة قبل تعيين سائق التوصيل.</p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger dg-toggle" data-bs-toggle="collapse" data-bs-target="#dg-list" aria-expanded="{{ $dgLate ? 'true' : 'false' }}" aria-controls="dg-list">
            <i class="fa fa-list ms-1"></i> عرض الزيارات
        </button>
    </div>
    <div class="collapse {{ $dgLate ? 'show' : '' }}" id="dg-list">
        <div class="dg-list">
            @foreach($driverGaps as $row)
                @php
                    $gv = $row['visit']; $late = $row['late_minutes'] > 0;
                    $picks = !$late && $dgCanAssign ? $dgSuggest->suggest($gv, 15, $row['missing']) : [];
                    $assignable = array_keys(array_filter($picks));
                @endphp
                <article class="dg-row {{ $late ? 'is-late' : '' }}">
                    <div class="dg-time">
                        <b dir="ltr">{{ $gv->scheduled_start_at->format('H:i') }}</b>
                        @if($late)
                            <span class="dg-chip is-late"><i class="fa fa-clock"></i> متأخرة {{ \App\Services\Hourly\DriverGaps::startsIn($gv->scheduled_start_at) }}</span>
                        @else
                            <span class="dg-chip is-soon"><i class="fa fa-hourglass-half"></i> بعد {{ \App\Services\Hourly\DriverGaps::startsIn($gv->scheduled_start_at) }}</span>
                        @endif
                    </div>
                    <div class="dg-order">
                        <a href="{{ route('admin.hourly-orders.show', $gv->order) }}">{{ $gv->order->order_number }}</a> <span class="dg-meta d-inline">· زيارة {{ $gv->visit_number }}</span>
                        <span class="dg-meta">{{ $gv->order->user?->name ?? '—' }} · <bdi>{{ $gv->order->user?->phone }}</bdi></span>
                    </div>
                    <div>
                        <div class="dg-legs">
                            @foreach(['delivery', 'pickup'] as $leg)
                                <span class="dg-leg {{ in_array($leg, $row['missing'], true) ? 'is-missing' : '' }}"><i class="fa {{ in_array($leg, $row['missing'], true) ? 'fa-xmark' : 'fa-check' }}"></i> {{ \App\Services\Hourly\DriverGaps::roleLabel($leg) }}</span>
                            @endforeach
                        </div>
                        <span class="dg-meta"><i class="fa fa-user-nurse"></i> {{ $gv->workers->pluck('name')->implode('، ') }}</span>
                    </div>
                    <div class="dg-suggest-cell">
                        @if($late)
                            <span class="dg-suggest is-none"><i class="fa fa-circle-exclamation"></i> فات موعد التوصيل؛ عيّن سائقًا يدويًا أو أعد جدولة الزيارة مع العميل.</span>
                        @elseif($assignable)
                            <span class="dg-suggest"><i class="fa fa-lightbulb"></i> مقترح:
                                @foreach($picks as $role => $pick){{ \App\Services\Hourly\DriverGaps::roleLabel($role) }} <b>{{ $pick['driver']->name ?? 'لا يوجد متاح' }}</b>@if(!$loop->last) · @endif @endforeach
                            </span>
                        @elseif($dgCanAssign)
                            <span class="dg-suggest is-none"><i class="fa fa-user-slash"></i> لا يوجد سائق متاح من فريق الزيارة في هذا الموعد.</span>
                        @endif
                    </div>
                    <div class="dg-actions">
                        @if($assignable)
                            <form method="POST" action="{{ route('admin.hourly-orders.operations', $gv->order) }}" onsubmit="return confirm('إسناد السائق المقترح لزيارة {{ $gv->order->order_number }}؟')">
                                @csrf
                                <input type="hidden" name="action" value="auto_driver">
                                <input type="hidden" name="visit_ids[]" value="{{ $gv->id }}">
                                <input type="hidden" name="driver_direction" value="{{ count($assignable) === 2 ? 'together' : $assignable[0] }}">
                                <input type="hidden" name="reason" value="إسناد السائق المقترح من تنبيه زيارات اليوم">
                                <button class="btn btn-sm btn-primary"><i class="fa fa-check"></i> إسناد المقترح</button>
                            </form>
                        @endif
                        <a class="btn btn-sm {{ $late ? 'btn-danger' : 'btn-outline-secondary' }}" href="{{ route('admin.hourly-orders.show', $gv->order) }}#visit-{{ $gv->id }}"><i class="fa fa-up-right-from-square"></i> {{ $late ? 'معالجة الآن' : 'فتح الطلب' }}</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
