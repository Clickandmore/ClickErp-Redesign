{{-- من يؤكد الوصول ومن يؤكد العودة للإيواء في الزيارات المحددة (VisitConfirmation). --}}
<div {{ $fieldsAttr }}="confirmers" class="vl-modal-group">
    <label><i class="fa fa-location-dot"></i> من يؤكد الوصول إلى العميل</label>
    <select name="arrival_confirmer" class="{{ $fieldClass }}">@foreach(\App\Services\Hourly\VisitConfirmation::MODES as $mode => $modeLabel)<option value="{{ $mode }}">{{ $modeLabel }}</option>@endforeach</select>
    <label class="mt-2"><i class="fa fa-house"></i> من يؤكد العودة للإيواء</label>
    <select name="return_confirmer" class="{{ $fieldClass }}">@foreach(\App\Services\Hourly\VisitConfirmation::MODES as $mode => $modeLabel)<option value="{{ $mode }}">{{ $modeLabel }}</option>@endforeach</select>
    <small class="d-block mt-2">«تلقائي»: سائق المهمة إن كان معيّنًا، وإلا العاملة. «السائق أو العاملة»: يكفي تأكيد أيٍّ منهما. يظهر الزر في تطبيق وبوابة من له الصلاحية فقط، وتبقى الإدارة قادرة على تأكيد العودة يدويًا.</small>
</div>
