<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\RequirementEnum;
use App\Http\Actions\ingaz\SelectWorkerAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\SelectWorkerRequest as objRequest;
use App\Models\SelectWorker as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SelectWorkerController extends Controller
{

    public $folderPath = "admin.ingaz.select_workers";
    public $mainRoute = "select-workers";

    function __construct()
    {
        // $this->middleware('permission:عنا-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:عنا-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:عنا-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:عنا-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $rows = objModel::query()->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('icon', function ($row) {
                    if ($row->icon) {
                        return '<i class="' . $row->icon . '"></i>';
                    }
                    return '<span class="text-muted">No Image</span>';
                })->editColumn('title', function ($row) {
                    return $row->title;
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->title);

                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.Features');
        $data["addButtonText"] = helperTrans('api.Features');
        $data['count'] = objModel::count();
        return view($this->folderPath . '.index', compact('data'));
    }


    public function create(Request $request)
    {
        if ($request->ajax()) {
            $enums = RequirementEnum::cases(); // هنا بترجع كل القيم بتاعة الـ enum
            $returnHTML = view($this->folderPath . ".create", compact('enums'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(objRequest $request, objAction $action)
    {
        $data = $request->validated();
        $data = $action->store($data);
        return jsonSuccess($data);

    }


    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $enums = RequirementEnum::cases(); // هنا بترجع كل القيم بتاعة الـ enum
            $obj = objModel::findorfail($id);
            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj, 'enums' => $enums])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(objRequest $request, objAction $action, $id)
    {
        $updatedData = $request->validated();
        $data = $action->find($id)->update($updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
