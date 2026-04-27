<?php

namespace Nami\Crm\Services;

use App\Enums\finance\AccountTypiesEnum;
use App\Http\Actions\finance\AccountAction;
use Nami\Crm\Models\Area;
use Nami\Crm\Models\Category;
use Nami\Crm\Models\Client;
use Nami\Crm\Models\SocialContact;
use App\Models\Nami\Admin;
use App\Services\Finance\AccountService;
use App\Services\MainService;

class ClientService extends MainService
{

    function __construct()
    {
        $this->model = new Client();
    }

    public function getSocialContacts() {
        return SocialContact::where('is_shown', 1)->get();
    }

    public function getCities() {
        return Area::whereNull('parent_id')->get();
    }

    public function getAreas() {
        return Area::whereNotNull('parent_id')->get();
    }

    public function getUsers() {
        return Admin::get();
    }

    public function getCategories() {
        return Category::where('is_shown', 1)->get();
    }




}
