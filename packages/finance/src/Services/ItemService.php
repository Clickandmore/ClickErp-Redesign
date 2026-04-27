<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Enums\ProductNumSettingEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Item;

class ItemService extends MainService
{
    public function __construct(Item                               $model, protected CategoryService $categoryService,
                                protected AccountService           $accountService,
                                protected UnitService              $unitService,
                                protected ItemTaxService           $itemTaxService,
                                protected ItemComponentService     $itemComponentService,
                                protected ProductNumSettingService $productNumSettingService,
                                protected SupplierService          $supplierService,
                                protected StockSettingService $stockSetting,
                                protected TaxSettingService        $taxSettingService)
    {
        $this->model = $model;
        $this->fileFolder = 'items/';
        $this->files = ['image'];
    }

    public function getItemByStock($stockId)
    {
        return $this->model->whereHas('itemStockStatus', function ($query) use ($stockId) {
            $query->where('fn_stock_id', $stockId);
        })->with('itemStockStatus')->get();
    }

    public function getMissingItems()
    {
        return $this->model
            ->whereDoesntHave('orderPurchaseItems') // items بدون purchase order items
            ->whereHas('itemStockStatus', function ($query) {
                $query->whereIn('item_id', function ($sub) {
                    $sub->select('item_id')
                        ->from('fn_stock_statuses')
                        ->groupBy('item_id')
                        ->havingRaw('SUM(amount) > MIN(min_order)');
                });
            })
            ->with('itemStockStatus');
    }

    public function getItems()
    {
        return $this->model->whereHas('itemStockStatus')->where('type', '!=', CategoryTypes::Service->value)->get();
    }

    public function getItemsWithoutStock()
    {
        return $this->model->where('type', '!=', CategoryTypes::Service->value)->get();
    }

    public function getItemsFromCategoryOrSubCategory($categoryId)
    {
        return $this->model
            ->where('main_category_id', $categoryId)
            ->orWhere('sub_category_id', $categoryId)
            ->orderBy('id', 'DESC')
            ->first();
    }


    public function getItemsWithoutSomeTypes(array $excludedTypes) {
        return $this->model
            ->whereNotIn('type', $excludedTypes)
            ->get();
    }

    public function getItemsWithSomeTypes(array $excludedTypes) {
        return $this->model
            ->whereIn('type', $excludedTypes)
            ->get();
    }

    public function getCreateData() {
        $data['main_categories'] = $this->categoryService->getWhere(['parent_id' => null]);
        $data['sub_categories'] = $this->categoryService->getWhere([['parent_id', '!=', null]]);
        $data['sale_accounts'] = $this->accountService->getWhere(["type" => AccountTypesEnum::Sales->value]);
        $data['stock_accounts'] = $this->accountService->getWhere(["type" => AccountTypesEnum::Storage->value]);
        $data['category_types'] = $this->categoryService->getCategoryTypes();
        $data['units'] = $this->unitService->get();
        $data['items_finished_product'] = $this->getWhereIn('type',[CategoryTypes::RawMaterials->value,CategoryTypes::Stock->value,CategoryTypes::Expired->value]);
        $data['items_boxs'] = $this->getWhereIn('type',[CategoryTypes::RawMaterials->value,CategoryTypes::Stock->value,CategoryTypes::Expired->value,CategoryTypes::FinishedProduct->value]);
        $data['category_types'] = CategoryTypes::cases();
        $data['suppliers'] = $this->supplierService->get();
        $data['tax_settings'] = $this->taxSettingService->get();
        $data['latest_items'] = $this->model->latest()->first();
        $allProductsCode = $this->productNumSettingService->getWhereFirst(["type" => ProductNumSettingEnum::AllProducts->value]);

        if ($data["latest_items"]) {
            $data['code'] = $data['latest_items']->code_string . ($data['latest_items']->code + 1);
        } elseif ($allProductsCode) {
            $data['code'] = $allProductsCode->code_string . $allProductsCode->code;
        } else {
            $data['code'] = "";
        }

        return $data;
    }

    public function storeItem($data)
    {
        $fullCode = $data['code'];
        preg_match('/([a-zA-Z]+)(\d+)/', $fullCode, $matches);

        if (isset($matches[1]) && isset($matches[2])) {
            $data['code_string'] = $matches[1];
            $data['code'] = $matches[2];
        } else {
            $data['code_string'] = '';
            $data['code'] = $fullCode;
        }
        $item = $this->store($data);
        $this->storeItemTax($data, $item->id);
        $this->storeItemComponent($data, $item->id);
        return $item;
    }

    public function storeItemTax($data, $itemId)
    {
        if ($data['is_tax'] == 1) {
            $data['item_id'] = $itemId;
            $this->itemTaxService->store($data);
        }
    }

    public function storeItemComponent($data, $itemId)
    {
        if (isset($data['component_item_id']) && is_array($data['component_item_id'])) {
            foreach ($data['component_item_id'] as $key => $component_item_id) {
                if (isset($data['amount'][$key], $data['one_purchase_price'][$key], $data['total_purchase_price'][$key])) {
                    $itemComponentData = [
                        'item_id' => $itemId,
                        'component_item_id' => $component_item_id,
                        'amount' => $data['amount'][$key],
                        'one_purchase_price' => $data['one_purchase_price'][$key],
                        'total_purchase_price' => $data['total_purchase_price'][$key]
                    ];

                    $this->itemComponentService->store($itemComponentData);
                }
            }
        }
    }


    public function getEditData($id)
    {
        $data["obj"] = $this->find($id);
        $data['main_categories'] = $this->categoryService->getWhere(['parent_id' => null]);
        $data['sub_categories'] = $this->categoryService->getWhere(['parent_id' => $data["obj"]->main_category_id]);
        $data['sale_accounts'] = $this->accountService->getWhere(["type" => AccountTypesEnum::Sales->value]);
        $data['stock_accounts'] = $this->accountService->getWhere(["type" => AccountTypesEnum::Storage->value]);
        $data['category_types'] = $this->categoryService->getCategoryTypes();
        $data['units'] = $this->unitService->get();
        $data['category_types'] = CategoryTypes::cases();
        $data['items'] = $this->get();
        $data['suppliers'] = $this->supplierService->get();
        $data['tax_settings'] = $this->taxSettingService->get();
        $data['latest_items'] = $this->model->latest()->first();
        $allProductsCode = $this->productNumSettingService->getWhereFirst(["type" => ProductNumSettingEnum::AllProducts->value]);

        if ($data["latest_items"]) {
            $data['code'] = $data['latest_items']->code_string . ($data['latest_items']->code + 1);
        } elseif ($allProductsCode) {
            $data['code'] = $allProductsCode->code_string . $allProductsCode->code;
        } else {
            $data['code'] = "";
        }

        return $data;
    }

    public function updateItem($id, $data)
    {
        $fullCode = $data['code'];
        preg_match('/([a-zA-Z]+)(\d+)/', $fullCode, $matches);

        if (isset($matches[1]) && isset($matches[2])) {
            $data['code_string'] = $matches[1];
            $data['code'] = $matches[2];
        } else {
            $data['code_string'] = '';
            $data['code'] = $fullCode;
        }
        $item = $this->update($id, $data);
        $this->storeItemTax($data, $id);
        $this->storeItemComponent($data, $item->id);
        return $item;
    }


    public function filter($array)
    {

        return $this->model->when(isset($array['supplier_id']), function ($q) use ($array) {
            return $q->where('supplier_id', $array['supplier_id']);
        })
            ->when(isset($array['category_id']), function ($q) use ($array) {
                return $q->where('main_category_id', $array['category_id']);
            })
            ->when(isset($array['stock_id']), function ($q) use ($array) {
                return $q->whereDoesntHave('firstStockItem', function ($q2) use ($array) {
                    return $q2->whereDoesntHave('first_stock',function ($q3) use ($array){
                        return $q3->where('stock_id','!=', $array['stock_id']);
                    });
                });
            })

            ->when(isset($array['from']), function ($q) use ($array) {
                return $q->doesntHave('orderPurchaseItems');
            })
            ->when(isset($array['sub_category_id']), function ($q) use ($array) {
                return $q->where('sub_category_id', $array['sub_category_id']);
            })
            ->when(isset($array['name']), function ($q) use ($array) {
                return $q->where('name', 'like', '%' . $array['name'] . '%');
            })->get();
    }
    public function filterStock($array)
    {
//        FirstStockItem::where()

    }


    public function getExcept($exceptedArray)
    {
        return $this->model->whereNotIn('type',$exceptedArray)->get();
    }

    public function getStocksSetting()
    {
        return $this->stockSetting->getSetting();
    }
}
