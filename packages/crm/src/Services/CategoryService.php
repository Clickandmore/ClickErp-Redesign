<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\Category;
use App\Services\MainService;

class CategoryService extends MainService
{

    function __construct()
    {
        $this->model = new Category();
    }


}
