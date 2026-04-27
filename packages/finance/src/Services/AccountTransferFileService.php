<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\AccountTransferFile as Model;

class AccountTransferFileService extends MainService
{
    public function __construct(
        Model $model,
    )
    {
        $this->model = $model;
        $this->fileFolder = 'account_transfer/';
        $this->files = ['file'];
    }

    public function storeFiles($constraint, $files)
    {
        foreach ($files as $file) {
            $data = [
                'file' => $file,
                'account_transfer_id' => $constraint->id,
            ];
            $this->store($data);
        }
    }
}
