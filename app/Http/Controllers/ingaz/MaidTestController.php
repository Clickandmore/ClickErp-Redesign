<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\MaidAction as objAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\ReligionAction;
use App\Http\Controllers\admin\AdminController;
use App\Http\Requests\ingaz\MaidRequest as ObjRequest;
use App\Models\ingaz\Maid as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MaidTestController extends AdminController
{

    public $folderPath = "admin.ingaz.maids";
    public $postData = ["nationality_id", 'cv_file', 'religion_id', 'passport_num', 'price', 'salary', 'age'];
    public $mainRoute = "maids";


    public function __construct(objAction $actionObj,ObjRequest $requestObj)
    {
        $this->actionObj = $actionObj;
        $this->requestObj = $requestObj;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objAction $Action)
    {
        dd('ddd');
        if ($request->ajax()) {
            $rows = objModel::latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('is_show', function ($row) {
                    return
                        $row->is_show == 1 ? '
                        <button class="btn btn-sm btn-success update-status" id="update-status" data-id="' . $row->id . '">ظاهر</button>' :
                            '<button class="btn btn-sm btn-dark update-status" data-id="' . $row->id . '">مخفي </button>';
                })
                ->editColumn('available', function ($row) {
                    return
                        $row->available == 1 ?
                            '<i class ="fa fa-check text-success">' : '<i class ="fa fa-times text-danger">';
                })
                ->editColumn('available_at', function ($row) {
                    return $row->available_at <= date('Y-m-d') ?
                        '<i class="fa fa-check text-success"></i>' :
                        $row->available_at;
                })
                ->editColumn('cv_file', function ($row) {
                    return getImgTag($row->cv_file);
                })
                ->editColumn('religion_id', function ($row) {
                    return ($row->religion) ? $row->religion->title : '';
                })
                ->editColumn('nationality_id', function ($row) {
                    return ($row->nationality) ? $row->nationality->title : '';
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    // if (Auth::guard('admin')->user()->hasPermission('maids-update')) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    // }

                    // if (Auth::guard('admin')->user()->hasPermission('maids-delete')) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    // }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans("ingaz.maids");
        $data["addButtonText"] = helperTrans("ingaz.maid");
        $data["modalType"] = "modal-lg";
        return view($this->folderPath . '.index', $data);

    }


    public function create(NationalityAction $act, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, Request $request)
    {
        if ($request->ajax()) {
            return $this->createAjaxResponse($act, $relAct, $jobTitleAction, $jobTypesAction, 'create');
        }
    }


    public function show($id)
    {
        $model = objModel::findOrFail($id);
        $model->is_show = !$model->is_show;
        $model->save(); // Save the changes
        return response()->json(['success' => true]);
    }

    public function edit(Request $request, $id, NationalityAction $act, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction)
    {
        if ($request->ajax()) {
            $obj = objModel::findOrFail($id);
            return $this->createAjaxResponse($act, $relAct, $jobTitleAction, $jobTypesAction, 'edit', ['obj' => $obj]);
        }
    }



    private function createAjaxResponse(NationalityAction $act, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, $viewName, $additionalData = [])
    {
        $nationalities = $act->getNationalities();
        $religions = $relAct->getReligions();
        $jobs = $jobTitleAction->getJobsTitles();
        $types = $jobTypesAction->getJobsTypes();
        $data = array_merge(compact('nationalities', 'religions', 'jobs', 'types'), $additionalData);
        $returnHTML = view("{$this->folderPath}.{$viewName}", $data)->render();
        return jsonSuccess(['html' => $returnHTML]);
    }
}
