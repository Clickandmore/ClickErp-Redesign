@once
@php
    $hourlyMapConfig = [
        'provider' => \App\Support\HourlyRentalSettings::mapProvider(),
        'key' => \App\Support\HourlyRentalSettings::googleMapsKey(),
        'language' => app()->getLocale(),
        'bridgeUrl' => asset('admin/assets/js/leaflet-google-mutant.js'),
    ];
@endphp
<script>window.hourlyMapConfig = @json($hourlyMapConfig);</script>
<script src="{{ asset('admin/assets/js/hourly-map-provider.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-map-provider.js')) }}"></script>
@endonce
