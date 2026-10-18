@extends('web.hourly.layout')
@section('style')
<link rel="stylesheet" href="{{ asset('web/assets/css/hourly-customer-orders.css') }}?v={{ filemtime(public_path('web/assets/css/hourly-customer-orders.css')) }}">
<style>
.hr-pagination .pagination { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px; padding: 0; margin: 0; list-style: none; }
.hr-pagination .page-link { display: flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; margin: 0; border: 1px solid #dce5f2; border-radius: 10px; background: #fff; color: #1d6fb8; font-size: 14px; line-height: 1; text-decoration: none; }
.hr-pagination .page-item.active .page-link { background: #1d6fb8; border-color: #1d6fb8; color: #fff; }
.hr-pagination .page-item.disabled .page-link { color: #94a3b8; background: #f8fafc; }
.hr-pagination a.page-link:hover, .hr-pagination a.page-link:focus-visible { background: #eaf3fc; border-color: #1d6fb8; }
</style>
@endsection
@section('content')
@php
    // ملخص الحجوزات في الصفحة الحالية: يكفي للتوجيه دون استعلام إضافي.
    $title = function ($order) {
        $snapshot = $order->package_snapshot ?? [];
        return collect($snapshot['translations'] ?? [])->firstWhere('locale', app()->getLocale())['title'] ?? $snapshot['title'] ?? $snapshot['code'] ?? 'باقة ساعات';
    };
@endphp
<main>
    @include('web.hourly._header', ['title' => 'حجوزات الساعات', 'crumb' => 'حجوزاتي'])
    <section class="hr-page" dir="rtl">
        <div class="container">
            @include('web.hourly._nav')

            <div class="ho-head">
                <div>
                    <h2>حجوزاتي</h2>
                    <p>{{ $orders->total() }} {{ $orders->total() === 1 ? 'حجز' : 'حجوزات' }} · تابعي مواعيد الزيارات وحالة الدفع لكل حجز.</p>
                </div>
                <a class="ho-new" href="{{ route('web.hourly.catalog') }}"><i class="fa-light fa-plus"></i> حجز جديد</a>
            </div>

            @if($orders->isEmpty())
                <div class="ho-empty">
                    <i class="fa-light fa-calendar-xmark"></i>
                    <h3>ليس لديك حجوزات ساعات بعد</h3>
                    <p>اختاري الباقة المناسبة وحددي مواعيد الزيارات في خطوات قليلة.</p>
                    <a class="ho-new" href="{{ route('web.hourly.catalog') }}">تصفح الباقات</a>
                </div>
            @else
                <div class="ho-list">
                @foreach($orders as $order)
                    @php
                        $nextVisit = $order->nextVisit(); $lastVisit = $nextVisit ? null : $order->lastVisit();
                        $visits = $order->visits;
                        $done = $visits->where('status', 'completed')->count();
                        $countable = $visits->whereNotIn('status', ['cancelled', 'expired'])->count() ?: $visits->count();
                        $remaining = max(0, (float) $order->total_amount - (float) $order->paid_amount);
                    @endphp
                    <a class="ho-row" href="{{ route('web.hourly.order', $order->id) }}">
                        <div class="ho-main">
                            <span class="ho-number">طلب <bdi>{{ $order->order_number }}</bdi></span>
                            <h3>{{ $title($order) }}</h3>
                            <span class="ho-sub">{{ $order->workers_per_visit }} {{ (int) $order->workers_per_visit === 1 ? 'عاملة' : 'عاملات' }} · {{ rtrim(rtrim(number_format((float) $order->hours_per_visit, 2), '0'), '.') }} ساعات للزيارة · بتاريخ <bdi>{{ $order->created_at?->format('Y-m-d') }}</bdi></span>
                        </div>

                        <div class="ho-visit">
                            <span class="ho-label">{{ $nextVisit ? 'الزيارة القادمة' : ($lastVisit ? 'آخر زيارة' : 'الزيارات') }}</span>
                            @if($nextVisit || $lastVisit)
                                @php $v = $nextVisit ?: $lastVisit; @endphp
                                <strong>{{ $v->scheduled_start_at->translatedFormat('l j F') }}</strong>
                                <span class="ho-sub"><bdi>{{ $v->scheduled_start_at->format('H:i') }} – {{ $v->scheduled_end_at?->format('H:i') }}</bdi></span>
                            @else
                                <strong>لم تُحدد المواعيد بعد</strong>
                            @endif
                            <div class="ho-progress" title="الزيارات المنفذة">
                                <span class="ho-bar"><span style="width: {{ $countable ? round($done / $countable * 100) : 0 }}%"></span></span>
                                <span class="ho-sub">{{ $done }} من {{ $countable }} زيارات</span>
                            </div>
                        </div>

                        <div class="ho-state">
                            @include('web.hourly._status', ['value' => $order->status])
                            @include('web.hourly._status', ['value' => $order->payment_status])
                        </div>

                        <div class="ho-amount">
                            <strong>{{ number_format((float) $order->total_amount, 2) }} <small>ر.س</small></strong>
                            <span class="ho-sub">{{ $remaining > 0 ? 'المتبقي '.number_format($remaining, 2).' ر.س' : 'مسدد بالكامل' }}</span>
                        </div>

                        <span class="ho-go" aria-hidden="true"><i class="fa-light fa-chevron-left"></i></span>
                    </a>
                @endforeach
                </div>
                <div class="hr-pagination">{{ $orders->onEachSide(1)->links('web.hourly._pagination') }}</div>
            @endif
        </div>
    </section>
</main>
@endsection
