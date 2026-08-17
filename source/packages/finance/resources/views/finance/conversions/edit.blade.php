<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')
    <div class="col-md-3">
        <label for="code" class="form-label">{{ __("finance::finance.code") }}</label>
        <input type="text" name="code" id="code" value="{{ $obj->code }}" class="form-control" readonly data-validation="required">
    </div>

    <div class="col-md-3">
        <label for="date" class="form-label">{{ __("finance::finance.date") }}</label>
        <input type="date" name="date" id="date" value="{{ $obj->date }}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-3">
        <label for="from_stock" class="form-label">{{ __("finance::finance.from_stock") }}</label>
        <select name="from_stock_id" id="from_stock" class="form-control" data-validation="required">
            {!! showSelectElement($stocks, 'name', $obj->from_stock_id) !!}
        </select>
    </div>

    <div class="col-md-3">
        <label for="to_stock" class="form-label">{{ __("finance::finance.to_stock") }}</label>
        <select name="to_stock_id" id="to_stock" class="form-control" data-validation="required">
            {!! showSelectElement($stocks, 'name', $obj->to_stock_id) !!}
        </select>
    </div>
    <div id="table_items">
        <table id="myDataTable_no_filter" class="table align-middle card-table my-2">
            <thead>
                <tr>
                    <th>{{ __("finance::finance.product_code") }}</th>
                    @if ($show_price_in_conversions == 1)
                    <th>{{ __("finance::finance.purchase_price") }} </th>
                    @endif
                    <th>{{ __("finance::finance.available_amount") }} </th>
                    @if ($show_price_in_conversions == 1)
                    <th>{{ __("finance::finance.price") }} </th>
                    @endif
                    <th>{{ __("finance::finance.transfer_amount") }} </th>
                    <th>{{ __("finance::finance.notes") }} </th>
                    <th>{{ __("finance::finance.actions") }}</th>
                </tr>
            </thead>
            <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>

            @forelse ($obj->conversionItems as $key => $conversionItem)
                <tr class="y-group">
                    <td>
                        <select name="items[{{ $key }}][item_id]" id="item_id" class="form-control index-plus item-select" data-validation="required">
                        @include("finance::finance.conversions.items_select")
                            <option value="">{{ __("finance::finance.choose") }}</option>
                            @foreach ($items as $item)
                                @php
                                    $total_current_amount = $item->itemStockStatus->sum('amount');
                                    $total_price = $item->itemStockStatus->sum('price');
                                @endphp
                                <option value="{{ $item->id }}"
                                        data-purchasePrice="{{ $item->purchase_price }}"
                                        data-price="{{ $total_price }}"
                                        data-availableAmount="{{ $total_current_amount }}"
                                        {{ $item->id == $conversionItem->item_id ? 'selected' : '' }}>
                                    {{ $item->name }} - {{ $item->code_string }}{{ $item->code }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    @if ($show_price_in_conversions == 1)
                    <td>
                        <input type="number" step="0.01" readonly name="items[{{ $key }}][purchase_price]" class="form-control index-plus purchase-price-field" value="{{ $conversionItem->purchase_price }}" data-validation="required">
                    </td>
                    @endif
                    <td>
                        <input type="number" step="0.01" readonly name="items[{{ $key }}][available_amount]" class="form-control index-plus available-amount-field" value="{{ $conversionItem->available_amount }}" data-validation="required">
                    </td>

                    @if ($show_price_in_conversions == 1)
                    <td>
                        <input type="number" step="0.01" readonly name="items[{{ $key }}][price]" class="form-control index-plus price-field" data-validation="required">
                    </td>
                    @endif
                    <td>
                        <input type="number" step="0.01" name="items[{{ $key }}][transfer_amount]" class="form-control index-plus transfer-amount-field" value="{{ $conversionItem->transfer_amount }}"
                               data-validation="required custom">
                    </td>
                    <td>
                        <textarea name="items[{{ $key }}][notes]" id="notes" class="form-control index-plus" cols="15" rows="4">{{ $conversionItem->notes }}</textarea>
                    </td>
                    <td>
                        <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                        <i class="fa fa-trash-o btnRemove"></i>
                    </td>
                </tr>
            @empty
                <tr class="y-group">
                    <td>
                        <select name="items[0][item_id]" id="item_id" class="form-control index-plus item-select"
                                data-validation="required">
                            <option value="">{{ __("finance::finance.choose") }}</option>
                            @foreach ($items as $item)
                                @php
                                    $total_current_amount = $item->itemStockStatus->sum('amount');
                                    $total_price = $item->itemStockStatus->sum('price');
                                @endphp
                                <option value="{{ $item->id }}"
                                        data-purchasePrice="{{ $item->purchase_price }}"
                                        data-price="{{ $total_price }}"
                                        data-availableAmount="{{ $total_current_amount }}">
                                    {{ $item->name }} - {{ $item->code_string }}{{ $item->code }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    @if ($show_price_in_conversions == 1)
                        <td>
                            <input type="number" step="0.01" readonly name="items[0][purchase_price]" class="form-control index-plus purchase-price-field" data-validation="required">
                        </td>
                    @endif
                    <td>
                        <input type="number" step="0.01" readonly name="items[0][available_amount]" class="form-control index-plus available-amount-field" data-validation="required">
                    </td>
                     @if ($show_price_in_conversions == 1)
                        <td>
                            <input type="number" step="0.01" readonly name="items[0][price]" class="form-control index-plus price-field" data-validation="required">
                        </td>
                    @endif
                    <td>
                        <input type="number" step="0.01" name="items[0][transfer_amount]" class="form-control index-plus transfer-amount-field"
                            data-validation="required custom">
                    </td>
                    <td>
                        <textarea name="items[0][notes]" id="notes" class="form-control index-plus" cols="15" rows="4"></textarea>
                    </td>
                    <td>
                        <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                        <i class="fa fa-trash-o btnRemove"></i>
                    </td>
                </tr>
            @endforelse

            </tbody>
        </table>
    </div>

    <div class="col-md-6">
        <label for="TextInput" class="form-label">{{ __("finance::finance.file") }}</label>
        <input type="file" name="file" class="dropify" data-default-file="{{showFile($obj->file)}}">
    </div>

    <div class="col-md-6">
        <label for="responsible" class="form-label">{{ __("finance::finance.notes") }}</label>
        <textarea name="notes" id="notes" cols="15" rows="8" class="form-control">
            {{ $obj->notes }}
        </textarea>
    </div>





    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
