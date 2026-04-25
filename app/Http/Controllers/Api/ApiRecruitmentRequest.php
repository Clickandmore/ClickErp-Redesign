<?php

namespace App\Http\Controllers\Api;

use App\Enums\Office\MaritalStatusEnum;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\ReligionAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\API\RecruitmentRequestRequest;
use App\Models\Country;
use App\Models\ingaz\RecruitmentRequestMaid;

class ApiRecruitmentRequest extends Controller
{
    public function index(NationalityAction $nationalityAction, JobTitleAction $jobTitleAction, ReligionAction $religionAction)
    {
        $user = auth()->user();
        $nationalities = $nationalityAction->get();
        $maritalStatus = MaritalStatusEnum::cases();
        $userCountry = Country::where('name_ar', $user->city)->orWhere('name_en', $user->city)->first();
        $countries = Country::get();
        $jobTitles = $jobTitleAction->get();
        $religions = $religionAction->get();

        $data = [
            'nationalities' => $nationalities,
            'maritalStatus' => $maritalStatus,
            'userCountry' => $userCountry,
            'countries' => $countries,
            'jobTitles' => $jobTitles,
            'religions' => $religions,
        ];

        return apiResponse(true, __('api.All items'), $data, 200);
    }

    public function store(RecruitmentRequestRequest $request)
    {
        $dataInserted = $request->validated();
        unset($dataInserted['g-recaptcha-response']);
        $dataInserted['client_id'] = auth()->user()->id;
        RecruitmentRequestMaid::create($dataInserted);

        return apiResponse(true, __("ingaz.Recruitment_Request_Sent_Successfully"), [], 201);
    }
}
