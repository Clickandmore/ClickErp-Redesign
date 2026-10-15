@if((int)request('add_worker_visit',0)>0)
@php $additionalVisit=$order->visits->firstWhere('id',(int)request('add_worker_visit',0)); @endphp
@if($additionalVisit)
<section class="hr-card" id="hourly-customer-add-worker" data-preview="{{ route('web.hourly.visit.additional-worker.preview',[$order->id,$additionalVisit->id]) }}">
<h2>إضافة عاملة للزيارة {{ $additionalVisit->visit_number }}</h2>
<p>تخص الزيادة هذه الزيارة فقط. ستظهر التكلفة قبل التأكيد؛ المدفوعات السابقة تبقى محفوظة.</p>
<p id="hca-status" role="status">جارٍ التحقق من توفر عاملة إضافية…</p>
<form id="hca-form" method="POST" action="{{ route('web.hourly.visit.additional-worker.store',[$order->id,$additionalVisit->id]) }}" hidden>
@csrf<input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
<input type="hidden" name="expected_workers"><input type="hidden" name="expected_price"><input type="hidden" name="expected_total">
<label id="hca-choice" hidden>العاملة الإضافية<select name="worker_id" class="form-control" id="hca-worker"></select></label>
<p id="hca-price"></p>
@if(($order->package_snapshot['payment_policy']??null)==='installments')<p>تُضاف تكلفة الزيادة إلى آخر قسط في الجدول، دون خصم تلقائي.</p>@endif
<label><input type="checkbox" name="confirmed" value="1" required> أوافق على إضافة عاملة لهذه الزيارة بالسعر المعروض.</label>
<button type="submit" class="hr-btn">تأكيد إضافة العاملة</button>
</form></section>
<script src="{{ asset('web/assets/js/hourly-add-worker-customer.js') }}?v={{ filemtime(public_path('web/assets/js/hourly-add-worker-customer.js')) }}" defer></script>
@endif
@endif