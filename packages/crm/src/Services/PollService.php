<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\Poll;
use App\Services\MainService;

class PollService extends MainService
{
    function __construct()
    {
        $this->model = new Poll();
    }

}
