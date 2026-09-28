{{-- تقييمات العميل للعاملات (hourly_worker_reviews). --}}
@php
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $summary = \Illuminate\Support\Facades\DB::table('hourly_worker_reviews')->where('user_id', $customer->id)->selectRaw('COUNT(*) as n, AVG(rating) as avg')->first();
@endphp
@if($reviews->isEmpty())
    <div class="hc-empty">{{ $c('ratings.empty') }}</div>
@else
    <div class="hc-note mb-3"><i class="fa fa-star mt-1" style="color:#f59e0b"></i><div>{{ $c('ratings.average', ['avg' => number_format((float) $summary->avg, 1), 'count' => $summary->n]) }}</div></div>
    <div class="table-responsive"><table class="hc-table">
        <thead><tr><th>{{ $c('ratings.date') }}</th><th>{{ $c('ratings.worker') }}</th><th>{{ $c('ratings.visit') }}</th><th>{{ $c('ratings.stars') }}</th><th>{{ $c('ratings.comment') }}</th></tr></thead>
        <tbody>
        @foreach($reviews as $review)
            <tr>
                <td class="hc-money" dir="ltr" style="text-align:start">{{ \Carbon\Carbon::parse($review->created_at)->format('d/m/Y H:i') }}</td>
                <td>@if($review->maid_id)<a href="{{ route('admin.hourly-maids.show', $review->maid_id) }}">{{ $review->maid_name ?? '#'.$review->maid_id }}</a>@else — @endif</td>
                <td>
                    @if($review->visit_id)<a href="{{ route('admin.hourly-visits.show', $review->visit_id) }}">{{ $review->visit_number ?? '#'.$review->visit_id }}</a>@endif
                    @if($review->order_number)<div class="hc-muted small">#{{ $review->order_number }}</div>@endif
                </td>
                <td><span class="hc-stars" aria-label="{{ (int) $review->rating }}/5">@for($i = 1; $i <= 5; $i++)<i class="fa fa-star {{ $i <= (int) $review->rating ? '' : 'is-off' }}"></i>@endfor</span></td>
                <td>{{ $review->comment ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <div class="mt-2">{{ $reviews->links('admin.hourly_customers._pagination') }}</div>
@endif
