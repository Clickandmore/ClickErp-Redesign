<table class="table" id="reportTableData">
    <thead>
    <tr>
        <th>{{ __("finance::finance.code") }}</th>
        <th>{{ __("finance::finance.date") }}</th>
        <th>{{ __("finance::finance.Statement") }}</th>
        <th>{{ __("finance::finance.client") }}</th>
        <th>{{ __("finance::finance.amount") }}</th>
        <th>{{ __("finance::finance.price") }}</th>
        <th>{{ __("finance::finance.stock") }}</th>
        <th>{{ __("finance::finance.total") }}</th>
    </tr>
    </thead>
    <tbody>
    @forelse($report as $item)
        <tr>
            <td>{{ $item->sale->code ?? "" }}</td>
            <td>{{ $item->sale->date ?? "" }}</td>
            <td>{{ Nami\Finance\Enums\SalesTypeEnum::from($item->sale->type)->lang() }}</td>
            <td>{{ $item->sale->client->name ?? '' }}</td>
            <td>{{ $item->amount }}</td>
            <td>{{ $item->price }}</td>
            <td>{{ $item->stock->name ?? '' }}</td>
            <td>{{ $item->final_total }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8">{{ __("No Data Available") }}</td>
        </tr>
    @endforelse
    </tbody>

    <tfoot>
    <tr>
        <td colspan="4">{{ __("finance::finance.total_amount") }}</td>
        <td>{{ $report->sum('amount') }}</td>
        <td colspan="2">{{ __("finance::finance.final_total") }}</td>
        <td>{{ $report->sum('final_total') }}</td>
    </tr>
    </tfoot>
</table>
