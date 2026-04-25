<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\JobTitleAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\JobTitleRequest;
use App\Models\ingaz\JobTitle as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class JobTitleController extends Controller
{

    public $folderPath = "admin.ingaz.job_titles";
    public $postData = ["image", 'title:ar', 'title:en'];
    public $mainRoute = "job-titles";

    function __construct()
    {
        // $this->middleware('permission:المسميات الوظيفية-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:المسميات الوظيفية-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:المسميات الوظيفية-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:المسميات الوظيفية-حذف,admin', ['only' => ['destroy']]);
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
                        <button class="btn btn-sm btn-danger update-status" id="update-status" data-id="' . $row->id . '">اخفاء</button>' :
                            '<button class="btn btn-sm btn-success update-status" data-id="' . $row->id . '">اظهار </button>';
                })
                ->editColumn('image', function ($row) {
                    return getImgTag($row->image);
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::JOB_TITLES_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::JOB_TITLES_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans("ingaz.job_titles");
        $data["addButtonText"] = helperTrans("ingaz.add_job_title");
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


    public function store(JobTitleRequest $request, objAction $action)
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

    public function update(JobTitleRequest $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->updateJobTitle($id, $updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
