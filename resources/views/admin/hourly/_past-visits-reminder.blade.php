@php($pastVisitReminder = \Illuminate\Support\Facades\Cache::get(\App\Console\Commands\HourlyPastVisitReminder::CACHE_KEY))
@if($pastVisitReminder && ($pastVisitReminder['count'] ?? 0)>0)
<div class="alert alert-warning mx-3 mt-2" role="status">
    <strong>{{ __('hourly_review.reminder_title') }}</strong>
    {{ __('hourly_review.reminder_body',['count'=>$pastVisitReminder['count'],'date'=>\Carbon\Carbon::parse($pastVisitReminder['reviewed_at'])->timezone('Asia/Riyadh')->format('Y-m-d H:i')]) }}
    <a class="alert-link" href="{{ route('admin.hourly-visits.past',['from'=>'','to'=>'']) }}">{{ __('hourly_review.review_link') }}</a>
</div>
@endif
