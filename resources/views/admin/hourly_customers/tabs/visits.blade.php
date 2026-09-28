{{-- زيارات العميل عبر كل طلباته، الأحدث أولًا. --}}
@php
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $sep = app()->getLocale() === 'ar' ? '، ' : ', ';
@endphp
@if($visits->isEmpty())
    <div class="hc-empty">{{ $c('visits.empty') }}</div>
@else
    <div class="table-responsive"><table class="hc-table">
        <thead><tr><th>{{ $c('visits.date') }}</th><th>{{ $c('visits.number') }}</th><th>{{ $c('visits.order') }}</th><th>{{ $c('visits.workers') }}</th><th>{{ $c('visits.status') }}</th></tr></thead>
        <tbody>
        @foreach($visits as $visit)
            <tr>
                <td class="hc-money" dir="ltr" style="text-align:start">{{ $visit->scheduled_start_at?->format('d/m/Y H:i') }}–{{ $visit->scheduled_end_at?->format('H:i') }}</td>
                <td><a class="hc-name" href="{{ route('admin.hourly-visits.show', $visit->id) }}">{{ $visit->visit_number }}</a></td>
                <td><a href="{{ route('admin.hourly-orders.show', $visit->hourly_rental_order_id) }}">#{{ $visit->order?->order_number }}</a></td>
                <td>@forelse($visit->workers as $worker)<a href="{{ route('admin.hourly-maids.show', $worker->id) }}">{{ $worker->name }}</a>{{ $loop->last ? '' : $sep }}@empty — @endforelse</td>
                <td><span class="hc-badge">{{ __('hourly_routing.visit_statuses.'.$visit->status) }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <div class="mt-2">{{ $visits->links('admin.hourly_customers._pagination') }}</div>
@endif
