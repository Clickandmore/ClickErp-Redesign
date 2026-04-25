<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\RecruitmentOfficeAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\RecruitmentOfficesRequest;
use App\Models\ingaz\Nationality;
use App\Models\RecruitmentOffices as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RecruitmentOfficeController extends Controller
{
    public $folderPath = "admin.ingaz.recruitment-offices";
    public $postData = ['title:ar', 'title:en', 'nationality_id'];
    public $mainRoute = "recruitment-offices";

    function __construct()
    {
        // $this->middleware('permission:مكاتب الاستقدام-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:مكاتب الاستقدام-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:مكاتب الاستقدام-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:مكاتب الاستقدام-حذف,admin', ['only' => ['destroy']]);
    }


    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rows = objModel::query()->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('nationality_id', function ($row) {
                    return $row->nationality ? $row->nationality->sub_title : '';
                })
                ->editColumn('created_at', function ($row) {
                    return ($row->created_at->format('Y-m-d h:i A'));
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::REC_OFFICES_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->title);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::REC_OFFICES_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans("ingaz.recruitment_offices");
        $data["addButtonText"] = helperTrans("ingaz.add_recruitment_office");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);

    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            $nationalities = Nationality::all();
            $returnHTML = view($this->folderPath . ".create", compact('nationalities'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(RecruitmentOfficesRequest $request, objAction $action)
    {
        $data = $request->only($this->postData);
        $data = $action->store($data);
        return jsonSuccess($data);
    }


    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = objModel::findorfail($id);
            $nationalities = Nationality::all();
            $returnHTML = view($this->folderPath . '.edit', compact('nationalities'))
                ->with(['obj' => $obj])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(RecruitmentOfficesRequest $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->updateRec($id, $updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
