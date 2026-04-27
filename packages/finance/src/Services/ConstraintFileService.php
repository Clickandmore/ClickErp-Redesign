<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\ConstraintFile as Model;

class ConstraintFileService extends MainService
{

    public function __construct(
        Model $model,
    )
    {
        $this->model = $model;
        $this->fileFolder = 'constraints/';
        $this->files = ['file'];
    }

    public function storeFiles($constraint, $files)
    {
        foreach ($files as $file) {
            $data = [
                'file' => $file,
                'constraint_id' => $constraint->id,
            ];
            $this->store($data);
        }
    }
}
