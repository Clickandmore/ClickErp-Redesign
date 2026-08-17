<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')
    <div class="col-md-4">
        <label for="code" class="form-label">{{ __("finance::finance.code") }}</label>
        <input type="text" name="code" id="code" value="{{ $obj->code }}" class="form-control" readonly data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="date" class="form-label">{{ __("finance::finance.date") }}</label>
        <input type="date" name="date" id="date" value="{{ $obj->date }}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="client_id" class="form-label">{{ __("finance::finance.clients") }}</label>
        <select name="client_id" id="client_id" class="form-control select2" data-validation="required">
            {!! showSelectElement($clients, 'name',$obj->client_id) !!}
        </select>
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

    <table id="myDataTable_no_filter" class="table align-middle card-table my-2">
        <thead>
            <tr>
                <th>{{ __("finance::finance.product_code") }}</th>
                <th>{{ __("finance::finance.unit") }} </th>
                <th>{{ __("finance::finance.available_amount") }} </th>
                <th>{{ __("finance::finance.sales_price") }} </th>
                <th>{{ __("finance::finance.sales_amount") }} </th>
                <th>{{ __("finance::finance.discount_per") }} </th>
                <th>{{ __("finance::finance.Tax rate") }} </th>
                <th>{{ __("finance::finance.final_total") }} </th>
                <th>{{ __("finance::finance.actions") }}</th>
            </tr>
        </thead>
        <tbody id="json_array" class="content"
               data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
            @forelse($obj->salesOrderItems as $key => $item)
                <tr class="y-group">
                    <td>
                        <select name="items[{{ $key }}][item_id]" id="item_id_{{ $key }}" class="form-control index-plus item-select select2">
                            <option value="">{{ __("finance::finance.choose") }}</option>
                            @foreach ($items as $availableItem)
                                @php
                                    $total_current_amount = $availableItem->itemStockStatus->sum('amount');
                                @endphp
                                <option value="{{ $availableItem->id }}"
                                        data-saleUnit="{{ $availableItem->saleUnit->title ?? '' }}"
                                        data-salePrice="{{ $availableItem->sale_price }}"
                                        data-availableAmount="{{ $total_current_amount }}"
                                        {{ $availableItem->id == $item->item_id ? 'selected' : '' }}>
                                    {{ $availableItem->name.'-'.$availableItem->code_string.'/'.$availableItem->code }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" name="items[{{ $key }}][unit]" value="{{ $item->item->saleUnit->title ?? '' }}" readonly class="form-control index-plus unit-field">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[{{ $key }}][available_amount]" value="{{ $item->item->itemStockStatus->sum('amount') }}" readonly class="form-control index-plus available-amount-field">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[{{ $key }}][sales_price]" value="{{ $item->sales_price }}" class="form-control index-plus sale-price-field" onkeyup="calculateTotal(this)">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[{{ $key }}][sales_amount]" value="{{ $item->sales_amount }}" class="form-control index-plus sale-amount-field" onkeyup="calculateTotal(this)">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[{{ $key }}][discount_per]" value="{{ $item->discount_per }}" class="form-control index-plus discount-per-field" onkeyup="calculateTotal(this)">
                        <input type="hidden" step="0.01" name="items[{{ $key }}][discount_value]" value="{{ $item->discount_value }}" class="form-control index-plus discount-value-field">
                    </td>
                    <!-- إضافة حقل الضريبة لكل عنصر -->
                    <td>
                        <input type="number" step="0.01" name="items[{{ $key }}][tax_per]" value="{{ $item->tax_per }}" class="form-control index-plus tax-per-field" onkeyup="calculateTotal(this)">
                        <input type="hidden" step="0.01" readonly name="items[{{ $key }}][tax_value]" value="{{ $item->tax_value }}" class="form-control index-plus tax-value-field">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[{{ $key }}][final_total]" value="{{ $item->final_total }}" readonly class="form-control index-plus final-total-field">
                        <input type="hidden" step="0.01" name="items[{{ $key }}][total_sales_price]" value="{{ $item->total_sales_price }}" readonly class="form-control index-plus total-sales-price-field">
                    </td>
                    <td>
                        <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                        <i class="fa fa-trash-o btnRemove"></i>
                    </td>
                </tr>
            @empty
                <tr class="y-group">
                    <td>
                        <select name="items[0][item_id]" id="item_id" class="form-control index-plus item-select select2"
                               >
                            <option value="">{{ __("finance::finance.choose") }}</option>
                            @foreach ($items as $item)
                                @php
                                    $total_current_amount = $item->itemStockStatus->sum('amount');
                                @endphp
                                <option value="{{ $item->id }}"
                                        data-saleUnit="{{ $item->saleUnit->title ?? '' }}"
                                        data-salePrice="{{ $item->sale_price }}"
                                        data-availableAmount="{{ $total_current_amount }}">
                                    {{ $item->name.'-'.$item->code_string.'/'.$item->code }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" name="items[0][unit]" readonly class="form-control index-plus unit-field" >
                    </td>
                    <td>
                        <input type="number" step="0.01" readonly name="items[0][available_amount]" class="form-control index-plus available-amount-field">
                    </td>
                    <td>
                        <input type="number" step="0.01" readonly name="items[0][sales_price]" class="form-control index-plus sale-price-field">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[0][sales_amount]" class="form-control index-plus sale-amount-field" onkeyup="calculateTotal(this)">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[0][discount_per]" class="form-control index-plus discount-per-field" onkeyup="calculateTotal(this)">
                        <input type="hidden" step="0.01" readonly name="items[0][discount_value]" class="form-control index-plus discount-value-field">
                    </td>
                    <!-- إضافة حقل الضريبة لكل عنصر إضافي -->
                    <td>
                        <input type="number" step="0.01" name="addition_items[0][tax_per]" class="form-control index-plus tax-per-field-additional" onkeyup="calculateRowAdd(this)" value="0">
                        <input type="hidden" step="0.01" readonly name="addition_items[0][tax_value]" class="form-control index-plus tax-value-field-additional" value="0">
                    </td>
                    <td>
                        <input type="number" step="0.01" readonly name="items[0][final_total]" class="form-control index-plus final-total-field">
                        <input type="hidden" step="0.01" readonly name="items[0][total_sales_price]" class="form-control index-plus total-sales-price-field">
                    </td>
                    <td>
                        <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                        <i class="fa fa-trash-o btnRemove"></i>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="col-md-3">
        <label for="number" class="form-label">{{ __("finance::finance.final_all_total") }}</label>
        <input type="number" readonly name="final_total" class="form-control" value="{{ $obj->final_total }}"> <!-- مجموع الاجمالي كله (final_total) -->
        <input type="hidden" readonly name="total_price" class="form-control" value="{{ $obj->total_price }}"> <!-- مجموع (sales_amount * sales_price) -->
        <input type="hidden" readonly name="total_discount_value" class="form-control" value="{{ $obj->total_discount_value }}"> <!-- مجموع (sales_amount * sales_price - discount_value) -->
    </div>

    <table id="myDataTable_no_filter" class="table align-middle card-table my-2">
        <thead>
            <tr>
                <th>{{ __("finance::finance.name") }}</th>
                <th>{{ __("finance::finance.code") }} </th>
                <th>{{ __("finance::finance.unit") }} </th>
                <th>{{ __("finance::finance.amount") }} </th>
                <th>{{ __("finance::finance.price") }} </th>
                <th>{{ __("finance::finance.discount_per") }} </th>
                <th>{{ __("finance::finance.Tax rate") }} </th>
                <th>{{ __("finance::finance.final_total") }} </th>
                <th>{{ __("finance::finance.actions") }}</th>
            </tr>
        </thead>
        <tbody id="json_array1" class="content"
               data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
                @if ($obj->additionItems->count() > 0)
                    @foreach ($obj->additionItems as $index => $addition)
                        <tr class="y-group">
                            <td>
                                <input type="text" name="addition_items[{{ $index }}][name]" value="{{ $addition->name }}" class="form-control index-plus" >
                            </td>
                            <td>
                                <input type="text" name="addition_items[{{ $index }}][code]" value="{{ $addition->code }}" class="form-control index-plus" >
                            </td>
                            <td>
                                <input type="text" name="addition_items[{{ $index }}][unit]" value="{{ $addition->unit }}" class="form-control index-plus" >
                            </td>
                            <td>
                                <input type="number" step="0.01" name="addition_items[{{ $index }}][amount]" value="{{ $addition->amount }}" class="form-control index-plus" onKeyup="calculateRow(this)">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="addition_items[{{ $index }}][price]" value="{{ $addition->price }}" class="form-control index-plus" onKeyup="calculateRow(this)">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="addition_items[{{ $index }}][discount_per]" value="{{ $addition->discount_per }}" class="form-control index-plus" onKeyup="calculateRow(this)">
                                <input type="hidden" name="addition_items[{{ $index }}][discount_value]" value="{{ $addition->discount_value }}" class="form-control index-plus">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="addition_items[{{ $index }}][tax_per]" value="{{ $addition->tax_per }}" class="form-control index-plus" onKeyup="calculateRow(this)">
                                <input type="hidden" name="addition_items[{{ $index }}][tax_value]" value="{{ $addition->tax_value }}" class="form-control index-plus">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="addition_items[{{ $index }}][final_price]" value="{{ $addition->final_price }}" class="form-control index-plus" readonly>
                            </td>
                            <td>
                                <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                                <i class="fa fa-trash-o btnRemove"></i>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr class="y-group">
                        <td>
                            <input type="text" name="addition_items[0][name]" class="form-control index-plus" >
                        </td>
                        <td>
                            <input type="text" name="addition_items[0][code]" class="form-control index-plus" >
                        </td>
                        <td>
                            <input type="text" name="addition_items[0][unit]" class="form-control index-plus" >
                        </td>
                        <td>
                            <input type="number" step="0.01" name="addition_items[0][amount]" class="form-control index-plus" onKeyup="calculateRow(this)">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="addition_items[0][price]" class="form-control index-plus" onKeyup="calculateRow(this)">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="addition_items[0][discount_per]" class="form-control index-plus" onKeyup="calculateRow(this)">
                            <input type="hidden" name="addition_items[0][discount_value]" class="form-control index-plus">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="addition_items[0][tax_per]" class="form-control index-plus" onKeyup="calculateRow(this)">
                            <input type="hidden" name="addition_items[0][tax_value]" class="form-control index-plus">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="addition_items[0][final_price]" class="form-control index-plus" readonly>
                        </td>
                        <td>
                            <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                            <i class="fa fa-trash-o btnRemove"></i>
                        </td>
                    </tr>
                @endif


        </tbody>
    </table>

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
<script>
    $(document).on('click', '#btnAdd-2', function () {
        setTimeout(function () {
            // // Remove existing select2 containers
            $('#json_array').find('.select2-container').remove();
            // Initialize the last select2 element after adding new fields
            $('#json_array .select2').each(function () {
                $(this).select2({
                    dropdownParent: $('#myDataTable_no_filter')
                });
            });

        }, 100);
    });
</script>
