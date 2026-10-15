@php
    $itemFieldHelp = [
        'service_category_id' => 'الخدمة التي تنتمي إليها الباقة، مثل التنظيف المنزلي أو تنظيف المكاتب.',
        'package_tier_id' => 'حدد زيارة واحدة أو نوع الباقة المتكررة. يحدد النوع نمط التكرار وعدد الزيارات الأسبوعية.',
        'attribute_id' => 'خيار إضافي من سمات الخدمة لتمييز الباقة، مثل نوع العمالة. اتركه بدون سمة إذا لم تحتاجه.',
        'title_ar' => 'الاسم الذي يراه العميل عند اختيار الباقة، مثل: تنظيف منزلي — 4 زيارات.',
        'code' => 'رمز فريد لتمييز الباقة والبحث عنها داخل النظام. يُقترح رمز تلقائيًا ويمكن تغييره.',
        'title_en' => 'اسم الباقة عند عرضها باللغة الإنجليزية. إذا تركته فارغًا يُستخدم الاسم العربي.',
        'description_ar' => 'تفاصيل الخدمة التي يقرأها العميل بالعربية؛ وضّح ما تتضمنه الباقة وما يحتاج العميل إلى معرفته.',
        'description_en' => 'وصف الخدمة باللغة الإنجليزية للعملاء الذين يستخدمون الواجهة الإنجليزية.',
        'city_ids' => 'المدن السعودية النشطة التي تتوفر فيها هذه الباقة. اختر المدن التي تغطيها فرق الخدمة.',
        'nationality_ids' => 'تحديد جنسيات العاملات المسموح بهن لهذه الباقة. اترك الاختيار فارغًا للسماح بكل الجنسيات.',
        'total_price' => 'إجمالي سعر جميع زيارات الباقة بالريال، شامل الضريبة؛ وليس سعر الزيارة الواحدة.',
        'deposit_amount' => 'المبلغ المطلوب مقدمًا من إجمالي الباقة. اتركه فارغًا إذا كان المطلوب دفع قيمة الباقة كاملة.',
        'monthly_amount' => 'قيمة القسط الشهري بالريال عند استخدام الأقساط. اتركه فارغًا إذا لم يوجد قسط شهري.',
        'extra_worker_price' => 'المبلغ الإضافي بالريال لكل زيارة عند زيادة عدد العاملات عن العدد الأساسي في الباقة.',
        'extra_hour_price' => 'سعر الساعة الإضافية بالريال عند تمديد مدة الزيارة.',
        'transport_fee' => 'رسوم النقل بالريال لكل زيارة. تُضاف عند إيقاف خيار «النقل مشمول».',
        'tax_rate' => 'نسبة الضريبة لحساب الجزء الضريبي داخل السعر الشامل. فارغ = تُستخدم ضريبة فئة الخدمة ثم الإعدادات العامة.',
        'duration' => 'مدة العمل في الزيارة الواحدة. مثال: 4 ساعات و30 دقيقة؛ وليست مجموع ساعات الباقة.',
        'break_minutes' => 'مدة الراحة بالدقائق بعد الزيارة؛ تُحجز ضمن الفاصل قبل إسناد زيارة أخرى للعاملة.',
        'buffer_minutes' => 'هامش إضافي بالدقائق بين الزيارات للتجهيز والانتقال، ويُضاف إلى وقت الاستراحة.',
        'start_times' => 'اضغط على الوقت للتبديل بين: مغلق، متاح للجميع، أو متاح لموظفي التشغيل فقط.',
        'use_general_schedule' => 'فعّل هذا الخيار لاتباع مواعيد التشغيل العامة بدل الأوقات المخصصة لهذه الباقة.',
        'recurring_months' => 'عدد شهور الباقة المتكررة. مثال: شهران. الزيارة الواحدة لا تحتاج تكرارًا شهريًا.',
        'visits_count' => 'إجمالي عدد الزيارات التي يشتريها العميل في الباقة، وليس عدد الزيارات الأسبوعية.',
        'workers_per_visit' => 'عدد العاملات الأساسيات المطلوب حضورهن في كل زيارة، مثل عاملة واحدة أو عاملتين.',
        'validity_days' => 'مدة صلاحية الباقة بالأيام من تاريخ بدايتها، والتي يجب استخدام الزيارات خلالها.',
        'is_private' => 'عند التفعيل تُخفى الباقة عن العملاء وتبقى للاستخدام من لوحة الإدارة.',
        'materials_included' => 'يوضح أن مواد ومستلزمات الخدمة مشمولة في الباقة.',
        'for_home' => 'حدد أن الباقة مناسبة لخدمة المنازل.',
        'for_company' => 'حدد أن الباقة مناسبة للشركات والمكاتب.',
        'show_on_app' => 'السماح بعرض الباقة في قنوات حجز العميل، مع استيفاء شروط النشاط والتغطية وألا تكون خاصة.',
        'ignore_closed_days' => 'السماح بحجز هذه الباقة في أيام الإغلاق والإغلاقات المؤقتة لفئة الخدمة.',
        'is_active' => 'تفعيل الباقة للحجز. عند الإيقاف لا تظهر للحجز في أي قناة.',
        'transport_included' => 'عند التفعيل يكون النقل مشمولًا. عند الإيقاف تُضاف رسوم النقل المحددة لكل زيارة.',
        'sort_order' => 'ترتيب ظهور الباقة في القائمة؛ الرقم الأصغر يظهر أولًا.',
        'min_notice_hours' => 'أقل عدد ساعات بين وقت الحجز وبداية الزيارة. فارغ = مهلة الإعدادات العامة.',
        'cancellation_window_hours' => 'تُطبّق رسوم الإلغاء إذا بقي على الزيارة أقل من هذه المدة بالساعات. فارغ = الإعدادات العامة.',
        'cancellation_fee_percent' => 'نسبة الخصم من سعر الزيارة عند الإلغاء داخل المهلة المحددة. مثال: 10 تعني 10%. فارغ = الإعدادات العامة.',
    ];
@endphp
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
<div class="si-line"><label for="service_category_id">فئة الخدمة<small class="si-help d-block" id="help-service_category_id">{{ $itemFieldHelp['service_category_id'] }}</small></label><select class="form-select" id="service_category_id" aria-describedby="help-service_category_id" name="service_category_id" required><option value="">يرجى الاختيار</option>@foreach($serviceCategories as $category)<option value="{{ $category->id }}" @if((string)old('service_category_id',$package->service_category_id)===(string)$category->id) selected @endif>{{ $category->name_ar }}{{ $category->is_active ? '' : ' (متوقفة)' }}</option>@endforeach</select></div>
<div class="si-line"><label for="item-tier">نوع الباقة وتكرارها<small class="si-help d-block" id="help-item-tier">{{ $itemFieldHelp['package_tier_id'] }}</small></label><select class="form-select" id="item-tier" aria-describedby="help-item-tier" name="package_tier_id"><option value="" data-frequency="one_off" data-rate="0">{{ __('hourly.tier_once') }}</option>@foreach($packageTiers as $tier)<option value="{{ $tier->id }}" data-frequency="{{ $tier->frequency }}" data-rate="{{ $tier->visits_per_week }}" @if((string)old('package_tier_id',$package->package_tier_id)===(string)$tier->id) selected @endif>{{ $tier->name }}{{ $tier->is_active ? '' : ' (متوقفة)' }}</option>@endforeach</select></div>
<div class="si-line"><label for="item-attribute">سمة الخدمة (اختياري)<small class="si-help d-block" id="help-item-attribute">{{ $itemFieldHelp['attribute_id'] }}</small></label><select class="form-select" id="item-attribute" aria-describedby="help-item-attribute" name="attribute_id"><option value="">بدون سمة</option>@foreach($productAttributes as $root)<optgroup label="{{ $root->name }}" data-category="{{ $root->service_category_id }}">@foreach($root->children as $attribute)@if(($root->is_active && $attribute->is_active) || $package->attribute_id==$attribute->id)<option value="{{ $attribute->id }}" @if((string)old('attribute_id',$package->attribute_id)===(string)$attribute->id) selected @endif>{{ $attribute->name }}{{ $root->is_active && $attribute->is_active ? '' : ' (متوقفة)' }}</option>@endif @endforeach</optgroup>@endforeach</select></div>
<div class="si-line"><label for="item-title">اسم الباقة الظاهر للعميل<small class="si-help d-block" id="help-item-title">{{ $itemFieldHelp['title_ar'] }}</small></label><input class="form-control" id="item-title" aria-describedby="help-item-title" name="title_ar" value="{{ old('title_ar',optional($package->translations->firstWhere('locale','ar'))->title) }}" required></div>
<details class="si-extra"><summary><i class="fa fa-layer-group"></i> بيانات إضافية ونطاق الخدمة (اختياري) <i class="fa fa-chevron-down si-extra-caret"></i></summary><div class="row g-3 mt-2">
<div class="col-md-6"><label for="item-code">رمز الباقة<small class="si-help d-block" id="help-code">{{ $itemFieldHelp['code'] }}</small></label><input class="form-control" id="item-code" aria-describedby="help-code" name="code" value="{{ old('code',$package->code ?: 'SI-'.strtoupper(\Illuminate\Support\Str::random(8))) }}"></div>
<div class="col-md-6"><label for="item-title_en">اسم المنتج بالإنجليزية<small class="si-help d-block" id="help-title_en">{{ $itemFieldHelp['title_en'] }}</small></label><input class="form-control" id="item-title_en" aria-describedby="help-title_en" name="title_en" value="{{ old('title_en',optional($package->translations->firstWhere('locale','en'))->title) }}"></div>
@include('admin.hourly._item-extra-general')
</div></details></div>
<div class="tab-pane" id="item-pricing">
<h6 class="si-section"><i class="fa fa-dollar-sign"></i> {{ __('hourly.item_form.base_price') }}</h6>
<div class="si-line"><label for="item-price">إجمالي سعر الباقة (ر.س)<small class="si-help d-block" id="help-item-price">{{ $itemFieldHelp['total_price'] }}</small></label><input class="form-control" id="item-price" aria-describedby="help-item-price" aria-label="إجمالي سعر الباقة شامل الضريبة" type="number" name="total_price" min="0.01" max="100000" step="0.01" value="{{ old('total_price',$package->total_price ?? ($package->exists ? $package->price_per_visit*$package->visits_count : '')) }}" required></div>

@if(\Illuminate\Support\Facades\Schema::hasColumn('hourly_packages','payment_policy'))
<div class="si-line"><label for="item-payment-policy">سياسة استحقاق الدفع<small class="si-help d-block" id="help-payment-policy">اختر متى يدفع العميل: كامل الباقة قبل الخدمة، أو قيمة كل زيارة عند موعدها، أو أقساط شهرية. يسري الاختيار على الحجوزات الجديدة؛ الطلبات الحالية تحتفظ بشروطها.</small></label>
<select class="form-select" id="item-payment-policy" name="payment_policy" aria-describedby="help-payment-policy help-payment-policy-auto">
@foreach([''=>'تلقائي: شروط سداد العميل أو إعداد فئة الخدمة','prepaid'=>'دفع كامل قيمة الباقة قبل بدء الخدمة','per_visit'=>'دفع قيمة كل زيارة في موعدها','installments'=>'أقساط شهرية وفق جدول سداد'] as $value=>$label)
<option value="{{ $value }}" {{ old('payment_policy',$package->payment_policy??($package->exists?'':'prepaid'))===$value?'selected':'' }}>{{ $label }}</option>
@endforeach</select></div>
<p class="text-muted small" id="help-payment-policy-auto"><strong>كيف يعمل التلقائي؟</strong> عند الحجز من الإدارة، تُستخدم شروط السداد المحددة في ملف العميل (مقدم أو آجل). إذا لم توجد شروط للعميل، أو كان الحجز من الموقع أو التطبيق، يُستخدم إعداد فئة الخدمة: «سعر لكل زيارة» يعني الدفع لكل زيارة، و«إجمالي سعر الباقة» يعني استحقاق إجمالي الباقة. اختيار سياسة أخرى هنا يحدد موعد الدفع مباشرةً بدل هذه القواعد.</p>
<p class="text-muted small">الأقساط: الدفعة المقدمة يوم الحجز، ثم القسط الشهري بعد شهر من بداية الخدمة. دون دفعة مقدمة يبدأ أول قسط يوم بداية الخدمة. آخر قسط يساوي المتبقي، بحد أقصى 120 قسطًا.</p>
@endif
<h6 class="si-section"><i class="fa fa-credit-card"></i> قسط</h6>
@foreach(['deposit_amount'=>['الدفعة المقدمة',__('hourly.item_form.full_payment_hint'),'اتركه فارغًا للدفع الكامل.'],'monthly_amount'=>[__('hourly.item_form.monthly_price'),__('hourly.item_form.no_installment_hint'),'قيمة القسط الشهري.']] as $key=>[$label,$placeholder,$help])
<div class="si-line"><label for="item-{{ $key }}">{{ $label }}<small class="si-help d-block" id="help-{{ $key }}">{{ $itemFieldHelp[$key] }}</small></label><input class="form-control" id="item-{{ $key }}" aria-describedby="help-{{ $key }}" type="number" name="{{ $key }}" min="0" max="100000" step="0.01" value="{{ old($key,$package->$key) }}" placeholder="{{ $placeholder }}"></div>
@endforeach
<details class="si-extra"><summary><i class="fa fa-calculator"></i> إعدادات التسعير الإضافية <i class="fa fa-chevron-down si-extra-caret"></i></summary><div class="row g-3 mt-2">@foreach(['extra_worker_price'=>'سعر العاملة الإضافية','extra_hour_price'=>'سعر الساعة الإضافية','transport_fee'=>'رسوم النقل','tax_rate'=>'نسبة الضريبة (فارغ = ترث من الفئة)'] as $key=>$label)<div class="col-md-6"><label for="item-{{ $key }}">{{ $label }}<small class="si-help d-block" id="help-{{ $key }}">{{ $itemFieldHelp[$key] }}</small></label><input id="item-{{ $key }}" aria-describedby="help-{{ $key }}" class="form-control" type="number" step="0.01" min="0" name="{{ $key }}" value="{{ old($key,$package->$key ?? ($key==='tax_rate' ? '' : 0)) }}"></div>@endforeach</div></details>
</div>
<div class="tab-pane" id="item-schedule">
<h6 class="si-section"><i class="fa fa-hourglass-half"></i> {{ __('hourly.item_form.visit_duration') }}</h6>
@php($duration=(int)old('duration_minutes',$package->duration_minutes ?? round(($package->hours_per_visit ?: 4)*60)))
<input type="hidden" name="duration_minutes" value="{{ $duration }}"><input type="hidden" name="hours_per_visit" value="{{ $package->hours_per_visit ?: 4 }}">
<div class="si-line"><label for="item-duration-hours">مدة الزيارة (ساعات ودقائق)<small class="si-help d-block" id="help-duration">{{ $itemFieldHelp['duration'] }}</small></label><div class="si-duration"><div class="input-group"><input aria-label="{{ __('hourly.item_form.hours') }}" class="form-control" id="item-duration-hours" aria-describedby="help-duration" data-duration-hours type="number" min="0" max="12" value="{{ intdiv($duration,60) }}" required><span class="input-group-text">ساعات</span></div><div class="input-group"><input aria-label="{{ __('hourly.item_form.minutes') }}" class="form-control" id="item-duration-minutes" aria-describedby="help-duration" data-duration-minutes type="number" min="0" max="59" value="{{ $duration%60 }}" required><span class="input-group-text">دقيقة</span></div></div></div>
@foreach(['break_minutes'=>'وقت الاستراحة (بالدقائق)','buffer_minutes'=>'الفاصل الزمني (بالدقائق)'] as $key=>$label)<div class="si-line"><label for="item-{{ $key }}">{{ $label }}<small class="si-help d-block" id="help-{{ $key }}">{{ $itemFieldHelp[$key] }}</small></label><input id="item-{{ $key }}" aria-describedby="help-{{ $key }}" class="form-control" name="{{ $key }}" type="number" min="0" max="240" value="{{ old($key,$package->$key ?? 0) }}" required></div>@endforeach
<h6 class="si-section"><i class="fa fa-calendar"></i> {{ __('hourly.item_form.allowed_start_times') }}</h6>
<div class="si-line"><label>أوقات بدء الخدمة<small class="si-help d-block" id="help-start-times">{{ $itemFieldHelp['start_times'] }}</small></label><div><div class="si-time-grid">@include('admin.hourly._item-times')</div><div class="si-legend"><span><i class="si-dot bg-light border"></i>إيقاف</span><span><i class="si-dot bg-success"></i>{{ __('hourly.item_form.all_channels') }}</span><span><i class="si-dot bg-warning"></i>{{ __('hourly.item_form.dispatchers_only') }}</span></div><label class="si-help"><input type="checkbox" aria-describedby="help-general-schedule" name="use_general_schedule" value="1" {{ old('use_general_schedule',$package->start_times===null) ? 'checked' : '' }}> استخدام مواعيد التشغيل العامة<small class="si-help d-block" id="help-general-schedule">{{ $itemFieldHelp['use_general_schedule'] }}</small></label></div></div>
<h6 class="si-section"><i class="fa fa-redo"></i> {{ __('hourly.item_form.recurring_settings') }}</h6>
@foreach(['recurring_months'=>['تكرار الشهور',0,24],'visits_count'=>['مجموع عدد الزيارات',1,730]] as $key=>[$label,$min,$max])<div class="si-line"><label for="item-{{ $key }}">{{ $label }}<small class="si-help d-block" id="help-{{ $key }}">{{ $itemFieldHelp[$key] }}</small></label><input id="item-{{ $key }}" aria-describedby="help-{{ $key }}" class="form-control" name="{{ $key }}" type="number" min="{{ $min }}" max="{{ $max }}" value="{{ old($key,$package->$key ?? $min) }}" required></div>@endforeach
<details class="si-extra"><summary><i class="fa fa-users"></i> العاملات وصلاحية الباقة <i class="fa fa-chevron-down si-extra-caret"></i></summary><div class="row g-3 mt-2">@foreach(['workers_per_visit'=>['عدد العاملات',1],'validity_days'=>['الصلاحية بالأيام',30]] as $key=>[$label,$default])<div class="col-md-6"><label for="item-{{ $key }}">{{ $label }}<small class="si-help d-block" id="help-{{ $key }}">{{ $itemFieldHelp[$key] }}</small></label><input id="item-{{ $key }}" aria-describedby="help-{{ $key }}" class="form-control" name="{{ $key }}" type="number" min="1" value="{{ old($key,$package->$key ?: $default) }}" required></div>@endforeach</div></details>
</div>
<div class="tab-pane" id="item-options">
<h6 class="si-section"><i class="fa fa-toggle-on"></i> {{ __('hourly.item_form.options') }}</h6>
@foreach(['is_private'=>'باقة خاصة بالإدارة','materials_included'=>'هل المواد مشمولة؟','for_home'=>'هل المنتج مخصص للمنزل؟','for_company'=>'مناسبة للشركات والمكاتب','show_on_app'=>__('hourly.item_form.show_on_app'),'ignore_closed_days'=>__('hourly.item_form.ignore_closed_days')] as $key=>$label)
<div class="si-line"><label for="item-{{ $key }}">{{ $label }}<small class="si-help d-block" id="help-{{ $key }}">{{ $itemFieldHelp[$key] }}</small></label><div class="si-switch form-switch"><input id="item-{{ $key }}" aria-describedby="help-{{ $key }}" class="form-check-input" type="checkbox" name="{{ $key }}" value="1" {{ old($key,$package->$key ?? in_array($key,['for_home','show_on_app'])) ? 'checked' : '' }}></div></div>
@if($key==='ignore_closed_days')<div class="si-line"><span></span><p class="si-help">عند التفعيل، يمكن حجز هذه الباقة في أيام الإغلاق والإغلاقات المؤقتة للفئة. يناسب الباقات التي تحتاج الخدمة كل أيام الأسبوع، مثل 7 مرات في الأسبوع.</p></div>@endif
@endforeach
<details class="si-extra" {{ $errors->hasAny(['sort_order','min_notice_hours','cancellation_window_hours','cancellation_fee_percent']) ? 'open' : '' }}>
<summary><i class="fa fa-sliders"></i> الحالة وإعدادات إضافية <i class="fa fa-chevron-down si-extra-caret"></i></summary>
<h6 class="si-section"><i class="fa fa-power-off"></i> الحالة</h6>
@foreach(['is_active'=>['الباقة نشطة','الباقة المتوقفة لا تظهر للحجز في أي قناة.'],'transport_included'=>['النقل مشمول','عند الإيقاف تُضاف رسوم النقل لكل زيارة.']] as $key=>[$label,$help])
<div class="si-line"><label for="item-{{ $key }}">{{ $label }}<small class="si-help d-block" id="help-{{ $key }}">{{ $itemFieldHelp[$key] }}</small></label><div><div class="si-switch form-switch"><input id="item-{{ $key }}" aria-describedby="help-{{ $key }}" class="form-check-input" name="{{ $key }}" type="checkbox" role="switch" value="1" {{ old($key,$package->$key ?? ($key==='transport_included')) ? 'checked' : '' }}></div></div></div>
@endforeach
<h6 class="si-section"><i class="fa fa-clock-rotate-left"></i> المهل والإلغاء</h6>
@foreach(['sort_order'=>['ترتيب العرض','','الأصغر يظهر أولًا.'],'min_notice_hours'=>['مهلة الحجز','ساعة','فارغ = مهلة الإعدادات العامة.'],'cancellation_window_hours'=>['مهلة الإلغاء','ساعة','فارغ = الإعدادات العامة.'],'cancellation_fee_percent'=>['رسوم الإلغاء','%','فارغ = الإعدادات العامة.']] as $key=>[$label,$unit,$help])
<div class="si-line"><label for="item-{{ $key }}">{{ $label }}<small class="si-help d-block" id="help-{{ $key }}">{{ $itemFieldHelp[$key] }}</small></label><div>
<div class="input-group"><input id="item-{{ $key }}" aria-describedby="help-{{ $key }}" class="form-control @error($key) is-invalid @enderror" type="number" min="0" {{ $key==='cancellation_fee_percent' ? 'max=100' : '' }} name="{{ $key }}" value="{{ old($key,$package->$key ?? ($key==='sort_order' ? 0 : '')) }}" placeholder="{{ $key==='sort_order' ? '' : __('hourly.inherit') }}">@if($unit)<span class="input-group-text">{{ $unit }}</span>@endif</div>
@error($key)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div></div>
@endforeach
</details></div>
</div></div>
<div class="si-actions"><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> حفظ</button><button type="button" class="btn btn-light border" data-bs-dismiss="modal" data-item-close>إغلاق</button></div>
</form>