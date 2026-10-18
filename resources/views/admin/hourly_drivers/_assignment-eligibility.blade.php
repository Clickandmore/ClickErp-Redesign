@php($residencyReason = $driver->residencyAssignmentReason())
@if($residencyReason)
    <div class="ht-chip hd-chip-danger dr-elig mt-1" data-driver-residency="expired" title="{{ $residencyReason }}"><i class="fa fa-ban" aria-hidden="true"></i> {{ __('hourly_driver_assignment.blocked_badge') }}</div>
    <div class="dr-elig-note" title="{{ $residencyReason }}">{{ __('hourly_driver_assignment.blocked_note', ['date' => \Carbon\Carbon::parse($driver->residency_expiry_date)->format('Y-m-d')]) }}</div>
@elseif($driver->residency_expiry_date && \Carbon\Carbon::parse($driver->residency_expiry_date)->startOfDay()->lte(today()->addDays(30)))
    <div class="ht-chip hd-chip-warn dr-elig mt-1" data-driver-residency="expiring">{{ __('hourly_driver_assignment.expiring_badge',['date'=>\Carbon\Carbon::parse($driver->residency_expiry_date)->format('Y-m-d')]) }}</div>
@endif
