@php($profile=$maid->hourly_settings['profile'] ?? [])
<form method="POST" action="{{ route('admin.hourly-maids.update',$maid) }}" enctype="multipart/form-data" class="mp-profile" autocomplete="off">
    @csrf @method('PUT')
    <fieldset {{ $canEdit ? '' : 'disabled' }}>
        <h5 class="mp-section mt-0">1. المعلومات الشخصية</h5>
        <div class="row g-3">
            @foreach(['name'=>['الاسم الكامل','text'],'birth_date'=>['تاريخ الميلاد','date'],'phone_1'=>['الهاتف','tel'],'phone_2'=>['الهاتف الثاني','tel']] as $field=>[$label,$type])
                <div class="col-md-6"><label class="form-label" for="mp-{{ $field }}">{{ $label }}</label><input id="mp-{{ $field }}" class="form-control" name="{{ $field }}" type="{{ $type }}" value="{{ old($field,$type==='date' ? $maid->$field?->format('Y-m-d') : $maid->$field) }}" {{ $field==='name' ? 'required' : '' }}></div>
            @endforeach
            @foreach(\App\Services\Hourly\MaidProfileService::FIELDS as $field=>[$label,$type])
                <div class="{{ $type==='textarea' ? 'col-12' : 'col-md-6' }}"><label class="form-label" for="mp-{{ $field }}">{{ $label }}</label>
                @if($type==='textarea')<textarea class="form-control" rows="3" id="mp-{{ $field }}" name="profile[{{ $field }}]">{{ old('profile.'.$field,$profile[$field] ?? '') }}</textarea>
                @elseif($type==='gender')<select class="form-control" id="mp-{{ $field }}" name="profile[gender]">@foreach(['female'=>'أنثى','male'=>'ذكر'] as $value=>$title)<option value="{{ $value }}" {{ old('profile.gender',$profile['gender'] ?? 'female')===$value ? 'selected' : '' }}>{{ $title }}</option>@endforeach</select>
                @else<input class="form-control" id="mp-{{ $field }}" type="{{ $type }}" name="profile[{{ $field }}]" value="{{ old('profile.'.$field,$profile[$field] ?? '') }}">@endif</div>
            @endforeach
            <div class="col-12"><label class="form-label" for="mp-photo">تحميل صورة العاملة</label><input class="form-control" type="file" id="mp-photo" name="photo" accept="image/jpeg,image/png,image/webp"><small class="text-muted">JPG، PNG، WEBP — حتى 5 ميجابايت.</small></div>
        </div>
        <div class="row g-3 mt-2">
            @foreach(['الجنسية'=>$maid->nationality?->sub_title ?: $maid->nationality?->title,'اللغات'=>$maid->languagesLabel(),'رقم الإقامة'=>$maid->id_number,'انتهاء الإقامة'=>$maid->id_expiry_date?->format('Y-m-d'),'رقم جواز السفر'=>$maid->passport_num,'رقم الحدود'=>$maid->border_number,'تاريخ الدخول'=>$maid->arrival_date?->format('Y-m-d'),'انتهاء التأمين الطبي'=>$maid->medical_insurance_expiry_date?->format('Y-m-d')] as $label=>$value)<div class="col-md-6"><div class="mp-document"><small>{{ $label }}</small>{{ $value ?: '—' }}</div></div>@endforeach
        </div>
        @if($canEdit)<a class="btn btn-link px-0 mt-2" href="{{ route('maids.edit',$maid) }}">تعديل بيانات الهوية والوثائق في سجل العاملة</a>@endif
        <h5 class="mp-section">2. معلومات الخدمة و3. معلومات التطبيق</h5>
        <select name="type_id" hidden aria-hidden="true"><option selected value="{{ \App\Services\Rental\RentalMaidQuery::rentalJobTypeId() }}">إيجار</option></select>
        @include('admin.ingaz.maids._hourly-fields',['obj'=>$maid,'hourlyProfilePage'=>true])
        <h5 class="mp-section">4. معلومات البنك</h5>
        <div class="row g-3">@foreach(['bank_name'=>'اسم البنك','iban_owner'=>'صاحب الحساب البنكي','iban'=>'رقم الحساب (IBAN)'] as $field=>$label)<div class="col-md-4"><label for="mp-{{ $field }}" class="form-label">{{ $label }}</label><input id="mp-{{ $field }}" class="form-control" name="profile[{{ $field }}]" value="{{ old('profile.'.$field,$profile[$field] ?? '') }}" @if($field==='iban') dir="ltr" @endif></div>@endforeach</div>
        <h5 class="mp-section">5. نوع دفع الموظف</h5><p class="text-muted small">سعر افتراضي لكل الخدمات، مع إمكانية تخصيص سعر لكل خدمة. هذه إعدادات الأجر ولا تنشئ صرفًا ماليًا.</p>
        @php($rates=old('profile.rates',$profile['rates'] ?? [['service_category_id'=>null,'payment_type'=>'hourly','payment_value'=>0]]))
        <div class="table-responsive"><table class="table" id="mp-rates"><thead><tr><th>الخدمة</th><th>نوع الدفع</th><th>المبلغ / النسبة</th><th></th></tr></thead><tbody>
            @foreach($rates as $index=>$rate) @include('admin.hourly_maids._rate') @endforeach
        </tbody></table></div>
        <button type="button" class="btn btn-outline-primary" id="mp-add-rate">إضافة سعر خدمة</button>
        @if($canEdit)<div class="border-top pt-3 mt-4 text-end"><button class="btn btn-primary px-5" type="submit">حفظ التغييرات</button></div>@endif
    </fieldset>
</form>
<template id="mp-rate-template">@include('admin.hourly_maids._rate',['index'=>'__INDEX__','rate'=>['service_category_id'=>-1,'payment_type'=>'hourly','payment_value'=>0]])</template>
<script>
// يعمل فورًا لأن التبويب قد يُحمَّل لاحقًا دون إعادة تحميل الصفحة؛ العناصر أعلاه موجودة عند التنفيذ.
(function(){
    const body=document.querySelector('#mp-rates tbody');let index={{ max(array_merge([0],array_map('intval',array_keys($rates))))+1 }};
    document.getElementById('mp-add-rate').addEventListener('click',function(){body.insertAdjacentHTML('beforeend',document.getElementById('mp-rate-template').innerHTML.replaceAll('__INDEX__',index++));});
    body.addEventListener('click',function(event){const button=event.target.closest('[data-remove-rate]');if(button)button.closest('tr').remove();});
})();
</script>
