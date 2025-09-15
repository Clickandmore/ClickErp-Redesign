<?php

namespace App\Http\Controllers\site;

use App\Enums\Office\MaritalStatusEnum;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Controllers\Controller;

class RenewContractController extends Controller
{
    public function index(NationalityAction $nationalityAction)
    {
        $nationalities = $nationalityAction->get();
        $maritalStatus = MaritalStatusEnum::cases();
        return view('site.pages.renew_contract', get_defined_vars());
    }
    public function store()
    {

    }


}
