<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\PurchaseItem;

class PurchaseItemService extends MainService
{

    public function __construct(PurchaseItem $model)
    {
        $this->model = $model;
    }

    public function getReport($filters)
    {
        $query = $this->model
            ->when($filters['from_date'], function ($query, $from_date) {
                return $query->whereHas('purchase', function ($q) use ($from_date) {
                    $q->where('date', '>=', $from_date);
                });
            })
            ->when($filters['to_date'], function ($query, $to_date) {
                return $query->whereHas('purchase', function ($q) use ($to_date) {
                    $q->where('date', '<=', $to_date);
                });
            })
            ->when($filters['supplier_id'], function ($query, $supplier_id) {
                return $query->whereHas('purchase', function ($q) use ($supplier_id) {
                    $q->where('supplier_id', $supplier_id);
                });
            })
            ->when($filters['item_id'], function ($query, $item_id) {
                return $query->where('item_id', $item_id);
            })
            ->when($filters['stock_id'], function ($query, $stock_id) {
                return $query->where('stock_id', $stock_id);
            });
        return $query->get();
    }

    public function getTotalItemPurchasesReport($filters)
    {
        $query = $this->model
            ->with('purchase.createdBy')
            ->selectRaw("
        item_id,
        discount_value,
        fn_purchases.type,
        admins.name as created_by,
        SUM(CASE WHEN fn_purchases.type = 'purchase' THEN fn_purchase_items.amount ELSE -fn_purchase_items.amount END) as total_amount,
        SUM(CASE WHEN fn_purchases.type = 'purchase' THEN fn_purchase_items.price ELSE -fn_purchase_items.price END) as total_price
    ")
            ->join('fn_purchases', 'fn_purchases.id', '=', 'fn_purchase_items.purchase_id')
            ->join('admins', 'admins.id', '=', 'fn_purchases.created_by_id')
            ->groupBy('item_id', 'discount_value', 'fn_purchases.type', 'admins.name')
            ->when(isset($filters['from_date']), function ($query) use ($filters) {
                return $query->whereHas('purchase', function ($q) use ($filters) {
                    $q->where('date', '>=', $filters['from_date']);
                });
            })
            ->when(isset($filters['to_date']), function ($query) use ($filters) {
                return $query->whereHas('purchase', function ($q) use ($filters) {
                    $q->where('date', '<=', $filters['to_date']);
                });
            })
            ->when(isset($filters['supplier_id']) && !empty($filters['supplier_id']), function ($query) use ($filters) {
                return $query->whereHas('purchase', function ($q) use ($filters) {
                    $q->where('supplier_id', $filters['supplier_id']);
                });
            })
            ->when(isset($filters['type']) && !empty($filters['type']), function ($query) use ($filters) {
                return $query->whereHas('purchase', function ($q) use ($filters) {
                    $q->where('type', $filters['type']);
                });
            })
            ->when(isset($filters['item_id']) && !empty($filters['item_id']), function ($query) use ($filters) {
                return $query->whereIn('fn_purchase_items.item_id', $filters['item_id']);
            });

        return $query->get();

    }




}
