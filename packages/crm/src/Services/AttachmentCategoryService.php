<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\AttachmentCategory;
use App\Services\MainService;

class AttachmentCategoryService extends MainService
{

    function __construct()
    {
        $this->model = new AttachmentCategory();
    }


}
