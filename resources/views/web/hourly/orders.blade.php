@extends('web.hourly.layout')
@section('content')
<main>
    @include('web.hourly._header', ['title' => 'حجوزات الساعات', 'crumb' => 'حجوزاتي'])
    <section class="hr-page" dir="rtl">
        <div class="container">
            @include('web.hourly._nav')
            <div class="hr-grid">
            @forelse($orders as $order)
                <article class="hr-card hr-order-card">
                    <div class="top"><h2>طلب {{ $order->order_number }}</h2><span class="hr-muted">{{ $order->created_at?->format('Y-m-d') }}</span></div>
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
            <div class="hr-pagination">{{ $orders->links() }}</div>
        </div>
    </section>
</main>
@endsection
