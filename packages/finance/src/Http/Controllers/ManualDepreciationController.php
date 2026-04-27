<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\ManualDepreciationRequest as ObjRequest;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\DepreciationPrincipleService as ObjService;
use Nami\Finance\Services\PrincipleSaleService;
use Nami\Finance\Services\PrincipleService as ObjPrincipleService;
use Yajra\DataTables\DataTables;

class ManualDepreciationController extends Controller
{
    public $folderPath = "finance::finance.manual_depreciations";
    public $postData = ["value", "date", "statement", "debtor_value", "creditor_value", "statement_account", "account_id", 'cost_center_id', 'tax_settings_id', 'tax_value'];
    public $mainRoute = "finance.manual-depreciations";

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request, ObjService $service)
    {
        $dataTable = $service->getDataTable();
        if ($request->ajax()) {
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('principle_id', function ($row) {
                    return $row->principle->name ?? "";
                })->editColumn('value', function ($row) {
                    return $row->constraint->value ?? "";
                })->editColumn('date', function ($row) {
                    return $row->constraint->date ?? "";
                })
                ->addColumn('actions', function ($row) {
                    $button = editHyperButton(route($this->mainRoute . ".show", $row->id), $row->name, "eye", true) . " " .
                        editButton(route($this->mainRoute . ".edit", $row->constraint_id), $row->name) . " " .
                        deleteButton(route($this->mainRoute . ".destroy", $row->constraint_id));

                    return $button;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.manual-depreciations");
        $data["addButtonText"] = __("finance::finance.manual-depreciations");
        $data['request'] = $request;
        return view($this->folderPath . '.index', $data);
    }

    public function create(Request $request, ObjPrincipleService $PrincipleService, PrincipleSaleService $principleSaleService)
    {
        if ($request->ajax()) {
            $data["storeRoute"] = route($this->mainRoute . ".store");
            $data["principles"] = $PrincipleService->getPrinciplesByPerishedTypes();
            $data["depreciationPrincipleIds"] = $principleSaleService->getDepreciationPrincipleIds();
            $returnHTML = view($this->folderPath . ".create", $data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ObjRequest $request, ObjPrincipleService $PrincipleService)
    {
        // dd($request->all());
        $dataInsert = $request->validated();
        if (!$PrincipleService->checkDateRange(date('Y-m-d'))) {
            return jsonValid(['error' => [__("finance.the year is not exist")]], '');
        }
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $constraint = $PrincipleService->storeManualDepreciationConstraint($dataInsert);
        $PrincipleService->storeDepreciationPrinciple($dataInsert, $constraint->id);
        return jsonSuccess($constraint);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Application|Factory|\Illuminate\Foundation\Application|View
     */
    public function show(Request $request, $id, ObjService $service, ObjPrincipleService $PrincipleService)
    {
        $menualDeprectiation = $service->find($id);
        return view($this->folderPath . ".show", [
            'obj' => $menualDeprectiation->principle,
            'employees' => $PrincipleService->getEmployees(),
            'registration_types' => $PrincipleService->getRegistrationTypes(),
            'creditor_accounts' => $PrincipleService->getAccounts(),
            'perished_types' => $PrincipleService->getPerishedTypes(),
            'perished_accounts' => $PrincipleService->getAccounts(),
            'principle_websites' => $PrincipleService->getPrincipleWebsites(),
            'bladeTitle' => __("finance.manual-depreciations"),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id, Request $request, ObjPrincipleService $PrincipleService, ConstraintService $constraintService)
    {
        if ($request->ajax()) {
            $data['obj'] = $constraintService->find($id);
            $data["updateRoute"] = route($this->mainRoute . ".update", $id);
            $data["principles"] = $PrincipleService->getPrinciplesByPerishedTypes();
            $returnHTML = view($this->folderPath . ".edit")->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ObjRequest $request, $id, ObjPrincipleService $PrincipleService)
    {
        $dataInsert = $request->validated();
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $data = $PrincipleService->updateManualDepreciationConstraint($id, $dataInsert);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id, ObjService $service, ConstraintService $PrincipleService)
    {
        $depreciationPrinciple = $service->getWhereFirst(['constraint_id' => $id]);
        // dd($depreciationPrinciple);
        if (isset($depreciationPrinciple->id)) {
            $depreciationPrinciple->delete();
        }

        $PrincipleService->delete($id);
        return jsonSuccess();
    }
}
