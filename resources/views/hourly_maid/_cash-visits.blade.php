@if(\App\Services\Hourly\CashCollectionService::ready())
@php
    $cashMaid=\App\Models\ingaz\Maid::findOrFail($maid->id);
    $cashBoard=app(\App\Services\Hourly\WorkerAppService::class)->today($cashMaid,$cashDate??null);
    $custody=app(\App\Services\Hourly\CashCollectionService::class)->custody('worker',(int)$maid->id);
@endphp
<section class="panel" style="padding:20px;margin-bottom:20px">
<h2>الزيارات والتحصيل النقدي</h2><form method="GET" action="{{ route('hourly-maid.home') }}"><label>تاريخ الزيارات</label><input type="date" name="date" value="{{ $cashDate??now()->toDateString() }}"><button>عرض</button></form>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
<p>العهدة النقدية المتبقية معك: <strong>{{ number_format($custody['outstanding'],2) }} ر.س</strong>. سلّميها للموظف المخوّل ليؤكد التوريد.</p>
<div style="overflow:auto"><table style="width:100%"><thead><tr><th>الزيارة</th><th>الحالة</th><th>مسؤول التحصيل</th><th>المطلوب تحصيله الآن</th><th>الإجراء</th></tr></thead><tbody>
@forelse($cashBoard as $task)<tr><td>#{{ $task['visit_number'] }} · {{ $task['start_time'] }}</td><td>{{ __('hourly.'.$task['visit_status']) }}<div>الدفع: {{ ['paid'=>'مسددة','partial'=>'مسددة جزئيًا','unpaid'=>'غير مسددة'][$task['financial']['payment_status']??'unpaid'] }} {{ !empty($task['financial']['is_overdue'])?'(متأخر)':'' }}</div></td><td>{{ $task['cash']['collector']['label'] ?? '—' }}</td><td>{{ number_format($task['cash']['cash_due']??0,2) }} ر.س</td><td>
@if($task['can_arrive'])<form method="POST" action="{{ route('hourly-maid.visits.action',$task['id']) }}">@csrf<input type="hidden" name="action" value="arrived"><button>وصلت</button></form>@endif
@if($task['cash']['can_collect']??false)<form method="POST" action="{{ route('hourly-maid.cash.collect',$task['id']) }}">@csrf<input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}"><label>النقد المستلم من العميل (ر.س)</label><input type="number" name="amount" min="0.01" max="{{ $task['cash']['cash_due'] }}" step="0.01" required><button>تأكيد استلام النقد</button></form>@endif
@if($task['can_finish'])<form method="POST" action="{{ route('hourly-maid.visits.action',$task['id']) }}">@csrf<input type="hidden" name="action" value="finish"><button>انتهيت من الخدمة</button></form>@endif
</td></tr>@empty<tr><td colspan="5">لا توجد زيارات في التاريخ المحدد.</td></tr>@endforelse
</tbody></table></div>
<details><summary>سندات التحصيل والعهدة</summary>@foreach($custody['collections'] as $entry)<p><a target="_blank" rel="noopener" href="{{ route('hourly-maid.cash.receipt',$entry['collection_id']) }}">{{ $entry['receipt_number'] }}</a> · محصّل {{ number_format($entry['amount'],2) }} · متبقي للتوريد {{ number_format($entry['custody_due'],2) }} ر.س</p>@endforeach</details>
<p>إكمال الخدمة لا يعني تحصيل المبلغ أو توريده. سجّلي فقط النقد الذي استلمتِه فعلًا.</p>
</section>
@endif
