<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\ServiceAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\ServiceRequest as objRequest;
use App\Models\ingaz\Service as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ServiceController extends Controller
{

    public $folderPath = "admin.ingaz.services";
    public $postData = ["image", 'title:ar', 'title:en', "description:ar", "description:en"];
    public $mainRoute = "services";

    function __construct()
    {
        // $this->middleware('permission:الخدمات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الخدمات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الخدمات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الخدمات-حذف,admin', ['only' => ['destroy']]);
    }


    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $rows = objModel::query()->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('image', function ($row) {
                    return getImgTag($row->image);
                })
                ->editColumn('title', function ($row) {
                    return "<span title='" . $row->title . "'>" . Str::limit($row->title, 30) . "</span>";
                })
                ->editColumn('description', function ($row) {
                    return "<span title='" . $row->description . "'>" . Str::limit($row->description, 30) . "</span>";
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::SERVICES_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->title);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::SERVICES_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.services');
        $data["addButtonText"] = helperTrans('api.service');
        $data["modalType"] = "modal-lg";
        $data['count'] = objModel::count();
        return view($this->folderPath . '.index', compact('data'));
    }


    public function create(Request $request)
    {
        if ($request->ajax() ) {
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
        $data = $action->updateService($id, $updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
