<?php

namespace App\Http\Controllers\ingaz;

use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\ingaz\Order;
use Illuminate\Http\Request;
use App\Enums\ingaz\OrderEnum;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\Religion;
use App\Enums\auth\AdminTypeis;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\UserAddress;
use App\Models\ingaz\MaidJobTitles;
use App\Models\ingaz\OrderDetail;
use App\Http\Controllers\Controller;
use App\Models\ingaz\OrderTransfers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\ingaz\Order as objModel;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Actions\ingaz\OrderAction as objAction;

class RentalOrderController extends Controller
{

    public $folderPath = "admin.ingaz.rental-orders";
    public $postData = ["status"];
    public $mainRoute = "rental-orders";

    public function __construct()
    {
        // $this->middleware('permission:الطلبات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الطلبات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الطلبات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الطلبات-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            \Log::info('Rental Orders AJAX request received', [
                'request_data' => $request->all(),
                'user_id' => auth('admin')->id()
            ]);

            $query = objModel::query();

            // Eager load relationships to avoid N+1 queries
            $query->with([
                'user',
                'maid.nationality',
                'jobType',
                'details.admin',
                'contracts'
            ]);

            // Order by created_at desc to show newest orders first
            $query->orderBy('created_at', 'desc');

            // Filter for rental orders only (job_type_id = 2)
            $query->where('job_type_id', 2);

            // Apply status filter
            if ($request->has('status') && $request->status != '') {
                $statusFilter = $this->getSelectedStatus($request->status);
                $query->whereIn('status', $statusFilter);
            }

            // Apply date range filter
            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Apply job type filter
            if ($request->filled('job_type')) {
                $query->where('job_type_id', $request->job_type);
            }

            // Apply admin filter
            if ($request->filled('admin_id')) {
                $query->whereHas('details', function($q) use ($request) {
                    $q->where('admin_id', $request->admin_id);
                });
            }

            // Apply client name search
            if ($request->filled('client_name')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->client_name . '%');
                });
            }

            // Apply phone search
            if ($request->filled('phone')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('phone', 'like', '%' . $request->phone . '%')
                      ->orWhere('phone_code', 'like', '%' . $request->phone . '%');
                });
            }

            // Apply passport number search
            if ($request->filled('passport_num')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('passport_num', 'like', '%' . $request->passport_num . '%');
                });
            }

            // Apply timer status filter
            if ($request->filled('timer_status')) {
                $query->where(function($q) use ($request) {
                    switch ($request->timer_status) {
                        case 'overdue':
                            $q->where('deadline_at', '<', now());
                            break;
                        case 'urgent':
                            $q->where('deadline_at', '>', now())
                              ->where('deadline_at', '<', now()->addHours(2));
                            break;
                        case 'normal':
                            $q->where('deadline_at', '>', now()->addHours(2));
                            break;
                    }
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('admin_name', function ($row) {
                    return $row->details->admin->name ?? '-';
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->editColumn('status', function ($row) {
                    return $this->getStatusHtml($row->status);
                })
                ->addColumn('timer', function ($row) {
                    return $this->getTimerHtml($row);
                })
                ->editColumn('job_type_id', function ($row) {
                    return ($row->jobType) ? $row->jobType->title : '';
                })
                ->editColumn('user_id', function ($row) {
                    return '<span class="client-name text-nowrap">' . (($row->user) ? $row->user->name : '-') . '</span>';
                })
                ->editColumn('phone', function ($row) {
                    return '<span class="phone-number">' . (($row->user) ? getPhoneWithTag(@$row->user->phone_code . @$row->user->phone) : '-') . '</span>';
                })
                ->editColumn('maid_id', function ($row) {
                    return ($row->maid) ? getImgTag($row->maid->cv_file, "50px", "50px", "maid-image") : '<span class="text-muted">-</span>';
                })
                ->editColumn('nationality_name', function ($row) {
                    return ($row->maid) ? @$row->maid->nationality->sub_title : '';
                })
                ->editColumn('maid_passport_num', function ($row) {
                    return ($row->maid) ? @$row->maid->passport_num : '';
                })
                ->editColumn('deliver_status', function ($row) {
                    if (@$row->details->deliver_maid == '1') {
                        return '<span class="badge bg-success status-badge">' . __('web.yes') . '</span>';
                    } else {
                        return '<span class="badge bg-danger status-badge">' . __('web.no') . '</span>';
                    }
                })
                       ->addColumn('package_name', function ($row) {
                           if ($row->details && $row->details->package) {
                               return '<span class="package-name">' . $row->details->package->title . '</span>';
                           }
                           return '<span class="text-muted">-</span>';
                       })
                       ->addColumn('address_status', function ($row) {
                           if ($row->details && $row->details->address_id) {
                               return '<span class="badge bg-success status-badge">تم تحديد العنوان</span>';
                           }
                           return '<span class="badge bg-warning status-badge">لم يتم تحديد العنوان</span>';
                       })
                       ->addColumn('payment_status', function ($row) {
                           // Assuming payment_method indicates payment status
                           if ($row->payment_method && $row->payment_method !== 'cash') {
                               return '<span class="badge bg-success status-badge">تم الدفع</span>';
                           }
                           return '<span class="badge bg-warning status-badge">لم يتم الدفع</span>';
                       })
                       ->addColumn('contract_status', function ($row) {
                           if ($row->contracts()->exists()) {
                               return '<span class="badge bg-info status-badge">' . __('ingaz.has_contract') . '</span>';
                           }
                           return '<span class="badge bg-danger status-badge">' . __('ingaz.no_contract') . '</span>';
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
                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    // View button
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW)) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('showSingleOrder', ['orderId' => $row->id]) . '">
                            <i class="fa fa-eye"></i> عرض التفاصيل
                        </a></li>';
                    }

                    // Add Contract button for approved orders
                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CREATE) && $row->status == OrderEnum::UnderProcess->value) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('RentContract.create', ['order_id' => $row->id]) . '">
                            <i class="fa fa-plus"></i> إضافة عقد إيجار
                        </a></li>';
                    }

                    // Change Maid button for new orders
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT) && $row->status == OrderEnum::New->value) {
                        $actions .= '<li><button class="dropdown-item change-maid" data-id="' . $row->id . '">
                            <i class="fa fa-user"></i> تغيير العاملة
                        </button></li>';
                    }

                           // Approve/Reject buttons for new orders
                           if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT) && $row->status == OrderEnum::New->value) {
                               $actions .= '<li><button class="dropdown-item approve-order" data-id="' . $row->id . '">
                                   <i class="fa fa-check text-success"></i> قبول الطلب
                               </button></li>';
                               $actions .= '<li><button class="dropdown-item reject-order" data-id="' . $row->id . '">
                                   <i class="fa fa-times text-danger"></i> رفض الطلب
                               </button></li>';
                           }

                    // Refuse/Return button for new and under_process orders
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT)) {
                        if (in_array($row->status, [OrderEnum::New->value, OrderEnum::UnderProcess->value])) {
                            $actions .= '<li><button class="dropdown-item refuse-order" data-id="' . $row->id . '">
                                <i class="fa fa-times"></i> رفض/إرجاع الطلب
                            </button></li>';
                        }
                    }

                    // Delete button
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_DELETE)) {
                        $actions .= '<li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item delete-order" data-id="' . $row->id . '">
                            <i class="fa fa-trash"></i> حذف الطلب
                        </button></li>';
                    }

                    $actions .= '</ul></div>';
                    return $actions;
                })
                ->escapeColumns([])
                       ->rawColumns(['actions', 'status', 'timer', 'user_id', 'phone', 'maid_id', 'deliver_status', 'package_name', 'address_status', 'payment_status', 'contract_status', 'order_code', 'created_at', 'admin_name'])
                ->make(true);
        }

        // Preload data for the main page to avoid multiple queries
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";

        // Cache filter data for 5 minutes to avoid repeated queries
        $data['jobTypes'] = \Cache::remember('job_types_list', 300, function() {
            return JobTitle::with('translations')->get();
        });

        $data['admins'] = \Cache::remember('admins_list', 300, function() {
            return \App\Models\auth\Admin::select('id', 'name')->whereNull('deleted_at')->get();
        });

        // Get statistics with optimized queries (cache for 1 minute) - Rental orders only
        $data['totalOrders'] = \Cache::remember('rental_orders_count', 60, function() {
            return objModel::where('job_type_id', 2)->count();
        });

        $data['newOrders'] = \Cache::remember('rental_new_orders_count', 60, function() {
            return objModel::where('job_type_id', 2)->where('status', OrderEnum::New->value)->count();
        });

        $data['underProcessOrders'] = \Cache::remember('rental_under_process_orders_count', 60, function() {
            return objModel::where('job_type_id', 2)->where('status', OrderEnum::UnderProcess->value)->count();
        });

        $data['completedOrders'] = \Cache::remember('rental_completed_orders_count', 60, function() {
            return objModel::where('job_type_id', 2)->whereIn('status', [OrderEnum::Completed->value, OrderEnum::Done->value, OrderEnum::Contracted->value])->count();
        });

        return view($this->folderPath . '.index', $data);
    }

    private function getSelectedStatus($status)
    {
        if ($status == 'current') {
            return [OrderEnum::New->value, OrderEnum::UnderProcess->value];
        } elseif ($status == 'ended') {
            return [OrderEnum::Completed->value, OrderEnum::Done->value, OrderEnum::Contracted->value];
        } elseif ($status == 'completed') {
            return [OrderEnum::Completed->value, OrderEnum::Done->value, OrderEnum::Contracted->value];
        } elseif ($status == 'not_completed') {
            return [OrderEnum::Refused->value, OrderEnum::Return->value];
        } else {
            return array_map(fn($case) => $case->value, OrderEnum::cases());
        }
    }

    private function getStatusHtml($status)
    {
        $statusClass = '';
        $statusText = '';

        switch ($status) {
            case OrderEnum::New->value:
                $statusClass = 'bg-primary';
                $statusText = __('ingaz.new');
                break;
            case OrderEnum::UnderProcess->value:
                $statusClass = 'bg-warning';
                $statusText = __('ingaz.under_process');
                break;
            case OrderEnum::Completed->value:
                $statusClass = 'bg-success';
                $statusText = __('ingaz.completed');
                break;
            case OrderEnum::Done->value:
                $statusClass = 'bg-info';
                $statusText = __('ingaz.done');
                break;
            case OrderEnum::Contracted->value:
                $statusClass = 'bg-success';
                $statusText = __('ingaz.contracted');
                break;
            case OrderEnum::Refused->value:
                $statusClass = 'bg-danger';
                $statusText = __('ingaz.refused');
                break;
            case OrderEnum::Return->value:
                $statusClass = 'bg-secondary';
                $statusText = __('ingaz.return');
                break;
            default:
                $statusClass = 'bg-secondary';
                $statusText = $status;
        }

        return '<span class="badge ' . $statusClass . ' status-badge">' . $statusText . '</span>';
    }

    private function getTimerHtml($order)
    {
        if (!$order->deadline_at) {
            return '<span class="text-muted">-</span>';
        }

        $deadline = \Carbon\Carbon::parse($order->deadline_at);
        $now = \Carbon\Carbon::now();
        $diff = $now->diffInHours($deadline, false);

        if ($diff < 0) {
            // Overdue
            $hoursOverdue = abs($diff);
            return '<span class="badge bg-danger timer-badge" title="متأخر ' . $hoursOverdue . ' ساعة">
                <i class="fa fa-clock"></i> متأخر ' . $hoursOverdue . 'س
            </span>';
        } elseif ($diff <= 2) {
            // Urgent
            return '<span class="badge bg-warning timer-badge" title="عاجل - متبقي ' . $diff . ' ساعة">
                <i class="fa fa-exclamation-triangle"></i> عاجل ' . $diff . 'س
            </span>';
        } else {
            // Normal
            return '<span class="badge bg-success timer-badge" title="متبقي ' . $diff . ' ساعة">
                <i class="fa fa-clock"></i> ' . $diff . 'س
            </span>';
        }
    }

    public function destroy($id)
    {
        try {
            $action = new objAction();
            $result = $action->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الطلب بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الطلب: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approveOrder(Request $request, $id)
    {
        try {
            $order = objModel::where('job_type_id', 2)->findOrFail($id);

            if ($order->status !== OrderEnum::New->value) {
                return response()->json(['success' => false, 'message' => 'لا يمكن قبول هذا الطلب.']);
            }

            $order->update(['status' => OrderEnum::UnderProcess->value]);

            return response()->json(['success' => true, 'message' => 'تم قبول الطلب بنجاح.']);
        } catch (\Exception $e) {
            \Log::error('Error approving rental order: ' . $e->getMessage(), ['order_id' => $id]);
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء قبول الطلب.'], 500);
        }
    }

    public function rejectOrder(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $order = objModel::where('job_type_id', 2)->findOrFail($id);

            if ($order->status !== OrderEnum::New->value) {
                return response()->json(['success' => false, 'message' => 'لا يمكن رفض هذا الطلب.']);
            }

            // Update order status to refused
            $order->update(['status' => OrderEnum::Refused->value]);

            // Make maid available again
            if ($order->maid) {
                $order->maid->update([
                    'status' => 'available',
                    'available' => 1,
                    'available_at' => now()->addDays(30)
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'تم رفض الطلب وإرجاع العاملة متاحة.']);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error rejecting rental order: ' . $e->getMessage(), ['order_id' => $id]);
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء رفض الطلب.'], 500);
        }
    }
}
