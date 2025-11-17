<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\Client;
use App\Models\Feature;
use App\Models\Requirement;
use App\Models\WhatDo;
use App\Models\WhatDoItem;

class WhatDoItemAction extends MainAction
{
    public function __construct(WhatDoItem $model)
    {
        $this->model = $model;
        $this->files = [''];
        $this->fileFolder = '';
    }

    public function storeItems($data, $what_do)
    {
        foreach ($data as $item) {
            $item['what_do_id'] = $what_do->id;
            $this->store($item);
        }
        return true;
    }

}
