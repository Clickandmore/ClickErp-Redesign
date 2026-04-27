<div class="container">
    <!-- Section: Transfer Details -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            {{ __("finance::finance.sarf-movement-details") }}
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Display the code -->
                <div class="col-md-4">
                    <label>{{ __('finance::finance.code') }}</label>
                    <p class="form-control-plaintext">{{ $movement->code }}</p>
                </div>

                <!-- Display the stock name -->
                <div class="col-md-4">
                    <label>{{ __('finance::finance.the_stock') }}</label>
                    <p class="form-control-plaintext">{{ @$movement->stock->name }}</p>
                </div>

                <!-- Display the date -->
                <div class="col-md-4">
                    <label>{{ __('finance::finance.date') }}</label>
                    <p class="form-control-plaintext">{{ $movement->date }}</p>
                </div>

                <!-- Display the notes -->
                <div class="col-md-12">
                    <label>{{ __('finance::finance.notes') }}</label>
                    <p class="form-control-plaintext">{{ $movement->note ?? 'لا يوجد' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Items Details -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            {{ __("finance::finance.items_details") }}
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>{{ __("finance::finance.code") }}</th>
                        <th>{{ __("finance::finance.items") }}</th>
                        <th>{{ __("finance::finance.unit") }}</th>
                        @if($stock_setting->show_price_in_additional_permit)
                            <th>{{ __("finance::finance.price") }}</th>
                        @endif
                        <th>{{ __("finance::finance.amount") }}</th>
                        @if($stock_setting->show_price_in_additional_permit)
                            <th>{{ __("finance::finance.total") }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                @foreach ($movement->details as $itemRow)
                    <tr>
                        <td>{{ $itemRow->item->code }}</td>
                        <td>{{ $itemRow->item->name }}</td>
                        <td>{{ $itemRow->item->purchaseUnit->title ?? '' }}</td>
                        @if($stock_setting->show_price_in_additional_permit)
                            <td>{{ number_format($itemRow->price, 2) }}</td>
                        @endif
                        <td>{{ abs($itemRow->amount) }}</td>
                        @if($stock_setting->show_price_in_additional_permit)
                            <td>{{ number_format($itemRow->total_price, 2) }}</td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer Section with Close Button -->
    <div class="col-12 modal-footer justify-content-center mt-4">
        {!! closeButton() !!}
    </div>
</div>
