<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Nami\Finance\Services\HomeService;


class HomeController extends Controller
{

    public function index(HomeService $homeAction)
    {
        $data = $homeAction->index();
        return view('finance::finance.home', $data);
    }

}
