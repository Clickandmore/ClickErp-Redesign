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
        padding: 12px; /* زيادة التباعد */
        vertical-align: middle;
        text-align: center; /* النص في منتصف الخلية */
    }

    /* إضافة لون الخلفية */
    .card-header {
        background-color: #007bff;
        color: white;
    }

    /* تنسيق للجدول في الـ items */
    .table-bordered th, .table-bordered td {
        border: 1px solid #dee2e6;
    }

    /* تعديل حجم الـ table بالكامل */
    .table {
        width: 100%;
    }

    /* تنسيق مخصص للحفاظ على التباعد بين الحقول في الجزء الخاص بالإجماليات */
    .summary-fields .form-group {
        margin-bottom: 15px;
    }

    /* تخصيص عرض الحقول لتتناسب مع التصميم */
    .summary-fields .form-control {
        text-align: center;
        font-weight: bold;
        font-size: 16px;
    }

    /* إضافة مسافة قبل زر الإغلاق */
    .close-button {
        margin-top: 30px;
    }

    /* تنسيق زر الإغلاق */
    .close-button .btn {
        padding: 10px 20px;
        font-size: 16px;
    }
</style>

<div class="container mt-4">
    <div class="row">
        <!-- معلومات الجرد الأساسية -->
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header bg-primary">
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
                            <td class="label-cell">{{ __("finance::finance.client") }}</td>
                            <td class="content-cell">{{ $obj->client->name ?? '' }}</td>
                            <td class="label-cell">{{ __("finance::finance.file") }}</td>
                            <td class="content-cell">
                                @if($obj->file)
                                    <a href="{{ showFile($obj->file) }}" target="_blank"
                                       download>{{ __("finance::finance.download") }}</a>
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
                <div class="card-header bg-secondary">
                    {{ __("finance::finance.inventory_items") }}
                </div>
                <div class="card-body">
                    <table id="myDataTable_no_filter" class="table table-striped table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th>{{ __("finance::finance.product_code") }}</th>
                            <th>{{ __("finance::finance.unit") }}</th>
                            <th>{{ __("finance::finance.available_amount") }}</th>
                            <th>{{ __("finance::finance.sales_price") }}</th>
                            <th>{{ __("finance::finance.sales_amount") }}</th>
                            <th>{{ __("finance::finance.discount_per") }}</th>
                            <th>{{ __("finance::finance.Tax rate") }}</th>
                            <th>{{ __("finance::finance.final_total") }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($obj->salesOrderItems as $key => $item)
                            <tr class="y-group">
                                <td>{{ $item->item->code }}</td>
                                <td>{{ $item->item->saleUnit->title ?? '' }}</td>
                                <td>{{ $item->item->itemStockStatus->sum('amount') }}</td>
                                <td>{{ number_format($item->sales_price, 2) }}</td>
                                <td>{{ $item->sales_amount }}</td>
                                <td>{{ $item->discount_per ? $item->discount_per . '%' : '0%' }}</td>
                                <td>{{ $item->tax_per ? $item->tax_per . '%' : '0%' }}</td>
                                <td>{{ number_format($item->final_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center content-cell">{{ __("finance::finance.no_items_found") }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($obj->additionItems->count() > 0)
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header bg-secondary">
                        {{ __("finance::finance.additional_items") }}
                    </div>
                    <div class="card-body">
                        <table id="myDataTable_no_filter" class="table table-striped table-bordered">
                            <thead class="thead-dark">
                            <tr>
                                <th>{{ __("finance::finance.product_code") }}</th>
                                <th>{{ __("finance::finance.name") }}</th>
                                <th>{{ __("finance::finance.unit") }}</th>
                                <th>{{ __("finance::finance.sales_price") }}</th>
                                <th>{{ __("finance::finance.sales_amount") }}</th>
                                <th>{{ __("finance::finance.discount_value") }}</th>
                                <th>{{ __("finance::finance.Tax rate") }}</th>
                                <th>{{ __("finance::finance.final_total") }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($obj->additionItems as $key => $item)
                                <tr class="y-group">
                                    <td>{{ $item->code ?? '' }}</td>
                                    <td>{{ $item->name ?? '' }}</td>
                                    <td>{{ $item->unit ?? '' }}</td>
                                    <td>{{ number_format($item->price ?? 0, 2) }}</td>
                                    <td>{{ $item->amount ?? '' }}</td>
                                    <td>{{ $item->discount_per ? $item->discount_per . '%' : '0%' }}</td>
                                    <td>{{ $item->tax_per ? $item->tax_per . '%' : '0%' }}</td>
                                    <td>{{ number_format($item->final_price ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7"
                                        class="text-center content-cell">{{ __("finance::finance.no_items_found") }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- الحقول الخاصة بالإجمالي النهائي والخصم -->
        <div class="col-md-12">
            @php
                // Ensure additionItems is not null before using sum() to avoid errors
                $additionItems = $obj->additionItems ?? collect([]);

                // Calculate total discount value and total tax value safely
                $total_discount_value =$obj->salesOrderItems->sum('discount_value') + $additionItems->sum('discount_value');
                $total_tax_value = $obj->salesOrderItems->sum('tax_value') + $additionItems->sum('tax_value');

                // Calculate total before discount
                $total_before_discount = $obj->salesOrderItems->sum('final_total') + $additionItems->sum('final_price') - $total_tax_value + $total_discount_value;

                // Calculate total after discount
                $total_after_discount = $obj->salesOrderItems->sum('final_total') + $additionItems->sum('final_price') - $total_tax_value;

                // Calculate final total (net)
                $final_total = $obj->salesOrderItems->sum('final_total') + $additionItems->sum('final_price');
            @endphp

            <div class="row summary-fields">
                <!-- Total before discount -->
                <div class="col-md-4 form-group">
                    <label for="total_price" class="label form-label">{{ __('finance::finance.Total before discount') }}</label>
                    <h3 id="total_price" class="value form-control">{{ number_format($total_before_discount, 2) }}</h3>
                </div>

                <!-- Total discount value -->
                <div class="col-md-4 form-group">
                    <label for="total_discount_value" class="label form-label">{{ __('finance::finance.discount_value') }}</label>
                    <input type="text" id="total_discount_value" readonly class="value form-control"
                           value="{{ number_format($total_discount_value, 2) }}">
                </div>

                <!-- Total after discount -->
                <div class="col-md-4 form-group">
                    <label for="final_total_after_discount" class="label form-label">{{ __('finance::finance.Total after discount') }}</label>
                    <input type="text" id="final_total_after_discount" readonly class="value form-control"
                           value="{{ number_format($total_after_discount, 2) }}">
                </div>

                <!-- Total tax value -->
                <div class="col-md-4 form-group">
                    <label for="total_tax_value" class="label form-label">{{ __('finance::finance.the_tax_value') }}</label>
                    <input type="text" id="total_tax_value" readonly class="value form-control"
                           value="{{ number_format($total_tax_value, 2) }}">
                </div>

                <!-- Final total (net) -->
                <div class="col-md-4 form-group">
                    <label for="final_total" class="label form-label">{{ __('finance::finance.net') }}</label>
                    <input type="text" id="final_total" readonly class="value form-control"
                           value="{{ number_format($final_total, 2) }}">
                </div>
            </div>
        </div>

        <!-- زر الإغلاق -->
        <div class="col-12 text-center close-button">
            {!! closeButton() !!}
        </div>
    </div>
</div>
