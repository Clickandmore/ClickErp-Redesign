<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Nami\Crm\Http\Requests\AreaRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Crm\Models\Area as ObjModel;
use Nami\Crm\Services\AreaService as ObjService;
use Yajra\DataTables\DataTables;


class AreaController extends Controller
{
    public $folderPath = "crm::crm.areas";
    public $mainRoute = "crm.areas";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = ObjModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('city_id', function ($row) {
                    return @$row->city->title;
                })
                ->addColumn('actions', function ($row) {

                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = ($row->areas()->count() == 0)? deleteButton(route($this->mainRoute . ".destroy", $row->id)) : '';
                    return $editButton . " " . $deleteButton;
                })
                ->rawColumns(['actions', 'is_shown'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('crm::crm.areas');
        $data["addButtonText"] = __('crm::crm.areas');
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $data['cities'] = $service->getCities();
            $data['storeRoute'] = route($this->mainRoute . ".store");
            $returnHTML = view($this->folderPath . ".create",$data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->store($dataInsert);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, ObjService $service)
    {

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
            $data['cities'] = $service->getCities();
            $data['updateRoute'] = route($this->mainRoute . ".update", $id);
            $data['obj'] = $service->find($id);
            $returnHTML = view($this->folderPath . ".edit")->with($data)->render();
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
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function destroy($id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();
    }
}
