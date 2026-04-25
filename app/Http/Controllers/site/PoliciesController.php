<?php

namespace App\Http\Controllers\site;

use App\Http\Controllers\admin\Controller;

class PoliciesController extends Controller
{
    public function index()
    {
        return view('site.pages.policies');
    }


}
