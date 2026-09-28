@extends('admin.layout.indexs.index')
@section('page-title', 'إنشاء طلب ساعات')
@section('content')
<div class="container-fluid"><div class="card p-4"><h3>إنشاء طلب ساعات</h3>
@include('admin.hourly.messages')
<form method="GET" class="mb-3"><label>البحث عن عميل بالاسم أو الهاتف</label><input name="search" value="{{ request('search') }}" class="form-control"><button class="btn btn-outline-primary">بحث</button></form>
<form method="GET" class="mb-3"><label>العميل</label><select name="user_id" class="form-select">@foreach($users as $u)<option value="{{ $u->id }}" @if(request('user_id')==$u->id) selected @endif>{{ $u->name }} — {{ $u->phone }}</option>@endforeach</select><button class="btn btn-outline-primary">عرض عناوين العميل</button></form>
@foreach($addresses as $a)
<details class="border rounded p-3 mb-3"><summary>تحديث موقع: {{ $a->address }}</summary>
<form class="row g-2 mt-2" method="POST" action="{{ route('admin.hourly-orders.location',$a->id) }}">@csrf @method('PUT')
<input type="hidden" name="user_id" value="{{ $a->user_id }}"><div class="col-md-6"><label>العنوان</label><input class="form-control" name="address" value="{{ $a->address }}" required></div>
<div class="col-md-6"><label>المدينة</label><select class="form-select" name="city_id" required>@foreach($cities as $c)<option value="{{ $c->id }}" @if($a->city_id==$c->id) selected @endif>{{ $c->name_ar }}</option>@endforeach</select></div>
<div class="col-md-6"><label>خط العرض</label><input class="form-control address-lat" name="latitude" type="number" step="any" min="-90" max="90" value="{{ $a->latitude }}" required></div>
<div class="col-md-6"><label>خط الطول</label><input class="form-control address-lng" name="longitude" type="number" step="any" min="-180" max="180" value="{{ $a->longitude }}" required></div>
@foreach(['district'=>'الحي','building'=>'المبنى','unit'=>'الوحدة','contact_phone'=>'هاتف التواصل'] as $field=>$label)<div class="col-md-3"><label>{{ $label }}</label><input class="form-control" name="{{ $field }}" value="{{ $a->$field }}"></div>@endforeach
<div class="col-12"><div class="address-map" style="height:250px"></div></div><div class="col-12"><button class="btn btn-outline-primary">حفظ موقع العنوان</button></div></form></details>
@endforeach
<form method="POST" action="{{ route('admin.hourly-orders.store') }}">@csrf<input type="hidden" name="user_id" value="{{ request('user_id') }}">
<label>عنوان العميل</label><select class="form-select mb-3" name="address_id" required>@foreach($addresses as $a)<option data-city="{{ $a->city_id }}" value="{{ $a->id }}">{{ $a->address }}</option>@endforeach</select>
<p>يجب أن يحتوي العنوان على المدينة والإحداثيات قبل إنشاء الطلب.</p>
<label>الباقة</label><select class="form-select mb-3" name="hourly_package_id">@foreach($packages as $p)<option value="{{ $p->id }}">{{ $p->title }} — {{ $p->price_per_visit }} ر.س × {{ $p->visits_count }} زيارة</option>@endforeach</select>
<div id="hourly-config-error" class="alert alert-danger" hidden></div>
<label>قناة الدفع</label><select name="payment_channel" class="form-select mb-3"><option value="employee">الموظف</option><option value="branch">الفرع</option></select>
<label>حي الخدمة</label><select name="district_id" class="form-select mb-3" required></select>
<div id="hourly-product-attributes"></div><input type="hidden" name="attribute_id" value=""><div id="hourly-steppers"></div><div id="hourly-worker-choice" hidden><label>اختيار العاملة</label><select name="preferred_maid_id" class="form-select mb-3" disabled></select></div>
<div id="hourly-order-notes" class="alert alert-info" hidden></div><div id="hourly-quote" class="alert alert-secondary" hidden></div>
<label>عدد العاملات (فارغ = عدد الباقة)</label><input class="form-control mb-3" type="number" min="1" max="20" name="workers_per_visit" value="{{ old('workers_per_visit') }}">
<label>طريقة الدفع</label><select class="form-select mb-3" name="payment_method">@foreach(['cash','card_on_delivery','bank_transfer','moyasar'] as $method)<option value="{{ $method }}">{{ __('hourly.'.$method) }}</option>@endforeach</select>
<label>مواعيد الزيارات (اختياري، يمكن حجزها لاحقًا)</label><p class="text-muted">اختر وقتًا مطابقًا لفترات الحجز المحددة في إعدادات الساعات.</p><div id="hourly-schedules"></div><button type="button" class="btn btn-outline-secondary mb-3" id="add-hourly-date">إضافة موعد زيارة</button>
<label>ملاحظات</label><textarea class="form-control mb-3" name="notes">{{ old('notes') }}</textarea><button class="btn btn-primary">إنشاء الطلب</button></form>
</div></div>
@endsection

@section('style')<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">@endsection
@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
@include('admin.hourly._map-provider')
<script>
document.querySelectorAll('details').forEach(function(details) {
 details.addEventListener('toggle', function() {
  if (!details.open || details.dataset.ready) return;
  var box=details.querySelector('.address-map'); if (!box || !window.L) return;
  details.dataset.ready='1'; var form=box.closest('form'), lat=form.querySelector('.address-lat'), lng=form.querySelector('.address-lng');
  var point=[Number(lat.value||24.7136),Number(lng.value||46.6753)], map=L.map(box).setView(point,12), marker=null;
  HourlyMaps.attach(map);
  if(lat.value && lng.value) marker=L.marker(point).addTo(map);
  map.on('click',function(e){lat.value=e.latlng.lat.toFixed(7);lng.value=e.latlng.lng.toFixed(7);if(marker)marker.setLatLng(e.latlng);else marker=L.marker(e.latlng).addTo(map);});
 });
});
document.getElementById('add-hourly-date').addEventListener('click',function(){
 var row=document.createElement('div');row.className='d-flex gap-2 mb-2';
 var input=document.createElement('input');input.type='datetime-local';input.className='form-control';input.required=true;
 var hidden=document.createElement('input');hidden.type='hidden';hidden.name='scheduled_starts[]';
 input.addEventListener('change',function(){hidden.value=input.value.replace('T',' ');});
 var remove=document.createElement('button');remove.type='button';remove.className='btn btn-outline-danger';remove.textContent='حذف';remove.onclick=function(){row.remove();};
 row.append(input,hidden,remove);document.getElementById('hourly-schedules').append(row);
});
</script>
@include('admin.hourly._order-category-script')
@endsection
