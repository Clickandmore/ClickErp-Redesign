<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Controllers\Controller;
use App\Models\ingaz\RecruitmentRequest;

class ProfileController extends Controller
{
    public function index()
    {
        $orders = RecruitmentRequest::where('user_id', auth()->user()->id)->whereHas('maid', function ($query) {
            $query->whereHas('jobTypes', function ($q) {
                $q->where('job_type_id', 1);
            });
        })->get();
        return view('site.pages.profile.index', compact('orders'));
    }


}
