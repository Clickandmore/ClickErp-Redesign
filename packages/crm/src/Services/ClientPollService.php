<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\ClientPoll;
use App\Services\MainService;

class ClientPollService extends MainService
{

    function __construct()
    {
        $this->model = new ClientPoll();
    }

}
