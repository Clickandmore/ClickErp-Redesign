<?php

namespace App\Http\Controllers\site;

use App\Http\Controllers\admin\Controller;

class AboutUsController extends Controller
{
    public function index()  {
        return view('site.pages.laborSelection');
    }
}
