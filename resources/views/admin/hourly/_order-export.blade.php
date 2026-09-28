<details class="card p-3 mb-3"><summary>تصدير Excel — اختيار الأعمدة</summary><form action="{{ route('admin.hourly-orders.export') }}" method="GET">
@foreach($filters as $key=>$value)@if($value!==null && $value!=='')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
<div class="row my-3">@foreach($exportColumns as $key=>$label)<label class="col-md-3 mb-2"><input type="checkbox" name="columns[]" value="{{ $key }}" checked> {{ $label }}</label>@endforeach</div><button class="btn btn-success">تصدير النتائج المفلترة</button></form></details>
