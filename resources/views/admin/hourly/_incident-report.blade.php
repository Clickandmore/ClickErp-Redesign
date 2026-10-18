@if(\Illuminate\Support\Facades\Schema::hasTable('hourly_service_incidents') && AdminGuardHasPerm('hourly_orders_create') && !in_array($visit->status,['completed','cancelled','cancelled_by_client','expired','no_show'],true) && $visit->workers->isNotEmpty())
@php($incidentReportableWorkers=$visit->workers->filter(fn($worker)=>!\App\Services\Hourly\ServiceIncident::openReport($visit->id,$worker->id)))
@if($incidentReportableWorkers->isNotEmpty())
@include('admin.hourly._action-panel-style')
<details class="ap is-incident">
    <summary>
        <span class="ap-icon"><i class="fa fa-notes-medical"></i></span>
        <span class="ap-title"><b>تسجيل بلاغ تعذر تنفيذ</b><small>مرض أو حادث أو ظرف طارئ يمنع العاملة من تنفيذ الزيارة</small></span>
        <span class="ap-chip">الزيارة {{ $visit->visit_number }}</span>
        <i class="fa fa-chevron-down ap-caret" aria-hidden="true"></i>
    </summary>
    <form class="ap-body" method="post" action="{{ route('admin.hourly-incidents.report',[$order->id,$visit->id]) }}">
        @csrf<input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">
        <p class="ap-intro"><i class="fa fa-circle-info"></i><span>يُسجَّل البلاغ «قيد المراجعة»، ولا يوقف الزيارات حتى يُعتمد من قائمة بلاغات تعذّر التنفيذ. التفاصيل داخلية ولا تظهر للعميل.</span></p>
        <div class="ap-grid">
            <label class="ap-field"><span>العاملة</span>
                <select class="form-control" name="maid_id" required>
                    @foreach($incidentReportableWorkers as $worker)<option value="{{ $worker->id }}">{{ $worker->name }}</option>@endforeach
                </select>
            </label>
            <label class="ap-field"><span>التصنيف</span>
                <select class="form-control" name="category"><option value="illness">مرض</option><option value="accident">حادث</option><option value="emergency">ظرف طارئ</option></select>
            </label>
            <label class="ap-field is-wide"><span>تفاصيل داخلية <em>(للإدارة فقط، حتى 2000 حرف)</em></span>
                <textarea class="form-control" name="note" required maxlength="2000" placeholder="ما الذي حدث، ومتى، ومن أبلغ عنه"></textarea>
            </label>
        </div>
        <div class="ap-foot">
            <button class="ap-submit" type="submit"><i class="fa fa-paper-plane"></i> تسجيل البلاغ للمراجعة</button>
        </div>
    </form>
</details>
@endif

@endif