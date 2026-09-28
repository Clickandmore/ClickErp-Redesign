@extends('admin.layout.indexs.index')
@section('page-title', 'إدارة المناطق')
@section('style') @include('admin.hourly_teams._style') @endsection
@section('content')
@php($canEdit = AdminGuardHasPerm('hourly_districts_edit'))
<div class="container-fluid ht-page" dir="rtl">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="ht-card"><div class="ht-head"><div><h5>{{ $selectedCity ? 'مناطق وأحياء '.$selectedCity->name_ar : 'مناطق وأحياء مدن المملكة' }}</h5><p>دليل مناطق المدن المستخدم في تعيين مناطق خدمة الفرق.</p></div><a class="btn btn-outline-secondary" href="{{ route('admin.hourly-cities.index') }}">مدن المملكة</a></div><div class="ht-body">
<form method="GET" action="{{ route('admin.hourly-districts.index') }}" class="row g-3 mb-4">
<div class="col-md-5"><label for="filter_city" class="form-label">المدينة</label><select name="city_id" id="filter_city" class="form-select"><option value="">جميع المدن</option>@foreach($cities as $city)<option value="{{ $city->id }}" {{ (string)($filters['city_id'] ?? '')===(string)$city->id ? 'selected' : '' }}>{{ $city->name_ar ?: $city->name_en }}</option>@endforeach</select></div>
<div class="col-md-5"><label for="search" class="form-label">اسم المنطقة</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" maxlength="150"></div>
<div class="col-md-2 align-self-end"><button class="btn btn-primary">بحث</button> <a class="btn btn-outline-secondary" href="{{ route('admin.hourly-districts.index') }}">مسح</a></div></form>
@if(AdminGuardHasPerm('hourly_districts_create'))
<details class="mb-4" {{ $errors->any() ? 'open' : '' }}><summary>إضافة منطقة جديدة</summary>
<form method="POST" action="{{ route('admin.hourly-districts.store') }}" class="row g-3 mt-1">@csrf
<div class="col-md-5"><label for="new_city" class="form-label">المدينة</label>
@if($selectedCity)<input type="hidden" name="city_id" value="{{ $selectedCity->id }}"><input id="new_city" class="form-control" value="{{ $selectedCity->name_ar }}" readonly>
@else<select id="new_city" name="city_id" class="form-select" required><option value="">اختر المدينة</option>@foreach($cities as $city)<option value="{{ $city->id }}" {{ (string)old('city_id')===(string)$city->id ? 'selected' : '' }}>{{ $city->name_ar ?: $city->name_en }}</option>@endforeach</select>@endif</div>
<div class="col-md-5"><label for="new_name" class="form-label">اسم المنطقة / الحي</label><input id="new_name" name="name" class="form-control" required maxlength="150" value="{{ old('name') }}"></div>
<div class="col-md-2 align-self-end"><button class="btn btn-primary">إضافة</button></div></form></details>
@endif
<div class="table-responsive"><table class="ht-table"><thead><tr><th>المنطقة</th><th>المدينة</th><th>الفرق المرتبطة</th><th>الإجراءات</th></tr></thead><tbody>
@forelse($districts as $district)<tr>
<td>{{ $district->name }}<div class="small {{ $district->is_active ? 'text-success' : 'text-danger' }}">{{ $district->is_active ? 'نشطة' : 'غير نشطة' }}</div></td><td>{{ $district->city->name_ar ?: $district->city->name_en }}</td><td>{{ $district->teams_count }}</td>
<td>
@if($canEdit)<form method="POST" action="{{ route('admin.hourly-districts.status',$district) }}">@csrf @method('PUT')<input type="hidden" name="is_active" value="{{ $district->is_active ? 0 : 1 }}"><button class="btn btn-sm btn-outline-warning mb-2">{{ $district->is_active ? 'إيقاف المنطقة' : 'تفعيل المنطقة' }}</button></form>@endif
@if(AdminGuardHasPerm('hourly_districts_create'))<details><summary>تعديل</summary><form method="POST" action="{{ route('admin.hourly-districts.update',$district) }}" class="d-grid gap-2 my-2">@csrf @method('PUT')
<label for="district-name-{{ $district->id }}">اسم المنطقة</label><input class="form-control" id="district-name-{{ $district->id }}" name="name" value="{{ $district->name }}" required maxlength="150">
<label for="district-city-{{ $district->id }}">المدينة</label><select class="form-select" id="district-city-{{ $district->id }}" name="city_id" required>@foreach($cities as $city)<option value="{{ $city->id }}" {{ $district->city_id==$city->id ? 'selected' : '' }}>{{ $city->name_ar ?: $city->name_en }}</option>@endforeach</select><button class="btn btn-sm btn-primary">حفظ</button></form></details>@endif
@if(AdminGuardHasPerm('hourly_districts_delete'))<form method="POST" action="{{ route('admin.hourly-districts.destroy',$district) }}" data-confirm-title="حذف المنطقة" data-confirm="حذف المنطقة من الدليل؟" data-confirm-button="نعم، احذف">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger mt-2">حذف</button></form>@endif
</td></tr>@empty<tr><td colspan="4" class="text-center py-4">لا توجد مناطق مطابقة.</td></tr>@endforelse
</tbody></table></div><div class="mt-3">{{ $districts->links() }}</div>
</div></div></div>
@endsection

@push('js')
@include('admin.hourly._confirm-script')
@endpush
