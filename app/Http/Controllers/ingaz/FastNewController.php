<?php

namespace App\Http\Controllers\ingaz;
use Illuminate\Support\Str;
 use App\Http\Actions\ingaz\FastNewAction as objAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\FastNew as objModel;
 use App\Http\Requests\ingaz\FastNewRequest as objRequest;
use Illuminate\Http\Request;
 use Yajra\DataTables\Facades\DataTables;

class FastNewController extends Controller
{

    public $folderPath = "admin.ingaz.fast_news";
     public $mainRoute = "fast-news";

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
         ->editColumn('title', function ($row) {
    $limited =  Str::limit($row?->title, 50, '...') ;
    return "<span title='" . $row->title . "'>" . $limited . "</span>";
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
        $data["bladeTitle"] = helperTrans('api.Fast news');
        $data["addButtonText"] = helperTrans('api.Fast news');
         return view($this->folderPath . '.index', compact('data'));
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
      $data = $request->validated();
         $data = $action->store($data);
        return jsonSuccess($data);
 
    }


    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
             $obj = objModel::findorfail($id);
            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(objRequest $request, objAction $action, $id)
    {
        $updatedData = $request->validated();
        $data = $action->find($id)->update($updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        // $action->deleteWithFile($id, 'image');
        $action->delete($id, 'image');
        return jsonSuccess();
    }
}
