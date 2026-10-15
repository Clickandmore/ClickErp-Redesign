@php $visitFinance=app(\App\Services\Hourly\PaymentScheduleService::class)->forOrder($order)['visits'][$visit->id]??null; @endphp
@if($visitFinance)
<div class="small mt-2" data-visit-financial="{{ $visit->id }}">
<strong>الدفع: {{ ['paid'=>'مسددة','partial'=>'مسددة جزئيًا','unpaid'=>'غير مسددة'][$visitFinance['payment_status']] }}</strong>
@if($visitFinance['is_overdue'])<span class="badge bg-danger">متأخر</span>@elseif($visitFinance['policy']==='per_visit' && !$visitFinance['is_due'] && $visitFinance['remaining']>0)<span class="text-muted">لم يحِن موعدها</span>@endif
<div>قيمة الزيارة {{ number_format($visitFinance['charge'],2) }} · مدفوع {{ number_format($visitFinance['paid'],2) }} · متبقٍ {{ number_format($visitFinance['remaining'],2) }} ر.س</div>
</div>
@endif