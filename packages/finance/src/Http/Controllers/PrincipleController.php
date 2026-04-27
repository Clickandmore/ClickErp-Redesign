<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\PrincipleRequest as ObjRequest;
use Nami\Finance\Services\PrincipleService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class PrincipleController extends Controller
{
    public string $folderPath = "finance::finance.principles";
    public string $mainRoute = "finance.principles";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->addColumn('parent_account_id', function ($row) {
                    return $row->parent_account->name ?? "";
                })
                ->addColumn('actions', function ($row) {
                    $showButton = editHyperButton(route($this->mainRoute . ".show", $row->id), $row->name, "eye", true);
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $showButton . " " . $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.principles");
        $data["addButtonText"] = __("finance::finance.principles");
        // $data["modalType"] = "modal-fullscreen";
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
                'types' => $service->getTypes(),
                'account_perished_type' => $service->getAccountPerishedTypes(),
                'taxs' => $service->getTaxs(),
                'registration_types' => $service->getRegistrationTypes(),
                'creditor_accounts' => $service->getAccounts(),
//                'employees' => $service->getEmployees(),
                'perished_types' => $service->getPerishedTypes(),
                "principle_websites" => $service->getPrincipleWebsites(),
                'perished_accounts' => $service->getAccounts(),
                'storeRoute' => route($this->mainRoute . ".store"),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
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
        if (!$service->checkDateRange(date('Y-m-d'))) {
            return jsonValid(['error' => [__("finance::finance.the year is not exist")]], '');
        }
        $obj = $service->storePrinciple($dataInsert);
        $account = $service->storeAccount($dataInsert);
        $obj->update(['account_id' => $account->id]);
        $service->storeFiles($dataInsert, $obj->id);
        $service->storePrinciplePerished($dataInsert, $obj->id);
        $service->storeConstraint($obj);
        return jsonSuccess($obj);
    }


    public function show(int $id, Request $request, ObjService $service)
    {
        return view($this->folderPath . ".show", [
            'types' => $service->getTypes(),
            'account_perished_type' => $service->getAccountPerishedTypes(),
            'taxs' => $service->getTaxs(),
            "bladeTitle" => __("finance::finance.principles"),
            'registration_types' => $service->getRegistrationTypes(),
            'creditor_accounts' => $service->getAccounts(),
//            'employees' => $service->getEmployees(),
            'perished_types' => $service->getPerishedTypes(),
            'perished_accounts' => $service->getAccounts(),
            'principle_websites' => $service->getPrincipleWebsites(),
            'obj' => $service->find($id),
            'updateRoute' => route($this->mainRoute . ".update", $id),
        ]);
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
                'types' => $service->getTypes(),
                'account_perished_type' => $service->getAccountPerishedTypes(),
                'taxs' => $service->getTaxs(),
                'registration_types' => $service->getRegistrationTypes(),
                'creditor_accounts' => $service->getAccounts(),
//                'employees' => $service->getEmployees(),
                'perished_types' => $service->getPerishedTypes(),
                'perished_accounts' => $service->getAccounts(),
                "principle_websites" => $service->getPrincipleWebsites(),
                "obj" => $service->find($id),
                'updateRoute' => route($this->mainRoute . ".update", $id),
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
        $data = $service->update($id, $dataInsert);
        $service->updatePrinciplePerished($dataInsert, $id);
        $service->storeFiles($dataInsert, $id);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function destroy(ObjRequest $request, int $id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();
    }
}
