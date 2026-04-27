<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\ItemComponent;
use Nami\Finance\Models\StockStatus;

class ItemComponentService extends MainService
{
    public function __construct(ItemComponent $model,private StockService $stockService)
    {
        $this->model = $model;
    }

    public function filter($request)
    {
        $query = $this->model->query();
        if ($request->has('item_id')) {
            $query->where('item_id', $request->item_id);
        }
        $items = $query->get();
//dd($request->all());
        $itemsWithStocks = $items->map(function($item) use ($request) {
            // جلب المخزون الخاص بكل عنصر باستخدام item_id
            $stocks = StockStatus::where('item_id', $item->component_item_id)
                ->when($request->has('from_stock_id'), function ($q) use ($request) {
                    $q->whereIn('fn_stock_id', $request->from_stock_id);
                })
                ->pluck('fn_stock_id')
                ->toArray();

            // إضافة بيانات المخزون إلى العنصر
            $item->stocks = $this->stockService->getWhereIn('id', $stocks);

            return $item;
        });
        return [
            'item_coms' => $itemsWithStocks,
        ];
    }

}
