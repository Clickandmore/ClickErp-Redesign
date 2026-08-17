<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')
    <div class="col-md-4">
        <label for="title" class="form-label">{{ __("finance::finance.from_stock") }}</label>
        <input type="hidden" name="stock_id" value="{{ $obj->to_stock_id }}" >
        <input type="hidden" name="note" value="لا يوجد" >
        <input type="hidden" name="code" value="{{ $obj->code }}" >
        <span class="form-control">{{ $obj->toStock->name }}</span>
    </div>
     <div class="col-md-4">
        <label for="title" class="form-label">{{ __("finance::finance.item") }}</label>
        <input type="hidden" name="item_id[0]" value="{{ $obj->item_id }}" >
        <input type="hidden" name="purchase_price[0]" value="{{ $obj->item->purchase_price }}" >
        <input type="hidden" name="price[0]" value="{{ $obj->item->purchase_price }}" >
        <input type="hidden" name="one_price[0]" value="{{ $obj->item->purchase_price }}" >
        <input type="hidden" name="total_price[0]" value="{{ $obj->item->purchase_price *$obj->amount }}" >
        <input type="hidden" name="total_item" value="{{ $obj->item->purchase_price }}" >
         <span class="form-control">{{ $obj->item->name }}</span>
    </div>

    <div class="col-md-4">
        <label for="title" class="form-label">{{ __("charities.amount") }}</label>
        <input type="number" readonly name="amount[0]" value="{{ $obj->amount }}" id="amount" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="to_stock_id" class="form-label">{{ __("finance::finance.add_to_stock") }}</label>
        <select name="to_stock_id" id="to_stock_id" class="form-control" data-validation="required">
            {!! showSelectElement($stocks, 'name', $obj->to_stock_id) !!}
        </select>
    </div>

    <div class="col-md-4">
        <label for="title" class="form-label">{{ __("charities.date") }}</label>
        <input type="date" name="date"  value="{{ date('Y-m-d') }}" id="date" class="form-control" data-validation="required">
    </div>
    <div id="items_list">
        <table id="myDataTable_no_filter mt-5" class="table align-middle card-table my-2">
            <thead>
            <tr>
                <th>{{ __("finance::finance.item_code") }}</th>
                <th>{{ __("finance::finance.the_stock") }}</th>
                <th>{{ __("finance::finance.amount") }}</th>
            </tr>
            </thead>
            <tbody id="json_array" class="content"
                   data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>

            @php
                $groupedDetails = $obj->details()
                    ->selectRaw('item_id, COUNT(*) as item_count, SUM(order_amount) as total_order_amount')
                    ->groupBy('item_id')
                    ->get();
                $key_stock = 0
            @endphp

            @foreach($groupedDetails as $key => $item_detail)
                @php
                    $itemStocks = $obj->details()->where('item_id', $item_detail->item_id)->get(); // جلب المخزون لكل عنصر
                    $firstRow = true; // متغير للتحقق من أول صف
                @endphp

                @foreach($itemStocks as $stock)
                    <tr class="y-group">
                        @if($firstRow)
                            <!-- عرض الكود والكمية المطلوبة مرة واحدة فقط في الصف الأول -->
                            <td style="min-width: 170px;" rowspan="{{ $item_detail->item_count }}">
                                <label class="form-label">{{ $stock->item->code.' - '. $stock->item->name }}</label>
                                <input type="hidden" value="{{ $item_detail->total_order_amount / $obj->amount }}" class="itemAmount">
                            </td>


                            @php
                                $firstRow = false; // لم يعد الصف الأول بعد الآن
                            @endphp
                        @endif

                        <!-- المخزون -->
                        <td style="max-width: 100px">
                            <input type="hidden" name="details[{{$key_stock}}][item_id]" value="{{ $stock->item_id }}">

                            <label class="form-label">{{ $stock->stock->name }}</label>
                            <input type="hidden" name="details[{{$key_stock}}][stock_id]" value="{{ $stock->stock_id }}">
                        </td>

                        <!-- الكمية المضافة -->
                        <td style="max-width: 100px">
                            <input readonly type="text" class="form-control addedAmount" name="details[{{$key_stock}}][order_amount]" value="{{ $stock->order_amount }}">
                        </td>
                    </tr>
                    @php

                        $key_stock++
                    @endphp
                @endforeach
            @endforeach
            </tbody>
        </table>

    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
