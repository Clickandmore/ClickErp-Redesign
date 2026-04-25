<?php

namespace App\Http\Controllers\site;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\RecruitmentRequest;
use Illuminate\Http\Request;

class RecruitmentOrderController extends Controller
{
    public function show($id, Request $request)
    {
        $order = RecruitmentRequest::find($id);
        return view('site.pages.order_details', get_defined_vars());
    }
}
