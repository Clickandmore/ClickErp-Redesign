<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\SaleItem;

class BillSalesItemService extends MainService
{

    public function __construct(SaleItem $model)
    {
        $this->model = $model;
    }

    // public function getReport($filters)
    // {
    //     $query = $this->model
    //         ->with(['sale', 'stock'])
    //         ->join('fn_sales', 'fn_sales.id', '=', 'fn_sale_items.sale_id')
    //         ->select('fn_sales.code', 'fn_sales.date', 'fn_sales.type', 'fn_sales.client_id', 'fn_sale_items.amount', 'fn_sale_items.stock_id', 'fn_sale_items.price', 'fn_sale_items.final_total')
    //         ->when($filters['from_date'], function ($query, $from_date) {
    //             return $query->where('fn_sales.date', '>=', $from_date);
    //         })
    //         ->when($filters['to_date'], function ($query, $to_date) {
    //             return $query->where('fn_sales.date', '<=', $to_date);
    //         })
    //         ->when($filters['client_id'], function ($query, $client_id) {
    //             return $query->where('fn_sales.client_id', '=', $client_id);
    //         })
    //         ->when($filters['item_id'], function ($query, $item_id) {
    //             return $query->where('fn_sale_items.item_id', '=', $item_id);
    //         })
    //         ->when($filters['stock_id'], function ($query, $stock_id) {
    //             return $query->where('fn_sale_items.stock_id', '=', $stock_id);
    //         });

    //     return $query->get();
    // }

    public function getReport($filters)
    {

        $query = $this->model
            ->when($filters['from_date'], function ($query, $from_date) {
                return $query->whereHas('sale', function ($q) use ($from_date) {
                    $q->where('date', '>=', $from_date);
                });
            })
            ->when($filters['to_date'], function ($query, $to_date) {
                return $query->whereHas('sale', function ($q) use ($to_date) {
                    $q->where('date', '<=', $to_date);
                });
            })
            ->when($filters['client_id'], function ($query, $client_id) {
                return $query->whereHas('sale.client', function ($q) use ($client_id) {
                    $q->where('id', $client_id);
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

    public function getSaleReport($filters)
    {
        $query = $this->model->with(['sale', 'item'])
            ->join('fn_sales', 'fn_sales.id', '=', 'fn_sale_items.sale_id')
            ->when(isset($filters['from_date']), function ($query) use ($filters) {
                return $query->where('fn_sales.date', '>=', $filters['from_date']);
            })
            ->when(isset($filters['to_date']), function ($query) use ($filters) {
                return $query->where('fn_sales.date', '<=', $filters['to_date']);
            })
            ->when(isset($filters['item_id']) && $filters['item_id'], function ($query) use ($filters) {
                return $query->whereIn('fn_sale_items.item_id', $filters['item_id']);
            })
            ->when(isset($filters['type']), function ($query) use ($filters) {
                return $query->where('fn_sales.type', $filters['type']);
            })
            ->selectRaw("
        fn_sale_items.item_id,
        fn_sales.type,
        fn_sale_items.sale_id,
        fn_sale_items.price,
        fn_sales.date,
        SUM(fn_sale_items.amount) AS total_amount,
        SUM(fn_sale_items.price) AS total_sale_price
    ")
            ->groupBy(
                'fn_sale_items.item_id',
                'fn_sales.type',
                'fn_sale_items.price',
                'fn_sale_items.sale_id',
                'fn_sales.date'
            );

        return $query->get();
    }
}
