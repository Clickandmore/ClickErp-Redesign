<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\RequirementEnum;
use App\Http\Actions\ingaz\SelectWorkerSettingsAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\SelectWorkerSettingsRequest as objRequest;
use App\Models\SelectSetting as objModel;
use Illuminate\Http\Request;

class SelectWorkerSettingsController extends Controller
{

    public $folderPath = "admin.ingaz.select_worker_settings";
    public $mainRoute = "select-workers-settings";

    function __construct()
    {
        // $this->middleware('permission:عنا-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:عنا-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:عنا-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:عنا-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request, objAction $action)
    {

        $obj = $action->first();
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.Features');
        $data["addButtonText"] = helperTrans('api.Features');
        return view($this->folderPath . '.index', compact('data','obj'));
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
        $data = $action->storeSelectSettings($data);
        return jsonSuccess($data, null, 201);

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
        $data = $action->updateSelectSettings($id, $updatedData);
        return jsonSuccess($data, null, 201);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
