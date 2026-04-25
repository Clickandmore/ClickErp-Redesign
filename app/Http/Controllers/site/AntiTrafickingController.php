<?php

namespace App\Http\Controllers\site;

use App\Http\Controllers\admin\Controller;

class AntiTrafickingController extends Controller
{
    public function index()
    {
        return view('site.pages.anti_trafficking');
    }


}
