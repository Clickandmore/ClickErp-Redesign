@if(AdminGuardHasPerm('hourly_orders_create'))
<button type="button" class="btn btn-sm btn-outline-primary om-note-btn" data-operational-note="{{ $visitId ?? '' }}"><i class="fa fa-plus-circle" aria-hidden="true"></i> {{ __('operational_notes.title') }}</button>
@once
@include('admin.hourly._operational-note-modal')
@endonce
@endif
