@extends('hourly_driver.layout')
@section('title','الزيارات المتاحة')
@section('content')
<h1>الزيارات المتاحة للاختيار</h1><a href="{{ route('hourly-maid.home') }}">الرئيسية</a>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
@forelse($visits as $visit)<section class="card"><h3>زيارة #{{ $visit->id }} · {{ $visit->order->serviceCategory?->name }}</h3><p>{{ $visit->scheduled_start_at?->format('Y-m-d H:i') }} — {{ $visit->scheduled_end_at?->format('H:i') }} · {{ $visit->planned_hours }} ساعات</p><p>{{ $visit->district }}</p><form method="POST" action="{{ route('hourly-maid.offers.claim',$visit) }}">@csrf<button>اختيار هذه الزيارة</button></form></section>@empty<p>لا توجد زيارات متاحة تناسب جدولك حاليًا.</p>@endforelse
{{ $visits->links() }}
@endsection
