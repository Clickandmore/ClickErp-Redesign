@extends('web.hourly.layout')
@section('style')
<style>
.hr-pagination .pagination { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px; padding: 0; margin: 0; list-style: none; }
.hr-pagination .page-link { display: flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; margin: 0; border: 1px solid #dce5f2; border-radius: 10px; background: #fff; color: #1d6fb8; font-size: 14px; line-height: 1; text-decoration: none; }
.hr-pagination .page-item.active .page-link { background: #1d6fb8; border-color: #1d6fb8; color: #fff; }
.hr-pagination .page-item.disabled .page-link { color: #94a3b8; background: #f8fafc; }
.hr-pagination a.page-link:hover, .hr-pagination a.page-link:focus-visible { background: #eaf3fc; border-color: #1d6fb8; }
</style>
@endsection
@section('content')
<main>
    @include('web.hourly._header', ['title' => 'حجوزات الساعات', 'crumb' => 'حجوزاتي'])
    <section class="hr-page" dir="rtl">
        <div class="container">
            @include('web.hourly._nav')
            <div class="hr-grid">
            @forelse($orders as $order)
                <article class="hr-card hr-order-card">
                    @php $nextVisit = $order->nextVisit(); $lastVisit = $nextVisit ? null : $order->lastVisit(); @endphp
                    <div class="top"><h2>طلب {{ $order->order_number }}</h2><span class="hr-muted" title="تاريخ الطلب">{{ $order->created_at?->format('Y-m-d') }}</span></div>
                    <div class="hr-next-visit {{ $nextVisit ? 'is-upcoming' : '' }}">
                        <i class="fa-light {{ $nextVisit ? 'fa-calendar-clock' : 'fa-calendar' }}"></i>
                        @if ($nextVisit)
                            <span>الزيارة القادمة: <strong dir="ltr">{{ $nextVisit->scheduled_start_at->format('Y-m-d H:i') }}</strong></span>
                        @elseif ($lastVisit)
                            <span>آخر زيارة: <strong dir="ltr">{{ $lastVisit->scheduled_start_at->format('Y-m-d H:i') }}</strong></span>
                        @else
                            <span>لم تُحدد مواعيد الزيارات بعد</span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @include('web.hourly._status', ['value' => $order->status])
                        @include('web.hourly._status', ['value' => $order->payment_status])
                    </div>
                    <div class="amount">{{ number_format($order->total_amount, 2) }} <small>ر.س</small></div>
                    <a class="hr-btn block" href="{{ route('web.hourly.order', $order->id) }}">تفاصيل الطلب والدفع</a>
                </article>
            @empty
                <div class="hr-empty"><i class="fa-light fa-calendar-xmark"></i><p class="m-0">ليس لديك حجوزات ساعات بعد.</p><a class="hr-btn" href="{{ route('web.hourly.catalog') }}">تصفح الباقات</a></div>
            @endforelse
            </div>
            <div class="hr-pagination">{{ $orders->onEachSide(1)->links('web.hourly._pagination') }}</div>
        </div>
    </section>
</main>
@endsection
