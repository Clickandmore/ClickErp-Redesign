{{-- كشف حساب بين تاريخين مع تصدير PDF وExcel. --}}
@php
    $c = fn (string $key, array $replace = []) => __('hourly_customers.'.$key, $replace);
    $money = fn ($value) => number_format((float) $value, 2);
    $range = ['from' => $statement['from']->toDateString(), 'to' => $statement['to']->toDateString()];
    $export = fn (string $format) => route('admin.hourly-customers.statement', $customer->id).'?'.http_build_query($range + ['format' => $format]);
@endphp
<form method="GET" action="{{ route('admin.hourly-customers.show', [$customer->id, 'report']) }}" class="d-flex flex-wrap gap-2 align-items-end mb-3">
    <div class="hc-field"><label for="hc-rep-from">{{ $c('report.from') }}</label><input class="form-control" type="date" id="hc-rep-from" name="from" value="{{ $range['from'] }}" required></div>
    <div class="hc-field"><label for="hc-rep-to">{{ $c('report.to') }}</label><input class="form-control" type="date" id="hc-rep-to" name="to" value="{{ $range['to'] }}" required></div>
    <button class="hc-btn is-primary"><i class="fa fa-magnifying-glass"></i> {{ $c('report.show') }}</button>
    <a class="hc-btn" href="{{ $export('pdf') }}" target="_blank" rel="noopener"><i class="fa fa-file-pdf" style="color:#c0262d"></i> {{ $c('report.pdf') }}</a>
    <a class="hc-btn" href="{{ $export('xlsx') }}"><i class="fa fa-file-excel" style="color:#0f7a5a"></i> {{ $c('report.excel') }}</a>
</form>

<div class="hc-buckets">
    <div class="hc-bucket"><h4>{{ $c('report.opening') }}</h4><b class="hc-money {{ $statement['opening'] < 0 ? 'is-neg' : '' }}">{{ $money($statement['opening']) }} {{ $c('sar') }}</b></div>
    <div class="hc-bucket"><h4>{{ $c('report.orders') }} / {{ $c('report.payments') }} / {{ $c('report.refunds') }}</h4>
        <b class="hc-money">{{ $money($statement['totals']['orders']) }} / {{ $money($statement['totals']['payments']) }} / {{ $money($statement['totals']['refunds']) }}</b></div>
    <div class="hc-bucket"><h4>{{ $c('report.closing') }}</h4><b class="hc-money {{ $statement['closing'] < 0 ? 'is-neg' : 'is-pos' }}">{{ $money($statement['closing']) }} {{ $c('sar') }}</b></div>
</div>
<div class="hc-muted small mb-2"><i class="fa fa-circle-info"></i> {{ $c('report.note') }} · {{ $c('report.wallet_opening') }}: <b>{{ $money($statement['wallet']['opening']) }}</b> · {{ $c('report.wallet_closing') }}: <b>{{ $money($statement['wallet']['closing']) }}</b></div>

@if($statement['lines']->isEmpty())
    <div class="hc-empty">{{ $c('report.empty') }}</div>
@else
    <div class="table-responsive"><table class="hc-table">
        <thead><tr><th>{{ $c('report.date') }}</th><th>{{ $c('report.kind') }}</th><th>{{ $c('report.ref') }}</th><th>{{ $c('report.debit') }}</th><th>{{ $c('report.credit') }}</th><th>{{ $c('report.balance') }}</th></tr></thead>
        <tbody>
        <tr><td colspan="5"><b>{{ $c('report.opening') }}</b></td><td class="hc-money">{{ $money($statement['opening']) }}</td></tr>
        @foreach($statement['lines'] as $line)
            <tr>
                <td class="hc-money" dir="ltr" style="text-align:start">{{ $line['at']->format('d/m/Y H:i') }}</td>
                <td>{{ $c('report.kinds.'.$line['kind']) }}</td>
                <td>{{ $line['ref'] }}</td>
                <td class="hc-money">{{ $line['debit'] ? $money($line['debit']) : '' }}</td>
                <td class="hc-money is-pos">{{ $line['credit'] ? $money($line['credit']) : '' }}</td>
                <td class="hc-money {{ $line['balance'] < 0 ? 'is-neg' : '' }}">{{ $money($line['balance']) }}</td>
            </tr>
        @endforeach
        <tr><td colspan="5"><b>{{ $c('report.closing') }}</b></td><td class="hc-money"><b>{{ $money($statement['closing']) }}</b></td></tr>
        </tbody>
    </table></div>
@endif
