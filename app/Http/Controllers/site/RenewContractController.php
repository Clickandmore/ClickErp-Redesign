<?php

namespace App\Http\Controllers\site;

use App\Enums\Office\MaritalStatusEnum;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\site\RenewContractAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\site\RenewContractRequest;

class RenewContractController extends Controller
{
    public function index(NationalityAction $nationalityAction)
    {
        $nationalities = $nationalityAction->get();
        $maritalStatus = MaritalStatusEnum::cases();
        return view('site.pages.renew_contract', get_defined_vars());
    }

    public function store(RenewContractRequest $request, RenewContractAction $renewContractAction)
    {


        $data = $request->validated();
        unset($data['g-recaptcha-response']);

        $renewContractAction->storeRenewContract($data);
        return jsonSuccess(null, __("ingaz.the_customer_contract_renewal_request_has_been_successfully_submitted"));
    }


}
