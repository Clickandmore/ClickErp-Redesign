<table class="table" id="reportTable_data">
    <thead>
    <tr>
        <th>{{ __("finance::finance.code") }}</th>
        <th>{{ __("finance::finance.item") }}</th>
        <th>{{ __("finance::finance.category") }}</th>
        <th>{{ __("finance::finance.unit") }}</th>
        <th>{{ __("finance::finance.amount") }}</th>
        <th>{{ __("finance::finance.sales_price") }}</th>
        <th>{{ __("finance::finance.total") }}</th>
        <th>{{ __("finance::finance.user") }}</th>
        <th>{{ __("finance::finance.Statement") }}</th>
        <th>{{ __("finance::finance.total_discount_value") }}</th>
    </tr>
    </thead>
    <tbody>
    @php
        $total = 0;
    @endphp
    @forelse($report as $item)
        @php
            $sign =  ($item->type == 'back_sales') ? '-' : "" ;
            $total =  ($item->type == 'back_sales') ? $total - $item->total_amount * $item->total_sale_price : $total + $item->total_amount * $item->total_sale_price;
        @endphp
        <tr>
            <td>{{ $item->item->code }}</td>
            <td>{{ $item->item->name }}</td>
            <td>{{ $item->item->mainCategory->title ?? '' }}</td>
            <td>{{ $item->item->saleUnit->title ?? '' }}</td>
            <td>{{ $sign . $item->total_amount }}</td>
            <td>{{ $sign . $item->total_sale_price }}</td>
            <td>{{ $sign . $item->total_amount * $item->total_sale_price }}</td>
            <td>{{ $item->sale->created_by->name ?? 'No User' }}</td>
            <td>{{ Nami\Finance\Enums\SalesTypeEnum::from($item->type)->lang() }}</td>

            <td>{{ $item->sale->discount ?? 0 }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="8">{{ __("No Data Available") }}</td>
        </tr>
    @endforelse
    </tbody>

    <tfoot>
    <tr>
        <td colspan="6">{{ __("finance::finance.total") }}</td>
        <td>{{ $total }}</td>

    </tr>
    </tfoot>
</table>
