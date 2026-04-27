<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\SupplierFile;

class SupplierFileService extends MainService
{
    public function __construct(SupplierFile $model)
    {
        $this->model = $model;
        $this->fileFolder = 'supplier_file/';
        $this->files = ['file'];
    }

    public function storeLawyerFile($data, $supplier_id)
    {
        if (isset($data['suplier_files'])) {
            foreach ($data['suplier_files'] as $item) {
                $item['supplier_id'] = $supplier_id;
                $this->store($item);
            }
        }
    }
    public function storeFiles($supplier_id, $files)
    {
        foreach ($files as $file) {
            $data = [
                'file' => $file,
                'supplier_id' => $supplier_id,
            ];
            $this->store($data);
        }
    }
}
