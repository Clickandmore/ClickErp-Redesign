<?php

namespace App\Http\Controllers\site;

use App\Http\Controllers\admin\Controller;

class MusanedController extends Controller
{
    public function index()
    {
        return view('site.pages.musand');
    }


}
