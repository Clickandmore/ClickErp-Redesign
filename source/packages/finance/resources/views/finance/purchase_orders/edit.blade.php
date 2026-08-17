<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')

    <div class="col-md-4">
        <label for="title" class="form-label">{{ __("finance::finance.order_purchase_number") }}</label>
        <input type="text" readonly value="{{$obj->code}}" name="code" id="code" class="form-control" data-validation="required">
    </div>
    <div class="col-md-4">
        <label for="title" class="form-label">{{ __("charities.date") }}</label>
        <input type="date" value="{{ $obj->date }}" name="date" id="date" class="form-control">
    </div>
    <div class="col-md-4">
        <label for="title" class="form-label">{{ __("finance::finance.supplier") }}</label>
        <select name="supplier_id" id="supplier_id" class="form-select select2" data-validation="required">
            {!! showSelectElement($suppliers,'name',$obj->supplier_id) !!}
        </select>
    </div>


    <div class="col-md-4">
        <label for="title" class="form-label">{{ __("charities.code") }}</label>
        <input type="text" readonly value="{{$obj->code}}" name="code" id="code" class="form-control" data-validation="required">
    </div>

    <div id="items_list" >
        <table id="myDataTable_no_filter" class="table align-middle card-table">
            <thead>
            <tr>
                <th style=" width: 20%"> {{__('finance::finance.product_code')}} </th>
                <th >{{__('finance::finance.units')}} </th>
                <th>{{__('finance::finance.current_balance')}} </th>
                <th>{{__('finance::finance.amount')}} </th>
                <th>{{__('finance::finance.price')}} </th>
                <th>{{ __("finance::finance.discount_per") }} </th>
                <th>{{ __("finance::finance.Tax rate") }} </th>
                <th>{{__('finance::finance.total')}} </th>
                <th>{{__('finance::finance.actions')}} </th>
            </tr>
            </thead>
            <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
            @forelse ($obj->purchaseOrderItems as $key => $item_detail)
                <tr class="y-group">
                    <td>
                        <select class="form-control show-tick ms  index-plus item-select select2" name="items[{{$key}}][item_id]" data-validation="required">
                            <option value="">{{ __("finance::finance.choose") }}</option>
                            @foreach ($items as $item)
                                @php
                                    $total_current_amount = $item->itemStockStatus->sum('amount');
                                @endphp
                                <option value="{{ $item->id }}" {{$item->id == $item_detail->item_id ? 'selected' : ''}}
                                        data-purchaseUnit="{{ $item->purchaseUnit->title ?? '' }}"
                                        data-purchasePrice="{{ $item->purchase_price }}"
                                        data-currentAmount="{{ $total_current_amount }}">
                                    {{ $item->name.'-'.$item->code_string.$item->code }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" readonly name="items[{{$key}}][unit_id]" value="{{@$item_detail->item->saleUnit->title}}" class="form-control  index-plus unit-field">
                    </td>
                    <td>
                        <input type="number" readonly name="items[{{$key}}][current-amount]" value="{{$item_detail->item->itemStockStatus->sum('amount') ?? 0}}" class="form-control  index-plus current-amount-field" data-validation="required">
                    </td>
                    <td>
                        <input type="number" name="items[{{$key}}][amount]" value="{{$item_detail->amount}}" class="form-control index-plus amount-field" data-validation="required" onkeyup="calculateDifference(this)">
                    </td>
                    <td>
                        <input type="text" name="items[{{$key}}][price]" value="{{$item_detail->price}}" class="form-control index-plus purchase-price-field" data-validation="required,number" data-validation-allowing="float">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[{{$key}}][discount_per]" class="form-control index-plus discount-per-field"  onkeyup="calculateDifference(this)" value="{{$item_detail->discount_per}}" data-validation="required">
                        <input type="hidden" step="0.01" readonly name="items[{{$key}}][discount_value]" class="form-control index-plus discount-value-field"  value="{{$item_detail->discount_value}}">
                    </td>
                    <!-- إضافة حقل الضريبة لكل عنصر -->
                    <td>
                        <input type="number" step="0.01" name="items[{{$key}}][tax_per]" class="form-control index-plus tax-per-field" onkeyup="calculateDifference(this)" value="{{$item_detail->tax_per}}" data-validation="required">
                        <input type="hidden" step="0.01" readonly name="items[{{$key}}][tax_value]" class="form-control index-plus tax-value-field" value="{{$item_detail->tax_value}}">
                    </td>
                    <td>
                        <input step="0.01" type="number" name="items[{{$key}}][total_price]" value="{{$item_detail->total_price}}" class="form-control  index-plus total-purchase-price-field" data-validation="required">
                    </td>

                    <td>
                        <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                        <i class="fa fa-trash-o btnRemove"></i>
                    </td>
                </tr>
            @empty
                <tr class="y-group">
                    <td> </td>
                    <td>
                        <select class="form-control show-tick ms  index-plus item-select select2" name="items[0][item_id]" data-validation="required">
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
                        <input type="text" readonly name="items[0][unit_id]" class="form-control  index-plus unit-field" data-validation="required">
                    </td>
                    <td>
                        <input type="number" readonly name="items[0][current-amount]" class="form-control  index-plus current-amount-field" data-validation="required">
                    </td>

                    <td>
                        <input type="number" name="items[0][amount]" class="form-control index-plus amount-field" data-validation="required" onkeyup="calculateDifference(this)">
                    </td>
                    <td>
                        <input type="number" name="items[0][price]" class="form-control index-plus purchase-price-field" data-validation="required,number" data-validation-allowing="float">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[0][discount_per]" class="form-control index-plus discount-per-field"  onkeyup="calculateDifference(this)" value="0" data-validation="required">
                        <input type="hidden" step="0.01" readonly name="items[0][discount_value]" class="form-control index-plus discount-value-field"  value="0">
                    </td>
                    <!-- إضافة حقل الضريبة لكل عنصر -->
                    <td>
                        <input type="number" step="0.01" name="items[0][tax_per]" class="form-control index-plus tax-per-field" onkeyup="calculateDifference(this)" value="0" data-validation="required">
                        <input type="hidden" step="0.01" readonly name="items[0][tax_value]" class="form-control index-plus tax-value-field" value="0">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[0][total_price]" class="form-control  index-plus total-purchase-price-field" data-validation="required">
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
