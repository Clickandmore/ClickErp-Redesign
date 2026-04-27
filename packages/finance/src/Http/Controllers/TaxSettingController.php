<?php

namespace Nami\Finance\Http\Controllers;


use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\TaxSeetingRequest as ObjRequest;
use Nami\Finance\Services\TaxSettingService as ObjService;
use Yajra\DataTables\DataTables;

class TaxSettingController extends Controller
{

    public $folderPath = "finance::finance.tax_setting";
    public $mainRoute = "finance.tax-setting";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('account', function ($row) {
                    return $row->account->name ?? "";
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = "";
                    if (!$row->hasChildrens()) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["canPermission"] = __("finance::taxSettings add");
        $data["bladeTitle"] = __("finance::finance.taxSettings");
        $data["addButtonText"] = __("finance::finance.tax");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $action)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->with([
                'storeRoute' => route($this->mainRoute . ".store"),
                'accounts' => $action->getAccounts(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(ObjRequest $request, ObjService $action)
    {
        $dataInsert = $request->validated();
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $data = $action->store($dataInsert);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $obj = $service->find($id);
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $obj,
                'updateRoute' => route($this->mainRoute . ".update", $id),
                'accounts' => $service->getAccounts(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(ObjRequest $request, $id, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->update($id, $dataInsert);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();
    }
}

