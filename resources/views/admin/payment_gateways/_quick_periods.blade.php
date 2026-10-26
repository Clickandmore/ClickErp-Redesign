{{-- اختصارات الأشهر الأخيرة. المتغيرات: $period, $route, $filters --}}
@php
    $quickFilters = array_filter($filters, fn ($value) => $value !== null && $value !== '');
    $quickMonths = collect(range(0, 5))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
@endphp
<div class="pg-quick">
    @foreach ($quickMonths as $i => $month)
        <a href="{{ route($route, $quickFilters + ['month' => $month->format('Y-m')]) }}"
            class="{{ $period->mode === 'month' && $period->month() === $month->format('Y-m') ? 'active' : '' }}">
            {{ $i === 0 ? __('payment_gateways.period.this_month') : ($i === 1 ? __('payment_gateways.period.last_month') : $month->locale(app()->getLocale())->translatedFormat('F Y')) }}
        </a>
    @endforeach
</div>
