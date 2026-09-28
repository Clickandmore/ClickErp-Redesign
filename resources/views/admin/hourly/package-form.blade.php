@extends('admin.layout.indexs.index')
@section('page-title', 'بيانات الباقة')
@section('content')
<div class="container-fluid"><div class="card p-4"><h3>بيانات الباقة</h3>
@include('admin.hourly.messages')
<form method="POST" action="{{ $package->exists ? route('admin.hourly-packages.update',$package) : route('admin.hourly-packages.store') }}">@csrf
@if($package->exists) @method('PUT') @endif<div class="row">
<div class="col-md-4 mb-3"><label>رمز الباقة</label><input class="form-control" type="text" name="code" value="{{ old('code', $package->code ?? '') }}"></div>
<div class="col-md-4 mb-3"><label>فئة الخدمة</label><input class="form-control" type="text" name="category" value="{{ old('category', $package->category ?? 'تنظيف') }}"></div>
<div class="col-md-4 mb-3"><label>ساعات الزيارة</label><input class="form-control" type="number" step="0.01" min="0" name="hours_per_visit" value="{{ old('hours_per_visit', $package->hours_per_visit ?? '4') }}"></div>
<div class="col-md-4 mb-3"><label>العاملات المشمولات</label><input class="form-control" type="number" step="0.01" min="0" name="workers_per_visit" value="{{ old('workers_per_visit', $package->workers_per_visit ?? '1') }}"></div>
<div class="col-md-4 mb-3"><label>عدد الزيارات</label><input class="form-control" type="number" step="0.01" min="0" name="visits_count" value="{{ old('visits_count', $package->visits_count ?? '1') }}"></div>
<div class="col-md-4 mb-3"><label>الصلاحية بالأيام</label><input class="form-control" type="number" step="0.01" min="0" name="validity_days" value="{{ old('validity_days', $package->validity_days ?? '30') }}"></div>
<div class="col-md-4 mb-3"><label>سعر الزيارة شامل الضريبة</label><input class="form-control" type="number" step="0.01" min="0" name="price_per_visit" value="{{ old('price_per_visit', $package->price_per_visit ?? '0') }}"></div>
<div class="col-md-4 mb-3"><label>سعر العاملة الإضافية</label><input class="form-control" type="number" step="0.01" min="0" name="extra_worker_price" value="{{ old('extra_worker_price', $package->extra_worker_price ?? '0') }}"></div>
<div class="col-md-4 mb-3"><label>سعر الساعة الإضافية</label><input class="form-control" type="number" step="0.01" min="0" name="extra_hour_price" value="{{ old('extra_hour_price', $package->extra_hour_price ?? '0') }}"></div>
<div class="col-md-4 mb-3"><label>رسوم النقل لكل زيارة</label><input class="form-control" type="number" step="0.01" min="0" name="transport_fee" value="{{ old('transport_fee', $package->transport_fee ?? '0') }}"></div>
<div class="col-md-4 mb-3"><label>نسبة الضريبة</label><input class="form-control" type="number" step="0.01" min="0" name="tax_rate" value="{{ old('tax_rate', $package->tax_rate ?? '15') }}"></div>
<div class="col-md-4 mb-3"><label>الترتيب</label><input class="form-control" type="number" step="0.01" min="0" name="sort_order" value="{{ old('sort_order', $package->sort_order ?? '0') }}"></div>
<div class="col-md-4 mb-3"><label>مهلة الحجز (فارغ = الإعدادات)</label><input class="form-control" type="number" step="0.01" min="0" name="min_notice_hours" value="{{ old('min_notice_hours', $package->min_notice_hours ?? '') }}"></div>
<div class="col-md-4 mb-3"><label>نافذة الإلغاء (فارغ = الإعدادات)</label><input class="form-control" type="number" step="0.01" min="0" name="cancellation_window_hours" value="{{ old('cancellation_window_hours', $package->cancellation_window_hours ?? '') }}"></div>
<div class="col-md-4 mb-3"><label>نسبة رسوم الإلغاء (فارغ = الإعدادات)</label><input class="form-control" type="number" step="0.01" min="0" name="cancellation_fee_percent" value="{{ old('cancellation_fee_percent', $package->cancellation_fee_percent ?? '') }}"></div>
<div class="col-md-6 mb-3"><label>الاسم — العربية</label><input class="form-control" name="title_ar" value="{{ old('title_ar', optional($package->translations->firstWhere('locale','ar'))->title) }}"></div>
<div class="col-md-6 mb-3"><label>الوصف — العربية</label><input class="form-control" name="description_ar" value="{{ old('description_ar', optional($package->translations->firstWhere('locale','ar'))->description) }}"></div>
<div class="col-md-6 mb-3"><label>الاسم — الإنجليزية</label><input class="form-control" name="title_en" value="{{ old('title_en', optional($package->translations->firstWhere('locale','en'))->title) }}"></div>
<div class="col-md-6 mb-3"><label>الوصف — الإنجليزية</label><input class="form-control" name="description_en" value="{{ old('description_en', optional($package->translations->firstWhere('locale','en'))->description) }}"></div>
<div class="col-md-6"><label>المدن (اختياري — اختيار متعدد)</label><select name="city_ids[]" multiple class="form-select">@foreach($cities as $city)<option value="{{ $city->id }}" @if(in_array($city->id,old('city_ids',$package->cities->pluck('id')->all()))) selected @endif>{{ $city->name_ar }}</option>@endforeach</select></div>
<div class="col-md-6"><label>الجنسيات (فارغ = الكل)</label><select name="nationality_ids[]" multiple class="form-select">@foreach($nationalities as $n)<option value="{{ $n->id }}" @if(in_array($n->id,old('nationality_ids',$package->nationalities->pluck('id')->all()))) selected @endif>{{ $n->title }}</option>@endforeach</select></div>
</div><p class="text-muted mt-3">تنتهي الزيارات غير المستخدمة بانتهاء صلاحية الباقة.</p>
<label class="m-2"><input type="checkbox" name="transport_included" value="1" @if(old('transport_included',$package->transport_included)) checked @endif> النقل مشمول</label>
<label class="m-2"><input type="checkbox" name="is_active" value="1" @if(old('is_active',$package->is_active)) checked @endif> الباقة مفعلة</label>
<button class="btn btn-primary">حفظ الباقة</button></form>
</div></div>
@endsection
