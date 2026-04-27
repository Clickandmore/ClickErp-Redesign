<?php

namespace Nami\Finance\Services;

use App\Http\Actions\finance\AccountAction;
use Nami\Finance\MainService;
use Nami\Finance\Models\Box;
use Nami\Finance\Models\PrincipleImage;

class PrincipleImageService extends MainService
{
    public function __construct(PrincipleImage $model)
    {
        $this->model = $model;
        $this->fileFolder = 'principles/';
        $this->files = ['file'];
    }

    public function storeFiles($data, $principle_id)
    {
        if (isset($data['file'])) {
            $data['principle_id'] = $principle_id;
            foreach ($data['file'] as $key => $file) {
                $data['file'] = $file;
                $this->store($data);
            }
        }

    }
}
