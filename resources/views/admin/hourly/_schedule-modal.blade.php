@php
// صفحة الطلب تمرر $order، وصفحة الزيارات تمرر $scheduleVisitList (زيارات من طلبات مختلفة؛ لكل زيارة رابط حفظ وحد أقصى من طلبها).
$scheduleVisits=[];$scheduleTargets=[];
if(isset($scheduleVisitList)){
    foreach($scheduleVisitList as $scheduleVisit){if(\App\Services\Hourly\VisitBatchService::slotPickable($scheduleVisit)){$scheduleVisits[$scheduleVisit->id]=$scheduleVisit->scheduled_start_at?->format('Y-m-d\TH:i');$scheduleTargets[$scheduleVisit->id]=['url'=>route('admin.hourly-orders.reschedule',['order'=>$scheduleVisit->order,'visit'=>$scheduleVisit->id]),'max'=>$scheduleVisit->order->expires_at?->toDateString()];}}
    $scheduleUrl=null;$scheduleMax=null;
}else{
    foreach($order->visits as $scheduleVisit){if($scheduleVisit->scheduled_start_at || ($scheduleVisit->status==='unscheduled' && in_array($order->status,['active','pending_confirmation'])))$scheduleVisits[$scheduleVisit->id]=$scheduleVisit->scheduled_start_at?->format('Y-m-d\TH:i');}
    $scheduleUrl=route('admin.hourly-orders.reschedule',[$order,'VISIT_ID']);$scheduleMax=$order->expires_at?->toDateString();
}
@endphp
@push('css')
<style>
#visit-schedule-modal .modal-dialog{max-width:500px}
#visit-schedule-modal .modal-content{border:0;border-radius:16px;overflow:hidden;box-shadow:0 18px 50px rgba(20,80,127,.18)}
#visit-schedule-modal .modal-header{display:flex;align-items:center;gap:.8rem;padding:1.1rem 1.4rem;border-bottom:1px solid #edf1f6;background:#f7fbff}
.vs-head-icon{flex:0 0 42px;width:42px;height:42px;border-radius:12px;background:#e3f0fc;color:#1b6fb8;display:flex;align-items:center;justify-content:center;font-size:1.1rem}
.vs-head-text{flex:1;min-width:0}
#vs-title{margin:0;font-size:1.05rem;font-weight:800;color:#14507f}
.vs-head-sub{display:block;font-size:.8rem;color:#6b7c8d;margin-top:.15rem}
.vs-visit{display:inline-block;margin-inline-start:.35rem;background:#14507f;color:#fff;border-radius:999px;padding:0 .55rem;font-size:.72rem;font-weight:700;vertical-align:middle}
#visit-schedule-modal .modal-header .btn-close{margin:0}
#visit-schedule-modal .modal-body{padding:1.3rem 1.4rem}
.vs-step{display:flex;align-items:center;gap:.5rem;font-size:.86rem;font-weight:700;color:#1e293b;margin-bottom:.5rem}
.vs-step-num{width:22px;height:22px;border-radius:50%;background:#1b6fb8;color:#fff;font-size:.74rem;display:inline-flex;align-items:center;justify-content:center}
.vs-date-wrap{position:relative}
.vs-date-wrap i{position:absolute;inset-inline-start:.85rem;top:50%;transform:translateY(-50%);color:#1b6fb8;pointer-events:none}
.vs-date-wrap input{padding-inline-start:2.4rem;height:46px;border-radius:11px;border-color:#d6e3f0;font-weight:600}
.vs-date-wrap input:focus{border-color:#2794eb;box-shadow:0 0 0 .2rem rgba(39,148,235,.15)}
.vs-slot-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(88px,1fr));gap:.5rem;max-height:220px;overflow-y:auto;padding:.15rem}
.vs-slot-card{border:1px solid #d6e3f0;border-radius:11px;background:#fff;color:#1b6fb8;padding:.6rem .4rem;font-weight:700;direction:ltr;transition:background .15s,border-color .15s,color .15s}
.vs-slot-card:hover{border-color:#2794eb;background:#eef6fe}
.vs-slot-card:focus-visible{outline:2px solid #2794eb;outline-offset:2px}
.vs-slot-card.is-current,.vs-slot-card.is-current:hover{background:#f1f3f5;border-style:dashed;border-color:#c3cbd4;color:#8a96a3;cursor:not-allowed}
.vs-slot-card small{display:block;font-weight:500;font-size:.7rem}
.vs-current{display:flex;align-items:center;gap:.5rem;margin-bottom:.9rem;padding:.55rem .8rem;border-radius:11px;background:#f1f7fd;border:1px solid #d6e6f6;color:#2c4a66;font-size:.84rem;font-weight:600}
.vs-slot-card.selected{background:#1b6fb8;border-color:#1b6fb8;color:#fff;box-shadow:0 4px 12px rgba(27,111,184,.3)}
.vs-slot-status{grid-column:1/-1;display:flex;align-items:center;gap:.5rem;justify-content:center;color:#68788b;background:#f8fafc;border:1px dashed #d6e3f0;border-radius:11px;padding:.9rem;margin:0;font-size:.84rem;text-align:center}
.vs-summary{display:flex;align-items:center;gap:.7rem;margin-top:1rem;padding:.7rem .9rem;border-radius:12px;background:#f8fafc;border:1px solid #edf1f6;color:#94a3b8}
.vs-summary i{font-size:1.1rem}
.vs-summary-label{display:block;font-size:.74rem}
#vs-preview{display:block;font-weight:800;font-size:.95rem}
.vs-summary.is-set{background:#ecfdf5;border-color:#a7f3d0;color:#047857}
#visit-schedule-modal .modal-footer{padding:.9rem 1.4rem;border-top:1px solid #edf1f6;gap:.5rem}
#visit-schedule-modal .modal-footer .btn{border-radius:10px;padding:.5rem 1.2rem;font-weight:700;margin:0}
#vs-save:disabled{opacity:.45}
.vs-open-btn{display:inline-flex;align-items:center;gap:.4rem;margin-top:.4rem;padding:.35rem .8rem;border-radius:9px;border:1px solid #2794eb;background:#eef6fe;color:#1b6fb8;font-size:.8rem;font-weight:700;white-space:nowrap;transition:background .15s,color .15s}
.vs-open-btn:hover,.vs-open-btn:focus-visible{background:#2794eb;color:#fff}
</style>
@endpush
<div class="modal fade" id="visit-schedule-modal" tabindex="-1" aria-labelledby="vs-title" aria-hidden="true" dir="{{ app()->getLocale()==='ar'?'rtl':'ltr' }}"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
<div class="modal-header">
    <span class="vs-head-icon" aria-hidden="true"><i class="fa fa-calendar-days"></i></span>
    <div class="vs-head-text"><h5 id="vs-title">{{ __('visit_schedule.title') }}<span id="vs-visit" class="vs-visit" hidden></span></h5><span class="vs-head-sub">{{ __('visit_schedule.subtitle') }}</span></div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('visit_schedule.close') }}"></button>
</div>
<form id="visit-schedule-form" method="POST">@csrf @method('PUT')<input type="hidden" name="starts_at"><input type="hidden" name="expected_start_at">
<div class="modal-body">
    <div id="vs-current" class="vs-current" hidden><i class="fa fa-clock-rotate-left" aria-hidden="true"></i><span></span></div>
    <label for="vs-date" class="vs-step"><span class="vs-step-num">1</span>{{ __('visit_schedule.date') }}</label>
    <div class="vs-date-wrap"><i class="fa fa-calendar" aria-hidden="true"></i><input class="form-control" type="date" id="vs-date" min="{{ now()->toDateString() }}" max="{{ $scheduleMax }}"></div>
    <div class="vs-step mt-3" id="vs-time-label"><span class="vs-step-num">2</span>{{ __('visit_schedule.time') }}</div>
    <div id="vs-slot-cards" class="vs-slot-cards" role="group" aria-labelledby="vs-time-label"></div>
    <div id="vs-summary" class="vs-summary"><i class="fa fa-circle-check" aria-hidden="true"></i><div><span class="vs-summary-label">{{ __('visit_schedule.selected') }}</span><output id="vs-preview" dir="ltr" aria-live="polite">{{ __('visit_schedule.none_selected') }}</output></div></div>
    <div id="vs-error" class="alert alert-danger mt-3 mb-0" role="alert" hidden></div>
</div>
<div class="modal-footer"><button id="vs-save" class="btn btn-primary"><i class="fa fa-check ms-1"></i> {{ __('visit_schedule.save') }}</button><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('visit_schedule.close') }}</button></div></form></div></div></div>
@push('js')
<script>window.hourlyScheduleConfig={visits:@json((object) $scheduleVisits),url:@json($scheduleUrl),targets:@json((object) $scheduleTargets),slotsUrl:@json(route('admin.hourly-visits.reschedule-slots','VISIT_ID')),locale:@json(app()->getLocale()),min:@json(now()->toDateString()),max:@json($scheduleMax),error:@json(__('visit_schedule.error')),text:{visit:@json(__('visit_schedule.visit')),loading:@json(__('visit_schedule.loading')),noSlots:@json(__('visit_schedule.no_slots')),pickSlot:@json(__('visit_schedule.pick_slot')),noneSelected:@json(__('visit_schedule.none_selected')),current:@json(__('visit_schedule.current')),currentHint:@json(__('visit_schedule.current_hint')),noOtherSlots:@json(__('visit_schedule.no_other_slots'))}};</script>
<script src="{{ asset('admin/assets/js/hourly-visit-schedule.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-visit-schedule.js')) }}"></script>
@endpush
