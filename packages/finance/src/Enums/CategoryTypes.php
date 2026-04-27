<?php

namespace Nami\Finance\Enums;

enum CategoryTypes: string
{
    case Service = "service"; // خدمة
    case Stock = "stock"; // مخزون
    case Expired = "expired"; // صلاحية
    case Boxs = "boxs";
    case RawMaterials = "raw_materials"; // خامات
    case FinishedProduct = "finished_product"; // منتج تام

    public function lang()
    {
        return match ($this) {
            CategoryTypes::Service => __("finance::finance.service"),
            CategoryTypes::Stock => __("finance::finance.stock"),
            CategoryTypes::Expired => __("finance::finance.expired"),
            CategoryTypes::Boxs => __("finance::finance.boxs"),
            CategoryTypes::RawMaterials => __("finance::finance.raw_materials"),
            CategoryTypes::FinishedProduct => __("finance::finance.finished_product"),
        };
    }

    public function rules()
    {
        return match ($this) {
            CategoryTypes::Service => [],
            CategoryTypes::Expired => [
                'min_order' => 'required',
                'sale_unit_id' => 'required|exists:fn_units,id',
                'purchase_unit_id' => 'required|exists:fn_units,id',
                'stock_account_id' => 'required|exists:fn_accounts,id',
                'supplier_id' => 'nullable|exists:fn_suppliers,id',
                'stock_tracking' => 'required|boolean',
            ],
            CategoryTypes::Boxs => [
                'sale_unit_id' => 'required|exists:fn_units,id',
                'stock_account_id' => 'required|exists:fn_accounts,id',
                'stock_tracking' => 'required|boolean',
                'component_item_id' => 'required|exists:fn_items,id',
                'amount' => 'required',
                'one_purchase_price' => 'required',
                'total_purchase_price' => 'required',
            ],
            CategoryTypes::RawMaterials => [
                'min_order' => 'required',
                'sale_unit_id' => 'required|exists:fn_units,id',
                'purchase_unit_id' => 'required|exists:fn_units,id',
                'stock_account_id' => 'nullable|exists:fn_accounts,id',
                'supplier_id' => 'nullable|exists:fn_suppliers,id',
                'stock_tracking' => 'required|boolean',
            ],
            CategoryTypes::FinishedProduct => [
                'min_order' => 'required',
                'sale_unit_id' => 'required|exists:fn_units,id',
                'stock_account_id' => 'nullable|exists:fn_accounts,id',
                'stock_tracking' => 'required|boolean',
                'component_item_id' => 'required|exists:fn_items,id',
                'amount' => 'required',
                'one_purchase_price' => 'required',
                'total_purchase_price' => 'required',
            ],
            CategoryTypes::Stock => [
                'min_order' => 'required',
                'sale_unit_id' => 'required|exists:fn_units,id',
                'purchase_unit_id' => 'required|exists:fn_units,id',
                'stock_account_id' => 'nullable|exists:fn_accounts,id',
                'stock_tracking' => 'required|boolean',
                'supplier_id' => 'nullable|exists:fn_suppliers,id',
            ],
        };
    }
}
