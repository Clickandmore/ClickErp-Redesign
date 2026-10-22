@php
    $tone = match (true) {
        in_array($value, ['active', 'paid', 'completed'], true) => 'ok',
        in_array($value, ['missed', 'cancelled', 'cancelled_by_client', 'cancelled_by_admin', 'expired', 'failed', 'refunded'], true) => 'bad',
        default => 'wait',
    };
@endphp
<span class="hr-status {{ $tone }}">{{ $label ?? __('hourly.' . $value) }}</span>
