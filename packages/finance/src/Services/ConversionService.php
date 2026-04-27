<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\Conversion;

class ConversionService extends MainService
{
    public function __construct(Conversion $model,protected StockService $stockService
    ,protected ItemService $itemService, protected ConversionItemService $conversionItemService
    ,protected StockSettingService $stockSettingService)
    {
        $this->model = $model;
        $this->fileFolder = 'conversions/';
        $this->files = ['file'];
    }

    public function getStocks() {
        return $this->stockService->get();
    }

    public function getConversionWithStatus($type) {
        return $this->getWhereIn('status', $type);
    }

    public function getShowPriceInConversions() {
        $stockSetting = $this->stockSettingService->first();
        if (isset($stockSetting)) {
            return $stockSetting->show_price_in_conversions;
        }
        return 1;
    }

    public function getCode()
    {
        $lastRow = $this->model->latest('code')->first();
        if ($lastRow) {
            return $lastRow->code + 1;
        } else {
            return 1;
        }
    }

    public function getItems() {
        return $this->itemService->getItems();
    }

    public function storeConversion($data) {
        $obj = $this->store($data);
        $this->conversionItemService->storeConversionItem($data, $obj->id);
        return $obj;
    }

    public function updateConversion($id,$data) {
        $update = $this->update($id,$data);
        $obj = $this->find($id);
        $obj->conversionItems()->delete();
        $this->conversionItemService->storeConversionItem($data, $obj->id);
        return $obj;
    }
}
