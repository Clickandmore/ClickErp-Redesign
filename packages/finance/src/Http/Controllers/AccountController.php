<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\AccountRequest as ObjRequest;
use Nami\Finance\Services\AccountService as ObjService;
use Throwable;

class AccountController extends Controller
{
    public string $folderPath = "finance::finance.account";
    public string $mainRoute = "finance.accounts";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|\Illuminate\View\View
     * @throws Exception
     */
    public function index(Request $request, ObjService $service)
    {
        $treeData = $service->getAccountTree();
        $data["accountTree"] = $treeData['accounts'];
        $data["constraints"] = $treeData['constraintDetails'];
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.accounts");
        $data["addButtonText"] = __("finance::finance.account");
        return view($this->folderPath . '.index', $data);
    }


    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return $service->getCreateView($request);
        }
        return $service->getAccountTree();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ObjRequest $request
     * @param ObjService $service
     * @return JsonResponse
     */
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $data = $service->storeAccount($dataInsert);
        return jsonSuccess($data, null, 201);
    }


    public function show($id)
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
                "accountTypes" => $service->getAccountTypes(),
                "accounts" => $service->getValidParents(),
                "obj" => $service->find($id),
                "types" => $service->getAccountTypesList(),
                "operationTypes" => $service->getOperationTypesList(),
                "updateRoute" => route($this->mainRoute . ".update", $id),
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
        $dataUpdated = $request->validated();
        $getLevelParentById = $service->getLevelParentById($id);
        $getLastChildByParentID = $service->getLastChildByParentID($id);
        $data = $service->updateAccount($id, $dataUpdated, $getLevelParentById, $getLastChildByParentID);
        return jsonSuccess($data, null, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function destroy(int $id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess(null, null, 201);
    }
}
