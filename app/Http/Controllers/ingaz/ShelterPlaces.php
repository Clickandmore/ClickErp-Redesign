<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\ShelterPlaceAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\ShelterPlaceRequest as objRequest;
use App\Models\ShelterPlaces as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ShelterPlaces extends Controller
{
    public $folderPath = "admin.ingaz.shelter-places";
    public $postData = ['title:ar', 'title:en'];
    public $mainRoute = "shelter-places";

    function __construct()
    {
        // $this->middleware('permission:مقرات الايواء-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:مقرات الايواء-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:مقرات الايواء-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:مقرات الايواء-حذف,admin', ['only' => ['destroy']]);
    }


    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rows = objModel::query()->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return ($row->created_at->format('Y-m-d h:i A'));
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_PLACES_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_PLACES_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans("ingaz.shelter_places");
        $data["addButtonText"] = helperTrans("ingaz.shelter_places");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);

    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(objRequest $request, objAction $action)
    {
        $data = $request->only($this->postData);
        $data = $action->store($data);
        return jsonSuccess($data);
    }


    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = objModel::findorfail($id);
            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(objRequest $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->updateRec($id, $updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
