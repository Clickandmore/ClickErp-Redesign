@extends('admin.layout.indexs.index')
@section('page-title', 'مدن المملكة — التأجير بالساعة')
@section('style') @include('admin.hourly_teams._style') @endsection
@section('content')
<div class="container-fluid ht-page" dir="rtl"><div class="ht-card">
<div class="ht-head"><div><h5>مدن المملكة</h5><p>اختر المدينة لإدارة مناطقها وأحيائها في نظام الساعات.</p></div></div><div class="ht-body">
<form method="GET" class="d-flex gap-2 mb-4"><label for="search" class="visually-hidden">بحث عن مدينة</label><input class="form-control" id="search" name="search" value="{{ $search }}" placeholder="ابحث باسم المدينة" maxlength="150"><button class="btn btn-primary">بحث</button></form>
<table class="ht-table"><thead><tr><th>المدينة</th><th>عدد المناطق</th><th>الإجراءات</th></tr></thead><tbody>
@forelse($cities as $city)<tr><td>{{ $city->name_ar ?: $city->name_en }}</td><td>{{ $city->service_districts_count }}</td><td><a class="btn btn-outline-primary" href="{{ route('admin.hourly-cities.districts',$city) }}">إدارة مناطق المدينة</a></td></tr>@empty<tr><td colspan="3">لا توجد مدن مطابقة.</td></tr>@endforelse
</tbody></table><div class="mt-3">{{ $cities->links() }}</div>
</div></div></div>
@endsection
