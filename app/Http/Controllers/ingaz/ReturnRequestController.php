<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\OrderEnum;
use App\Http\Actions\ingaz\ReturnRequestAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\NationalityRequest;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\Nationality as NationalityModel;
use App\Models\ingaz\Notification;
use App\Models\ingaz\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rows = $this->buildBaseQuery();
            $this->applyFilters($rows, $request);
            $rows->orderByDesc('created_at');

            return DataTables::of($rows)
                ->addColumn('maid_photo', function (Order $row) {
                    return $this->maidSummaryHtml($row);
                })
                ->editColumn('user_id', function (Order $row) {
                    $name = e($row->user?->name ?? '-');
                    $idNumber = e($row->user?->id_number ?? '-');

                    return '<div class="d-flex flex-column">'
                        . '<span class="fw-semibold text-nowrap">' . $name . '</span>'
                        . '<small class="text-muted">' . e(helperTrans('ingaz.id_number')) . ': ' . $idNumber . '</small>'
                        . '</div>';
                })
                ->editColumn('phone', function (Order $row) {
                    return '<span class="phone-number">' . ($row->user ? getPhoneWithTag($row->user->phone_code . $row->user->phone) : '-') . '</span>';
                })
                ->editColumn('nationality_name', function (Order $row) {
                    return e($row->maid?->nationality?->sub_title ?? $row->maid?->nationality?->title ?? '-');
                })
                ->editColumn('maid_passport_num', function (Order $row) {
                    return e($row->maid?->passport_num ?? '-');
                })
                ->editColumn('job_type_id', function (Order $row) {
                    return e($row->jobType?->title ?? '-');
                })
                ->editColumn('status', function (Order $row) {
                    return $this->statusHtml($row);
                })
                ->editColumn('created_at', function (Order $row) {
                    return '<span class="text-muted">' . optional($row->created_at)->format('Y-m-d H:i') . '</span>';
                })
                ->addColumn('admin_name', function (Order $row) {
                    return '<span class="text-info">' . e($row->resolveResponsibleAdmin()?->name ?? '-') . '</span>';
                })
                ->addColumn('actions', function (Order $row) {
                    return $this->actionsHtml($row);
                })
                ->escapeColumns([])
                ->rawColumns([
                    'maid_photo',
                    'user_id',
                    'phone',
                    'status',
                    'created_at',
                    'admin_name',
                    'actions',
                ])->make(true);
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('ingaz.cancelled_orders');
        $data["modalType"] = "modal-lg";
        $data['jobTypes'] = \Cache::remember('return_requests_job_types_list', 300, function () {
            return JobTitle::with('translations')->get();
        });
        $data['nationalities'] = \Cache::remember('return_requests_nationalities_list', 300, function () {
            return NationalityModel::with('translations')->get()->sortBy('title');
        });
        $data['admins'] = \Cache::remember('return_requests_admins_list', 300, function () {
            return \App\Models\auth\Admin::select('id', 'name')->whereNull('deleted_at')->get();
        });

        $data['totalOrders'] = \Cache::remember('return_requests_total_count', 60, function () {
            return $this->buildBaseQuery()->count();
        });
        $data['refusedOrders'] = \Cache::remember('return_requests_refused_count', 60, function () {
            return Order::where('status', OrderEnum::Refused->value)->count();
        });
        $data['returnedOrders'] = \Cache::remember('return_requests_returned_count', 60, function () {
            return Order::where('status', OrderEnum::Return->value)->count();
        });
        $data['deletedOrders'] = \Cache::remember('return_requests_deleted_count', 60, function () {
            return Order::onlyTrashed()->count();
        });

        return view($this->folderPath . '.index', $data);
    }

    private function buildBaseQuery(): Builder
    {
        return Order::query()
            ->withTrashed()
            ->where(function (Builder $query) {
                $query->whereIn('status', [
                    OrderEnum::Refused->value,
                    OrderEnum::Return->value,
                ])->orWhereNotNull('deleted_at');
            })
            ->with([
                'user',
                'maid.nationality',
                'maid.office.translations',
                'jobType',
                'details.admin',
            ]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status')) {
            if ($request->status === 'deleted') {
                $query->whereNotNull('deleted_at');
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->filled('job_type')) {
            $query->where('job_type_id', $request->job_type);
        }

        if ($request->filled('admin_id')) {
            $query->whereHas('details', function (Builder $q) use ($request) {
                $q->where('admin_id', $request->admin_id);
            });
        }

        if ($request->filled('client_name')) {
            $query->whereHas('user', function (Builder $q) use ($request) {
                $q->where('name', 'like', '%' . trim($request->client_name) . '%');
            });
        }

        if ($request->filled('phone')) {
            $query->whereHas('user', function (Builder $q) use ($request) {
                $search = trim($request->phone);
                $q->where('phone', 'like', '%' . $search . '%')
                    ->orWhere('phone_code', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('passport_num')) {
            $query->whereHas('maid', function (Builder $q) use ($request) {
                $q->where('passport_num', 'like', '%' . trim($request->passport_num) . '%');
            });
        }

        if ($request->filled('nationality_id')) {
            $query->whereHas('maid', function (Builder $q) use ($request) {
                $q->where('nationality_id', $request->nationality_id);
            });
        }

        if ($request->filled('office_name')) {
            $officeName = trim($request->office_name);
            $query->whereHas('maid.office.translations', function (Builder $q) use ($officeName) {
                $q->where('title', 'like', '%' . $officeName . '%')
                    ->where('locale', app()->getLocale());
            });
        }
    }

    private function statusHtml(Order $order): string
    {
        if ($order->deleted_at) {
            return '<span class="badge bg-secondary status-badge">' . e(helperTrans('ingaz.deleted')) . '</span>';
        }

        if ($order->status === OrderEnum::Refused->value) {
            return '<span class="badge bg-danger status-badge">' . e(helperTrans('ingaz.refused')) . '</span>';
        }

        if ($order->status === OrderEnum::Return->value) {
            return '<span class="badge bg-warning text-dark status-badge">' . e(helperTrans('ingaz.return')) . '</span>';
        }

        $translated = getEnumValue('orders_statuses', $order->status);
        return '<span class="badge bg-' . $this->statusColor($order->status) . ' status-badge">' . e($translated) . '</span>';
    }

    private function statusColor(?string $status): string
    {
        return match ($status) {
            OrderEnum::Refused->value => 'danger',
            OrderEnum::Return->value => 'warning',
            OrderEnum::Completed->value,
            OrderEnum::Done->value,
            OrderEnum::Contracted->value,
            OrderEnum::ContractedWithoutContract->value => 'success',
            OrderEnum::UnderProcess->value => 'info',
            default => 'secondary',
        };
    }

    private function maidSummaryHtml(Order $order): string
    {
        if (!$order->maid) {
            return '<span class="text-muted">-</span>';
        }

        $rawPath = $order->maid->image ?: ($order->maid->cv_preview ?: $order->maid->cv_file);
        $imageUrl = $rawPath ? showFile($rawPath) : asset('admin/assets/img/avatar.png');
        $maidName = e($order->maid->name ?? '-');
        $passport = e($order->maid->passport_num ?? '-');

        return '<div class="d-flex align-items-center gap-2">'
            . '<img src="' . $imageUrl . '" alt="maid-avatar" class="border" style="width:60px;height:75px;object-fit:cover;border-radius: 6px;">'
            . '<div class="d-flex flex-column">'
            . '<span class="fw-semibold text-nowrap">' . $maidName . '</span>'
            . '<small class="text-muted">' . e(helperTrans('ingaz.passport_num')) . ': ' . $passport . '</small>'
            . '</div>'
            . '</div>';
    }

    private function actionsHtml(Order $order): string
    {
        $actions = [];

        if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW)) {
            $actions[] = '<li><a class="dropdown-item" href="' . route('showSingleOrder', ['orderId' => $order->id]) . '">
                            <i class="fa fa-eye"></i> ' . e(helperTrans('ingaz.show')) . '
                        </a></li>';
        }

        if ((@AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_EDIT) || @AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_CONTROL)) && !$order->deleted_at) {
            $actions[] = '<li><button class="dropdown-item edit-status" data-id="' . $order->id . '">
                            <i class="fa fa-edit"></i> ' . e(helperTrans('ingaz.edit_order_status')) . '
                        </button></li>';
        }

        if ((@AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_DELETE) || @AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_CONTROL)) && $order->deleted_at) {
            $actions[] = '<li><button class="dropdown-item restore-order" data-id="' . $order->id . '">
                            <i class="fa fa-undo"></i> ' . e(helperTrans('ingaz.restore_order')) . '
                        </button></li>';
        }

        if (@AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_DELETE) || @AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_CONTROL)) {
            $actions[] = '<li><hr class="dropdown-divider"></li>';

            if ($order->deleted_at) {
                $actions[] = '<li><button class="dropdown-item force-delete-order text-danger" data-id="' . $order->id . '">
                                <i class="fa fa-trash"></i> ' . e(helperTrans('ingaz.force_delete')) . '
                            </button></li>';
            } else {
                $actions[] = '<li><button class="dropdown-item delete-order text-danger" data-id="' . $order->id . '">
                                <i class="fa fa-trash"></i> ' . e(helperTrans('ingaz.delete_order')) . '
                            </button></li>';
                $actions[] = '<li><button class="dropdown-item force-delete-order text-danger" data-id="' . $order->id . '">
                                <i class="fa fa-trash-alt"></i> ' . e(helperTrans('ingaz.force_delete')) . '
                            </button></li>';
            }
        }

        if (empty($actions)) {
            return '<span class="text-muted">-</span>';
        }

        return ' <div class="dropdown actions-dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false" title="الإجراءات المتاحة">
                        <i class="fas fa-cogs me-1"></i> الإجراءات
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg top-0">'
            . implode('', $actions)
            . '</ul></div>';
    }


    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(NationalityRequest $request, objAction $action)
    {

        $data = $request->only($this->postData);
        $data = $action->store($data);
        return jsonSuccess($data);
    }


    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = Order::with([
                'user',
                'maid',
                'jobType',
                'details.admin',
                'details.package',
                'details.address'
            ])->withTrashed()->findOrFail($id);

            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function show(Request $request, objAction $action, $id)
    {
        $updatedData = $request->only($this->postData);
        $data = $action->updateRequest($id, $updatedData);
        return response()->json(['success' => true]);
    }

    public function update(Request $request, objAction $action, $id)
    {
        try {
            $validated = $request->validate([
                'status' => ['required', Rule::in(array_map(fn ($case) => $case->value, OrderEnum::cases()))],
                'refuse_reason' => ['nullable', 'string', 'max:500'],
            ]);

            $order = Order::withTrashed()->findOrFail($id);

            if ($order->trashed()) {
                return jsonError('لا يمكن تعديل حالة طلب محذوف. قم باستعادته أولاً.', 422);
            }

            $newStatus = $validated['status'];

            if ($this->isReactivationStatus($newStatus) && $this->isRefusalStatus((string) $order->status)) {
                $availabilityError = $this->validateMaidCanBeReactivated($order);
                if ($availabilityError) {
                    return jsonError($availabilityError, 422);
                }
            }

            $order->status = $newStatus;
            $order->status_changed_at = now();
            $order->deadline_at = $order->getDeadlineHours() > 0
                ? now()->addHours($order->getDeadlineHours())
                : null;

            if ($this->isRefusalStatus($newStatus)) {
                $order->refuse_reason = $this->resolveRefuseReason($validated['refuse_reason'] ?? null, $newStatus);
            } else {
                $order->refuse_reason = null;
            }

            $order->save();

            Notification::create([
                'title' => __('ingaz.update_order_status'),
                'description' => __('ingaz.order_status_updated', ['order_id' => $order->id]) . " " . __('ingaz.' . $order->status),
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'type' => 'order_status',
            ]);

            if ($this->isRefusalStatus($newStatus)) {
                $this->releaseMaid($order);
            } elseif ($this->isReactivationStatus($newStatus)) {
                $this->reserveMaid($order);
            }

            return jsonSuccess(['success' => true], __('ingaz.order_updated'), 200);
        } catch (\Illuminate\Validation\ValidationException $validationException) {
            $messages = collect($validationException->errors())->flatten();
            return jsonError($messages->first() ?? $validationException->getMessage(), 422);
        } catch (\Exception $e) {
            \Log::error('Error updating return request order status', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return jsonError(__('ingaz.update_error'), 400);
        }
    }

    public function destroy(Request $request, $id, objAction $action)
    {
        $order = Order::withTrashed()->findOrFail($id);

        if ($order->trashed() || $request->boolean('force_delete')) {
            $order->forceDelete();
        } else {
            $order->delete();
        }

        return jsonSuccess(['success' => true], __("ingaz.order_deleted_successfully"), 200);
    }

    public function restore($id)
    {
        try {
            $order = Order::withTrashed()->findOrFail($id);
            $order->restore();

            Notification::create([
                'title' => __('ingaz.update_order_restored'),
                'description' => __('ingaz.order_restored_msg', ['order_id' => $order->id]),
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'type' => 'order_status',
            ]);

            return jsonSuccess(['success' => true], __('ingaz.order_restored'));
        } catch (\Exception $e) {
            return jsonError(__('ingaz.update_error'));
        }
    }

    private function isRefusalStatus(string $status): bool
    {
        return in_array($status, [OrderEnum::Refused->value, OrderEnum::Return->value], true);
    }

    private function isReactivationStatus(string $status): bool
    {
        return in_array($status, [OrderEnum::New->value, OrderEnum::UnderProcess->value], true);
    }

    private function resolveRefuseReason(?string $reason, string $status): string
    {
        $trimmed = trim((string) $reason);
        if ($trimmed !== '') {
            return $trimmed;
        }

        return $status === OrderEnum::Return->value
            ? __('ingaz.auto_return_reason')
            : __('ingaz.auto_refuse_reason');
    }

    private function releaseMaid(Order $order): void
    {
        $order->loadMissing('maid');

        if (!$order->maid) {
            return;
        }

        $hasOtherActiveOrders = Order::where('maid_id', $order->maid->id)
            ->where('id', '!=', $order->id)
            ->whereNotIn('status', [
                OrderEnum::Completed->value,
                OrderEnum::Done->value,
                OrderEnum::Refused->value,
                OrderEnum::Return->value,
            ])->exists();

        if ($hasOtherActiveOrders) {
            return;
        }

        $order->maid->update([
            'status' => 'available',
            'available' => true,
            'available_at' => now(),
        ]);
    }

    private function validateMaidCanBeReactivated(Order $order): ?string
    {
        $order->loadMissing('maid');

        if (!$order->maid) {
            return 'لا يمكن تنشيط الطلب لأنه غير مرتبط بعاملة.';
        }

        if (!$order->maid->available || !in_array($order->maid->status, ['available', 'completed'], true)) {
            return 'لا يمكن تنشيط الطلب لأن العاملة لم تعد متاحة.';
        }

        if ($order->maid->available_at && now()->lt($order->maid->available_at)) {
            return 'لا يمكن تنشيط الطلب لأن العاملة متاحة من يوم ' . $order->maid->available_at;
        }

        $hasOtherActiveOrders = Order::where('maid_id', $order->maid->id)
            ->where('id', '!=', $order->id)
            ->whereNull('deleted_at')
            ->whereNotIn('status', [
                OrderEnum::Completed->value,
                OrderEnum::Done->value,
                OrderEnum::Refused->value,
                OrderEnum::Return->value,
                OrderEnum::CanceledBySystem->value,
            ])->exists();

        if ($hasOtherActiveOrders) {
            return 'لا يمكن تنشيط الطلب لأن العاملة مرتبطة بطلب نشط آخر.';
        }

        return null;
    }

    private function reserveMaid(Order $order): void
    {
        $order->loadMissing('maid');

        if (!$order->maid) {
            return;
        }

        $order->maid->update([
            'status' => 'reserved',
            'available' => false,
        ]);
    }

    /**
     * Export return requests to PDF
     */
    public function exportPdf(Request $request)
    {
        $rows = $this->buildBaseQuery();
        $this->applyFilters($rows, $request);
        $data = $rows->orderByDesc('created_at')->get();

        // Generate PDF
        $pdf = \PDF::loadView('admin.ingaz.return_request.pdf', compact('data'));
        return $pdf->download('return_requests_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export return requests to Excel
     */
    public function exportExcel(Request $request)
    {
        $rows = $this->buildBaseQuery();
        $this->applyFilters($rows, $request);
        $data = $rows->orderByDesc('created_at')->get();

        // Generate Excel
        return \Excel::download(new \App\Exports\ReturnRequestsExport($data), 'return_requests_' . date('Y-m-d') . '.xlsx');
    }
}
