{{-- قالب PDF لتقرير بوابة دفع (mPDF: جداول بلا flex/grid). المتغيرات: $document, $brand, $rtl --}}
@php
    $main = $document['sheets'][0] ?? ['columns' => [], 'rows' => [], 'money' => []];
    $money = $main['money'] ?? [];
    $align = $rtl ? 'right' : 'left';
    $summaryRows = array_chunk($document['summary'], 4);
@endphp
<html dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
<style>
    body { font-size: 9.5pt; color: #0f172a; }
    .head { background: {{ $brand }}; color: #fff; padding: 12px 16px; border-radius: 8px; }
    .head h1 { font-size: 16pt; margin: 0 0 3px; }
    .head div { font-size: 9.5pt; }
    .meta { width: 100%; margin: 8px 0 10px; font-size: 8.5pt; color: #475569; }
    .meta td { padding: 2px 4px; text-align: {{ $align }}; }
    .kpis { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 0 -6px 8px; }
    .kpis td { width: 25%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 7px 9px; text-align: {{ $align }}; vertical-align: top; }
    .kpis .label { color: #64748b; font-size: 8pt; }
    .kpis .value { font-size: 12pt; font-weight: bold; color: #0f172a; }
    h2 { font-size: 11pt; color: {{ $brand }}; margin: 8px 0 5px; }
    table.data { width: 100%; border-collapse: collapse; font-size: 8.3pt; }
    table.data th { background: {{ $brand }}; color: #fff; padding: 5px 4px; text-align: {{ $align }}; font-weight: bold; }
    table.data td { padding: 4px; border-bottom: 1px solid #e2e8f0; text-align: {{ $align }}; }
    table.data tr.alt td { background: #f8fafc; }
    .num { direction: ltr; white-space: nowrap; }
    .empty { text-align: center; color: #64748b; padding: 18px; }
</style>
</head>
<body>
    <div class="head">
        <h1>{{ $document['title'] }}</h1>
        <div>{{ __('payment_gateways.period.title') }}: <span class="num">{{ $document['period'] }}</span></div>
    </div>

    <table class="meta">
        <tr>
            @foreach ($document['filters'] as $label => $value)
                <td><b>{{ $label }}:</b> {{ $value }}</td>
            @endforeach
            <td><b>{{ __('payment_gateways.export.generated_at') }}:</b> <span class="num">{{ now()->format('Y-m-d H:i') }}</span></td>
        </tr>
    </table>

    <table class="kpis">
        @foreach ($summaryRows as $chunk)
            <tr>
                @foreach ($chunk as [$label, $value])
                    <td><div class="label">{{ $label }}</div><div class="value num">{{ $value }}</div></td>
                @endforeach
                @for ($i = count($chunk); $i < 4; $i++)<td style="border:0"></td>@endfor
            </tr>
        @endforeach
    </table>

    <h2>{{ $main['name'] ?? '' }} ({{ count($main['rows']) }})</h2>
    @if (empty($main['rows']))
        <div class="empty">{{ __('payment_gateways.table.empty') }}</div>
    @else
        <table class="data">
            <thead>
                <tr>@foreach ($main['columns'] as $column)<th>{{ $column }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @foreach ($main['rows'] as $i => $row)
                    <tr class="{{ $i % 2 ? 'alt' : '' }}">
                        @foreach (array_values($row) as $c => $value)
                            <td class="{{ in_array($c, $money, true) || preg_match('/^[\d\s:\-\/.,+#]+$/', (string) $value) ? 'num' : '' }}">{{ in_array($c, $money, true) && is_numeric($value) ? number_format((float) $value, 2) : $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
