<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\ComplaintAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\Complaint as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ComplaintController extends Controller
{
    public $folderPath = "admin.ingaz.complaints";
    public $mainRoute = "complaints";

    function __construct()
    {
        // $this->middleware('permission:الشكاوى-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الشكاوى-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الشكاوى-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الشكاوى-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            $contacts = objModel::query()->latest();
            return DataTables::of($contacts)
                ->addIndexColumn()
                ->editColumn('user_id',function($rows){
                    return ($rows->user) ? $rows->user->name : '';
                })
                ->addColumn('actions', function ($row) {
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        return view($this->folderPath . '.index', $data);
    }

    public function destroy($id,ComplaintAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }
}
