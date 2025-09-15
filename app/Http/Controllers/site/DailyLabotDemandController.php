<?php

namespace App\Http\Controllers\site;

use App\Http\Controllers\Controller;

class DailyLabotDemandController extends Controller
{
    public function index()
    {
        return view('site.pages.daily_labor_demand');
    }


}
