<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\StockStatus as Model;

class StockStatusService extends MainService
{

    function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getReport($filters = [])
    {
        $stockStatuses = $this->model->with(['stock', 'item'])
            ->when($filters['stock_id'] ?? null, fn($q) => $q->where('fn_stock_id', $filters['stock_id']))
            ->when($filters['item_id'] ?? null, fn($q) => $q->where('item_id', $filters['item_id']))
            ->orderBy('fn_stock_id')
            ->where('amount','!=',0)
            ->get()
            ->groupBy('fn_stock_id');

        $report = $stockStatuses->map(fn($statuses, $stockId) => [
            'data' => $statuses->map(fn($status) => [
                'stock' => $status->stock->name ?? '',
                'item_code' => $status->item->code ?? '',
                'item_name' => $status->item->name ?? '',
                'amount' => $status->amount,
                'price' => $status->price,
                'total' => $status->price * $status->amount,
            ]),
            'total' => [
                'stock_total_amount' => $statuses->sum('amount'),
                'stock_total_price' => $statuses->sum(fn($status) => $status->price * $status->amount),
            ]
        ]);

        $allStocksTotal = $stockStatuses->flatten()->reduce(function ($totals, $status) {
            return [
                // 'total_amount' => $totals['total_amount'] + $status->amount,
                'total_price' => $totals['total_price'] + ($status->price * $status->amount),
            ];
        }, ['total_price' => 0]);
        $report['all_stocks_total'] = $allStocksTotal;
        return $report;
    }



    public function updateItemsBalances($movement,$items)
    {
        foreach ($items as $itemRow) {
            $existingBalance = $this->model->where([
                'fn_stock_id' => $movement->fn_stock_id,
                'item_id'     => $itemRow->item_id,
                'price'       => $itemRow->price,
            ])->first();

            if(!$existingBalance){
                $existingBalance = $this->model->create([
                    'fn_stock_id' => $movement->fn_stock_id,
                    'item_id'     => $itemRow->item_id,
                    'price'       => $itemRow->price,
                    'amount'      => $itemRow->amount,
                ]);
            }else{
                // إذا كان المنتج موجود بالفعل يتم زيادة الكمية
                // وإذا لم يكن موجودًا (او موجود بسعر مختلف) ، يتم تعيين الكمية الجديدة بالسعر الجديد
                $existingBalance->amount += $itemRow->amount;
                $existingBalance->save();
            }

        }
    }

    public function increaseItemsBalances($movement,$items)
    {
        foreach ($items as $itemRow) {
            $existingBalance = $this->getWhereFirst([
                'fn_stock_id' => $movement->fn_stock_id,
                'item_id'     => $itemRow->item_id]);
            $existingBalance->amount -= $itemRow->amount;
            $existingBalance->save();

            $balance = $this->getWhereFirst([
                'fn_stock_id' => $movement->fn_stock_id,
                'item_id'     => $itemRow->item_id]);
            if($balance->amount <= 0){
                $balance->delete();
            }

        }
    }

    public function decrementItemBalance($stockId, $itemId, $price, $amount)
    {
        $existingBalance = $this->model->where('fn_stock_id', $stockId)
            ->where('item_id', $itemId)
            ->where('price', $price)
            ->first();

        if ($existingBalance) {
            $existingBalance->amount -= $amount;
            $existingBalance->save();
        }
    }

    public function checkItemBalanceBeforeDelete($stockId, $itemId, $amount)
    {
        $existingBalance = $this->model->where('fn_stock_id', $stockId)
        ->where('item_id', $itemId)
        ->first();

        // the user want to edit item to the purchase;
        if ($existingBalance && $existingBalance->amount < $amount) {
            return false;
        }
        return true;
    }

    public function checkItemBalanceBeforeUpdate($stockId, $itemId, $amount, $purchaseData)
    {
        $existingBalance = $this->model->where('fn_stock_id', $stockId)
        ->where('item_id', $itemId)
        ->first();


        $index = array_search($existingBalance->item_id, $purchaseData['item_id']);
        if($index === false){ // the user want to remove item from the purchase
            if ($existingBalance && $existingBalance->amount < $amount) {
                return false;
            }
        }

        // the user want to edit item to the purchase;
        if ($existingBalance && ($existingBalance->amount + (int)$purchaseData['amount'][$index]  - $amount) < 0) {
            return false;
        }
        return true;
    }

    public function decrementItemBalanceWhenDelete($stockId, $itemId, $amount)
    {
        $existingBalance = $this->model->where('fn_stock_id', $stockId)
            ->where('item_id', $itemId)
            ->first();

        if ($existingBalance) {
            $newAmount = $existingBalance->amount - $amount;
            $existingBalance->amount = max($newAmount, 0);
            $existingBalance->save();
        }
    }

    public function storeMany($data,$fn_stock_id)
    {
        $data = array_map(function($item) use ($fn_stock_id) {
            $item['fn_stock_id'] = $fn_stock_id;
            unset($item['unit_id']);
            unset($item['total_price']);
            return $item;
        }, $data);
      return  $this->insertMany($data);
    }


}
