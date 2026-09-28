@foreach(['price'=>'تحديث الأسعار جماعيًا','shift'=>'تحديث الشفتات جماعيًا'] as $kind=>$title)
<div class="modal fade ht-page" id="{{ $kind }}-modal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5>{{ $title }} — {{ $category?->name_ar }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
<form class="item-bulk-form"><input type="hidden" name="service_category_id" value="{{ $category?->id }}">
@if($kind==='price')
<input type="hidden" name="type" value="prices"><p>أدخل السعر النهائي لكل باقة. النسبة الموجبة تزيد الأسعار والسالبة تخفضها. راجع المعاينة قبل الحفظ.</p>
<label>تعديل جميع الأسعار بنسبة %</label><input class="form-control mb-3" type="number" step="0.01" min="-99.99" max="1000" name="percentage" value="0">
@foreach($items as $item)<label class="mt-2">{{ $item->title }} — {{ $item->hours_per_visit }} ساعات / {{ $item->visits_count }} زيارات</label><input class="form-control" type="number" step="0.01" min="0.01" name="prices[{{ $item->id }}]" value="{{ $item->total_price ?? $item->price_per_visit*$item->visits_count }}" required>@endforeach
@else
<h6>الوضع الحالي للباقات النشطة</h6><table class="table table-sm"><thead><tr><th>الباقة</th><th>المواعيد</th><th>الأول</th><th>الأخير</th></tr></thead><tbody>@foreach($items->where('is_active',true) as $item)@php($times=array_keys($item->start_times ?? []))<tr><td>{{ $item->title }}</td><td>{{ count($times) }}</td><td>{{ $times[0] ?? 'عام' }}</td><td>{{ $times ? end($times) : 'عام' }}</td></tr>@endforeach</tbody></table>
<label>التحديث المطلوب</label><select class="form-select mb-3" name="type"><option value="shifts">تكوين الشفتات</option><option value="breaks">استراحة الغداء</option><option value="buffer">الفاصل بين الزيارات</option></select>
<fieldset data-bulk-section="shifts"><div class="row g-3">
<div class="col-6"><label>نوع الشفت</label><select name="shifts[mode]" class="form-select"><option value="grid">شبكة مستمرة</option><option value="discrete">مواعيد منفصلة</option></select></div>
<div class="col-6"><label>متاح لـ</label><select name="shifts[access]" class="form-select"><option value="all">الموقع والتطبيق والتشغيل</option><option value="dispatch">موظفي التشغيل فقط</option></select></div>
<div class="col-4"><label>أول موعد</label><input type="time" class="form-control" name="shifts[first]" value="08:00"></div><div class="col-4"><label>آخر موعد بدء</label><input type="time" class="form-control" name="shifts[last]" value="15:00"></div><div class="col-4"><label>كل (دقيقة)</label><input type="number" class="form-control" name="shifts[step]" value="30" min="5" max="240"></div>
<div class="col-12"><label>المواعيد المنفصلة (اختيار متعدد)</label><select name="shifts[times][]" multiple class="form-select">@for($i=0;$i<48;$i++)@php($time=sprintf('%02d:%02d',intdiv($i*30,60),($i*30)%60))<option>{{ $time }}</option>@endfor</select></div>
<div class="col-12"><label><input type="checkbox" name="shifts[dispatcher_enabled]" value="1"> إضافة نطاق خاص بموظفي التشغيل</label></div>
<div class="col-4"><label>أول موعد للتشغيل</label><input type="time" class="form-control" name="shifts[dispatcher_first]" value="08:00"></div><div class="col-4"><label>آخر موعد للتشغيل</label><input type="time" class="form-control" name="shifts[dispatcher_last]" value="18:00"></div><div class="col-4"><label>الفاصل</label><input type="number" class="form-control" name="shifts[dispatcher_step]" value="30"></div>
</div></fieldset>
<fieldset data-bulk-section="breaks" hidden disabled><p>تُضاف الاستراحة بعد مدة العمل دون تغيير السعر.</p><label>الزيارات التي تبلغ مدتها (دقيقة) فأكثر</label><input class="form-control" name="minimum_duration" type="number" value="360" min="0"><label>مدة الاستراحة (0 = بدون استراحة)</label><input class="form-control" name="break_minutes" type="number" value="0" min="0" max="240"></fieldset>
<fieldset data-bulk-section="buffer" hidden disabled><p>وقت إضافي للانتقال أو الراحة بعد كل زيارة؛ لا يغير السعر أو أوقات البداية.</p><label>الفاصل بالدقائق</label><input class="form-control" name="buffer_minutes" type="number" value="0" min="0" max="240"></fieldset>
@endif
<button class="btn btn-outline-primary mt-3" type="submit">حساب ومعاينة</button>
</form><div class="bulk-result mt-3" aria-live="polite"></div><button class="btn btn-success mt-3 bulk-save" hidden>حفظ التحديثات المعروضة</button>
</div></div></div></div>
@endforeach
