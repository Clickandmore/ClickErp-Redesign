<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\SaleAttachment;

class SaleAttachmentService extends MainService
{
    public function __construct(SaleAttachment $model)
    {
        $this->model = $model;
        $this->fileFolder = 'SaleAttachment/';
        $this->files = ['file'];
    }

    public function storeAttachments($id, $data)
    {
        foreach ($data['file'] as $item) {
            $this->store([
                'sale_id' => $id,
                'file' => $item,
            ]);
        }
        return true;
    }
}
