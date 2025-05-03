<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\ComplaintAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\Complaint as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ingaz\ComplaintType;

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
            $query = objModel::query()->with('user');

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('user_id', function($row) {
                    return $row->user ? $row->user->name : 'غير معروف';
                })
                ->editColumn('status', function($row) {
                    $status = $row->status;
                    $badgeClass = '';
                    switch($status) {
                        case 'pending':
                            $badgeClass = 'bg-warning';
                            break;
                        case 'resolved':
                            $badgeClass = 'bg-success';
                            break;
                        case 'rejected':
                            $badgeClass = 'bg-danger';
                            break;
                    }
                    return '<span class="badge ' . $badgeClass . '">' . helperTrans('ingaz.' . $status) . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    $buttons = '<div class="btn-group" role="group" aria-label="Actions">';
                    $buttons .= '<button class="btn btn-sm btn-primary response-btn" data-id="' . $row->id . '" data-response="' . htmlspecialchars($row->response ?? '', ENT_QUOTES) . '" data-status="' . $row->status . '">
                        <i class="fa fa-reply"></i> ' . helperTrans('ingaz.respond') . '
                    </button>';
                    $buttons .= deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    $buttons .= '</div>';
                    return $buttons;
                })
                ->rawColumns(['status', 'actions'])
                ->make(true);
        }

        return view($this->folderPath . '.index');
    }

    public function response(Request $request)
    {
        $complaint = objModel::findOrFail($request->complaint_id);
        $complaint->response = $request->response;
        $complaint->status = $request->status;
        $complaint->save();

        return jsonSuccess(null, 'تم حفظ الرد بنجاح');
    }

    public function destroy($id, ComplaintAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }

    public function type()
    {
        return $this->belongsTo(ComplaintType::class, 'complaint_type_id');
    }
}
