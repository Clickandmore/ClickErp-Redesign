<tr>
    <td>@if(empty($rate['service_category_id']))<input type="hidden" name="profile[rates][{{ $index }}][service_category_id]" value="">كل الخدمات
        @else<select class="form-control" name="profile[rates][{{ $index }}][service_category_id]" required><option value="">اختر الخدمة</option>@foreach($services as $service)<option value="{{ $service->id }}" {{ $rate['service_category_id']==$service->id ? 'selected' : '' }}>{{ $service->name_ar }}</option>@endforeach</select>@endif</td>
    <td><select class="form-control" name="profile[rates][{{ $index }}][payment_type]">@foreach(\App\Services\Hourly\MaidProfileService::PAYMENT_TYPES as $value=>$label)<option value="{{ $value }}" {{ ($rate['payment_type'] ?? 'hourly')===$value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></td>
    <td><input class="form-control" type="number" min="0" step="0.01" required name="profile[rates][{{ $index }}][payment_value]" value="{{ $rate['payment_value'] ?? 0 }}"></td>
    <td>@if(!empty($rate['service_category_id']))<button type="button" class="btn btn-outline-danger" data-remove-rate aria-label="حذف سعر الخدمة">×</button>@endif</td>
</tr>
