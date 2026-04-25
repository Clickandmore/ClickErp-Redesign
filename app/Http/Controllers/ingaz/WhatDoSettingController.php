<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\RequirementEnum;
use App\Http\Actions\ingaz\WhatDoAction as objAction;
use App\Http\Actions\ingaz\WhatDoSettingAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\WhatDoSettingRequest as objRequest;
use App\Models\WhatDo as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class WhatDoSettingController extends Controller
{

    public $folderPath = "admin.ingaz.what_do_setting.";
    public $mainRoute = "what-do-setting";

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
                ->editColumn('image', function ($row) {
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
        $data["bladeTitle"] = helperTrans('ingaz.what_do');
        $data["addButtonText"] = helperTrans('ingaz.what_do');
        $data['count'] = objModel::count();
        return view($this->folderPath . '.index', compact('data'));
    }


    public function create(Request $request, WhatDoSettingAction $whatDoSettingAction)
    {
        if ($request->ajax()) {
            $obj = $whatDoSettingAction->first();
            if (isset($obj)) {
                $returnHTML = view($this->folderPath . ".edit")->with(['obj' => $obj])->render();
            } else {
                $returnHTML = view($this->folderPath . ".create")->render();
            }

            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(objRequest $request, WhatDoSettingAction $action)
    {
        $data = $request->validated();
        $obj = $action->store($data);
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

    public function update(objRequest $request, WhatDoSettingAction $action, $id)
    {
        $updatedData = $request->validated();
        $obj = $action->find($id);
        $data = $obj->update($updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
