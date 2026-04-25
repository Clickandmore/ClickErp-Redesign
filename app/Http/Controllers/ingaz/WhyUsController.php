<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\WhyUsAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\WhyUsRequest as objRequest;
use App\Models\WhyUs as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class WhyUsController extends Controller
{

    public $folderPath = "admin.ingaz.why_us";
    public $postData = ["title:ar", "title:en", "description:ar", "description:en", 'icon'];
    public $mainRoute = "why-us";

    function __construct()
    {
        // $this->middleware('permission:الأسئلة-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الأسئلة-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الأسئلة-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الأسئلة-حذف,admin', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $units = objModel::all();
            return DataTables::of($units)
                ->addIndexColumn()
                ->editColumn('icon', function ($row) {
                    return '<i class="' . $row->icon . '"></i>';
                })
                ->editColumn('title', function ($row) {
                    return "<span title='" . $row->title . "'>" . Str::limit($row->title, 30) . "</span>";
                })
                ->editColumn('description', function ($row) {
                    return "<span title='" . $row->description . "'>" . Str::limit($row->description, 30) . "</span>";
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
//                    if (@AdminGuardHasPerm(\App\PermKeys::FAQ_CONTROL)) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->title);
//                    }

//                    if (@AdminGuardHasPerm(\App\PermKeys::FAQ_DELETE)) {
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
//                    }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.why_us');
        $data["addButtonText"] = helperTrans('api.why_us');
        $data["modalType"] = "modal-lg";
        return view($this->folderPath . '.index', $data);

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
        $data = $action->store($data);
        return jsonSuccess($data);
    }

    public function show($id)
    {
        $model = objModel::find($id);
        $model->is_show = !$model->is_show;
        $model->update();
        return response()->json(['success' => true]);
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
        $updatedData = $request->only($this->postData);
        $data = $action->find($id)->update($updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
