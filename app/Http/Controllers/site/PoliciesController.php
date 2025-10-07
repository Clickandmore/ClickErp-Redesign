<?php

namespace App\Http\Controllers\site;

use App\Http\Controllers\Controller;

class PoliciesController extends Controller
{
    public function index()
    {
        return view('site.pages.policies');
    }


}
