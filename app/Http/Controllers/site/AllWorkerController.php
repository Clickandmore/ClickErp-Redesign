<?php

namespace App\Http\Controllers\site;

use App\Http\Controllers\Controller;

class AllWorkerController extends Controller
{
    public function index()
    {
        return view('site.pages.all_workers');
    }


}
