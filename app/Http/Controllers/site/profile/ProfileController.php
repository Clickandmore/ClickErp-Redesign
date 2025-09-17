<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Controllers\Controller;
use App\Models\ingaz\RecruitmentRequest;

class ProfileController extends Controller
{
    public function index()
    {
        $orders = RecruitmentRequest::where('user_id', auth()->user()->id)->get();
        return view('site.pages.profile.index', compact('orders'));
    }


}
