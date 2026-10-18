@if(\Illuminate\Support\Facades\Schema::hasTable('hourly_service_incidents') && !in_array($visit->status,['completed','cancelled','cancelled_by_client','expired','no_show'],true) && $visit->workers->isNotEmpty())
@php
    $incidentReports=$visit->workers->mapWithKeys(fn($worker)=>[$worker->id=>\App\Services\Hourly\ServiceIncident::openReport($visit->id,$worker->id)]);
    $reportableWorkers=$visit->workers->filter(fn($worker)=>!$incidentReports[$worker->id]);
@endphp
@foreach($visit->workers as $worker)
    @if($existingReport=$incidentReports[$worker->id])
    <p role="status" class="mt-2" style="padding:12px;border:1px solid #f4d99c;border-radius:10px;background:#fff9ed;color:#805817">
        <strong>بلاغ قائم #{{ $existingReport->id }}</strong> — {{ $worker->name }}<br>
        {{ $existingReport->status==='pending'?'بانتظار تحقق الإدارة؛ لا حاجة لإرسال بلاغ آخر.':'بلاغ معتمد؛ تتابع الإدارة المعالجة والعودة حسب حالة التنفيذ.' }}
    </p>
    @endif
@endforeach
@if($reportableWorkers->isNotEmpty())
<details class="mt-2"><summary>الإبلاغ عن تعذر تنفيذ العاملة</summary>
<form method="post" action="{{ route('hourly-driver.visits.incidents',$visit->id) }}">
    @csrf<input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
    <label>العاملة<select name="maid_id" required>
        @foreach($reportableWorkers as $worker)<option value="{{ $worker->id }}">{{ $worker->name }}</option>@endforeach
    </select></label>
    <label>نوع البلاغ<select name="category"><option value="illness">مرض</option><option value="accident">حادث</option><option value="emergency">ظرف طارئ</option></select></label>
    <label>تفاصيل للإدارة فقط<textarea name="note" required maxlength="2000"></textarea></label>
    <button type="submit" class="action-btn">إرسال البلاغ للتحقق</button>
</form></details>
@endif
@endif