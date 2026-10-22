{{-- قائمة مختصرة لقائمتي «غير المخصصة» و«الماضية غير المنتهية»: كل صف يبرز مدى إلحاح الزيارة وما ينقصها. --}}
@once
@push('css')
<style>
    .vc-table { width: 100%; border-collapse: separate; border-spacing: 0 .55rem; font-size: .85rem; margin-top: .5rem; }
    .vc-table th { font-size: .76rem; color: var(--muted); font-weight: 700; padding: .35rem .8rem; white-space: nowrap; text-align: start; }
    .vc-table td { background: #fff; padding: .8rem; vertical-align: middle; border-block: 1px solid var(--line); }
    .vc-table td:first-child { border-inline-start: 4px solid var(--vc-tone, #9fb0c2); border-start-start-radius: 14px; border-end-start-radius: 14px; }
    .vc-table td:last-child { border-inline-end: 1px solid var(--line); border-start-end-radius: 14px; border-end-end-radius: 14px; }
    .vc-table tr.is-selected td { background: #f3f9ff; }
    .vc-table tbody tr:hover td { background: #fbfdff; }
    .vc-table a { color: var(--brand-ink); font-weight: 700; text-decoration: none; }
    .vc-table a:hover { text-decoration: underline; }
    .vc-red { --vc-tone: #e5484d; } .vc-amber { --vc-tone: #e08a17; } .vc-blue { --vc-tone: #2794eb; } .vc-grey { --vc-tone: #9fb0c2; }
    .vc-sub { display: block; font-size: .74rem; color: var(--muted); margin-top: .15rem; }
    .vc-num { font-variant-numeric: tabular-nums; }
    .vc-id { font-size: .95rem; }
    .vc-day { font-weight: 700; color: var(--ink); white-space: nowrap; }
    .vc-time { color: var(--brand-ink); font-weight: 700; white-space: nowrap; }
    .vc-urgency { display: inline-flex; align-items: center; gap: .3rem; margin-top: .3rem; border-radius: 8px; padding: .12rem .5rem; font-size: .72rem; font-weight: 700; border: 1px solid transparent; white-space: nowrap; }
    .vc-urgency.vc-red { background: #fdeeee; color: #b42a2e; border-color: #f7cdcf; }
    .vc-urgency.vc-amber { background: #fef5e7; color: #9a6206; border-color: #f7ddb2; }
    .vc-urgency.vc-blue { background: #eaf5fe; color: #1b6fb8; border-color: #c4e2f8; }
    .vc-urgency.vc-grey { background: #eef3f8; color: #5a6b7d; border-color: #dbe4ee; }
    .vc-customer { min-width: 150px; }
    .vc-customer b { color: var(--ink); }
    .vc-address { min-width: 180px; max-width: 260px; font-size: .8rem; color: var(--ink); }
    .vc-address i { color: #9fb0c2; }
    .vc-need { white-space: nowrap; }
    .vc-seats { display: inline-flex; align-items: center; gap: .35rem; font-weight: 700; color: #9a6206; background: #fef5e7; border: 1px dashed #f2c48a; border-radius: 8px; padding: .15rem .55rem; font-size: .78rem; }
    .vc-price { font-weight: 700; color: var(--ink); white-space: nowrap; }
    .vc-status { display: inline-flex; align-items: center; gap: .3rem; border-radius: 8px; padding: .12rem .5rem; font-size: .72rem; font-weight: 700; background: #eef3f8; color: #5a6b7d; border: 1px solid #dbe4ee; margin-top: .3rem; }
    .vc-offered { display: inline-flex; align-items: center; gap: .3rem; margin-top: .35rem; font-size: .72rem; font-weight: 700; color: #4f47b8; }
    .vc-actions { display: flex; gap: .35rem; flex-wrap: wrap; justify-content: flex-end; min-width: 190px; }
    .vc-actions .btn { border-radius: 9px; font-size: .78rem; font-weight: 700; padding: .35rem .7rem; display: inline-flex; align-items: center; gap: .35rem; }
    .vc-empty { text-align: center; padding: 3rem 1rem; color: var(--muted); }
    .vc-empty i { font-size: 2rem; color: #9fd8c5; display: block; margin-bottom: .6rem; }
    .vc-check { width: 1.1rem; height: 1.1rem; cursor: pointer; }
</style>
@endpush
@endonce
@php
    /* مدى الإلحاح: يعتمد على بداية الزيارة لغير المخصصة، وعلى نهايتها للماضية غير المنتهية. */
    $urgency = function ($visit) use ($mode) {
        $at = $mode === 'past' ? $visit->scheduled_end_at : $visit->scheduled_start_at;
        if (!$at) return ['vc-grey', 'fa-calendar-xmark', 'بلا موعد'];
        $minutes = now()->diffInMinutes($at, false);
        if ($mode === 'past') return [$minutes < -1440 ? 'vc-red' : 'vc-amber', 'fa-hourglass-end', 'انتهى موعدها '.$at->diffForHumans()];
        return match (true) {
            $minutes < 0 => ['vc-red', 'fa-triangle-exclamation', 'فات موعدها '.$at->diffForHumans()],
            $minutes <= 360 => ['vc-red', 'fa-stopwatch', 'تبدأ '.$at->diffForHumans()],
            $minutes <= 1440 => ['vc-amber', 'fa-clock', 'تبدأ '.$at->diffForHumans()],
            default => ['vc-blue', 'fa-calendar', 'تبدأ '.$at->diffForHumans()],
        };
    };
@endphp
<div class="table-responsive">
<table class="vc-table">
    <thead><tr>
        <th><span class="visually-hidden">تحديد</span></th>
        <th data-column="info">الزيارة</th>
        <th data-column="date">الموعد</th>
        <th data-column="customer">العميل</th>
        <th data-column="address">العنوان</th>
        <th data-column="duration">{{ $mode === 'unassigned' ? 'المطلوب' : 'المدة' }}</th>
        @if($mode === 'unassigned')<th data-column="price">السعر</th>@endif
        <th class="text-end">الإجراء</th>
    </tr></thead>
    <tbody>
    @forelse($visits as $visit)
        @php
            [$tone, $icon, $label] = $urgency($visit);
            $order = $visit->order;
            $phone = $visit->contact_phone ?: $order->contact_phone ?: $order->user?->phone;
        @endphp
        <tr data-visit-card="{{ $visit->id }}" class="{{ $tone }}">
            <td><input class="form-check-input vc-check" type="checkbox" data-select-visit data-collectable="{{ $collectables[$visit->id] ?? 0 }}" data-order-id="{{ $visit->hourly_rental_order_id }}" data-order-remaining="{{ max(0, $visit->order->total_amount - $visit->order->paid_amount) }}" data-started="{{ $visit->has_started_execution ? '1' : '0' }}" value="{{ $visit->id }}" aria-label="تحديد زيارة {{ $visit->id }}"></td>
            <td data-column="info">
                <a class="vc-id vc-num" href="{{ route('admin.hourly-visits.show', $visit) }}">#{{ $visit->id }}</a>
                <span class="vc-sub">طلب <a class="vc-num" href="{{ route('admin.hourly-orders.show', $order) }}">#{{ $order->order_number }}</a> · زيارة {{ $visit->visit_number }}</span>
                <span class="vc-sub"><i class="fa fa-briefcase"></i> {{ $order->serviceCategory?->name ?: '—' }}</span>
                @if($mode === 'past')<span class="vc-status"><i class="fa fa-circle-half-stroke"></i> {{ __('hourly.'.$visit->display_status) }}</span>@endif
            </td>
            <td data-column="date">
                @if($visit->scheduled_start_at)
                    <span class="vc-day">{{ $visit->scheduled_start_at->translatedFormat('l') }} <span class="vc-num" dir="ltr">{{ $visit->scheduled_start_at->format('d/m/Y') }}</span></span>
                    <span class="vc-sub"><span class="vc-time vc-num" dir="ltr">{{ $visit->scheduled_start_at->format('H:i') }} – {{ $visit->scheduled_end_at?->format('H:i') }}</span></span>
                @else
                    <span class="vc-day">لم يُحدَّد الموعد</span>
                @endif
                <span class="vc-urgency {{ $tone }}"><i class="fa {{ $icon }}"></i> {{ $label }}</span>
                @include('admin.hourly_visits._schedule-button')
            </td>
            <td data-column="customer" class="vc-customer">
                @if($order->user)<a href="{{ route('clients.show', $order->user_id) }}">{{ $order->user->name }}</a>@else<b>عميل محذوف</b>@endif
                @if($phone)<span class="vc-sub vc-num" dir="ltr"><i class="fa fa-phone"></i> {{ $phone }}</span>@endif
            </td>
            <td data-column="address" class="vc-address">
                <i class="fa fa-location-dot"></i> {{ $visit->district ?: $order->district ?: '' }}{{ ($visit->district ?: $order->district) ? ' — ' : '' }}{{ \Illuminate\Support\Str::limit($visit->address_text ?: $order->address_text, 70) }}
                <a class="vc-sub" target="_blank" rel="noopener" href="{{ $visit->map_url }}"><i class="fa fa-map-location-dot"></i> الخريطة</a>
            </td>
            <td data-column="duration" class="vc-need">
                @if($mode === 'unassigned')<span class="vc-seats"><i class="fa fa-user-plus"></i> {{ $visit->workers_count }} {{ (int) $visit->workers_count === 1 ? 'عاملة' : 'عاملات' }}</span>@endif
                <span class="vc-sub"><i class="fa fa-hourglass-half"></i> {{ rtrim(rtrim(number_format((float) $visit->planned_hours, 2), '0'), '.') }} ساعات</span>
                @include('admin.hourly_visits._delay')
            </td>
            @if($mode === 'unassigned')
                <td data-column="price"><span class="vc-price vc-num">{{ number_format((float) $visit->visit_price, 2) }}</span> <span class="vc-sub d-inline">ر.س</span></td>
            @endif
            <td>
                <div class="vc-actions">
                @if($canEdit)
                    @if($mode === 'unassigned')
                        <button class="btn btn-sm btn-primary" data-single="assign_all" data-id="{{ $visit->id }}"><i class="fa fa-wand-magic-sparkles"></i> تعيين تلقائي</button>
                        <button class="btn btn-sm btn-outline-primary" data-single="offer" data-id="{{ $visit->id }}"><i class="fa fa-bullhorn"></i> {{ $visit->offered_to_workers ? 'إعداد العرض' : 'عرض للعاملات' }}</button>
                    @else
                        @if($visit->is_missed)
                            @if(!$visit->has_started_execution)<button class="btn btn-sm btn-warning" data-single="reschedule" data-id="{{ $visit->id }}"><i class="fa fa-calendar-days"></i> إعادة الجدولة</button>@endif
                        @else
                            <button class="btn btn-sm btn-success" data-single="complete" data-id="{{ $visit->id }}"><i class="fa fa-circle-check"></i> إكمال</button>
                        @endif
                        @if(!$visit->has_started_execution)<button class="btn btn-sm btn-outline-danger" data-single="cancel_visit" data-id="{{ $visit->id }}"><i class="fa fa-ban"></i> إلغاء</button>@endif
                    @endif
                @endif
                </div>
                @if($mode === 'unassigned' && $visit->offered_to_workers)<span class="vc-offered"><i class="fa fa-bullhorn"></i> معروضة للعاملات المؤهلات</span>@endif
            </td>
        </tr>
    @empty
        <tr><td colspan="8" class="vc-empty" style="border-inline:1px solid var(--line);border-radius:14px">
            <i class="fa {{ $mode === 'unassigned' ? 'fa-user-check' : 'fa-circle-check' }}"></i>
            {{ $mode === 'unassigned' ? 'كل الزيارات المطابقة لها عاملات. لا يوجد ما يحتاج تعيينًا.' : 'لا توجد زيارات ماضية معلّقة للفلاتر الحالية.' }}
        </td></tr>
    @endforelse
    </tbody>
</table>
</div>
