<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')

{{--    <div id="items_list" >--}}
{{--        <table id="myDataTable_no_filter" class="table align-middle card-table">--}}
{{--            <thead>--}}
{{--            <tr>--}}
{{--                <th></th>--}}
{{--                <th> {{__('finance::finance.product_code')}} </th>--}}
{{--                <th>{{__('finance::finance.units')}} </th>--}}
{{--                <th>{{__('finance::finance.current_balance')}} </th>--}}
{{--                <th>{{__('finance::finance.Amount')}} </th>--}}
{{--                <th>{{__('finance::finance.price')}} </th>--}}
{{--                <th>{{__('finance::finance.total')}} </th>--}}
{{--            </tr>--}}
{{--            </thead>--}}
{{--            <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>--}}

{{--            <tr class="y-group">--}}
{{--                <td> </td>--}}
{{--                <td>--}}
{{--                    <select class="form-control show-tick ms  index-plus item-select" name="items[0][item_id]" data-validation="required">--}}
{{--                        <option value="">{{ __("finance::finance.choose") }}</option>--}}
{{--                        @foreach ($items as $item)--}}
{{--                            @php--}}
{{--                                $total_current_amount = $item->itemStockStatus->sum('amount');--}}
{{--                            @endphp--}}
{{--                            <option value="{{ $item->id }}" {{$item->id == $obj->item_id ? 'selected' : ''}}--}}
{{--                                    data-purchaseUnit="{{ $item->purchaseUnit->title ?? '' }}"--}}
{{--                                    data-purchasePrice="{{ $item->purchase_price }}"--}}
{{--                                    data-currentAmount="{{ $total_current_amount }}">--}}
{{--                                {{ $item->code_string.'-'.$item->code.'/'.$item->name }}--}}
{{--                            </option>--}}
{{--                        @endforeach--}}
{{--                    </select>--}}
{{--                </td>--}}
{{--                <td>--}}
{{--                    <input type="text" readonly name="items[0][unit_id]" value="{{@$obj->item->saleUnit->title}}" class="form-control  index-plus unit-field" data-validation="required">--}}
{{--                </td>--}}
{{--                <td>--}}
{{--                    <input type="number" readonly name="items[0][current-amount]" value="{{$obj->item->itemStockStatus->sum('amount') ?? 0}}" class="form-control  index-plus current-amount-field" data-validation="required">--}}
{{--                </td>--}}

{{--                <td>--}}
{{--                    <input type="number" name="items[0][amount]" value="{{$obj->amount}}" class="form-control index-plus amount-field" data-validation="required" onkeyup="calculateDifference(this)">--}}
{{--                </td>--}}
{{--                <td>--}}
{{--                    <input type="number" name="items[0][price]" value="{{$obj->price}}" class="form-control index-plus purchase-price-field" data-validation="required">--}}
{{--                </td>--}}
{{--                <td>--}}
{{--                    <input type="number" readonly name="items[0][total_price]" value="{{$obj->total_price}}" class="form-control  index-plus total-purchase-price-field" data-validation="required">--}}
{{--                </td>--}}


{{--            </tr>--}}

{{--            </tbody>--}}
{{--        </table>--}}


{{--    </div>--}}
    <div class="alert alert-info text-center">
        {{__("finance::finance.to control of amounts go to sarf or add")}}
    </div>
    <div class="d-flex mb-3 justify-content-center">
        {!! addHyperButton(route('finance.stock-movement.create') , __("finance::finance.Additional Permit")) !!}
        {!! addHyperButton(route('finance.sarf-movement.create') , __("finance::finance.sarf Permit")) !!}
    </div>


    <div class="col-12 modal-footer">
{{--        {!! submitButton($updateRoute) !!}--}}
        {!! closeButton() !!}
    </div>

</form>


