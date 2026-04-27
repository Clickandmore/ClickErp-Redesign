<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\DoubleAccountRequest as ObjRequest;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\DoubleAccountService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;


class DoubleAccountController extends Controller
{
    public string $folderPath = "finance::finance.double_accounts";
    public string $mainRoute = "finance.double-account";


    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->addColumn('accounts', function ($row) {
                    return editButton(route($this->mainRoute . ".show", $row->id), $row->name,'eye',true);
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions','accounts'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.double_accounts");
        $data["addButtonText"] = __("finance::finance.double_accounts");
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return JsonResponse
     */
    public function create(Request $request,AccountService $accountService)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create", [
                'storeRoute' => route($this->mainRoute . ".store"),
                'accounts' => $accountService->getAccountWithNoChild()
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->storeDoubleAccount($dataInsert);
        return jsonSuccess($data);
    }

    public function show(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".show")->with([
                "obj" => $service->find($id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     * @throws Throwable
     */
    public function edit(int $id, Request $request, ObjService $service,AccountService $accountService)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                'updateRoute' => route($this->mainRoute . ".update", $id),
                "obj" => $service->find($id),
                'accounts' => $accountService->getAccountWithNoChild(),
                'accountIds' => $service->getAccountIds($id),

            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ObjRequest $request
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function update(ObjRequest $request, int $id, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->updateDoubleAccount($id, $dataInsert);
        return jsonSuccess($data);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();
    }
}
