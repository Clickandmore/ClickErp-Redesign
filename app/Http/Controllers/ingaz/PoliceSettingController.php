<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\PoliceSettingAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\PoliceSettingRequest as objRequest;
use App\Models\PoliceSetting as objModel;
use Illuminate\Http\Request;


class PoliceSettingController extends Controller
{

    public $folderPath = "admin.ingaz.police_settings";
    public $postData = ["title_1:ar", "title_1:en", "title_2:ar", "title_2:en", "description_1:ar", "description_1:en", "description_2:ar", "description_2:en", "file_workers_rights", "file_employee_rights"];
    public $mainRoute = "police-settings";

    public function index(Request $request, objAction $Action)
    {
        $policeSetting = objModel::first();
        return view('admin.ingaz.police_settings.index', compact('policeSetting'));
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
        if (isset($data['file_workers_rights'])) {
            $data['file_workers_rights'] = uploadFile($data['file_workers_rights'], 'images/polices_settings/', 1);
        }
        if (isset($data['file_employee_rights'])) {
            $data['file_employee_rights'] = uploadFile($data['file_employee_rights'], 'images/polices_settings/', 1);
        }
        $data = $action->store($data);
        return jsonSuccess($data, null, 201);
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
        $dataInsert = $request->only($this->postData);
        if (isset($dataInsert['file_workers_rights'])) {
            $dataInsert['file_workers_rights'] = uploadFile($dataInsert['file_workers_rights'], 'images/polices_settings/', 1);
        }
        if (isset($dataInsert['file_employee_rights'])) {
            $dataInsert['file_employee_rights'] = uploadFile($dataInsert['file_employee_rights'], 'images/polices_settings/', 1);
        }
        $action->find($id)->update($dataInsert);
        return jsonSuccess(null, null, 201);
    }

    public function destroy($id, objAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }
}
