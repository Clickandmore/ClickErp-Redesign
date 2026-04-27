<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\Sanction\EditSanctionRequest;
use Nami\Hr\Http\Requests\Sanction\SanctionRequest;
use Nami\Hr\Models\Sanction as objModel;
use Nami\Hr\Services\SanctionService as objService;
use Yajra\DataTables\DataTables;

class SanctionController extends Controller
{
    public $folderPath = "hr::hr.sanctions";
    public $postData = ['title','type','value','is_delays'];
    public $mainRoute = "sanctions";
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
                ->editColumn('title', function ($row) {
                    return $row->title;
                })
                ->editColumn('is_delays', function ($row) {
                    return ($row->is_delays == 1 ? 'نعم'  : 'لا');
                })
                ->editColumn('typy_name', function ($row) {
                    return getHrEnumValue("allowance_typeis", $row->type);
                })
                ->editColumn('value', function ($row) {
                    return $row->value;
                })
                ->addColumn('actions', function ($row) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions','typy_name','value'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.sanctions');
        $data["addButtonText"] = __('hr::hr.sanction');
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
            $returnHTML = view($this->folderPath . ".create")->with(
                "allowance_typeis", $action->getSanctionTypies())->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SanctionRequest $request, objService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeSanction($dataInsert,$request);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, ObjService $action)
    {
        $sanction = $action->find($id);
        return response()->json(['type' => $sanction->type, 'value' => $sanction->value]);
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
                "allowance_typeis" => $action->getSanctionTypies(),
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
    public function update(EditSanctionRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateSanction($id, $dataInsert,$request);
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
        $action->deleteSanction($id);
        return jsonSuccess();
    }}
