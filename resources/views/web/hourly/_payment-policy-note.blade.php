<div class="hr-notice"><div>سياسة الدفع: <strong>{{ $data['pricing']['payment_policy_label'] }}</strong>
@if($data['pricing']['payment_policy']==='installments')<br>دفعة مقدمة {{ number_format($data['pricing']['installment_deposit'],2) }} ر.س · قسط شهري {{ number_format($data['pricing']['installment_monthly'],2) }} ر.س. يتحدد آخر قسط بالمتبقي على الطلب.
@elseif($data['pricing']['payment_policy']==='per_visit')<br>تستحق كل زيارة في موعدها، وقد تُطلب قيمة الأولى قبل تفعيل الحجز حسب وسيلة الدفع.
@elseif($data['pricing']['payment_policy']==='prepaid')<br>يلزم سداد قيمة الباقة كاملة قبل التفعيل.
@endif</div></div>