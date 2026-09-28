@extends('web.hourly.layout')
@section('content')
<main>
    @include('web.hourly._header', ['title' => $data['title'], 'crumb' => 'تفاصيل الباقة'])
    <section class="hr-page" dir="rtl">
        <div class="container">
            @include('web.hourly._nav')

            <div class="hr-layout">
                <article class="hr-card">
                    <span class="hr-badge mb-3"><i class="fa-light fa-clock"></i> إيجار ساعات</span>
                    <h2>{{ $data['title'] }}</h2>
                    @if($worker)<div class="hr-notice"><i class="fa-light fa-user mt-1"></i><p>العاملة المختارة: <strong>{{ $worker->name }}</strong></p></div>@endif
                    @include('web.hourly._package-details')
                    @if(!empty($data['booking_config']['notes']))
                        <h2 class="mt-4">ملاحظات مهمة</h2>
                        <ul class="hr-notes">@foreach($data['booking_config']['notes'] as $note)<li>{{ $note['text'] }}</li>@endforeach</ul>
                    @endif
                </article>

                <aside class="hr-card hr-side">
                    <div class="hr-card-title"><span class="hr-card-icon"><i class="fa-light fa-receipt"></i></span><h2>ملخص الباقة</h2></div>
                    <ul class="hr-meta">
                        <li><span>عدد الزيارات</span><strong>{{ $data['visits_count'] }}</strong></li>
                        <li><span>ساعات كل زيارة</span><strong>{{ $data['hours_per_visit'] }}</strong></li>
                        <li><span>العاملات لكل زيارة</span><strong>{{ $data['pricing']['workers'] }}</strong></li>
                        <li><span>مدة الصلاحية</span><strong>{{ $data['validity_days'] }} يومًا</strong></li>
                    </ul>
                    <div class="hr-summary-line total"><span>الإجمالي شامل الضريبة</span><strong>{{ number_format($data['pricing']['total_amount'], 2) }} ر.س</strong></div>
                    <a class="hr-btn block mt-3" href="{{ route('web.hourly.checkout', ['package'=>$item->id,'city'=>$city,'maid'=>$maid]) }}"><i class="fa-light fa-cart-shopping"></i> شراء الباقة</a>
                </aside>
            </div>
        </div>
    </section>
</main>
@endsection
