<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\ConversionItem;

class ConversionItemService extends MainService
{
    public function __construct(ConversionItem $model)
    {
        $this->model = $model;
    }

    public function storeConversionItem($data,$conversionId) {
        foreach ($data['items'] as $key => $item) {
            $item['conversion_id'] = $conversionId;
            $item['notes'] = $item['notes'] ?? "لا يوجد";
            $this->model->create($item);
        }
    }
}
