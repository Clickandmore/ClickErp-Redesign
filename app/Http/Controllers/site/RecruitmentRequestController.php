<?php

namespace App\Http\Controllers\site;

use App\Enums\Office\MaritalStatusEnum;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\site\RenewContractAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\RenewContractRequest;

class RecruitmentRequestController extends Controller
{
    public function index(NationalityAction $nationalityAction)
    {
        $nationalities = $nationalityAction->get();
        $maritalStatus = MaritalStatusEnum::cases();
        return view('site.pages.recruitment_request', get_defined_vars());
    }




}
