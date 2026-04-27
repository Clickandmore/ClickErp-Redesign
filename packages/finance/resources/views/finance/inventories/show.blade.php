<style>
    /* تنسيق الـ label */
    .label-cell {
        font-weight: bold;
        font-size: 16px; /* يمكنك تعديل الحجم هنا */
    }

    /* تنسيق المحتوى (الإجابة) */
    .content-cell {
        font-size: 14px; /* حجم الخط للإجابات يمكن تغييره حسب الحاجة */
    }

    /* تنسيق عام للجدول */
    .table td, .table th {
        padding: 8px;
        vertical-align: middle;
    }
</style>

<div class="container mt-4">
    <div class="row">
        <!-- معلومات الجرد الأساسية -->
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    {{ __("finance::finance.inventory_details") }}
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td class="label-cell">{{ __("finance::finance.code") }}</td>
                                <td class="content-cell">{{ $obj->code }}</td>
                                <td class="label-cell">{{ __("finance::finance.date") }}</td>
                                <td class="content-cell">{{ $obj->date }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">{{ __("finance::finance.stock") }}</td>
                                <td class="content-cell">{{ $obj->stock->name ?? '' }}</td>
                                <td class="label-cell">{{ __("finance::finance.responsible") }}</td>
                                <td class="content-cell">{{ $obj->responsible }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">{{ __("finance::finance.file") }}</td>
                                <td colspan="3" class="content-cell">
                                    @if($obj->file)
                                        <a href="{{ showFile($obj->file) }}" target="_blank" download>Download</a>
                                    @else
                                        {{ __("finance::finance.no_file") }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="label-cell">{{ __("finance::finance.notes") }}</td>
                                <td colspan="3" class="content-cell">{{ $obj->notes }}</td>
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
                    {{ __("finance::finance.inventory_items") }}
                </div>
                <div class="card-body">
                    <table id="myDataTable_no_filter" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th class="label-cell">{{ __("finance::finance.product_code") }}</th>
                                <th class="label-cell">{{ __("finance::finance.unit") }}</th>
                                <th class="label-cell">{{ __("finance::finance.current_amount") }}</th>
                                <th class="label-cell">{{ __("finance::finance.purchase_price") }}</th>
                                <th class="label-cell">{{ __("finance::finance.real_amount") }}</th>
                                <th class="label-cell">{{ __("finance::finance.difference_amount") }}</th>
                                <th class="label-cell">{{ __("finance::finance.difference_purchase_price") }}</th>
                                <th class="label-cell">{{ __("finance::finance.notes") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($obj->inventoryItems as $key => $inventoryItem)
                            <tr>
                                <td class="content-cell">{{ $inventoryItem->item->name ?? '' }}</td>
                                <td class="content-cell">{{ $inventoryItem->unit }}</td>
                                <td class="content-cell">{{ $inventoryItem->current_amount }}</td>
                                <td class="content-cell">{{ $inventoryItem->purchase_price }}</td>
                                <td class="content-cell">{{ $inventoryItem->real_amount }}</td>
                                <td class="content-cell">{{ $inventoryItem->difference_amount }}</td>
                                <td class="content-cell">{{ $inventoryItem->difference_purchase_price }}</td>
                                <td class="content-cell">{{ $inventoryItem->notes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center content-cell">{{ __("finance::finance.no_items_found") }}</td>
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
