{{-- يفتح نافذة اختيار المواعيد المتاحة نفسها المستخدمة في صفحة الطلب: تحديد موعد لزيارة بلا موعد، أو إعادة جدولة زيارة قادمة. --}}
@if(($canSchedule ?? false) && \App\Services\Hourly\VisitBatchService::slotPickable($visit))
    <button type="button" class="vs-open-btn" onclick="window.openHourlySchedule({{ $visit->id }})" aria-label="{{ __('visit_schedule.title') }} — زيارة {{ $visit->visit_number }}"><i class="fa {{ $visit->scheduled_start_at ? 'fa-calendar-days' : 'fa-calendar-plus' }}" aria-hidden="true"></i> {{ $visit->scheduled_start_at ? __('visit_schedule.reschedule') : __('visit_schedule.set') }}</button>
@endif
