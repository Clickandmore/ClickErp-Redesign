@php($delay = \App\Services\Hourly\VisitDelay::state($visit))
@if($visit->is_missed)
    <span class="badge bg-danger">{{ __('hourly.missed') }}</span>
@elseif($delay)
    <span class="badge {{ $delay['stage']==='ended'?'bg-warning text-dark':'bg-danger' }}">{{ $delay['label'] }} · {{ $delay['minutes'] }} دقيقة بعد الموعد</span>
@elseif($visit->status==='picked_up')
    <span class="badge bg-info text-dark">في طريق العودة إلى الإيواء</span>
@endif
