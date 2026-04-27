<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Http\Requests\Bond\TazherRequest;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\CatchCheckService as objService;
use Nami\Finance\Services\TazherCheckService as tazherService;

class TazherCheckController extends Controller
{
    public string $folderPath = "finance::finance.tazher-checks";
    public string $mainRoute = "finance.tazher-check";

    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }


    public function edit(string $id,request $request,objService $service,BondService $bondService)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "bond_obj" => $service->find($id)->bond,
                "check_obj" => $service->find($id),
                "lastId" => $bondService->lastCode(BondTypes::Qabd->value),
                "payment_type" => BondPaymentTypes::BankTransfer->value,
                "accounts" => $bondService->getAccounts(),
                'route' => route($this->mainRoute . ".update", $id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function update(TazherRequest $request, string $id,tazherService $tazherService)
    {
        $data = $request->validated();
        $tazherService->makeTazher($id,$data);
        return jsonSuccess();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
