<div class="row g-3">
    <div class="col-md-4">
        <label>{{ __('finance::finance.code') }}</label>
        <input type="text" class="form-control" value="{{ $movement->code }}" readonly>
    </div>

    <div class="col-md-4">
        <label>{{ __('finance::finance.the_stock') }}</label>
        <input type="text" class="form-control" value="{{ @$movement->stock->name }}" readonly>
    </div>

    <div class="col-md-4">
        <label>{{ __('finance::finance.date') }}</label>
        <input type="text" class="form-control" value="{{ $movement->date }}" readonly>
    </div>

    <div class="col-md-12">
        <label>{{ __('finance::finance.notes') }}</label>
        <textarea class="form-control" rows="3" readonly>{{ $movement->note ?? 'لا يوجد' }}</textarea>
    </div>
</div>
<table class="table table-striped">
    <thead>
    <tr>
        <th>{{ __("finance::finance.code") }}</th>
        <th>{{ __("finance::finance.items") }}</th>
        <th>{{ __("finance::finance.unit") }}</th>

        @if($stock_setting->show_price_in_additional_permit)
            <th>{{ __("finance::finance.price") }}</th>
        @endif

        <th>{{ __("finance::finance.amount") }}</th>

        @if($stock_setting->show_price_in_additional_permit)
            <th>{{ __("finance::finance.total") }}</th>
        @endif
    </tr>
    </thead>
    <tbody>
    @foreach ($movement->details as $itemRow)
        <tr>
            <td>{{ $itemRow->item->code_string . $itemRow->item->code }}</td>
            <td>{{ $itemRow->item->name }}</td>
            <td>{{ $itemRow->item->purchaseUnit->title ?? '' }}</td>

            @if($stock_setting->show_price_in_additional_permit)
                <td>{{ number_format($itemRow->price, 2) }}</td>
            @endif

            <td>{{ abs($itemRow->amount) }}</td>

            @if($stock_setting->show_price_in_additional_permit)
                <td>{{ number_format($itemRow->total_price, 2) }}</td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>
<div class="col-12 modal-footer">
    {!! closeButton() !!}
</div>
