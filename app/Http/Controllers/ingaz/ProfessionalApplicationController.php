<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\ProfessionalApplicationAction as objAction;
use App\Http\Controllers\Controller;
use App\Models\ProfessionalApplication as objModel;
use App\Http\Requests\ingaz\ProfessionalApplicationRequest as objRequest;
use Illuminate\Http\Request;
 use Yajra\DataTables\Facades\DataTables;

class ProfessionalApplicationController extends Controller
{

    public $folderPath = "admin.ingaz.ProfessionalApplication";
    public $postData = ["image"];
    public $mainRoute = "professional_application";

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
                ->editColumn('facility_name', function ($row) {
                    return $row->facility_name;
                    
                })
                  ->editColumn('number_of_workers_required', function ($row) {
                    return $row->number_of_workers_required;
                    
                })
           
->editColumn('status', function ($row) {
    $status = $row->status ?? 'pending';
    $id = $row->id;
    $checked = $status === 'approved' ? 'checked' : '';

    return '
        <label class="switch">
            <input type="checkbox" class="toggle-status" data-id="' . $id . '" ' . $checked . '>
            <span class="slider round"></span>
        </label>
        <span class="badge badge-' . ($status === 'approved' ? 'success bg-success' : 'secondary bg-secondary') . ' ml-2 status-text-' . $id . '">' . $status . '</span>
    ';
})


                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                   

                    // if (@AdminGuardHasPerm(\App\PermKeys::PARTENR_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    // }
                     // if (@AdminGuardHasPerm(\App\PermKeys::PARTENR_DELETE)) {
                        $showbutton = viewSingleButton(route($this->mainRoute . ".show", $row->id));
                    // }
                    return  $editButton . " " . $deleteButton."  ".$showbutton;
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
    $contract = objModel::findOrFail($id);

    // لو جاي من toggle status
    if ($request->has('status')) {
        $contract->update([
            'status' => $request->status
        ]);

        return response()->json(['success' => true, 'status' => $request->status]);
    }

    // التحديث العادي (مثلاً صورة أو غيره)
    $updatedData = $request->only($this->postData);
    $data = $action->find($id)->update($updatedData);

    return jsonSuccess($data);
    }   
public function show($id)
{
    $application = objModel::with(['user', 'jobtitle'])->findOrFail($id);
    return view($this->folderPath . '.show', compact('application'));
}

    public function destroy($id, objAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }
}
