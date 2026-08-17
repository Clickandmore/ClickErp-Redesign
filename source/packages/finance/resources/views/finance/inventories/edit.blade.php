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
        <label for="stock_form_id" class="form-label">{{ __("finance::finance.stock") }}</label>
        {!! addUpButton(route('finance.stocks.create'),'stock_form_id','' ,'name',false) !!}
        <select name="stock_id" id="stock_form_id" class="form-control" data-validation="required">
            {!! showSelectElement($stocks, 'name', $obj->stock_id) !!}
        </select>
    </div>

    <div class="col-md-3">
        <label for="responsible" class="form-label">{{ __("finance::finance.responsible") }}</label>
        <input type="text" name="responsible" id="responsible" value="{{ $obj->responsible }}" class="form-control" data-validation="required">
    </div>

    <table id="myDataTable_no_filter" class="table align-middle card-table my-2">
        <thead>
        <tr>
            <th>{{ __("finance::finance.product_code") }}</th>
            <th>{{ __("finance::finance.unit") }} </th>
            <th>{{ __("finance::finance.current_amount") }} </th>
            <th>{{ __("finance::finance.purchase_price") }} </th>
            <th>{{ __("finance::finance.real_amount") }} </th>
            <th>{{ __("finance::finance.difference_amount") }} </th>
            <th>{{ __("finance::finance.difference_purchase_price") }} </th>
            <th>{{ __("finance::finance.notes") }} </th>
            <th>{{ __("finance::finance.actions") }}</th>
        </tr>
        </thead>
        <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>

        @forelse ($obj->inventoryItems as $key => $inventoryItem)
            <tr class="y-group">
                <td>
                    <select name="items[{{ $key }}][item_id]" id="item_id" class="form-control index-plus item-select" data-validation="required">
                        <option value="">{{ __("finance::finance.choose") }}</option>
                        @foreach ($items as $item)
                            @php
                                $total_current_amount = $item->itemStockStatus->sum('amount');
                            @endphp
                            <option value="{{ $item->id }}"
                                    data-purchaseUnit="{{ $item->purchaseUnit->title ?? '' }}"
                                    data-purchasePrice="{{ $item->purchase_price }}"
                                    data-currentAmount="{{ $total_current_amount }}"
                                    {{ $item->id == $inventoryItem->item_id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" name="items[{{ $key }}][unit]" readonly class="form-control index-plus unit-field" value="{{ $inventoryItem->unit }}" data-validation="required">
                </td>
                <td>
                    <input type="number" step="0.01" readonly name="items[{{ $key }}][current_amount]" class="form-control index-plus current-amount-field" value="{{ $inventoryItem->current_amount }}" data-validation="required">
                </td>
                <td>
                    <input type="number" step="0.01" readonly name="items[{{ $key }}][purchase_price]" class="form-control index-plus purchase-price-field" value="{{ $inventoryItem->purchase_price }}" data-validation="required">
                </td>
                <td>
                    <input type="number" step="0.01" name="items[{{ $key }}][real_amount]" class="form-control index-plus real-amount-field" value="{{ $inventoryItem->real_amount }}" data-validation="required" onkeyup="calculateDifference(this)">
                </td>
                <td>
                    <input type="number" step="0.01" readonly name="items[{{ $key }}][difference_amount]" class="form-control index-plus difference-amount-field" value="{{ $inventoryItem->difference_amount }}" data-validation="required">
                </td>
                <td>
                    <input type="number" step="0.01" readonly name="items[{{ $key }}][difference_purchase_price]" class="form-control index-plus difference-purchase-price-field" value="{{ $inventoryItem->difference_purchase_price }}" data-validation="required">
                </td>
                <td>
                    <textarea name="items[{{ $key }}][notes]" id="notes" class="form-control index-plus" cols="15" rows="4">{{ $inventoryItem->notes }}</textarea>
                </td>
                <td>
                    <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                    <i class="fa fa-trash-o btnRemove"></i>
                </td>
            </tr>
        @empty
            <tr class="y-group">
                <td>
                    <select name="items[0][item_id]" id="item_id" class="form-control index-plus item-select" data-validation="required">
                        <option value="">{{ __("finance::finance.choose") }}</option>
                        @foreach ($items as $item)
                            @php
                                $total_current_amount = $item->itemStockStatus->sum('amount');
                            @endphp
                            <option value="{{ $item->id }}"
                                    data-purchaseUnit="{{ $item->purchaseUnit->title ?? '' }}"
                                    data-purchasePrice="{{ $item->purchase_price }}"
                                    data-currentAmount="{{ $total_current_amount }}">
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" name="items[0][unit]" readonly class="form-control index-plus unit-field" data-validation="required">
                </td>
                <td>
                    <input type="number" step="0.01" readonly name="items[0][current_amount]" class="form-control index-plus current-amount-field" data-validation="required">
                </td>
                <td>
                    <input type="number" step="0.01" readonly name="items[0][purchase_price]" class="form-control index-plus purchase-price-field" data-validation="required">
                </td>
                <td>
                    <input type="number" step="0.01" name="items[0][real_amount]" class="form-control index-plus real-amount-field" data-validation="required" onkeyup="calculateDifference(this)">
                </td>
                <td>
                    <input type="number" step="0.01" readonly name="items[0][difference_amount]" class="form-control index-plus difference-amount-field" data-validation="required">
                </td>
                <td>
                    <input type="number" step="0.01" readonly name="items[0][difference_purchase_price]" class="form-control index-plus difference-purchase-price-field" data-validation="required">
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
