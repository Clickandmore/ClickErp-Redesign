<?php

namespace App\Http\Controllers\site;

use App\Http\Controllers\Controller;

class ArrivalWorkerController extends Controller
{
    public function index()
    {
        return view('site.pages.arrival_workers', get_defined_vars());
    }


}
