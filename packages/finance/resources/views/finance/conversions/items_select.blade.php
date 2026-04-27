
<option value="">{{ __("finance::finance.choose") }}</option>
    @if ($items->count() > 0)
    @foreach ($items as $item)
        @php
            $total_current_amount = $item->itemStockStatus->sum('amount');
            $total_price = $item->itemStockStatus->sum('price');
        @endphp
        <option value="{{ $item->id }}"
                data-purchasePrice="{{ $item->purchase_price }}"
                data-price="{{ $total_price }}"
                data-availableAmount="{{ $total_current_amount }}"
                {{ $item->id == @$conversionItem->item_id ? 'selected' : '' }}>
            {{ $item->name }}
        </option>
    @endforeach
@else
    <option value="">{{ __("finance::finance.No Data Available") }}</option>
@endif
