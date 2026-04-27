<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\Job\EditJobRequest;
use Nami\Hr\Http\Requests\Job\JobRequest;
use Nami\Hr\Models\Job as objModel;
use Nami\Hr\Services\JobService as objService;
use Yajra\DataTables\DataTables;

class JobController extends Controller
{
    public $folderPath = "hr::hr.jobs";
    public $postData = ['title'];
    public $mainRoute = "jobs";

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $dataTable = objModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('title', function ($row) {
                    return $row->title;
                })
                ->addColumn('number_employees', function ($row) {
                    return $row->employees->count() ?? 0;
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.jobs');
        $data["addButtonText"] = __('hr::hr.job_title');
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(JobRequest $request, objService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeJob($dataInsert,$request);
        return jsonSuccess($data);
    }


    public function show($id)
    {
        //
    }


    public function edit($id, Request $request, ObjService $action)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $action->find($id)
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function update(EditJobRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateJob($id, $dataInsert);
        return jsonSuccess($data);
    }

    public function destroy($id, ObjService $action)
    {
        $data = $action->deleteJob($id);
        if(!$data) {
            return jsonValid( ['error'=>[__("hr.sorry,can't delete this job")]]);
        }
        return jsonSuccess();
    }
}
