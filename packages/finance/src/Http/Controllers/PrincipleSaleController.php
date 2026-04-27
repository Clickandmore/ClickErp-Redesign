<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\PrincipleSaleRequest;
use Nami\Finance\Services\PrincipleSaleService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class PrincipleSaleController extends Controller
{
    public string $folderPath = "finance::finance.principle_sales";
    public string $mainRoute = "finance.principle-sales";

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
                ->editColumn('principle_id', function ($row) {
                    return $row->principle->name;
                })
                ->editColumn('debit_account_id', function ($row) {
                    return $row->debit_account->name;
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.principle-sales");
        $data["addButtonText"] = __("finance::finance.principle-sales");
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
            $data["principles"] = $service->getPrinciples();
            $data["debit_accounts"] = $service->getAccounts();
            $data["tax_settings"] = $service->getTaxs();
            $data["storeRoute"] = route($this->mainRoute . ".store");
            $returnHTML = view($this->folderPath . ".create",$data)->render();
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
    public function store(PrincipleSaleRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $obj = $service->store($dataInsert);
        $service->storeConstraint($obj);
        return jsonSuccess($obj);
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
            $data["principles"] = $service->getPrinciples();
            $data["debit_accounts"] = $service->getAccounts();
            $data["tax_settings"] = $service->getTaxs();
            $data["obj"] = $service->find($id);
            $data["updateRoute"] = route($this->mainRoute . ".update", $id);
            $returnHTML = view($this->folderPath . ".edit",$data)->render();
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
    public function update(PrincipleSaleRequest $request, int $id, ObjService $service)
    {
        $dataInsert = $request->validated();
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $data = $service->update($id, $dataInsert);
        $obj = $service->find($id);
        if ($obj->constraint) {
            $obj->constraint->forceDelete();
        }
        $service->storeConstraint($obj);
        return jsonSuccess($obj);
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
        $obj = $service->find($id);
        if ($obj->constraint) {
            $obj->constraint->forceDelete();
        }
        $service->delete($id);
        return jsonSuccess();
    }
}
