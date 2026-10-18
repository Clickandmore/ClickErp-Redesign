@php($visitReference = __('hourly_review.visit_reference',['number'=>$trip['visit_number'],'id'=>$trip['visit_id']]))
@if(AdminGuardHasPerm('hourly_orders_view'))
    <a href="{{ route('admin.hourly-visits.show',$trip['visit_id']) }}">{{ $visitReference }}</a>
@else
    {{ $visitReference }}
@endif
· {{ __('hourly_review.order_reference',['number'=>$trip['order_number'] ?: '#'.$trip['order_id']]) }}
@if($trip['since']) · {{ __('hourly_review.started',['date'=>\Carbon\Carbon::parse($trip['since'])->format('Y-m-d H:i')]) }} @endif
@if($trip['until']) · {{ __('hourly_review.scheduled_end',['date'=>\Carbon\Carbon::parse($trip['until'])->format('Y-m-d H:i')]) }} @endif
