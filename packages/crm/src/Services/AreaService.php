<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\Area;
use App\Services\MainService;

class AreaService extends MainService
{
    function __construct()
    {
        $this->model = new Area();
    }

    public function getCities()
    {
        return Area::whereNull('parent_id')->get();
    }

}
