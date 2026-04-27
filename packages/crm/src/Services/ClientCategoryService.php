<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\ClientCategory;
use App\Services\MainService;

class ClientCategoryService extends MainService
{
    function __construct()
    {
        $this->model = new ClientCategory();
    }

}
