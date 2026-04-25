<?php

namespace App\Http\Controllers\Api;

use App\Http\Actions\Api\DailyLabotDemandAction;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\TestimonialsAction;
use App\Http\Actions\ingaz\WhyUsAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\API\DailyLabotDemandRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiDailyLabotDemandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(
        JobTitleAction $jobTitleAction,
        WhyUsAction $whyUsAction,
        TestimonialsAction $testimonialsAction
    ) {
        $jobTitles = $jobTitleAction->get();
        $whyUs = $whyUsAction->get();
        $testimonials = $testimonialsAction->get();

        return apiResponse(true, __('api.All items'), [
            'jobTitles' => $jobTitles,
            'whyUs' => $whyUs,
            'testimonials' => $testimonials,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DailyLabotDemandRequest $request, DailyLabotDemandAction $dailyLabotDemandAction)
    {
        $data = $request->validated();
        unset($data['g-recaptcha-response']);

        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        }

        $result = $dailyLabotDemandAction->store($data);

        return apiResponse(
            true,
            __("ingaz.Daily_Labot_Demand_Request_Sent_Successfully"),
            ['dailyLabotDemand' => $result],
            200
        );
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }
}
