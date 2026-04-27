<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Nami\Hr\Services\HomeService as HomeService;


class HomeController extends Controller
{

    public function index(HomeService $homeAction)
    {
        $data = $homeAction->index();
//        return $data;
//        dd($data);
        return view('hr.home', $data);
    }

}
