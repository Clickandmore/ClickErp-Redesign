<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\ReligionAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\ReligionRequest;
use App\Models\ingaz\Religion as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReligionController extends Controller
{

    public $folderPath = "admin.ingaz.religions";
    public $postData = ["title:ar","title:en","image"];
    public $mainRoute = "religions";

    function __construct()
    {
        // $this->middleware('permission:الديانات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الديانات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الديانات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الديانات-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $rows = objModel::query()->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('is_show', function ($row) {
                    return
                        $row->is_show == 1 ? '
                        <button class="btn btn-sm btn-success update-status" id="update-status" data-id="' . $row->id . '">ظاهر</button>' :
                            '<button class="btn btn-sm btn-dark update-status" data-id="' . $row->id . '">مخفي </button>';
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::RELEGIONS_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->title);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::RELEGIONS_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.sliders');
        $data["addButtonText"] = helperTrans('api.slider');
        $data["modalType"] = "modal-lg";
        return view($this->folderPath . '.index', $data);

    }


    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(ReligionRequest $request,objAction $action)
    {
        $data = $request->only($this->postData);
        $data = $action->store($data);
        return jsonSuccess($data);
    }

    public function show($id)
    {
        $model = objModel::find($id);
        $model->is_show = !$model->is_show;
        $model->update();
        return response()->json(['success' => true]);
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

    public function update(ReligionRequest $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->find($id)->update($updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
