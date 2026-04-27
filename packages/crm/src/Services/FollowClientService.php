<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\Client;
use Nami\Crm\Models\FollowClient;
use App\Services\MainService;


class FollowClientService extends MainService
{

    function __construct()
    {
        $this->model = new FollowClient();
    }

    public function getClients() {
        return Client::where('status', 1)->get();
    }

}
