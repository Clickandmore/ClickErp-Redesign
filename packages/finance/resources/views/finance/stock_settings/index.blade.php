@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">
        @csrf

        <div class="col-md-12">
            <label for="costCenter" class="form-label">{{ __("finance::finance.Do you want to show the price in the additional permit ?") }}:</label>
            <input type="radio" class="tax-rate-value" value="1"
                   name="show_price_in_additional_permit" {{ ($obj->show_price_in_additional_permit == 1) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.Yes") }}</span>
            <input type="radio" class="tax-rate-value" value="0"
                   name="show_price_in_additional_permit" {{ ($obj->show_price_in_additional_permit == 0) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.No") }}</span>
        </div>

        <div class="col-md-12">
            <label for="purchaseBill" class="form-label">{{ __("finance::finance.Do you want to create an additional permit with the purchase bill ?") }}:</label>
            <input type="radio" class="tax-rate-value" value="1"
                   name="create_additional_permit_with_purchase_bill" {{ ($obj->create_additional_permit_with_purchase_bill == 1) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.Yes") }}</span>
            <input type="radio" class="tax-rate-value" value="0"
                   name="create_additional_permit_with_purchase_bill" {{ ($obj->create_additional_permit_with_purchase_bill == 0) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.No") }}</span>
        </div>

        <div class="col-md-12">
            <label for="purchaseBill" class="form-label">{{ __("finance::finance.Do you want to create an additional permit with confirme purchase ?") }}:</label>
            <input type="radio" class="tax-rate-value" value="1"
                   name="create_additional_permit_with_confirmed_purchase_bill" {{ ($obj->create_additional_permit_with_confirmed_purchase_bill == 1) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.Yes") }}</span>
            <input type="radio" class="tax-rate-value" value="0"
                   name="create_additional_permit_with_confirmed_purchase_bill" {{ ($obj->create_additional_permit_with_confirmed_purchase_bill == 0) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.No") }}</span>
        </div>

        <div class="col-md-12">
            <label for="purchaseBill" class="form-label">{{ __("finance::finance.Do you want to create an additional permit for the returned purchase bill?") }}:</label>
            <input type="radio" class="tax-rate-value" value="1"
                   name="create_additional_permit_with_back_purchase_bill" {{ ($obj->create_additional_permit_with_back_purchase_bill == 1) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.Yes") }}</span>
            <input type="radio" class="tax-rate-value" value="0"
                   name="create_additional_permit_with_back_purchase_bill" {{ ($obj->create_additional_permit_with_back_purchase_bill == 0) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.No") }}</span>
        </div>

        <!-- More Than Additional Permit for Bill -->
        <div class="col-md-12">
            <label for="additionalPermit" class="form-label">{{ __("finance::finance.Do you want more than one additional permit for a bill ?") }}:</label>
            <input type="radio" class="tax-rate-value" value="1"
                   name="more_than_additional_permit_for_bill" {{ ($obj->more_than_additional_permit_for_bill == 1) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.Yes") }}</span>
            <input type="radio" class="tax-rate-value" value="0"
                   name="more_than_additional_permit_for_bill" {{ ($obj->more_than_additional_permit_for_bill == 0) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.No") }}</span>
        </div>

        <!-- Create Additional Permit with Return Bill -->
        <div class="col-md-12">
            <label for="returnBill" class="form-label">{{ __("finance::finance.Do you want to create an additional permit with the return bill ?") }}:</label>
            <input type="radio" class="tax-rate-value" value="1"
                   name="create_additional_permit_with_return_bill" {{ ($obj->create_additional_permit_with_return_bill == 1) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.Yes") }}</span>
            <input type="radio" class="tax-rate-value" value="0"
                   name="create_additional_permit_with_return_bill" {{ ($obj->create_additional_permit_with_return_bill == 0) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.No") }}</span>
        </div>

        <!-- Sync with ZATCA -->
        <div class="col-md-12">
            <label for="syncWithZatca" class="form-label">{{ __("finance::finance.Sync with ZATCA ?") }}:</label>
            <input type="radio" class="tax-rate-value" value="1"
                   name="sync_with_zatca" {{ ($obj->sync_with_zatca == 1) ? 'checked' : '' }}>
            <span class="yesOrNo">{{ __("finance::finance.Yes") }}</span>
            <input type="radio" class="tax-rate-value" value="0"
                   name="sync_with_zatca" {{ ($obj->sync_with_zatca == 0) ? 'checked' : '' }}>
            <span class="yesOrNo">{{ __("finance::finance.No") }}</span>
        </div>

        <!-- Multiple Stocks in Bill Sales -->
        <div class="col-md-12">
            <label for="multipleStocksInBillSales" class="form-label">{{ __("finance::finance.Multiple stocks in bill sales ?") }}:</label>
            <input type="radio" class="tax-rate-value" value="1"
                   name="multiple_stocks_in_bill_sales" {{ ($obj->multiple_stocks_in_bill_sales == 1) ? 'checked' : '' }}>
            <span class="yesOrNo">{{ __("finance::finance.Yes") }}</span>
            <input type="radio" class="tax-rate-value" value="0"
                   name="multiple_stocks_in_bill_sales" {{ ($obj->multiple_stocks_in_bill_sales == 0) ? 'checked' : '' }}>
            <span class="yesOrNo">{{ __("finance::finance.No") }}</span>
        </div>

        <!-- Multiple Stocks in Bill Purchase -->
        <div class="col-md-12">
            <label for="multipleStocksInBillPurchase" class="form-label">{{ __("finance::finance.Multiple stocks in bill purchase ?") }}:</label>
            <input type="radio" class="tax-rate-value" value="1"
                   name="multiple_stocks_in_bill_purchase" {{ ($obj->multiple_stocks_in_bill_purchase == 1) ? 'checked' : '' }}>
            <span class="yesOrNo">{{ __("finance::finance.Yes") }}</span>
            <input type="radio" class="tax-rate-value" value="0"
                   name="multiple_stocks_in_bill_purchase" {{ ($obj->multiple_stocks_in_bill_purchase == 0) ? 'checked' : '' }}>
            <span class="yesOrNo">{{ __("finance::finance.No") }}</span>
        </div>

        <!-- Auto Create Stock Movement with Bill Sale -->
        <div class="col-md-12">
            <label for="autoCreateStockMovement" class="form-label">{{ __("finance::finance.Auto create stock movement with bill sale ?") }}:</label>
            <input type="radio" class="tax-rate-value" value="1"
                   name="auto_create_stock_movement_with_bill_sale" {{ ($obj->auto_create_stock_movement_with_bill_sale == 1) ? 'checked' : '' }}>
            <span class="yesOrNo">{{ __("finance::finance.Yes") }}</span>
            <input type="radio" class="tax-rate-value" value="0"
                   name="auto_create_stock_movement_with_bill_sale" {{ ($obj->auto_create_stock_movement_with_bill_sale == 0) ? 'checked' : '' }}>
            <span class="yesOrNo">{{ __("finance::finance.No") }}</span>
        </div>

        <!-- show_purchase_price_in_first_stocks -->
        <div class="col-md-12">
            <label for="autoCreateStockMovement" class="form-label">{{ __("finance::finance.Show_purchase_price_in_first_stocks ?") }}:</label>
            <input type="radio" class="tax-rate-value" value="1"
                   name="show_purchase_price_in_first_stocks" {{ ($obj->show_purchase_price_in_first_stocks == 1) ? 'checked' : '' }}>
            <span class="yesOrNo">{{ __("finance::finance.Yes") }}</span>
            <input type="radio" class="tax-rate-value" value="0"
                   name="show_purchase_price_in_first_stocks" {{ ($obj->show_purchase_price_in_first_stocks == 0) ? 'checked' : '' }}>
            <span class="yesOrNo">{{ __("finance::finance.No") }}</span>
        </div>

        <!-- More Than Additional Permit for Bill -->
        <div class="col-md-12">
            <label for="showPriceInConversions" class="form-label">{{ __("finance::finance.Do you want the price to appear in the conversion between the two?") }}:</label>
            <input type="radio" class="tax-rate-value" value="1"
                   name="show_price_in_conversions" {{ ($obj->show_price_in_conversions == 1) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.Yes") }}</span>
            <input type="radio" class="tax-rate-value" value="0"
                   name="show_price_in_conversions" {{ ($obj->show_price_in_conversions == 0) ? 'checked' : '' }}> <span class="yesOrNo"> {{ __("finance::finance.No") }}</span>
        </div>

        <!-- Modal footer with buttons -->
        <div class="col-12 modal-footer">
            {!! submitButton( $storeRoute ) !!}
        </div>
    </form>

@endsection
@section('js')
    <script>
        $('.select2').select2({
            dropdownParent: $('#upCreateModal .modal-body'),
        });
        $('.select2').select2();
    </script>
@endsection
