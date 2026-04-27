<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\SocialContact;
use App\Services\MainService;

class SocialContactService extends MainService
{

    function __construct()
    {
        $this->model = new SocialContact();
    }


}
