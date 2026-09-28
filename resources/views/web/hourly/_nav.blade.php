@push('css')
<link rel="stylesheet" href="{{ asset('web/assets/css/hourly-rental.css') }}?v={{ filemtime(public_path('web/assets/css/hourly-rental.css')) }}">
@endpush
<nav class="hr-subnav" aria-label="إيجار الساعات">
    @foreach ([
        ['web.hourly.index', ['web.hourly.index'], 'fa-light fa-user-clock', 'عاملات الساعات'],
        ['web.hourly.catalog', ['web.hourly.catalog', 'web.hourly.package', 'web.hourly.checkout'], 'fa-light fa-box-open', 'تصفح الباقات'],
        ['web.hourly.orders', ['web.hourly.orders', 'web.hourly.order'], 'fa-light fa-calendar-check', 'حجوزاتي'],
    ] as [$routeName, $activeOn, $icon, $label])
        <a href="{{ route($routeName) }}" class="{{ request()->routeIs(...$activeOn) ? 'active' : '' }}" aria-current="{{ request()->routeIs(...$activeOn) ? 'page' : 'false' }}">
            <i class="{{ $icon }}"></i> {{ $label }}
        </a>
    @endforeach
</nav>
