<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\RequirementEnum;
use App\Http\Actions\dashboard\SettingAction;
use App\Http\Actions\ingaz\MaidAction as objAction;
use App\Http\Actions\ingaz\SkillAction;
use App\Http\Controllers\Controller;
use App\Models\Requirement as objModel;
use App\Http\Requests\ingaz\RequirementRequest as objRequest;
use App\Models\ingaz\Maid;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;



class CvConvertController extends Controller
{
    public $folderPath = "admin.ingaz.maids";
    public string $mainRoute = "cv-convert";

    public function show(int $id, objAction $service, SkillAction $SkillAction ,SettingAction $setting)
    {
        return view($this->folderPath . ".cv")->with([
            "obj" =>  Maid::findOrFail($id),
            "skills" => $SkillAction->get(),
            'setting_cv' => $setting->get($id),


        ]);
    }

    public function store(Request $request, objAction $service)
    {
        $dataInsert['cv_preview'] = $request->file('cv');
        $data = $service->update($request->cvId, $dataInsert);
        return response()->json(['success' => true]);
    }
}
