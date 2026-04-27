<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\MainService;
use Nami\Finance\Models\FirstStock;

class FirstStockService extends MainService
{
    public function __construct(FirstStock $model,
                                protected StockSettingService $stockSetting,
                                protected FirstStockItemService $firstStockItemService,
                                protected ItemService $itemService,
                                protected StockStatusService $stockStatusService
        ,protected StockService $stockService)
    {
        $this->model = $model;
    }

    public function createData($mainRoute)
    {
        return [
            'storeRoute' => route($mainRoute . '.store'),
            'stocks' => $this->stockService->for_first_stock(),
            'items' => $this->itemService->getItemsWithoutSomeTypes(
                [CategoryTypes::Service->value]
            ),
            'stock_setting' => $this->getStocksSetting(),
        ];
    }

    public function stocks()
    {
        return $this->stockService->getActive();
    }


    public function items()
    {
        return $this->itemService->get();
    }

    public function editData($id, $mainRoute)
    {
        return [
            'obj' => $this->firstStockItemService->find($id),
            'stocks' => $this->stockService->for_first_stock(),
            'items' => $this->itemService->getItemsWithoutSomeTypes(
                [CategoryTypes::Service->value]
            ),
            'stock_setting' => $this->getStocksSetting(),
            'updateRoute' => route($mainRoute . '.update', $id)
        ];
    }

    public function customStore($data)
    {
        $fn_stock_id = $data['stock_id'];
        $obj = parent::customStore($data);
        $obj->firstStockItems()->createMany($data['items']);

        $data['items'] = $this->stockStatusService->storeMany($data['items'], $fn_stock_id);

        return $obj;
    }

    public function customUpdate($id, $data)
    {

        $obj = $this->firstStockItemService->find($id);
        $obj->update($data['items'][0]);
//        parent::customUpdate($id, $data);
        return $obj;
    }

    public function getDataTable()
    {
        return $this->firstStockItemService->getDataTable();
    }

    public function delete($id)
    {
        $obj = $this->firstStockItemService->find($id);
        $firstStock = $this->find($obj->first_stock->id);
//        dd($firstStock);
        if($firstStock->firstStockItems->count() == 1){
            return $firstStock->delete();
        }
        return $this->firstStockItemService->delete($id);
    }

    public function getStocksSetting()
    {
        return $this->stockSetting->getSetting();
    }
}
