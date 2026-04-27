<?php

namespace Nami\Hr\Http\Controllers\Employee;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Services\HomeService as HomeService;

class EmployeeProfileController extends Controller
{

    public function index(Request $request ,HomeService $homeAction)
    {
        $data = $homeAction->profile($request->user_id);
        return view('hr.employees.employee_porfile', $data);
    }

}
