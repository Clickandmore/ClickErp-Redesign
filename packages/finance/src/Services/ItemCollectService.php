<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Enums\StockMovementTypeEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\ItemCollect;
use Nami\Finance\Models\StockStatus;

class ItemCollectService extends MainService
{
    public function __construct(ItemCollect $model,private StockStatusService $stockStatusService
        ,private ItemService $itemService
        ,private StockMovementService $stockMovementService
        ,private SarfMovementService $sarfMovementService
        ,private StockService $stockService)
    {
        $this->model = $model;
    }

    public function createData($mainRoute)
    {
        return [
            'code' => $this->model->max('code') + 1,
            'items' => $this->itemService->getItemsWithSomeTypes([CategoryTypes::FinishedProduct->value,CategoryTypes::Boxs->value]),
            'stocks' => $this->stockService->get(),
            'storeRoute' => route($mainRoute.'.store'),
        ];
    }

    public function editData($id, $mainRoute)
    {
        return [
            'obj' => $this->find($id),
            'updateRoute' => route($mainRoute.'.update', $id),
            'stocks' => $this->stockService->get(),
            'stocks_details' => $this->stockService->getWhereIn('id', StockStatus::where('item_id', $this->find($id)->item_id)->pluck('fn_stock_id')->toArray()),
            'items' => $this->itemService->getItemsWithSomeTypes([CategoryTypes::FinishedProduct->value,CategoryTypes::Boxs->value]),
        ];
    }

    public function customStore($data)
    {
        $obj = parent::customStore($data);
        $obj->details()->createMany($data['details']);
        $itemStock = StockStatus::where(['item_id'=> $obj->item_id,'fn_stock_id'=> $obj->to_stock_id])->first();
        if(!$itemStock){
            $itemStock = StockStatus::create(['item_id'=> $obj->item_id,'price'=> $obj->item->purchase_price,'fn_stock_id'=> $obj->to_stock_id,'amount'=> $obj->amount]);
        }else{
            $itemStock->amount = $itemStock->amount + $obj->amount;
            $itemStock->price = $obj->item->purchase_price;
            $itemStock->save();
        }
        foreach ($data['details'] as $detail) {
            $stock = StockStatus::where(['item_id'=> $detail['item_id'],'fn_stock_id'=> $detail['stock_id']])->first();
            if(!$stock){
                 StockStatus::create(['item_id'=> $detail['item_id'],'fn_stock_id'=> $detail['stock_id'],'amount'=> $detail['order_amount']]);
            }else{
                $stock->amount = $stock->amount - $detail['order_amount'];
                $stock->save();
            }

        }
    }

    public function customUpdate( $id,$data)
    {
        $obj = $this->find($id);
//        dd($data);
        $movement = $this->sarfMovementService->model->create([
            'fn_stock_id' => $data['stock_id'],
            'type'        => StockMovementTypeEnum::SaleNormal->value,
            'date'        => $data['date'],
            'code'        => $this->sarfMovementService->getLastCodeOfType(StockMovementTypeEnum::SaleNormal->value),
            'note'        => $data['note'],
        ]);


        // Call the method to save movement items
        $items = $this->sarfMovementService->saveMovementItems($data, $movement,$data);
        $data['stock_id'] = $data['to_stock_id'];
//        dd($data['details']);
        unset($data['item_id'],$data['purchase_price'],$data['total_price'],$data['price'],$data['one_price'],$data['order_amount'],$data['amount']);
        foreach($obj->details as $key => $detail) {
            $data['item_id'][] = $detail->item_id;
            $data['purchase_price'][] = $detail->item->purchase_price;
            $data['total_price'][] = $detail->item->purchase_price * $detail->order_amount;
            $data['price'][] = $detail->item->purchase_price;
            $data['one_price'][] = $detail->item->purchase_price;
            $data['order_amount'][] = $detail->order_amount;
            $data['amount'][] = $detail->order_amount;
        }
//        dd($data);
         $this->stockMovementService->storeNormalStoreMovement($data);

        $obj->touch();
        return $movement;
    }



}
