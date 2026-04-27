<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\permissionSetting\PermissionSettingRequest;
use Nami\Hr\Models\PermissionSetting as objModel;
use Nami\Hr\Services\PermissionSettingService as objService;
use Yajra\DataTables\DataTables;

class PermissionSettingController extends Controller
{
    public $folderPath = "hr::hr.permissionSettings";
    public $postData = ['number_of_hours','per_month'];
    public $mainRoute = "permissionsSettings";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objService $objService)
    {
        if ($request->ajax()) {
            $dataTable = objModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->addColumn('shifts_count', function ($row) {
                    return $row->shifts->count() ?? 0;
                })
                ->addColumn('actions', function ($row) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.permissionsSettings');
        $data["addButtonText"] = __('hr::hr.permissionsSetting');
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, objService $action)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create", [
                'shifts' => $action->getShifts(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML],null,201);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PermissionSettingRequest $request, objService $action)
    {
        $dataInsert = $request->validated();
        $data = $action->storePermissionSetting($dataInsert);
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
            $permession_setting = $action->find($id);
            // dd($action->getShifts($permession_setting->shifts->pluck('id')->toArray()));
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $permession_setting,
                'shifts' => $action->getShifts($permession_setting->shifts->pluck('id')->toArray()),
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
    public function update(PermissionSettingRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->validated();
        $data = $action->updatePermissionSetting($id, $dataInsert);
        return jsonSuccess($data,null,201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $action)
    {
        $action->deletePermissionSetting($id);
        return jsonSuccess();
    }
}
