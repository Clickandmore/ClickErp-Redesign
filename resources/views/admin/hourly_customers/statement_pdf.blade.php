@php
    $rtl = app()->getLocale() === 'ar';
    $c = fn (string $key, array $replace = []) => __('hourly_customers.report.'.$key, $replace);
    $money = fn ($value) => number_format((float) $value, 2);
    $align = $rtl ? 'right' : 'left';
@endphp
<style>
body{font-family:xbriyaz;color:#17354d;font-size:10.5pt;direction:{{ $rtl ? 'rtl' : 'ltr' }}}table{width:100%;border-collapse:collapse}td,th{padding:6px;text-align:{{ $align }}}
h1{font-size:18pt;margin:0 0 4px}.muted{color:#64748b;font-size:9pt}.header{border-bottom:3px solid #176795;padding-bottom:8px}
.items{margin-top:14px}.items th{background:#17354d;color:#fff}.items td{border-bottom:1px solid #dce4ed}.num{direction:ltr}
.sum td{background:#f5f8fb;font-weight:bold}.neg{color:#b42318}.pos{color:#0f7a5a}
</style>
<table class="header"><tr>
    <td><h1>{{ $c('title') }}</h1><div>{{ $customer->name }} — #{{ $customer->id }}</div><div class="muted num">{{ $customer->phone }}{{ $customer->email ? ' · '.$customer->email : '' }}</div></td>
    <td style="text-align:{{ $rtl ? 'left' : 'right' }}"><div>{{ $c('period') }}: <span class="num">{{ $statement['from']->format('Y-m-d') }} → {{ $statement['to']->format('Y-m-d') }}</span></div>
        <div class="muted">{{ $c('printed', ['date' => now()->format('Y-m-d H:i')]) }}</div></td>
</tr></table>

<table class="items">
    <thead><tr><th>{{ $c('date') }}</th><th>{{ $c('kind') }}</th><th>{{ $c('ref') }}</th><th>{{ $c('debit') }}</th><th>{{ $c('credit') }}</th><th>{{ $c('balance') }}</th></tr></thead>
    <tbody>
    <tr class="sum"><td colspan="5">{{ $c('opening') }}</td><td class="num">{{ $money($statement['opening']) }}</td></tr>
    @forelse($statement['lines'] as $line)
        <tr>
            <td class="num">{{ $line['at']->format('Y-m-d H:i') }}</td>
            <td>{{ $c('kinds.'.$line['kind']) }}</td>
            <td>{{ $line['ref'] }}</td>
            <td class="num">{{ $line['debit'] ? $money($line['debit']) : '' }}</td>
            <td class="num pos">{{ $line['credit'] ? $money($line['credit']) : '' }}</td>
            <td class="num {{ $line['balance'] < 0 ? 'neg' : '' }}">{{ $money($line['balance']) }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="muted">{{ $c('empty') }}</td></tr>
    @endforelse
    <tr class="sum"><td colspan="5">{{ $c('closing') }}</td><td class="num {{ $statement['closing'] < 0 ? 'neg' : '' }}">{{ $money($statement['closing']) }}</td></tr>
    </tbody>
</table>

<table class="items">
    <tr><td>{{ $c('orders') }}</td><td class="num">{{ $money($statement['totals']['orders']) }}</td><td>{{ $c('payments') }}</td><td class="num">{{ $money($statement['totals']['payments']) }}</td><td>{{ $c('refunds') }}</td><td class="num">{{ $money($statement['totals']['refunds']) }}</td></tr>
    <tr><td>{{ $c('wallet_opening') }}</td><td class="num">{{ $money($statement['wallet']['opening']) }}</td><td>{{ $c('wallet_closing') }}</td><td class="num">{{ $money($statement['wallet']['closing']) }}</td><td colspan="2"></td></tr>
</table>
<p class="muted">{{ $c('note') }}</p>
