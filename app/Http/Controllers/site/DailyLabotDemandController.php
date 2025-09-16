<?php

namespace App\Http\Controllers\site;

use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\TestimonialsAction;
use App\Http\Actions\ingaz\WhyUsAction;
use App\Http\Actions\site\DailyLabotDemandAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\DailyLabotDemandRequest;

class DailyLabotDemandController extends Controller
{
    public function index(JobTitleAction $jobTitleAction, WhyUsAction $whyUsAction, TestimonialsAction $testimonialsAction)
    {
        $jobTitles = $jobTitleAction->get();
        $whyUs = $whyUsAction->get();
        $testimonials = $testimonialsAction->get();
        return view('site.pages.daily_labor_demand', get_defined_vars());
    }

    public function store(DailyLabotDemandRequest $request, DailyLabotDemandAction $dailyLabotDemandAction)
    {
        $data = $request->validated();
        $dailyLabotDemandAction->store($data);
        return jsonSuccess(null, __("ingaz.Daily_Labot_Demand_Request_Sent_Successfully"));
    }


}
