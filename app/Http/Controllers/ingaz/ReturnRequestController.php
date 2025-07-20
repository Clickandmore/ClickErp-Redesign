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
            // عرض الطلبات المحذوفة أو الملغاة (refused, return) أو المحذوفة soft deleted
            $rows = \App\Models\ingaz\Order::withTrashed()
                ->where(function($query) {
                    $query->whereIn('status', [
                        \App\Enums\ingaz\OrderEnum::Refused->value,
                        \App\Enums\ingaz\OrderEnum::Return->value
                    ])
                    ->orWhereNotNull('deleted_at'); // إضافة الطلبات المحذوفة soft deleted
                })
                ->with(['user', 'maid', 'details.admin']);

            // فلتر الحالة
            if ($request->filled('status')) {
                if ($request->status === 'deleted') {
                    $rows->whereNotNull('deleted_at');
                } else {
                    $rows->where('status', $request->status);
                }
            }

            // فلتر التاريخ من
            if ($request->filled('from_date')) {
                $rows->whereDate('created_at', '>=', $request->from_date);
            }

            // فلتر التاريخ إلى
            if ($request->filled('to_date')) {
                $rows->whereDate('created_at', '<=', $request->to_date);
            }

            // فلتر اسم العميل
            if ($request->filled('client_name')) {
                $rows->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->client_name . '%');
                });
            }

            $rows = $rows->latest();

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    if ($row->deleted_at) {
                        return '<span class="badge bg-secondary status-badge">محذوفة</span>';
                    } elseif ($row->status == \App\Enums\ingaz\OrderEnum::Refused->value) {
                        return '<span class="badge bg-danger status-badge">مرفوضة</span>';
                    } elseif ($row->status == \App\Enums\ingaz\OrderEnum::Return->value) {
                        return '<span class="badge bg-warning status-badge">ملغية</span>';
                    }
                    return '<span class="badge bg-secondary status-badge">' . getEnumValue('orders_statuses', $row->status) . '</span>';
                })
                ->editColumn('user_id', function ($row) {
                    return '<span class="client-name">' . ($row->user ? $row->user->name : '-') . '</span>';
                })
                ->editColumn('phone', function ($row) {
                    return '<span class="phone-number">' . ($row->user ? getPhoneWithTag($row->user->phone_code.$row->user->phone) : '-') . '</span>';
                })
                ->editColumn('maid_id', function ($row) {
                    return ($row->maid) ? getImgTag($row->maid->cv_file,"50px","50px", "maid-image") : '<span class="text-muted">-</span>';
                })
                ->editColumn('order_code', function ($row) {
                    return '<span class="order-code">' . $row->order_code . '</span>';
                })
                ->editColumn('created_at', function ($row) {
                    return '<span class="text-muted">' . $row->created_at->format('Y-m-d H:i') . '</span>';
                })
                ->editColumn('admin_name', function ($row) {
                    return '<span class="text-info">' . ($row->details->admin->name ?? '-') . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="btn-group btn-group-sm" role="group">';

                    // View button
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW)) {
                        $actions .= '<a href="' . route('showSingleOrder', $row->id) . '" class="btn btn-info" title="عرض التفاصيل">
                            <i class="fa fa-eye"></i>
                        </a>';
                    }

                    // Edit button (only for non-deleted orders)
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT) && !$row->deleted_at) {
                        $actions .= '<a href="' . route('orders.edit', $row->id) . '" class="btn btn-warning" title="تعديل">
                            <i class="fa fa-edit"></i>
                        </a>';
                    }

                    // Restore button (only for deleted orders)
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_DELETE) && $row->deleted_at) {
                        $actions .= '<button class="btn btn-success restore-order" data-id="' . $row->id . '" title="استعادة">
                            <i class="fa fa-undo"></i>
                        </button>';
                    }

                    // Delete button
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_DELETE)) {
                        $actions .= '<button class="btn btn-danger delete-order" data-id="' . $row->id . '" title="حذف نهائي">
                            <i class="fa fa-trash"></i>
                        </button>';
                    }

                    $actions .= '</div>';
                    return $actions;
                })->escapeColumns([])->make(true);
        }
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('ingaz.cancelled_orders');
        $data["modalType"] = "modal-lg";

        // Add statistics
        if ($request->ajax() && $request->has('statistics')) {
            $statistics = [
                'refused' => \App\Models\ingaz\Order::where('status', \App\Enums\ingaz\OrderEnum::Refused->value)->count(),
                'return' => \App\Models\ingaz\Order::where('status', \App\Enums\ingaz\OrderEnum::Return->value)->count(),
                'deleted' => \App\Models\ingaz\Order::withTrashed()->whereNotNull('deleted_at')->count(),
                'total' => \App\Models\ingaz\Order::withTrashed()
                    ->where(function($query) {
                        $query->whereIn('status', [
                            \App\Enums\ingaz\OrderEnum::Refused->value,
                            \App\Enums\ingaz\OrderEnum::Return->value
                        ])
                        ->orWhereNotNull('deleted_at');
                    })->count()
            ];
            return response()->json(['statistics' => $statistics]);
        }

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
        $data = $action->destroy($id);
        return jsonSuccess($data);
    }

    /**
     * Export return requests to PDF
     */
    public function exportPdf(Request $request)
    {
        // Get filtered data
        $rows = \App\Models\ingaz\Order::withTrashed()
            ->where(function($query) {
                $query->whereIn('status', [
                    \App\Enums\ingaz\OrderEnum::Refused->value,
                    \App\Enums\ingaz\OrderEnum::Return->value
                ])
                ->orWhereNotNull('deleted_at');
            })
            ->with(['user', 'maid', 'details.admin']);

        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'deleted') {
                $rows->whereNotNull('deleted_at');
            } else {
                $rows->where('status', $request->status);
            }
        }

        if ($request->filled('from_date')) {
            $rows->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $rows->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('client_name')) {
            $rows->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->client_name . '%');
            });
        }

        $data = $rows->latest()->get();

        // Generate PDF
        $pdf = \PDF::loadView('admin.ingaz.return_request.pdf', compact('data'));
        return $pdf->download('return_requests_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export return requests to Excel
     */
    public function exportExcel(Request $request)
    {
        // Get filtered data
        $rows = \App\Models\ingaz\Order::withTrashed()
            ->where(function($query) {
                $query->whereIn('status', [
                    \App\Enums\ingaz\OrderEnum::Refused->value,
                    \App\Enums\ingaz\OrderEnum::Return->value
                ])
                ->orWhereNotNull('deleted_at');
            })
            ->with(['user', 'maid', 'details.admin']);

        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'deleted') {
                $rows->whereNotNull('deleted_at');
            } else {
                $rows->where('status', $request->status);
            }
        }

        if ($request->filled('from_date')) {
            $rows->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $rows->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('client_name')) {
            $rows->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->client_name . '%');
            });
        }

        $data = $rows->latest()->get();

        // Generate Excel
        return \Excel::download(new \App\Exports\ReturnRequestsExport($data), 'return_requests_' . date('Y-m-d') . '.xlsx');
    }
}
