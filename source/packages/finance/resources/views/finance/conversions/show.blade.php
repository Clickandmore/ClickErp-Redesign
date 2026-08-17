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
</style>

<div class="container mt-4">
    <div class="row">
        <!-- معلومات الجرد الأساسية -->
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    {{ __("finance::finance.conversion_details") }}
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
                                <td class="label-cell">{{ __("finance::finance.from_stock") }}</td>
                                <td class="content-cell">{{ $obj->fromStock->name ?? '' }}</td>
                                <td class="label-cell">{{ __("finance::finance.to_stock") }}</td>
                                <td class="content-cell">{{ $obj->toStock->name ?? '' }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell">{{ __("finance::finance.file") }}</td>
                                <td colspan="3" class="content-cell">
                                    @if($obj->file)
                                        <a href="{{ showFile($obj->file) }}" target="_blank" download> Download</a>
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
                    {{ __("finance::finance.conversion_items") }}
                </div>
                <div class="card-body">
                    <table id="myDataTable_no_filter" class="table table-striped">
                        <thead>
                            <tr>
                                <th class="label-cell">{{ __("finance::finance.product_code") }}</th>
                                @if ($show_price_in_conversions == 1)
                                <th class="label-cell">{{ __("finance::finance.purchase_price") }} </th>
                                @endif
                                <th class="label-cell">{{ __("finance::finance.available_amount") }} </th>
                                {{-- @if ($show_price_in_conversions == 1)
                                <th class="label-cell">{{ __("finance::finance.price") }} </th>
                                @endif --}}
                                <th class="label-cell">{{ __("finance::finance.transfer_amount") }} </th>
                                <th class="label-cell">{{ __("finance::finance.notes") }} </th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($obj->conversionItems as $key => $conversionItem)
                            <tr class="y-group">
                                <td class="content-cell">{{ $conversionItem->item->name ?? '' }}</td>
                                @if ($show_price_in_conversions == 1)
                                <td class="content-cell">{{ $conversionItem->purchase_price }}</td>
                                @endif
                                <td class="content-cell">{{ $conversionItem->available_amount }}</td>
                                {{-- @if ($show_price_in_conversions == 1)
                                <td class="content-cell">{{ $conversionItem->price }}</td>
                                @endif --}}
                                <td class="content-cell">{{ $conversionItem->transfer_amount }}</td>
                                <td class="content-cell">{{ $conversionItem->notes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center content-cell">{{ __("finance::finance.no_items_found") }}</td>
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
