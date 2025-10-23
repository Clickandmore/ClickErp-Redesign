<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\NationalityAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\NationalityRequest;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Nationality as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class NationalityController extends Controller
{

    public $folderPath = "admin.ingaz.nationalities";
    public $postData = [
        "logo",
        "phone_code",
        "sub_title:ar",
        "sub_title:en",
        "description:ar",
        "description:en",
        "price",
        "salary_with_no_experience",
        "salary_with_experience",
        "service_transportation_cost",
        "rental_cost",
        "monthly_salary",
        "is_show",
    ];
    public $mainRoute = "nationalities";

    function __construct()
    {
        // $this->middleware('permission:الجنسيات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الجنسيات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الجنسيات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الجنسيات-حذف,admin', ['only' => ['destroy']]);
    }


    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $rows = objModel::query()->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('is_show', function ($row) {
                    $statusLabel = $row->is_show
                        ? helperTrans('ingaz.hide')
                        : helperTrans('ingaz.show');

                    $statusClass = $row->is_show ? 'btn-danger' : 'btn-success';

                    return '<button class="btn btn-sm ' . $statusClass . ' update-status" data-id="'
                        . $row->id . '">' . $statusLabel . '</button>';
                })
                ->editColumn('logo', function ($row) {
                    return getImgTag($row->logo);
                })
                ->addColumn('description', function ($row) {
                    return Str::limit(strip_tags($row->description ?? ''), 60);
                })
                ->editColumn('price', function ($row) {
                    return $row->price ?? 'لم يتم توحيد سعر';
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->title);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::NATIONALITIES_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.nationalities');
        $data["addButtonText"] = helperTrans('api.nationality');
        $data["modalType"] = "modal-lg";
        return view($this->folderPath . '.index', $data);

    }

    public function show($id)
    {
        $model = objModel::findOrFail($id);
        $model->is_show = !$model->is_show;
        $model->save();

        return response()->json(['success' => true, 'is_show' => $model->is_show]);
    }


    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(NationalityRequest $request,objAction $action)
    {

        $data = $request->only($this->postData);
        $data = $action->store($data);
        return jsonSuccess($data);
    }


    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = Nationality::findorfail($id);
            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(NationalityRequest $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->updateNationality($id, $updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
