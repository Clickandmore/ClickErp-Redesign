<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\makeTransferRequest;
use App\Models\Contract;
use App\Models\ingaz\User;
use Illuminate\Http\Request;

class SponsorshipTransfer extends Controller
{
    public $folderPath = "admin.ingaz.SponsorshipTransfer";
    public $mainRoute = "SponsorshipTransfer";


    public function index()
    {

    }


    public function create()
    {
        // نقل الكفالة بتتعمل للعقود الخاصة بالاستقدام
        $contracts = Contract::RecruitmentContract()
            ->where('status', '!=', ContractStatusEnum::Back->value)
            ->where('status', '!=', ContractStatusEnum::Shelter->value)
            ->latest()
            ->get();

        $clients = User::select('id','name')->latest()->get();

        return view($this->folderPath . '.create', compact('contracts','clients'));
    }


    public function store(makeTransferRequest $request)
    {
        $contract = Contract::find($request->contract_id);
        $contract->user_id = $request->new_client_id;
        $contract->save();
        return jsonSuccess();
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
