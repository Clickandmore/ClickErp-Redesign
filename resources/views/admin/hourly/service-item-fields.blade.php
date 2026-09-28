<form class="si-editor" method="POST" action="{{ $package->exists ? route('admin.hourly-packages.update',$package) : route('admin.hourly-packages.store') }}">
@csrf @if($package->exists) @method('PUT') @endif
<input type="hidden" name="service_item_form" value="1">
<div class="si-editor-body">
<ul class="nav nav-tabs" role="tablist">
@foreach(['general'=>[__('hourly.item_form.general'),'fa-tag'],'pricing'=>[__('hourly.item_form.pricing'),'fa-dollar-sign'],'schedule'=>[__('hourly.item_form.schedule'),'fa-clock'],'options'=>[__('hourly.item_form.options'),'fa-toggle-on']] as $key=>[$label,$icon])<li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#item-{{ $key }}"><i class="fa {{ $icon }}"></i>{{ $label }}</button></li>@endforeach
</ul>
<div class="tab-content">
<div class="tab-pane show active" id="item-general">
<h6 class="si-section"><i class="fa fa-info-circle"></i> {{ __('hourly.item_form.basic_information') }}</h6>
<div class="si-line"><label for="service_category_id">اسم الخدمة <i class="fa fa-info-circle text-muted"></i></label><select class="form-select" id="service_category_id" name="service_category_id" required><option value="">يرجى الاختيار</option>@foreach($serviceCategories as $category)<option value="{{ $category->id }}" @if((string)old('service_category_id',$package->service_category_id)===(string)$category->id) selected @endif>{{ $category->name_ar }}{{ $category->is_active ? '' : ' (متوقفة)' }}</option>@endforeach</select></div>
<div class="si-line"><label for="item-tier">اسم الباقة <i class="fa fa-info-circle text-muted"></i></label><select class="form-select" id="item-tier" name="package_tier_id"><option value="" data-frequency="one_off" data-rate="0">{{ __('hourly.tier_once') }}</option>@foreach($packageTiers as $tier)<option value="{{ $tier->id }}" data-frequency="{{ $tier->frequency }}" data-rate="{{ $tier->visits_per_week }}" @if((string)old('package_tier_id',$package->package_tier_id)===(string)$tier->id) selected @endif>{{ $tier->name }}{{ $tier->is_active ? '' : ' (متوقفة)' }}</option>@endforeach</select></div>
<div class="si-line"><label for="item-attribute">سمة <i class="fa fa-info-circle text-muted"></i></label><select class="form-select" id="item-attribute" name="attribute_id"><option value="">بدون سمة</option>@foreach($productAttributes as $root)<optgroup label="{{ $root->name }}" data-category="{{ $root->service_category_id }}">@foreach($root->children as $attribute)@if(($root->is_active && $attribute->is_active) || $package->attribute_id==$attribute->id)<option value="{{ $attribute->id }}" @if((string)old('attribute_id',$package->attribute_id)===(string)$attribute->id) selected @endif>{{ $attribute->name }}{{ $root->is_active && $attribute->is_active ? '' : ' (متوقفة)' }}</option>@endif @endforeach</optgroup>@endforeach</select></div>
<div class="si-line"><label for="item-title">اسم المنتج <i class="fa fa-info-circle text-muted"></i></label><input class="form-control" id="item-title" name="title_ar" value="{{ old('title_ar',optional($package->translations->firstWhere('locale','ar'))->title) }}" required></div>
<details class="si-extra"><summary>بيانات إضافية ونطاق الخدمة (اختياري)</summary><div class="row g-3 mt-2">
<div class="col-md-6"><label>رمز الباقة</label><input class="form-control" name="code" value="{{ old('code',$package->code ?: 'SI-'.strtoupper(\Illuminate\Support\Str::random(8))) }}"></div>
<div class="col-md-6"><label>اسم المنتج بالإنجليزية</label><input class="form-control" name="title_en" value="{{ old('title_en',optional($package->translations->firstWhere('locale','en'))->title) }}"></div>
@include('admin.hourly._item-extra-general')
</div></details></div>
<div class="tab-pane" id="item-pricing">
<h6 class="si-section"><i class="fa fa-dollar-sign"></i> {{ __('hourly.item_form.base_price') }}</h6>
<div class="si-line"><label for="item-price">السعر <i class="fa fa-info-circle text-muted"></i></label><input class="form-control" id="item-price" aria-label="إجمالي سعر الباقة شامل الضريبة" type="number" name="total_price" min="0.01" max="100000" step="0.01" value="{{ old('total_price',$package->total_price ?? ($package->exists ? $package->price_per_visit*$package->visits_count : '')) }}" required></div>
<h6 class="si-section"><i class="fa fa-credit-card"></i> قسط</h6>
@foreach(['deposit_amount'=>['الدفعة المقدمة',__('hourly.item_form.full_payment_hint'),'اتركه فارغًا للدفع الكامل.'],'monthly_amount'=>[__('hourly.item_form.monthly_price'),__('hourly.item_form.no_installment_hint'),'قيمة القسط الشهري.']] as $key=>[$label,$placeholder,$help])
<div class="si-line"><label for="item-{{ $key }}">{{ $label }} <i class="fa fa-info-circle text-muted"></i><p class="si-help">{{ $help }}</p></label><input class="form-control" id="item-{{ $key }}" type="number" name="{{ $key }}" min="0" max="100000" step="0.01" value="{{ old($key,$package->$key) }}" placeholder="{{ $placeholder }}"></div>
@endforeach
<details class="si-extra"><summary>إعدادات التسعير الإضافية</summary><div class="row g-3 mt-2">@foreach(['extra_worker_price'=>'سعر العاملة الإضافية','extra_hour_price'=>'سعر الساعة الإضافية','transport_fee'=>'رسوم النقل','tax_rate'=>'نسبة الضريبة (فارغ = ترث من الفئة)'] as $key=>$label)<div class="col-md-6"><label>{{ $label }}</label><input class="form-control" type="number" step="0.01" min="0" name="{{ $key }}" value="{{ old($key,$package->$key ?? ($key==='tax_rate' ? '' : 0)) }}"></div>@endforeach</div></details>
</div>
<div class="tab-pane" id="item-schedule">
<h6 class="si-section"><i class="fa fa-hourglass-half"></i> {{ __('hourly.item_form.visit_duration') }}</h6>
@php($duration=(int)old('duration_minutes',$package->duration_minutes ?? round(($package->hours_per_visit ?: 4)*60)))
<input type="hidden" name="duration_minutes" value="{{ $duration }}"><input type="hidden" name="hours_per_visit" value="{{ $package->hours_per_visit ?: 4 }}">
<div class="si-line"><label>مدة الخدمة <i class="fa fa-info-circle text-muted"></i></label><div class="si-duration"><div class="input-group"><input aria-label="{{ __('hourly.item_form.hours') }}" class="form-control" data-duration-hours type="number" min="0" max="12" value="{{ intdiv($duration,60) }}" required><span class="input-group-text">ساعات</span></div><div class="input-group"><input aria-label="{{ __('hourly.item_form.minutes') }}" class="form-control" data-duration-minutes type="number" min="0" max="59" value="{{ $duration%60 }}" required><span class="input-group-text">دقيقة</span></div></div></div>
@foreach(['break_minutes'=>'وقت الاستراحة (بالدقائق)','buffer_minutes'=>'الفاصل الزمني (بالدقائق)'] as $key=>$label)<div class="si-line"><label for="item-{{ $key }}">{{ $label }} <i class="fa fa-info-circle text-muted"></i></label><input id="item-{{ $key }}" class="form-control" name="{{ $key }}" type="number" min="0" max="240" value="{{ old($key,$package->$key ?? 0) }}" required></div>@endforeach
<h6 class="si-section"><i class="fa fa-calendar"></i> {{ __('hourly.item_form.allowed_start_times') }}</h6>
<div class="si-line"><label>أوقات بدء الخدمة <i class="fa fa-info-circle text-muted"></i></label><div><div class="si-time-grid">@include('admin.hourly._item-times')</div><div class="si-legend"><span><i class="si-dot bg-light border"></i>إيقاف</span><span><i class="si-dot bg-success"></i>{{ __('hourly.item_form.all_channels') }}</span><span><i class="si-dot bg-warning"></i>{{ __('hourly.item_form.dispatchers_only') }}</span></div><label class="si-help"><input type="checkbox" name="use_general_schedule" value="1" {{ old('use_general_schedule',$package->start_times===null) ? 'checked' : '' }}> استخدام مواعيد التشغيل العامة</label></div></div>
<h6 class="si-section"><i class="fa fa-redo"></i> {{ __('hourly.item_form.recurring_settings') }}</h6>
@foreach(['recurring_months'=>['تكرار الشهور',0,24],'visits_count'=>['مجموع عدد الزيارات',1,730]] as $key=>[$label,$min,$max])<div class="si-line"><label for="item-{{ $key }}">{{ $label }} <i class="fa fa-info-circle text-muted"></i></label><input id="item-{{ $key }}" class="form-control" name="{{ $key }}" type="number" min="{{ $min }}" max="{{ $max }}" value="{{ old($key,$package->$key ?? $min) }}" required></div>@endforeach
<details class="si-extra"><summary>العاملات وصلاحية الباقة</summary><div class="row g-3 mt-2">@foreach(['workers_per_visit'=>['عدد العاملات',1],'validity_days'=>['الصلاحية بالأيام',30]] as $key=>[$label,$default])<div class="col-md-6"><label>{{ $label }}</label><input class="form-control" name="{{ $key }}" type="number" min="1" value="{{ old($key,$package->$key ?: $default) }}" required></div>@endforeach</div></details>
</div>
<div class="tab-pane" id="item-options">
<h6 class="si-section"><i class="fa fa-toggle-on"></i> {{ __('hourly.item_form.options') }}</h6>
@foreach(['is_private'=>'خاصة','materials_included'=>'هل المواد مشمولة؟','for_home'=>'هل المنتج مخصص للمنزل؟','for_company'=>'شركة','show_on_app'=>__('hourly.item_form.show_on_app'),'ignore_closed_days'=>__('hourly.item_form.ignore_closed_days')] as $key=>$label)
<div class="si-line"><label for="item-{{ $key }}">{{ $label }} <i class="fa fa-info-circle text-muted"></i></label><div class="si-switch form-switch"><input id="item-{{ $key }}" class="form-check-input" type="checkbox" name="{{ $key }}" value="1" {{ old($key,$package->$key ?? in_array($key,['for_home','show_on_app'])) ? 'checked' : '' }}></div></div>
@if($key==='ignore_closed_days')<div class="si-line"><span></span><p class="si-help">عند التفعيل، يمكن حجز هذه الباقة في أيام الإغلاق والإغلاقات المؤقتة للفئة. يناسب الباقات التي تحتاج الخدمة كل أيام الأسبوع، مثل 7 مرات في الأسبوع.</p></div>@endif
@endforeach
<details class="si-extra" {{ $errors->hasAny(['sort_order','min_notice_hours','cancellation_window_hours','cancellation_fee_percent']) ? 'open' : '' }}>
<summary><i class="fa fa-sliders"></i> الحالة وإعدادات إضافية <i class="fa fa-chevron-down si-extra-caret"></i></summary>
<h6 class="si-section"><i class="fa fa-power-off"></i> الحالة</h6>
@foreach(['is_active'=>['الباقة نشطة','الباقة المتوقفة لا تظهر للحجز في أي قناة.'],'transport_included'=>['النقل مشمول','عند الإيقاف تُضاف رسوم النقل لكل زيارة.']] as $key=>[$label,$help])
<div class="si-line"><label for="item-{{ $key }}">{{ $label }}</label><div><div class="si-switch form-switch"><input id="item-{{ $key }}" class="form-check-input" name="{{ $key }}" type="checkbox" role="switch" value="1" {{ old($key,$package->$key ?? ($key==='transport_included')) ? 'checked' : '' }}></div><p class="si-help">{{ $help }}</p></div></div>
@endforeach
<h6 class="si-section"><i class="fa fa-clock-rotate-left"></i> المهل والإلغاء</h6>
@foreach(['sort_order'=>['ترتيب العرض','','الأصغر يظهر أولًا.'],'min_notice_hours'=>['مهلة الحجز','ساعة','فارغ = مهلة الإعدادات العامة.'],'cancellation_window_hours'=>['مهلة الإلغاء','ساعة','فارغ = الإعدادات العامة.'],'cancellation_fee_percent'=>['رسوم الإلغاء','%','فارغ = الإعدادات العامة.']] as $key=>[$label,$unit,$help])
<div class="si-line"><label for="item-{{ $key }}">{{ $label }}</label><div>
<div class="input-group"><input id="item-{{ $key }}" class="form-control @error($key) is-invalid @enderror" type="number" min="0" {{ $key==='cancellation_fee_percent' ? 'max=100' : '' }} name="{{ $key }}" value="{{ old($key,$package->$key ?? ($key==='sort_order' ? 0 : '')) }}" placeholder="{{ $key==='sort_order' ? '' : __('hourly.inherit') }}">@if($unit)<span class="input-group-text">{{ $unit }}</span>@endif</div>
@error($key)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
<p class="si-help">{{ $help }}</p></div></div>
@endforeach
</details></div>
</div></div>
<div class="si-actions"><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> حفظ</button><button type="button" class="btn btn-light border" data-bs-dismiss="modal" data-item-close>إغلاق</button></div>
</form>