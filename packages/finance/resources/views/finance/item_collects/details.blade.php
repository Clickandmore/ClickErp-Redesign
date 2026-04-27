@if($item_coms->count() > 0)
    <table class="table align-middle card-table my-2" id="json_array">
    <thead>
    <tr>
        <th>{{ __("finance::finance.item_code") }}</th>
        <th>{{ __("finance::finance.order_amount") }}</th>
        <th>{{ __("finance::finance.the_stock") }}</th>
        <th>{{ __("finance::finance.available_amount") }}</th>
        <th>{{ __("finance::finance.added_amount") }}</th>
    </tr>
    </thead>
    <tbody >
    @php
        $key_stock = 0
    @endphp
    @foreach($item_coms as $key => $item_com)
        @php
            $firstRow = true; // متغير للتحقق من أول صف
        @endphp

        @foreach($item_com->stocks as $stock)
            <tr class="y-group">
                @if($firstRow)
                    <td style="min-width: 170px;" rowspan="{{$item_com->stocks->count()}}">
                        <label class="form-label">{{ $item_com->componentItem->name }}</label>

                        <input type="hidden" value="{{ $item_com->amount }}" class="itemAmount itemList{{$key}}">
                    </td>
                    <td style="max-width: 100px" rowspan="{{$item_com->stocks->count()}}">
                        <label class="form-label orderAmountInput"></label>

                        <input type="hidden" class=" orderAmountInput orderAmountInput_{{$key}}"
                                value=""
                               id="order_amount" readonly>
                    </td>
                    @php
                        $firstRow = false;
                    @endphp
                @endif

                <td style="max-width: 100px">
                    <input type="hidden" name="details[{{$key_stock}}][item_id]"
                           value="{{ $item_com->componentItem->id }}">
                    <input type="hidden" name="details[{{$key_stock}}][stock_id]" value="{{ $stock->id }}">
                    <label class="form-label">{{ $stock->name }}</label>
                </td>

                <td style="max-width: 100px">
                    <label class="form-label availableAmount">{{ $item_com->componentItem->getItemStockAmount($stock->id,$item_com->componentItem->id)}}</label>
{{--                    <input type="text"--}}
{{--                           value=""--}}
{{--                           class="form-control availableAmount"  readonly>--}}
                </td>

                    <td style="max-width: 100px">
                        <input data-row="{{$key}}" type="number" name="details[{{$key_stock}}][order_amount]" class="form-control addedAmount"   data-validation="required,max_value_{{$key_stock}}" value="0">
                    </td>

                @php
                    $key_stock++
                @endphp
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>

@endif
<script>
</script>

