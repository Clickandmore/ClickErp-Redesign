<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\Shift\EditShiftRequest;
use Nami\Hr\Http\Requests\Shift\ShiftRequest;
use Nami\Hr\Models\Shift as objModel;
use Nami\Hr\Services\ShiftService as objService;
use Yajra\DataTables\DataTables;

class ShiftController extends Controller
{
    public $folderPath = "hr::hr.shifts";
    public $postData = ['name','from_hour','to_hour','month_days','absence_sanctions_id'];
    public $mainRoute = "shifts";
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $dataTable = objModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return $row->name;
                })
                ->editColumn('from_hour', function ($row) {
                    return $row->from_hour;
                })
                ->editColumn('to_hour', function ($row) {
                    return $row->to_hour;
                })
                ->editColumn('month_days', function ($row) {
                    return $row->month_days;
                })
                ->editColumn('absence_sanctions_id', function ($row) {
                    return $row->sanctions->title;
                })
                ->addColumn('status', function ($row) {
                    return updateStatusButton('hr_shifts',$row->status, $row->id);
                })
                ->addColumn('actions', function ($row) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions','absence_sanctions_id','status'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.shifts');
        $data["addButtonText"] = __('hr::hr.shifts');
        $data["modalType"] = "modal-lg";
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
            'sanctions' => $action->getSanction(),
            'allSanction' => $action->getAllSanction(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ShiftRequest $request, objService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeShift($dataInsert,$request);
        return jsonSuccess($data);
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
    public function edit($id, Request $request, ObjService $action)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $action->find($id),
                'sanctions' => $action->getSanction(),
                'allSanction' => $action->getAllSanction(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(EditShiftRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateShift($id, $dataInsert,$request);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $action)
    {
        $action->deleteShift($id);
        return jsonSuccess();
    }
}
