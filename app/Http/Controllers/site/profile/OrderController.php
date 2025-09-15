<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function index()
    {
        return view('site.pages.profile.last_orders');
    }


}
