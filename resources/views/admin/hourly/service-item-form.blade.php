@extends('admin.layout.indexs.index')
@section('page-title','بيانات الباقة')
@section('style')
@include('admin.hourly_teams._style')
@include('admin.hourly._item-form-style')
@endsection
@section('content')
@php($packageTitle = optional($package->translations->firstWhere('locale','ar'))->title)
<div class="container-fluid ht-page" dir="rtl">
    <div class="ht-card">
        <div class="ht-head">
            <div class="ht-head-icon"><i class="fa {{ $package->exists ? 'fa-pen-to-square' : 'fa-box-open' }}"></i></div>
            <div>
                <h5>{{ $package->exists ? 'تعديل الباقة'.($packageTitle ? ' — '.$packageTitle : '') : 'إضافة باقة جديدة' }}</h5>
                <p>البيانات الأساسية، ثم التسعير، ثم المدة والمواعيد، ثم خيارات الظهور والحجز</p>
            </div>
            <div class="ht-actions">
                <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-service-items.index', array_filter(['category' => $package->service_category_id])) }}"><i class="fa fa-arrow-right ms-1"></i> باقات الخدمات</a>
            </div>
        </div>
        <div class="ht-body">
            @include('admin.hourly.messages')
            @include('admin.hourly.service-item-fields')
        </div>
    </div>
</div>
@endsection
@push('js')
@include('admin.hourly.service-item-script')
@endpush
