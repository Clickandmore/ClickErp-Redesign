@extends('admin.layout.indexs.index')
@section('page-title', 'طلبات معالجة الزيارات غير المنفذة')
@push('css')
<style>
    .rr-page { --rr-ink: #14507f; --rr-brand: #1b6fb8; --rr-line: #e6edf5; max-width: 1100px; margin: 0 auto; }
    .rr-hero { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; background: linear-gradient(105deg, #14507f 0%, #1b6fb8 55%, #2794eb 100%); color: #fff; border-radius: 16px; padding: 1.2rem 1.4rem; margin-bottom: 1.25rem; box-shadow: 0 8px 26px rgba(20, 80, 127, .18); }
    .rr-hero-main { display: flex; align-items: center; gap: .9rem; }
    .rr-hero-icon { width: 48px; height: 48px; border-radius: 14px; background: rgba(255, 255, 255, .16); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    .rr-hero h4 { margin: 0; font-size: 1.2rem; font-weight: 800; color: #fff; }
    .rr-hero p { margin: .2rem 0 0; font-size: .82rem; opacity: .85; }
    .rr-stats { display: flex; flex-wrap: wrap; gap: .5rem; }
    .rr-stat { background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .22); border-radius: 12px; padding: .45rem .9rem; text-align: center; min-width: 92px; }
    .rr-stat b { display: block; font-size: 1.15rem; font-weight: 800; }
    .rr-stat span { font-size: .74rem; opacity: .9; }


    .rr-empty { text-align: center; background: #fff; border: 1px dashed #cfdbe8; border-radius: 16px; padding: 3rem 1rem; color: #64748b; }
    .rr-empty i { font-size: 2.2rem; color: #86efac; margin-bottom: .6rem; display: block; }
    .rr-empty b { display: block; color: #1e293b; font-size: 1rem; margin-bottom: .2rem; }
</style>
@endpush
@section('content')
@php
    // الأعداد تخص الطلبات المعروضة في هذه الصفحة من القائمة.
    $recoveryCounts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    foreach ($orders as $countOrder)
        foreach (($countOrder->booking_metadata['missed_visit_requests'] ?? []) as $countRequest)
            if (isset($recoveryCounts[$countRequest['status'] ?? 'pending'])) $recoveryCounts[$countRequest['status'] ?? 'pending']++;
@endphp
<div class="container-fluid p-3" dir="rtl">
    <div class="rr-page">
        <div class="rr-hero">
            <div class="rr-hero-main">
                <span class="rr-hero-icon" aria-hidden="true"><i class="fa fa-calendar-xmark"></i></span>
                <div>
                    <h4>طلبات معالجة الزيارات غير المنفذة</h4>
                    <p>طلبات العملاء لإعادة جدولة زيارة لم تُنفذ أو إلغائها واسترداد قيمتها للمحفظة.</p>
                </div>
            </div>
            <div class="rr-stats">
                <div class="rr-stat"><b>{{ $recoveryCounts['pending'] }}</b><span>قيد المراجعة</span></div>
                <div class="rr-stat"><b>{{ $recoveryCounts['approved'] }}</b><span>تمت الموافقة</span></div>
                <div class="rr-stat"><b>{{ $recoveryCounts['rejected'] }}</b><span>مرفوضة</span></div>
            </div>
        </div>

        @include('admin.hourly.messages')
        @include('admin.hourly._recovery-requests', ['recoveryOrders' => $orders])
        @if($orders->isEmpty())
            <div class="rr-empty"><i class="fa fa-circle-check" aria-hidden="true"></i><b>لا توجد طلبات معالجة</b>ستظهر هنا طلبات العملاء عند تعذّر تنفيذ إحدى زياراتهم.</div>
        @endif
        <div class="mt-3">{{ $orders->links() }}</div>
    </div>
</div>
@endsection
