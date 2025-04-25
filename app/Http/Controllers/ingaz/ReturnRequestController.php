<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\ReturnRequestAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\JobTitleRequest;
use App\Http\Requests\ingaz\NationalityRequest;
use App\Models\ingaz\ReturnRequest as objModel;
use App\Models\ingaz\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ReturnRequestController extends Controller
{



    public $folderPath = "admin.ingaz.return_request";
    public $postData = ["status"];
    public $mainRoute = "return_requests";

    function __construct()
    {
        // $this->middleware('permission:طلبات الارجاع-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:طلبات الارجاع-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:طلبات الارجاع-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:طلبات الارجاع-حذف,admin', ['only' => ['destroy']]);
    }
    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $rows = ReturnRequest::query()->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    $status = $row->status =="accept"? 'مقبول' : "مرفوض";
                    return
                        $row->status =='new'  ? '
                        <button class="btn btn-sm btn-success update-status" data-value="accept" id="update-status"  data-id="' . $row->id . '">قبول</button>
                            <button class="btn btn-sm btn-danger update-status" data-value="refuse" data-id="' . $row->id . '">رفض </button>' : ' <button class="btn btn-sm btn-info "   data-id="' . $row->id . '">'. $status .'</button>';
                })
                ->editColumn('user_id', function ($row) {
                    return ($row->user) ? $row->user->name : '';
                })
                ->editColumn('file', function ($row) {
                    if ($row->file) {
                        return "<a href='" . showFile($row->file) . "'>عرض التقرير <i class='fa fa-file'></i> </a>";
                    } else {
                        return "لا يوجد تقرير";
                    }
                })
                ->editColumn('phone', function ($row) {
                    return ($row->user) ? getPhoneWithTag($row->user->phone_code.$row->user->phone) :'';
                })
                ->editColumn('maid_id', function ($row) {
                    return ($row->maid) ? getImgTag($row->maid->cv_file,"80px","80px") : '';
                })

                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';

                    if (@AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.return_request');
        $data["addButtonText"] = helperTrans('api.return_request');
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


    public function show(Request $request,objAction $action,$id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->updateRequest($id, $updatedData);
        return response()->json(['success' => true]);
    }

    public function update(Request $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->updateRequest($id, $updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }
}
