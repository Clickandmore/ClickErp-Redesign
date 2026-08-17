<div class="container mt-4">
    <div class="row">
        <!-- معلومات الجرد الأساسية -->
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    {{ __("finance::finance.details") }}
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                        <tr>
                            <td class="label-cell">{{ __("finance::finance.code") }}</td>
                            <td class="content-cell">{{ $obj->code }}</td>
                            <td class="label-cell">{{ __("finance::finance.date") }}</td>
                            <td class="content-cell">{{ $obj->date }}</td>
                            <td class="label-cell">{{ __("finance::finance.supplier") }}</td>
                            <td class="content-cell">{{ $obj->suppiler->name ?? '' }}</td>
                        </tr>

                        </tbody>
                    </table>

                </div>
            </div>
        </div>

        <!-- جدول العناصر المرتبطة بالجرد -->
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    {{ __("finance::finance.items") }}
                </div>
                <div class="card-body">
                    <table id="myDataTable_no_filter" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th class="label-cell">{{ __("finance::finance.items") }}</th>
                                <th class="label-cell">{{ __("finance::finance.unit") }} </th>
                                <th class="label-cell">{{ __("finance::finance.amount") }} </th>
                                <th class="label-cell">{{ __("finance::finance.purchase_price") }} </th>
                                <th>{{ __("finance::finance.discount_per") }} </th>
                                <th>{{ __("finance::finance.Tax rate") }} </th>
                                <th class="label-cell">{{ __("finance::finance.total") }} </th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($obj->purchaseOrderItems as $key => $item)

                            <tr>
                                <td>{{ $item->item->name ?? '' }}</td>
                                <td>{{ @$item->item->saleUnit->title }}</td>
                                <td>{{ $item->amount }}</td>
                                <td>{{ $item->price }}</td>
                                <td>{{ $item->discount_per ? $item->discount_per . '%' : '0%' }}</td>
                                <td>{{ $item->tax_per ? $item->tax_per . '%' : '0%' }}</td>
                                <td>{{ $item->total_price }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">{{ __("finance::finance.no_items_found") }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- زر الإغلاق -->
        <div class="col-12 text-center">
            {!! closeButton() !!}
        </div>
    </div>
</div>
