<?php

namespace App\Http\Controllers\site;

use App\Http\Controllers\Controller;

class SelectWorkerController extends Controller
{
    public function index()
    {
        return view('site.pages.select_worker');
    }


}
