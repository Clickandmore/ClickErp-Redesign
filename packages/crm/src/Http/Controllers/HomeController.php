<?php

namespace Nami\Crm\Http\Controllers;

use Nami\Crm\Services\HomeService as HomeService;
use App\Http\Controllers\Controller;


class HomeController extends Controller
{

    public function index(HomeService $homeAction)
    {
        $data = $homeAction->indexCRM();
        return view('crm::crm.home', $data);
    }

}
