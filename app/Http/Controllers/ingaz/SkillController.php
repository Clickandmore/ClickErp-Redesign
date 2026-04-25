<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\SkillAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\SkillRequest;
use App\Models\ingaz\Skill as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SkillController extends Controller
{
    public $folderPath = "admin.ingaz.skills";
    public $postData = ["title:ar", "title:en"];
    public $mainRoute = "skills";

    function __construct()
    {
        // $this->middleware('permission:skills-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:skills-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:skills-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:skills-حذف,admin', ['only' => ['destroy']]);
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
                    if (@AdminGuardHasPerm(\App\PermKeys::SKILLS_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->title);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::SKILLS_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('ingaz.skills');
        $data["addButtonText"] = helperTrans('ingaz.skill');
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

    public function store(SkillRequest $request, objAction $action)
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

    public function update(SkillRequest $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->find($id)->update($updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }
}
