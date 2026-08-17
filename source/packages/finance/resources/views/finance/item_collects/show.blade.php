<!-- Card for displaying details -->
<style>
    th {
        font-size: large; /* جعل حجم النص كبيرًا */
        font-weight: bold; /* جعل النص عريضًا */
    }
</style>

<div class="card">
    <!-- Header with a nice background color -->
    <div class="card-header bg-primary text-white">
        <h5>{{ __("finance::finance.details") }}</h5>
    </div>

    <!-- Card body where the data is displayed in a table -->
    <div class="card-body">
        <table class="table table-bordered">
            <tbody>
            <tr>
                <th>{{ __("charities.code") }}</th>
                <td>{{ $obj->code }}</td>

                <th>{{ __("charities.date") }}</th>
                <td>{{ $obj->date }}</td>
            </tr>
            <tr>
                <th>{{ __("charities.amount") }}</th>
                <td>{{ $obj->amount }}</td>

                <th>{{ __("finance::finance.add_to_stock") }}</th>
                <td>{{ $obj->toStock->name }}</td>
            </tr>
            <tr>
                <th>{{ __("finance::finance.item") }}</th>
                <td>{{ $obj->item->name }}</td>

                <th>{{ __("finance::finance.notes") }}</th>
                <td>{{ $obj->notes ?? "لا توجد ملاحظات" }}</td>
            </tr>
            <tr >
                <th>{{ __("finance::finance.file") }}</th>
                <td colspan="3">
                    @if($obj->attachment)
                        <a href="{{ showFile($obj->attachment) }}" class="btn btn-primary" target="_blank">عرض الملف</a>
                    @else
                        لا يوجد ملف مرفق
                    @endif
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Table for displaying item details -->
<div class="card mt-4">
    <div class="card-header bg-secondary text-white">
        <h5>{{ __("finance::finance.items") }}</h5>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>{{ __("finance::finance.item_code") }}</th>
                <th>{{ __("finance::finance.order_amount") }}</th>
                <th>{{ __("finance::finance.the_stock") }}</th>
                <th>{{ __("finance::finance.added_amount") }}</th>
            </tr>
            </thead>
            <tbody class="content">
            @php
                // جلب تفاصيل العناصر مع التجميع
                $groupedDetails = $obj->details()
                    ->selectRaw('item_id, SUM(order_amount) as total_order_amount')
                    ->groupBy('item_id')
                    ->get();
            @endphp

            @foreach($groupedDetails as $key => $item_detail)
                @php
                    // جلب العنصر الحالي
                    $currentItem = $item_detail->item;
                    // جلب المخزون لكل عنصر بناءً على `item_id`
                    $itemStocks = $obj->details()
                        ->where('order_amount', '>', 0)
                        ->where('item_id', $item_detail->item_id)
                        ->get();

                    $stocksCount = $itemStocks->count();
                    $rowCount = max($stocksCount, 1); // عدد الصفوف لكل عنصر (على الأقل 1)
                    $firstRow = true; // متغير للتحقق من أول صف
                @endphp

                @if($stocksCount > 0)
                    @foreach($itemStocks as $stock)
                        <tr>
                            @if($firstRow)
                                <!-- عرض كود الصنف والكمية المطلوبة مرة واحدة فقط في الصف الأول لكل مجموعة -->
                                <td rowspan="{{ $rowCount }}">
                                    {{ $currentItem->name }}
                                </td>
                                <td rowspan="{{ $rowCount }}">
                                    {{ $item_detail->total_order_amount }}
                                </td>
                                @php
                                    $firstRow = false; // تغيير الحالة بعد الصف الأول
                                @endphp
                            @endif

                            <!-- عرض بيانات المخزون -->
                            <td>{{ $stock->stock->name }}</td>
                            <td>{{ $stock->order_amount }}</td>
                        </tr>
                    @endforeach
                @else
                    <!-- إذا لم يكن هناك مخازن، نعرض صفًا واحدًا للعنصر -->
                    <tr>
                        <td>{{ $currentItem->name }}</td>
                        <td>{{ $item_detail->total_order_amount }}</td>
                        <td colspan="2" class="text-center">{{ __("No stocks available") }}</td>
                    </tr>
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="col-12 modal-footer">
    {!! closeButton() !!}
</div>
