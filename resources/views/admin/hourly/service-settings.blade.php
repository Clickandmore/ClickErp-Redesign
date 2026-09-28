@extends('admin.layout.indexs.index')
@section('page-title',$heading)
@section('style') @include('admin.hourly_teams._style') @endsection
@section('content')
<div class="container-fluid ht-page" dir="rtl">
<div class="d-flex justify-content-end mb-3"><a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-service-items.index') }}"><i class="fa fa-arrow-right ms-1"></i> رجوع لباقات الخدمات</a></div>
@include('admin.hourly.messages')
@include('admin.hourly_service_categories._tab-settings')
</div>
@endsection
@push('js')
@stack('category-js')
@endpush
