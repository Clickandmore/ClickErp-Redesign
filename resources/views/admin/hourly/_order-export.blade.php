{{-- تصدير الطلبات المفلترة إلى Excel: زر منسدل في شريط النتائج يختار الأعمدة ويحمل الفلاتر الحالية. --}}
<details class="ho-export">
    <summary><i class="fa fa-file-excel"></i> تصدير Excel <i class="fa fa-chevron-down" style="font-size:.7rem"></i></summary>
    <form class="ho-export-panel" action="{{ route('admin.hourly-orders.export') }}" method="GET">
        @foreach($filters as $key=>$value)@if($value!==null && $value!=='')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
        <h6>اختر الأعمدة المطلوب تصديرها</h6>
        <div class="ho-export-cols">@foreach($exportColumns as $key=>$label)<label><input type="checkbox" name="columns[]" value="{{ $key }}" checked> {{ $label }}</label>@endforeach</div>
        <button class="btn btn-success btn-sm rounded-3"><i class="fa fa-download ms-1"></i> تصدير النتائج المفلترة</button>
    </form>
</details>
