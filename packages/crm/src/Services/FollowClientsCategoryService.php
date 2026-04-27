<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\FollowClient;
use Nami\Crm\Models\FollowClientCategory;
use App\Services\MainService;


class FollowClientsCategoryService extends MainService
{
    function __construct()
    {
        $this->model = new FollowClientCategory();
    }

    public function checkDelete($id)
    {
        return FollowClient::where('follow_clients_category_id',$id)->count();
    }

}
