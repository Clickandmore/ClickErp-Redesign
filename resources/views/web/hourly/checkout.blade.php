@extends('web.hourly.layout')
@section('content')
@php
    $config = $data['booking_config'];
    $steppers = collect($config['steppers'])->keyBy('key');
    $workerMin = max($data['workers_per_visit'], $steppers->get('workers')['min'] ?? $data['workers_per_visit']);
    $workerMax = min(20, $steppers->get('workers')['max'] ?? 20);
    $paymentLabels = ['moyasar'=>'دفع إلكتروني (ميسر)','bank_transfer'=>'تحويل بنكي','cash'=>'الدفع عند الاستلام'];
    $paymentIcons = ['moyasar'=>'fa-light fa-credit-card','bank_transfer'=>'fa-light fa-building-columns','cash'=>'fa-light fa-money-bill-wave'];
    $methods = array_values(array_intersect($config['payment_methods'], array_keys($paymentLabels)));
    $canChooseWorker = (bool)app(\App\Services\Hourly\HourlyBookingRules::class)->settings()->value($item,'customer_worker_selection');
    $checkoutConfig = ['package'=>$item->id,'city'=>$city,'maid'=>$maid,'data'=>$data,'mapCenter'=>$mapCenter,'token'=>(string)\Illuminate\Support\Str::uuid(),
        'canChooseWorker'=>$canChooseWorker,
        'addresses'=>$addresses->map->only(['id','title','address','hourly_district_id','district','latitude','longitude'])->values(),
        'districts'=>$districts->map->only(['id','name','name_en'])->values(),
        'urls'=>['slots'=>route('web.hourly.slots'),'quote'=>route('web.hourly.quote',$item->id),'address'=>route('web.hourly.address'),
            'geocode'=>route('web.hourly.geocode'),'store'=>route('web.hourly.store')],'csrf'=>csrf_token()];
@endphp
<main>
    @include('web.hourly._header', ['title' => 'إتمام حجز ' . $data['title'], 'crumb' => 'إتمام الحجز'])
    <section class="hr-page" dir="rtl">
        <div class="container">
            @include('web.hourly._nav')

            <div class="hr-checkout" id="hourly-checkout">
                <div class="hr-notice">
                    <i class="fa-light fa-box-open mt-1"></i>
                    <p>الباقة: <strong>{{ $data['title'] }}</strong> · {{ $data['visits_count'] }} زيارة، {{ $data['hours_per_visit'] }} ساعات لكل زيارة
                    </p>
                </div>

                <div class="hr-stepper" role="tablist" aria-label="خطوات الحجز">
                    <button type="button" role="tab" id="hr-tab-0" aria-selected="true" aria-controls="hr-panel-0" data-step="0"><b>1</b> العنوان</button>
                    <button type="button" role="tab" id="hr-tab-1" aria-selected="false" aria-controls="hr-panel-1" data-step="1"><b>2</b> تفاصيل الزيارة</button>
                    <button type="button" role="tab" id="hr-tab-2" aria-selected="false" aria-controls="hr-panel-2" data-step="2"><b>3</b> الملخص والدفع</button>
                </div>
                <div id="hr-error" class="hr-error" role="alert" hidden></div>

                <section class="hr-card" id="hr-panel-0" role="tabpanel" aria-labelledby="hr-tab-0">
                    <div class="hr-card-title"><span class="hr-card-icon"><i class="fa-light fa-location-dot"></i></span><div><h2>عنوان الزيارة</h2><p class="hr-muted m-0">المدينة: <strong>{{ $cityName }}</strong></p></div></div>
                    <div class="hr-form-grid">
                        <div>
                            <label for="hr-address">اختيار عنوان محفوظ</label>
                            <select id="hr-address"><option value="">اختاري عنوانًا</option>@foreach($addresses as $address)<option value="{{ $address->id }}">{{ $address->title }} — {{ $address->address }}</option>@endforeach</select>
                        </div>
                        <div>
                            <label for="hr-district">الحي</label>
                            <select id="hr-district"><option value="">اختاري الحي</option>@foreach($districts as $district)<option value="{{ $district->id }}">{{ $district->name }}</option>@endforeach</select>
                        </div>
                    </div>
                    <p class="hr-muted mt-2">يُحدَّد الحي تلقائيًا من موقع العنوان أو بياناته عند الحفظ. اختاريه يدويًا فقط إذا تعذّر تحديده. الأحياء المعروضة ضمن تغطية الباقة.</p>
                    <button type="button" class="hr-btn secondary" id="hr-new-address"><i class="fa-light fa-map-location-dot"></i> إضافة عنوان جديد / اختيار من الخريطة</button>

                    <form id="hr-address-form" class="hr-subform" hidden>
                        <h3>إضافة عنوان في {{ $cityName }}</h3>
                        <div class="hr-form-grid">
                            <div><label for="hr-title">اسم العنوان</label><input id="hr-title" name="title" required maxlength="100" placeholder="المنزل"></div>
                            <div><label for="hr-phone">رقم التواصل</label><input id="hr-phone" name="contact_phone" type="tel" dir="ltr" required maxlength="30" value="{{ auth('web')->user()->phone }}"></div>
                        </div>
                        <label for="hr-address-text">العنوان التفصيلي</label><textarea id="hr-address-text" name="address" required maxlength="500" rows="2"></textarea>
                        <div class="hr-form-grid">
                            <div><label for="hr-building">المبنى</label><input id="hr-building" name="building" maxlength="255"></div>
                            <div><label for="hr-unit">الوحدة / الشقة</label><input id="hr-unit" name="unit" maxlength="255"></div>
                        </div>
                        <label for="hr-map-search">البحث على الخريطة</label><input id="hr-map-search" maxlength="200" placeholder="اسم الشارع أو المكان">
                        <div class="hr-actions mt-2"><button type="button" class="hr-btn secondary" id="hr-search"><i class="fa-light fa-magnifying-glass"></i> بحث</button><button type="button" class="hr-btn secondary" id="hr-locate"><i class="fa-light fa-location-crosshairs"></i> موقعي الحالي</button></div>
                        <div id="hr-map" class="hr-map" aria-label="اختيار موقع العنوان"></div>
                        <p class="hr-muted mt-2 mb-0">اضغطي على الخريطة لتحديد الموقع أو أدخلي الإحداثيات.</p>
                        <div class="hr-form-grid">
                            <div><label for="hr-latitude">خط العرض</label><input id="hr-latitude" name="latitude" type="number" step="any" min="-90" max="90" dir="ltr" required></div>
                            <div><label for="hr-longitude">خط الطول</label><input id="hr-longitude" name="longitude" type="number" step="any" min="-180" max="180" dir="ltr" required></div>
                        </div>
                        <div class="hr-actions"><button type="submit" class="hr-btn"><i class="fa-light fa-floppy-disk"></i> حفظ العنوان واختياره</button></div>
                    </form>
                    <div class="hr-actions"><button class="hr-btn" type="button" data-next="1">متابعة إلى الموعد <i class="fa-light fa-arrow-left"></i></button></div>
                </section>

                <section class="hr-card" id="hr-panel-1" role="tabpanel" aria-labelledby="hr-tab-1" hidden>
                    <div class="hr-card-title"><span class="hr-card-icon"><i class="fa-light fa-calendar-days"></i></span><h2>تفاصيل الزيارة الأولى</h2></div>
                    <div class="hr-form-grid {{ $steppers->has('quantity') ? 'three' : '' }}">
                        <div><label for="hr-workers">عدد العاملات لكل زيارة</label><input id="hr-workers" type="number" step="1" min="{{ $workerMin }}" max="{{ $workerMax }}" value="{{ $data['pricing']['workers'] }}" {{ $workerMin === $workerMax ? 'readonly' : '' }} aria-describedby="hr-workers-hint">
                            <p id="hr-workers-hint" class="hr-muted mt-2">@if($workerMin === $workerMax)عدد العاملات ثابت لهذه الخدمة: {{ $workerMin }}.@elseيمكن اختيار {{ $workerMin }} إلى {{ $workerMax }} عاملة، بحسب التوفر في الموعد المختار.@endif</p>
                        </div>
                        @if($steppers->has('quantity'))<div><label for="hr-quantity">{{ $steppers->get('quantity')['label'] }}</label><input id="hr-quantity" type="number" step="1" min="{{ $steppers->get('quantity')['min'] }}" max="{{ $steppers->get('quantity')['max'] }}" value="{{ $data['pricing']['quantity'] }}"></div>@endif
                        <div><label for="hr-date">تاريخ أول زيارة</label><input id="hr-date" type="date" min="{{ $config['booking']['min_date'] }}" max="{{ min($config['booking']['max_date'],now()->addDays($data['validity_days'])->toDateString()) }}"></div>
                    </div>
                    <div id="hr-time-field" {{ $config['booking']['time_selection'] === 'date' ? 'hidden' : '' }}>
                        <label id="hr-time-label">اختاري موعد الزيارة</label>
                        <input type="hidden" id="hr-time" value="">
                        <div id="hr-time-cards" class="hr-time-cards" role="radiogroup" aria-labelledby="hr-time-label" aria-describedby="hr-slot-status"></div>
                    </div>
                    <p id="hr-slot-status" class="hr-muted mt-2" role="status">تظهر الأوقات بحسب الحي وعدد العاملات. التوقيت: {{ $data['timezone'] }}</p>
                    @if($canChooseWorker)
                    <div class="mt-4" id="hr-worker-choice">
                        <h3 id="hr-worker-choice-label">اختيار العاملات</h3>
                        <p class="hr-muted">اختيار العاملات اختياري. يمكنك اختيار عاملة أو أكثر حتى العدد المطلوب، أو ترك الاختيار لنا. يُستكمل الباقي تلقائيًا عند تفعيل الإسناد وتوفر عاملات، وإلا يتولى الفريق إسناده. يجب أن تتوفر المختارات في الموعد نفسه.</p>
                        <p id="hr-worker-choice-status" class="hr-muted" role="status">اختاري الموعد لعرض العاملات المتاحات.</p>
                        <div id="hr-worker-cards" class="hr-time-cards" role="group" aria-labelledby="hr-worker-choice-label"></div>
                    </div>
                    @endif
                    @foreach($config['fields'] as $key=>$field)
                        @if($field['type']==='boolean')<label class="hr-check"><input type="checkbox" id="hr-option-{{ $key }}" {{ $field['default'] ? 'checked' : '' }}>{{ $field['label'] }}</label>
                        @else<label for="hr-option-{{ $key }}">{{ $field['label'] }}</label><select id="hr-option-{{ $key }}">@foreach($field['options'] as $option)<option value="{{ $option['value'] }}">{{ $option['label'] }}</option>@endforeach</select>@endif
                    @endforeach
                    @if($data['visits_count'] > 1)<div class="hr-notice mt-3"><i class="fa-light fa-circle-info mt-1"></i><p>يُحجز هنا موعد الزيارة الأولى. تبقى الزيارات الأخرى في رصيد الباقة لجدولتها لاحقًا خلال مدة الصلاحية.</p></div>@endif
                    <div class="hr-actions split"><button class="hr-btn secondary" type="button" data-next="0"><i class="fa-light fa-arrow-right"></i> السابق</button><button class="hr-btn" type="button" data-next="2">متابعة إلى الدفع <i class="fa-light fa-arrow-left"></i></button></div>
                </section>

                <section class="hr-card" id="hr-panel-2" role="tabpanel" aria-labelledby="hr-tab-2" hidden>
                    <div class="hr-card-title"><span class="hr-card-icon"><i class="fa-light fa-receipt"></i></span><h2>ملخص الطلب</h2></div>
                    <div id="hr-summary" aria-live="polite"></div>

                    <div class="hr-card-title mt-4"><span class="hr-card-icon"><i class="fa-light fa-wallet"></i></span><h2>طريقة الدفع</h2></div>
                    @forelse($methods as $method)<label class="hr-payment"><input type="radio" name="hr-payment" value="{{ $method }}" {{ $loop->first ? 'checked' : '' }}><i class="{{ $paymentIcons[$method] }} ms-2"></i> {{ $paymentLabels[$method] }}</label>
                    @empty<p class="hr-error">لا توجد طريقة دفع متاحة لهذه الباقة حاليًا.</p>@endforelse
                    <p class="hr-muted">الدفع الإلكتروني يحوّلك إلى صفحة ميسر. عند التحويل البنكي تظهر بيانات الحساب وإرفاق الإيصال بعد إنشاء الطلب.</p>
                    @if(!empty($config['notes']))<ul class="hr-notes">@foreach($config['notes'] as $note)<li>{{ $note['text'] }}</li>@endforeach</ul>@endif
                    <div class="hr-actions split"><button class="hr-btn secondary" type="button" data-next="1"><i class="fa-light fa-arrow-right"></i> السابق</button><button class="hr-btn" type="button" id="hr-submit" {{ $methods ? '' : 'disabled' }}><i class="fa-light fa-circle-check"></i> تأكيد الطلب والمتابعة</button></div>
                </section>
            </div>
        </div>
    </section>
</main>
<script type="application/json" id="hourly-checkout-config">@json($checkoutConfig)</script>
@endsection
@push('css')<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">@endpush
@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
@include('admin.hourly._map-provider')
<script src="{{ asset('web/assets/js/hourly-rental.js') }}?v={{ filemtime(public_path('web/assets/js/hourly-rental.js')) }}" defer></script>
@endpush
