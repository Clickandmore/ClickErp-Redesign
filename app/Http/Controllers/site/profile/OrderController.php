<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Controllers\Controller;
use App\Models\ingaz\RecruitmentRequest;

class OrderController extends Controller
{
    public function index()
    {
        $orders = RecruitmentRequest::where('user_id', auth()->user()->id)->get();
        return view('site.pages.profile.last_orders', get_defined_vars());
    }


}
