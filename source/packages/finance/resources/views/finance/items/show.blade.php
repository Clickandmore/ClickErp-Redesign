@extends('click.layout.indexs.index')
@section('style')
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')

    <div class="container my-4">
        <!-- Item Details Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4>{{ __('finance::finance.item_details') }}</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>{{ __('finance::finance.name') }}:</strong>
                        <p>{{ $obj->name }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('finance::finance.type') }}:</strong>
                        <p>{{ $obj->type->lang() }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('finance::finance.main_categories') }}:</strong>
                        <p>{{ optional($obj->mainCategory)->title }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('finance::finance.sub_categories') }}:</strong>
                        <p>{{ optional($obj->subCategory)->title }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('finance::finance.code') }}:</strong>
                        <p>{{ $obj->code_string }}{{ $obj->code }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('finance::finance.sale_account') }}:</strong>
                        <p>{{ optional($obj->saleAccount)->name }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('finance::finance.purchase price or cost') }}:</strong>
                        <p>{{ number_format($obj->purchase_price, 2) }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('finance::finance.sale price') }}:</strong>
                        <p>{{ number_format($obj->sale_price, 2) }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('finance::finance.Is there a tax?') }}:</strong>
                        <p>{{ $obj->is_tax ? __('finance::finance.Yes') : __('finance::finance.No') }}</p>
                    </div>

                    @if ($obj->is_tax)
                        <div class="col-md-4">
                            <strong>{{ __('finance::finance.Tax Account') }}:</strong>
                            <p>{{ optional($obj->itemTax)->tax_account_id }}</p>
                        </div>
                        <div class="col-md-4">
                            <strong>{{ __('finance::finance.Tax rate') }}:</strong>
                            <p>{{ number_format(optional($obj->itemTax)->tax_rate, 2) }}%</p>
                        </div>
                        <div class="col-md-4">
                            <strong>{{ __('finance::finance.Tax amount') }}:</strong>
                            <p>{{ number_format(optional($obj->itemTax)->tax_amount, 2) }}</p>
                        </div>
                    @endif

                    @if ($obj->min_order > 0)
                        <div class="col-md-4">
                            <strong>{{ __('finance::finance.min_order') }}:</strong>
                            <p>{{ $obj->min_order }}</p>
                        </div>
                    @endif

                    @if (optional($obj->supplier)->name)
                        <div class="col-md-4">
                            <strong>{{ __('finance::finance.suppliers') }}:</strong>
                            <p>{{ optional($obj->supplier)->name }}</p>
                        </div>
                    @endif

                    @if (optional($obj->saleUnit)->title)
                        <div class="col-md-4">
                            <strong>{{ __('finance::finance.sale_unit') }}:</strong>
                            <p>{{ optional($obj->saleUnit)->title }}</p>
                        </div>
                    @endif

                    @if (optional($obj->purchaseUnit)->title)
                        <div class="col-md-4">
                            <strong>{{ __('finance::finance.purchase_unit') }}:</strong>
                            <p>{{ optional($obj->purchaseUnit)->title }}</p>
                        </div>
                    @endif

                    @if (optional($obj->stockAccount)->name)
                        <div class="col-md-4">
                            <strong>{{ __('finance::finance.stock_account') }}:</strong>
                            <p>{{ optional($obj->stockAccount)->name }}</p>
                        </div>
                    @endif

                    @if ($obj->stock_tracking)
                        <div class="col-md-4">
                            <strong>{{ __('finance::finance.stock_tracking') }}:</strong>
                            <p>{{ $obj->stock_tracking ? __('finance::finance.Yes') : __('finance::finance.No') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Description Section -->
        @if (!empty($obj->details))
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h4>{{ __('finance::finance.description') }}</h4>
                </div>
                <div class="card-body">
                    <p>{{ $obj->details }}</p>
                </div>
            </div>
        @endif

        <!-- Image Section -->
        @if (!empty($obj->image))
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h4>{{ __('finance::finance.image') }}</h4>
                </div>
                <div class="card-body text-center">
                    <img src="{{ showFile($obj->image) }}" alt="{{ $obj->name }}" class="img-fluid img-thumbnail"
                        style="max-width: 300px; height: auto;">
                </div>
            </div>
        @endif

        <!-- Component Items Section -->
        @if ($obj->itemComponents->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h4>{{ __('finance::finance.component_items') }}</h4>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>{{ __('finance::finance.component_item') }}</th>
                                <th>{{ __('finance::finance.amount') }}</th>
                                <th>{{ __('finance::finance.one_purchase_price') }}</th>
                                <th>{{ __('finance::finance.total_purchase_price') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($obj->itemComponents as $component)
                                @if ($component->amount > 0 && $component->one_purchase_price > 0 && $component->total_purchase_price > 0)
                                    <tr>
                                        <td>{{ optional($component->componentItem)->name }}</td>
                                        <td>{{ $component->amount }}</td>
                                        <td>{{ number_format($component->one_purchase_price, 2) }}</td>
                                        <td>{{ number_format($component->total_purchase_price, 2) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

@endsection
@section('js')
@endsection
