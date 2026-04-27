<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\BankAccountRequest;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\BankAccountService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class BankAccountController extends Controller
{
    public string $folderPath = "finance::finance.bank_accounts";
    public string $mainRoute = "finance.bank_accounts";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service)
    {

        if ($request->ajax()) {
            $dataTable = $service->getBankDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->addColumn('bank', function ($row) {
                    return $row->bank->name;
                })
                ->addColumn('code', function ($row) {
                    return $row->account->code ?? '--';
//                    return \DB::table('fn_accounts')->where('id', $row->account_id)->first()->code;
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.banksAccounts");
        $data["addButtonText"] = __("finance::finance.banksAccount");
        // $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    /**
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->with([
                'storeRoute' => route($this->mainRoute . ".store"),
                'banks' => $service->getBanks()
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *

     */
    public function store(BankAccountRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $bank = $service->store($dataInsert);
        return jsonSuccess($bank);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function show(int $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function edit(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $service->find($id),
                'banks' => $service->getBanks(),
                'updateRoute' => route($this->mainRoute . ".update", $id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(BankAccountRequest $request, int $id, ObjService $service,AccountService $accountAction)
    {
        $dataInsert = $request->validated();
        $data = $service->update($id, $dataInsert);
        $bank = $service->find($id);
        $service->updateAccountName($bank->account_id, $dataInsert, $accountAction);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function destroy(int $id, ObjService $service,BankAccountRequest $request)
    {
        $service->delete($id);
        return jsonSuccess();
    }
}
