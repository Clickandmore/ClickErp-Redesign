<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\YearRequest;
use Nami\Hr\Services\VacationYearService as ObjService;
use Yajra\DataTables\DataTables;

class VacationYearController extends Controller
{
    public string $folderPath = "hr::hr.vacation-years";
    public string $mainRoute = "vacation-year";


    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->addColumn('actions', function ($row) {
                    $editButton = "";
                    if ($row->status == 0) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    }
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    $toggleStatusButton = $row->status == 1 ?
                        '<button class="btn btn-success status-toggle" data-id="'.$row->id.'" data-status="0">'.__("hr::hr.Enable").'</button>' :
                        '<button class="btn btn-danger status-toggle" data-id="'.$row->id.'" data-status="1">'.__("hr::hr.Disable").'</button>' ;

                    return $editButton . " " . $deleteButton . " " . $toggleStatusButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("hr::hr.year_id_vacation");
        $data["addButtonText"] = __("hr::hr.year_id_vacation");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->with([
                'storeRoute' => route($this->mainRoute . ".store"),
                'checkYear'=> $service->getActiveYear()
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function store(YearRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $exists = $service->isDateRangeOverlap($dataInsert);
        if ($exists) {
            return jsonValid( ['error'=>[__("finance.The specified time period overlaps with an existing period in the same year.")]],'');
        }
        $obj = $service->store($dataInsert);
        return jsonSuccess($obj);
    }


    public function show(Request $request, $id, ObjService $service)
    {
        //
    }

    public function edit(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $service->find($id),
                'updateRoute' => route($this->mainRoute . ".update", $id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(YearRequest $request, int $id, ObjService $service)
    {
        $dataInsert = $request->validated();
        $exists = $service->isDateRangeOverlapForUpdate($dataInsert, $id);
        if ($exists) {
            return jsonValid( ['error'=>[__("finance.The specified time period overlaps with an existing period in the same year.")]],'');
        }
        $data = $service->update($id, $dataInsert);
        return jsonSuccess($data);
    }


    public function destroy(int $id, ObjService $service)
    {
        $year = $service->find($id);
        if ($year->vacationBalances->count() > 1) {
            return jsonValid( ['error'=>[__("finance.It is not allowed to delete this item because it is linked to other items")]],'');
        }
        $service->delete($id);
        return jsonSuccess();

    }
}
