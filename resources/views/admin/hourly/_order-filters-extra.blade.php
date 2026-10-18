{{-- الفلاتر المتقدمة لقائمة الطلبات. Laravel 8 لا يدعم @selected، لذلك تُكتب selected صراحة. --}}
@php
    $extraValue = fn ($key) => (string) ($filters[$key] ?? '');
    $extraSet = fn ($key) => $extraValue($key) !== '' ? 'is-set' : '';
    $extraSel = fn ($key, $value) => $extraValue($key) === (string) $value ? 'selected' : '';
@endphp
@foreach(['customer_name'=>['اسم العميل','fa-user'],'order_number'=>['رقم الطلب','fa-hashtag'],'unique_id'=>['رقم تعريف فريد','fa-fingerprint'],'short_id'=>['معرف قصير','fa-hashtag'],'coupon_code'=>['رمز الكوبون','fa-ticket'],'customer_source'=>['مصدر العميل','fa-bullhorn']] as $key=>[$label,$icon])
<div class="hd-filter-field"><label for="f_{{ $key }}"><i class="fa {{ $icon }}"></i> {{ $label }}</label><input id="f_{{ $key }}" class="form-control {{ $extraSet($key) }}" name="{{ $key }}" value="{{ $filters[$key] ?? '' }}" type="{{ $key==='short_id'?'number':'text' }}"></div>
@endforeach
<div class="hd-filter-field"><label for="f_admin_id"><i class="fa fa-user-tie"></i> موظف الفرع</label><select id="f_admin_id" name="admin_id" class="form-select {{ $extraSet('admin_id') }}"><option value="">الكل</option>@foreach($admins as $admin)<option value="{{ $admin->id }}" {{ $extraSel('admin_id', $admin->id) }}>{{ $admin->name }}</option>@endforeach</select></div>
<div class="hd-filter-field"><label for="f_service_id"><i class="fa fa-briefcase"></i> الخدمة</label><select id="f_service_id" name="service_id" class="form-select {{ $extraSet('service_id') }}"><option value="">الكل</option>@foreach($categories as $category)<option value="{{ $category->id }}" {{ $extraSel('service_id', $category->id) }}>{{ $category->name }}</option>@endforeach</select></div>
<div class="hd-filter-field"><label for="f_hours"><i class="fa fa-clock"></i> ساعات الخدمة</label><input id="f_hours" type="number" min="0.5" max="24" step="0.5" name="hours" class="form-control {{ $extraSet('hours') }}" value="{{ $filters['hours']??'' }}"></div>
<div class="hd-filter-field"><label for="f_installment"><i class="fa fa-money-check-dollar"></i> الأقساط</label><select id="f_installment" name="installment" class="form-select {{ $extraSet('installment') }}"><option value="">الكل</option>@foreach(['has'=>'لديه أقساط','pending'=>'معلقة','overdue'=>'متأخرة','none'=>'بدون أقساط'] as $value=>$label)<option value="{{ $value }}" {{ $extraSel('installment', $value) }}>{{ $label }}</option>@endforeach</select></div>
