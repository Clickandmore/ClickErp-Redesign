<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\ClientAction as objAction;
use App\Http\Controllers\Controller;
use App\Models\Client as objModel;
use App\Http\Requests\ingaz\ClientRequest as objRequest;
use Illuminate\Http\Request;
 use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ClientController extends Controller
{

    public $folderPath = "admin.ingaz.partenrs";
    public $postData = ["image"];
    public $mainRoute = "partenrs";

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
    if ($row->image) {
        return '<img src="' . asset('storage/' . $row->image) . '" width="80" height="80" style="object-fit:cover;border-radius:8px;">';
    }
    return '<span class="text-muted">No Image</span>';
})
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    // if (@AdminGuardHasPerm(\App\PermKeys::PARTENR_EDIT)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->title);
                    // }

                    // if (@AdminGuardHasPerm(\App\PermKeys::PARTENR_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    // }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.parteners');
        $data["addButtonText"] = helperTrans('api.parteners');
        $data['count'] = objModel::count();
        return view($this->folderPath . '.index', compact('data'));
    }


    public function create(Request $request)
    {
        if ($request->ajax())   {
            $returnHTML = view($this->folderPath . ".create")->render();
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
