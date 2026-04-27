<?php

namespace Nami\Crm\Http\Controllers;

use Nami\Crm\Services\ClientAction;
use App\Http\Controllers\Controller;
use Nami\Crm\Services\FollowClientsCategoryService as ObjService;
use Nami\Crm\Http\Requests\FollowClientsCategoryRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Crm\Models\FollowClientCategory as ObjModel;
use Yajra\DataTables\DataTables;

class FollowClientsCategoryController extends Controller
{
    public $folderPath = "crm::crm.follow_clients_categories";
    public $mainRoute = "crm.follow_clients_categories";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = ObjModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('is_shown', function ($row) {
                    if ($row->is_shown == 1) {
                        $is_shown = "<button class='btn btn-success update-status' data-id='" . $row->id . "'>" . __("crm::crm.show") . "</button>";
                    } else {
                        $is_shown = "<button class='btn btn-danger update-status' data-id='" . $row->id . "'>" . __("crm::crm.hidden") . "</button>";
                    }
                    return $is_shown;
                })
                ->addColumn('actions', function ($row) {

                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("crm::crm.follow_clients_categories");
        $data["addButtonText"] = __("crm::crm.follow_clients_categories");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->store($dataInsert, $request);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id, ObjService $service)
    {
        $crm = $service->find($id);
        $crm->is_shown = !$crm->is_shown;
        $crm->save();
        return response()->json(['success' => true]);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
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
     * @return \Illuminate\Http\JsonResponse
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id, ObjService $service)
    {
        $check = $service->checkDelete($id);
        if ($check) {
            return jsonValid(['errors' => 'عفوا يوجد عميل مرتبط بهذا النوع']);
        }
        $service->delete($id);
        return jsonSuccess();
    }
}
