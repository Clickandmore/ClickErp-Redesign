<table class="table" id="reportTableData" style="border: 1px solid black; border-collapse: collapse;">
    <thead>
        <tr>
            <th scope="col" style="border: 1px solid black;">{{ __("finance::finance.stock") }}</th>
            <th scope="col" style="border: 1px solid black;">{{ __("finance::finance.item_code") }}</th>
            <th scope="col" style="border: 1px solid black;">{{ __("finance::finance.item") }}</th>
            <th scope="col" style="border: 1px solid black;">{{ __("finance::finance.amount") }}</th>
            <th scope="col" style="border: 1px solid black;">{{ __("finance::finance.price") }}</th>
            <th scope="col" style="border: 1px solid black;">{{ __("finance::finance.total") }}</th>
        </tr>
    </thead>
    @if ($stock_status->count() > 0 && $stock_status['all_stocks_total']['total_price'] != 0)
        <tbody>
            @foreach ($stock_status as $stockId => $stockData)
                @if ($stockId !== 'all_stocks_total')
                    @php
                        $rowspan = count($stockData['data']) + 1;
                    @endphp
                    @foreach ($stockData['data'] as $index => $status)
                        <tr>
                            @if ($index == 0)
                                <td rowspan="{{ $rowspan }}">{{ $status['stock'] }}</td>
                            @endif
                            <td>{{ $status['item_code'] }}</td>
                            <td>{{ $status['item_name'] }}</td>
                            <td>{{ $status['amount'] }}</td>
                            <td>{{ $status['price'] }}</td>
                            <td>{{ $status['total'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="highlight">
                        <td colspan="4">{{ __("finance::finance.total") }}</td>
                        <td>{{ $stockData['total']['stock_total_price'] }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="border: 1px solid black;">{{ __("finance::finance.total_for_all_stocks") }}</td>
                <td colspan="2" style="border: 1px solid black;">{{ $stock_status['all_stocks_total']['total_price'] }}</td>
            </tr>
        </tfoot>
    @else
        <tfoot>
            <tr style="text-align: center">
                <td colspan="6" style="border: 1px solid black;">{{ __("finance::finance.No Data Available") }}</td>
            </tr>
        </tfoot>
    @endif
</table>
