<?php

namespace Nami\Hr\Http\Controllers\Employee;

use App\Http\Controllers\admin\Controller;
use Nami\Hr\Services\HomeService as HomeService;


class HomeController extends Controller
{

    public function index(HomeService $homeAction)
    {
        $data = $homeAction->profile();
        $data["dataTableRoute"] = route("employee.vacations.index");
        return view('hr.employees.home', $data);
    }

}
