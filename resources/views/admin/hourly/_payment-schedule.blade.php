@php $paymentSchedule=app(\App\Services\Hourly\PaymentScheduleService::class)->forOrder($order); @endphp
<section class="card mb-3"><div class="card-body" dir="rtl"><h5>استحقاق الدفع — {{ $paymentSchedule['policy_label'] }}</h5>
<p>المستحق الآن: <strong>{{ number_format($paymentSchedule['due_now'],2) }} ر.س</strong> · متأخر: <strong>{{ number_format($paymentSchedule['overdue'],2) }} ر.س</strong> · باقي قيمة الطلب: {{ number_format($paymentSchedule['remaining'],2) }} ر.س</p>
<p class="small text-muted">اكتمال الخدمة لا يثبت السداد. المبالغ تُسدّد عند تسجيل التحصيل الفعلي، ولا يلزم انتظار انتهاء كل الزيارات.</p>
@if($paymentSchedule['policy']==='installments')
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>الاستحقاق</th><th>القسط</th><th>مدفوع</th><th>متبقي</th><th>الحالة</th></tr></thead><tbody>
@foreach($paymentSchedule['installments'] as $inst)<tr><td>{{ $inst['due_date'] }}</td><td>{{ number_format($inst['amount'],2) }}</td><td>{{ number_format($inst['paid'],2) }}</td><td>{{ number_format($inst['remaining'],2) }}</td><td>{{ $inst['is_overdue']?'متأخر':($inst['payment_status']==='paid'?'مسدد':($inst['is_due']?'مستحق':'لم يحِن موعده')) }}</td></tr>@endforeach
</tbody></table></div>
@endif
</div></section>