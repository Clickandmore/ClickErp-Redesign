<?php

namespace App\Http\Controllers\Api;

use App\Enums\Office\MaritalStatusEnum;
use App\Http\Actions\Api\RenewContractAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\API\RenewContractRequest;
use App\Models\ingaz\Nationality;
use App\Models\RenewContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiRenewContractsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(NationalityAction $nationalityAction)
    {
        // $nationalities = $nationalityAction->get();
        $nationalities = Nationality::all();
        $maritalStatus = MaritalStatusEnum::cases();
        $renewContracts = RenewContract::with('nationality')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        $data = [
            'nationalities' => $nationalities,
            'maritalStatus' => $maritalStatus,
            'renewContracts' => $renewContracts,
        ];

        return apiResponse(true, __('api.All items'), $data, 200);
    }

    public function page()
    {
        $nationalities = Nationality::all();
        $maritalStatus = MaritalStatusEnum::cases();

        $data = [
            'nationalities' => $nationalities,
            'maritalStatus' => $maritalStatus,
        ];

        return apiResponse(true, __('api.All items'), $data, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RenewContractRequest $request, RenewContractAction $renewContractAction)
    {
        $data = $request->validated();
        unset($data['g-recaptcha-response']);

        $result = $renewContractAction->storeRenewContract($data);

        return apiResponse(true, __("ingaz.the_customer_contract_renewal_request_has_been_successfully_submitted"), [], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
