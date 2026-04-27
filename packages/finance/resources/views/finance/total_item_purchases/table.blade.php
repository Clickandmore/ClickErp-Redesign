<table class="table" id="reportTableData">
    <thead>
    <tr>
        <th>{{ __("finance::finance.item") }}</th>
        <th>{{ __("finance::finance.item_code") }}</th>
        <th>{{ __("finance::finance.main_category") }}</th>
        <th>{{ __("finance::finance.unit") }}</th>
        <th>{{ __("finance::finance.amount") }}</th>
        <th>{{ __("finance::finance.purchase_price") }}</th>
        <th>{{ __("finance::finance.total") }}</th>
        <th>{{ __("finance::finance.Statement") }}</th>
        <th>{{ __("finance::finance.user") }}</th>
        <th>{{ __("finance::finance.discount_value") }}</th>
    </tr>
    </thead>
    <tbody>
    @php
        $final_total = 0; // متغير لحساب الإجمالي النهائي
    @endphp
    @forelse($report as $item)
        @php
            // حساب الإجمالي لكل عنصر
            $item_total = $item->total_amount * $item->total_price;

            // التأكد من أن الناتج النهائي يأخذ القيم السالبة في الاعتبار
            if ($item->total_amount < 0 || $item->total_price < 0) {
                $item_total = abs($item_total) * -1; // إذا كان كلاهما سالب
            }

            // إضافة الإجمالي لكل عنصر إلى الإجمالي النهائي
            $final_total += $item_total;
        @endphp
        <tr>
            <td>{{ $item->item->name ?? "" }}</td>
            <td>{{ $item->item->code ?? "" }}</td>
            <td>{{ $item->item->mainCategory->title ?? "" }}</td>
            <td>{{ $item->item->purchaseUnit->title ?? "" }}</td>
            <td>{{ $item->total_amount }}</td> <!-- مجموع الكمية -->
            <td>{{ $item->total_price ?? "" }}</td> <!-- سعر الشراء -->
            <td>{{ $item_total }}</td> <!-- الإجمالي النهائي المحسوب لكل عنصر -->
            <td>{{ $item->type ? Nami\Finance\Enums\PurchaseTypeEnum::from($item->type)->lang() : "" }}</td>
            <td>{{ $item->created_by ?? "" }}</td>
            <td>{{ $item->discount_value }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="11">{{ __("No Data Available") }}</td>
        </tr>
    @endforelse
    </tbody>

    <tfoot>
    <tr>
        <td colspan="6">{{ __("finance::finance.final_total") }}</td>
        <td>{{ $final_total }}</td> <!-- عرض الإجمالي النهائي -->
    </tr>
    </tfoot>
</table>
