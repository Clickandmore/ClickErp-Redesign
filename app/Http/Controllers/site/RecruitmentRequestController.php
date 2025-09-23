<?php

namespace App\Http\Controllers\site;

use App\Enums\Office\MaritalStatusEnum;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\ReligionAction;
use App\Http\Actions\site\RenewContractAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\RenewContractRequest;
use App\Http\Requests\site\RecruitmentRequestRequest;
use App\Models\Country;
use App\Models\ingaz\RecruitmentRequestMaid;
use Illuminate\Http\Request;

class RecruitmentRequestController extends Controller
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
        return view('site.pages.recruitment_request', get_defined_vars());
    }

    public function store(RecruitmentRequestRequest $request)
    {
        $dataInserted = $request->validated();
        // dd($dataInserted);
        unset($dataInserted['g-recaptcha-response']);
        $dataInserted['client_id'] = auth()->user()->id;
        RecruitmentRequestMaid::create($dataInserted);
        return jsonSuccess(null, __("ingaz.Recruitment_Request_Sent_Successfully"));
    }


}
