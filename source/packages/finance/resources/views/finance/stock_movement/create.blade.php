@extends('click.layout.indexs.index')
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <form class="row g-2 from-submit-global" method="post" action="{{ $storeRoute }}">
        @if (isset($items) && count($items))
            <div class="col-md-4 col-12">
                <label>{{ __('finance::finance.code') }}</label>
                <input type="text" class="form-control" name="code" value="{{ $lastCode }}" disabled>
                <input type="hidden" name="code" value="{{ $lastCode }}">
            </div>

            <div class="col-md-4 col-12">
                <label>
                    {{ __('finance::finance.the_stock') }}
                    {!! addUpButton(route('finance.stocks.create'), 'stockk_id', '', 'name', true) !!}
                </label>
                <select class="form-control select2" name="stock_id" id="stockk_id" data-validation="required">
                    {!! showSelectElement($stocks, 'name') !!}
                </select>
            </div>

            <div class="col-md-4 col-12">
                <label>{{ __('finance.date') }}</label>
                <input type="date" name="date" data-validation="required" class="form-control"
                    value="{{ date('Y-m-d') }}" placeholder="{{ __('finance::finance.date') }}">
            </div>

            <div class="col-md-12 col-12">
                <label>{{ __('finance::finance.notes') }}</label>
                <textarea name="note" class="form-control form-control-lg mb-1" placeholder="{{ __('finance::finance.notes') }}"></textarea>
            </div>
            <table id="myDataTable_no_filter" class="table align-middle card-table my-2">
                <thead>
                    <tr>
                        <th></th>
                        {{--                <th>{{ __("finance::finance.code") }}</th> --}}
                        <th>{{ __('finance::finance.items') }}</th>
                        <th>{{ __('finance::finance.unit') }}</th>

                        @if ($stock_setting->show_price_in_additional_permit)
                            <th>{{ __('finance::finance.price') }}</th>
                        @endif

                        <th>{{ __('finance::finance.amount') }}</th>

                        @if ($stock_setting->show_price_in_additional_permit)
                            <th>{{ __('finance::finance.total') }}</th>
                        @endif

                        <th>{{ __('finance::finance.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="json_array" class="content"
                    data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
                    <tr class="y-group">
                        <td></td>
                        {{--                <td> --}}
                        {{--                    <input type="number" class="form-control codeSearch" readonly> --}}
                        {{--                </td> --}}
                        <td>
                            <select name="item_id[]" id="item_id" data-validation="required" class="form-control select2">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                                        data-purchase-price="{{ $item->purchase_price }}"
                                        data-unit="{{ @$item->purchaseUnit->title }}">
                                        {{ $item->code_string . $item->code . ' | ' . $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td>
                            <input type="text" class="form-control itemUnit" readonly>
                        </td>

                        @if ($stock_setting->show_price_in_additional_permit)
                            <td>
                                <input type="text" name="one_price[]" class="form-control itemPurchasePrice" readonly>
                            </td>
                        @else
                            <input type="hidden" name="one_price[]" class="form-control itemPurchasePrice" readonly>
                        @endif

                        <td>
                            <input type="number" name="amount[]" data-validation="required" min="0"
                                class="form-control plus-index total-price-input">
                        </td>

                        @if ($stock_setting->show_price_in_additional_permit)
                            <td>
                                <input type="number" name="price[]" class="form-control totalItemPrice" readonly>
                            </td>
                        @else
                            <!-- Hidden input for totalItemPrice when price is not shown -->
                            <input type="hidden" name="price[]" class="totalItemPrice">
                        @endif

                        <td>
                            <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                            <i class="fa fa-trash-o btnRemove"></i>
                        </td>
                    </tr>
                </tbody>
            </table>
        @else
            <div class="text-center">
                <p class="text-danger fs-5 d-inline-flex">{{ __('finance::finance.no items available') }}</p>
                <a class="d-inline-flex btn btn-primary" href="{{ route('finance.items.index') }}">اضف الان</a>
            </div>
        @endif

        <div class="col-12 modal-footer">
            {!! submitButton($storeRoute) !!}
        </div>
    </form>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            $('#json_array').multifield();

            select2AfterPlus()

            // Function to calculate and update the total price
            function updateTotalPrice(row) {
                let amount = parseFloat(row.find('input[name="amount[]"]').val()) || 0;
                let price = parseFloat(row.find('.itemPurchasePrice').val()) || 0;
                let totalPrice = amount * price;

                // Update totalItemPrice whether it's hidden or visible
                row.find('.totalItemPrice').val(totalPrice.toFixed(2)); // Set the total price with 2 decimal places
            }

            // When an item is selected, update the code input and price fields
            $('#json_array').on('change', 'select[name="item_id[]"]', function() {
                let selectedOption = $(this).find('option:selected');
                let itemCode = selectedOption.data('code');
                let itemUnit = selectedOption.data('unit');
                let itemPurchasePrice = selectedOption.data('purchase-price');

                // Find the corresponding row
                let row = $(this).closest('tr');

                // Update the code, unit, and price fields
                row.find('.codeSearch').val(itemCode);
                row.find('.itemUnit').val(itemUnit);
                row.find('.itemPurchasePrice').val(itemPurchasePrice);

                // Calculate the total price
                updateTotalPrice(row);
            });

            // When the amount is changed, calculate the total price
            $('#json_array').on('input', 'input[name="amount[]"]', function() {
                let row = $(this).closest('tr');
                // Calculate the total price
                updateTotalPrice(row);
            });
        });
    </script>
@endsection
