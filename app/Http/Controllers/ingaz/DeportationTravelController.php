<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\AdminNotification;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\DeportationProcedure;
use App\Models\TravelHistory;
use App\Models\TravelHistoryFiles;
use App\Services\ManagedFileStorage;
use App\Support\TravelAirports;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class DeportationTravelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:travel_view,admin', ['only' => ['index', 'show', 'statistics', 'export', 'print']]);
        $this->middleware('permission:travel_create,admin', ['only' => ['create', 'store']]);
        $this->middleware('permission:travel_edit,admin', ['only' => ['edit', 'update']]);
        $this->middleware('permission:travel_control,admin', ['only' => ['complete']]);
        $this->middleware('permission:travel_delete,admin', ['only' => ['destroy']]);
    }

    /**
     * عرض قائمة رحلات الترحيل
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->deportationTravelQuery($request);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('contract_number', function ($row) {
                    return $row->order_id ? 'عقد رقم: ' . $row->order_id : 'غير محدد';
                })
                ->addColumn('maid_data', function ($row) {
                    $html = '<div class="maid-info">';
                    if ($row->maid) {
                        $html .= '<span class="fw-bold">' . $row->maid->name . '</span><br>';
                        $html .= '<small class="text-muted">رقم: ' . $row->maid->id . '</small>';
                    } else {
                        $html .= '<span class="text-muted">غير محدد</span>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('client', function ($row) {
                    $html = '<div class="client-info">';
                    if ($row->client) {
                        $html .= '<span>' . $row->client->name . '</span><br>';
                        $html .= '<small class="text-muted">' . $row->client->phone . '</small>';
                    } else {
                        $html .= '<span class="text-muted">غير محدد</span>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('travel_details', function ($row) {
                    $html = '<div class="travel-details">';
                    $html .= '<i class="fa fa-plane"></i> ' . $row->arrival_airport . '<br>';
                    $html .= '<i class="fa fa-calendar"></i> ' . ($row->arrival_date ? \Carbon\Carbon::parse($row->arrival_date)->format('Y-m-d') : 'غير محدد') . '<br>';
                    $html .= '<i class="fa fa-clock"></i> ' . ($row->arrival_time ?? 'غير محدد') . '<br>';
                    $html .= '<i class="fa fa-user-check"></i> '
                        . ($row->assignedTo ? e($row->assignedTo->name) : 'غير مسندة');
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('status', function ($row) {
                    $statusClass = $row->status == 'completed' ? 'success' : 'warning';
                    $statusText = $row->status == 'completed' ? 'مكتملة' : 'مجدولة';
                    return '<span class="badge bg-' . $statusClass . '">' . $statusText . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    $canEdit = auth('admin')->check() && \AdminGuardHasPerm(\App\PermKeys::TRAVEL_EDIT);
                    $canDelete = auth('admin')->check() && \AdminGuardHasPerm(\App\PermKeys::TRAVEL_DELETE);
                    $canControl = auth('admin')->check() && \AdminGuardHasPerm(\App\PermKeys::TRAVEL_CONTROL);
                    $menuItems = [];

                    if ($row->status !== 'completed' && $canEdit) {
                        $menuItems[] = '<li><a class="dropdown-item" href="' . route('DeportationTravel.edit', $row->id) . '">
                                            <i class="fa fa-edit"></i> تعديل الرحلة
                                        </a></li>';
                    }

                    if ($row->status !== 'completed' && $canControl) {
                        $menuItems[] = '<li><a class="dropdown-item" href="javascript:void(0)" onclick="completeTrip(' . $row->id . ')">
                                            <i class="fa fa-check-circle text-success"></i> تأكيد إتمام الرحلة
                                        </a></li>';
                    }

                    if ($row->status !== 'completed' && $canDelete) {
                        $menuItems[] = '<li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteTravel(' . $row->id . ')">
                                            <i class="fa fa-trash"></i> حذف الرحلة
                                        </a></li>';
                    }

                    if ($menuItems === []) {
                        return '<span class="text-muted">لا توجد إجراءات</span>';
                    }

                    return '<div class="dropdown actions-dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false" title="الإجراءات المتاحة">
                                    <i class="fa fa-cogs me-1"></i> الإجراءات
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg">'
                                . implode('', $menuItems) .
                                '</ul></div>';
                })
                ->rawColumns(['maid_data', 'client', 'travel_details', 'status', 'actions'])
                ->toJson();
        }

        return view('admin.ingaz.DeportationTravel.index');
    }

    public function export(Request $request)
    {
        $rows = $this->deportationTravelQuery($request)->get();
        $filename = 'deportation-trips-' . now()->format('Y-m-d-His') . '.csv';

        return $this->csvDownload($filename, $this->deportationExportHeadings(), $rows->map(function (TravelHistory $row) {
            return $this->deportationExportRow($row);
        }));
    }

    public function print(Request $request)
    {
        $rows = $this->deportationTravelQuery($request)->get();

        return view('admin.ingaz.DeportationTravel.print', [
            'title' => 'رحلات الترحيل',
            'headings' => $this->deportationExportHeadings(),
            'rows' => $rows->map(fn (TravelHistory $row) => $this->deportationExportRow($row)),
        ]);
    }

    /**
     * إنشاء رحلة ترحيل جديدة
     */
    public function create(Request $request)
    {
        $travel = null;
        $contract = $request->filled('contract_id')
            ? Contract::with(['maid', 'user', 'companyProfile', 'info'])->find((int) $request->input('contract_id'))
            : null;
        $contracts = $this->availableDeportationContracts($contract);
        $arrivalAirports = TravelAirports::options();
        $assignableStaff = $this->assignableStaff();

        return view('admin.ingaz.DeportationTravel.create', compact(
            'contract',
            'contracts',
            'travel',
            'arrivalAirports',
            'assignableStaff'
        ));
    }

    /**
     * حفظ رحلة الترحيل
     */
    public function store(Request $request)
    {
        $validated = $this->validateTravelData($request);
        $contract = Contract::with(['maid', 'user', 'companyProfile'])->findOrFail($validated['contract_id']);
        $assigneeId = $validated['assigned_to'] ?? null;

        // إنشاء رحلة الترحيل
        $travel = TravelHistory::create([
            'user_id' => $contract->company_profile_id ? null : $contract->user_id,
            'company_profile_id' => $contract->company_profile_id,
            'maid_id' => $contract->maid_id,
            'order_id' => $contract->id,
            'trip_group_code' => null,
            'travel_type' => 'deportation',
            'travel_with' => $validated['travel_with'],
            'status' => 'scheduled',
            'arrival_airport' => $validated['arrival_airport'],
            'arrival_date' => $validated['arrival_date'],
            'arrival_time' => $validated['arrival_time'],
            'flight_company' => $validated['flight_company'] ?? null,
            'flight_number' => $validated['flight_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'assigned_to' => $assigneeId,
            'assigned_by' => $assigneeId ? auth('admin')->id() : null,
            'assigned_at' => $assigneeId ? now() : null,
            'assignment_note' => $assigneeId ? ($validated['assignment_note'] ?? null) : null,
        ]);

        $this->storeTravelFiles($request, $travel);

        // تحديث إجراءات الترحيل
        $procedure = DeportationProcedure::where('contract_id', $contract->id)->first();
        if ($procedure) {
            $procedure->update([
                'trip_scheduled' => true,
                'trip_id' => $travel->id
            ]);
        }

        if ($assigneeId) {
            $this->notifyAssignee($travel, $contract);
        }

        return  jsonSuccess(['url'=>route('DeportationTravel.index')] , __("تم جدولة رحلة الترحيل بنجاح") , 201);

//        return redirect()->route('DeportationTravel.index')
//            ->with('success', 'تم جدولة رحلة الترحيل بنجاح');
    }

    /**
     * عرض تفاصيل الرحلة
     */
    public function show($id)
    {
        // بما أن كل التفاصيل معروضة في الجدول، نعيد التوجيه إلى صفحة القائمة
        return redirect()->route('DeportationTravel.index')
            ->with('info', 'جميع تفاصيل الرحلة متوفرة في جدول رحلات الترحيل');
    }

    public function edit($id)
    {
        $travel = TravelHistory::with(['deportationContract.maid', 'deportationContract.user', 'deportationContract.companyProfile', 'deportationContract.info', 'files'])
            ->where('travel_type', 'deportation')
            ->findOrFail($id);

        if ($travel->status === 'completed') {
            return redirect()->route('DeportationTravel.index')
                ->with('error', 'لا يمكن تعديل رحلة ترحيل مكتملة');
        }

        $contract = $travel->deportationContract;
        $contracts = $this->availableDeportationContracts($contract);
        $arrivalAirports = TravelAirports::options();
        $assignableStaff = $this->assignableStaff();

        return view('admin.ingaz.DeportationTravel.create', compact(
            'contract',
            'contracts',
            'travel',
            'arrivalAirports',
            'assignableStaff'
        ));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validateTravelData($request);
        $travel = TravelHistory::where('travel_type', 'deportation')->findOrFail($id);

        if ($travel->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل رحلة مكتملة'
            ], 422);
        }

        $contract = Contract::with(['maid', 'user', 'companyProfile'])->findOrFail($validated['contract_id']);
        $previousAssigneeId = $travel->assigned_to;
        $assigneeId = $validated['assigned_to'] ?? null;
        $assigneeChanged = (string) $previousAssigneeId !== (string) $assigneeId;

        $travel->update([
            'user_id' => $contract->company_profile_id ? null : $contract->user_id,
            'company_profile_id' => $contract->company_profile_id,
            'maid_id' => $contract->maid_id,
            'order_id' => $contract->id,
            'trip_group_code' => null,
            'travel_with' => $validated['travel_with'],
            'arrival_airport' => $validated['arrival_airport'],
            'arrival_date' => $validated['arrival_date'],
            'arrival_time' => $validated['arrival_time'],
            'flight_company' => $validated['flight_company'] ?? null,
            'flight_number' => $validated['flight_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'assigned_to' => $assigneeId,
            'assigned_by' => $assigneeId
                ? ($assigneeChanged ? auth('admin')->id() : ($travel->assigned_by ?: auth('admin')->id()))
                : null,
            'assigned_at' => $assigneeId
                ? ($assigneeChanged ? now() : ($travel->assigned_at ?: now()))
                : null,
            'assignment_note' => $assigneeId ? ($validated['assignment_note'] ?? null) : null,
        ]);

        $this->storeTravelFiles($request, $travel);

        if ($assigneeId && $assigneeChanged) {
            $this->notifyAssignee($travel, $contract);
        }

        return jsonSuccess(['url' => route('DeportationTravel.index')], __('تم تحديث رحلة الترحيل بنجاح'));
    }

    /**
     * جلب إحصائيات رحلات الترحيل
     */
    public function statistics(Request $request)
    {
        try {
            $query = TravelHistory::where('travel_type', 'deportation');

            // فلتر الحالة
            if ($request->filled('status')) {
                if ($request->status !== 'all') {
                    $query->where('status', $request->status);
                }
            }

            // فلتر التاريخ من
            if ($request->filled('date_from')) {
                $query->whereDate('arrival_date', '>=', $request->date_from);
            }

            // فلتر التاريخ إلى
            if ($request->filled('date_to')) {
                $query->whereDate('arrival_date', '<=', $request->date_to);
            }

            $total = $query->count();
            $active = $query->clone()->whereIn('status', ['new', 'active', 'scheduled', 'in_progress', 'pending', 'confirmed'])->count();
            $completed = $query->clone()->where('status', 'completed')->count();
            $upcoming = $query->clone()->whereIn('status', ['new', 'scheduled', 'pending'])
                ->where('arrival_date', '>', now())->count();
            $cancelled = $query->clone()->where('status', 'cancelled')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'active' => $active,
                    'completed' => $completed,
                    'upcoming' => $upcoming,
                    'cancelled' => $cancelled
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in deportation travel statistics:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ], 500);
        }
    }

    /**
     * تأكيد إتمام الرحلة
     */
    public function complete($id)
    {
        $travel = TravelHistory::findOrFail($id);

        if ($travel->travel_type !== 'deportation') {
            return response()->json([
                'success' => false,
                'message' => 'هذه ليست رحلة ترحيل'
            ], 400);
        }

        $travel->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        // تحديث إجراءات الترحيل إلى مكتملة (القيد 18 + حالة العاملة + سجل حالة العقد من خدمة واحدة)
        $procedure = DeportationProcedure::where('trip_id', $travel->id)->first();
        if ($procedure && $procedure->isAllProceduresCompleted()) {
            try {
                app(\App\Services\ingaz\DeportationCompletionService::class)->complete($procedure, $travel->arrival_date ?? null);
            } catch (\RuntimeException $e) {
                $travel->update(['status' => 'scheduled', 'completed_at' => null]);

                return response()->json([
                    'success' => false,
                    'message' => 'فشل إنشاء قيد الترحيل ولم يتم إنهاء الرحلة: ' . $e->getMessage(),
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تأكيد إتمام الرحلة بنجاح'
        ]);
    }

    /**
     * حذف رحلة ترحيل
     */
    public function destroy($id)
    {
        try {
            $travel = TravelHistory::findOrFail($id);

            // التحقق من أن الرحلة من نوع ترحيل
            if ($travel->travel_type !== 'deportation') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه ليست رحلة ترحيل'
                ], 400);
            }

            // التحقق من أن الرحلة لم تكتمل
            if ($travel->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف رحلة مكتملة'
                ], 400);
            }

            // إذا كانت مرتبطة بإجراءات ترحيل، نحدثها
            $procedure = DeportationProcedure::where('trip_id', $travel->id)->first();
            if ($procedure) {
                $procedure->update([
                    'trip_scheduled' => false,
                    'trip_id' => null
                ]);
            }

            $travel->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الرحلة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الرحلة: ' . $e->getMessage()
            ], 500);
        }
    }

    private function validateTravelData(Request $request): array
    {
        $validated = $request->validate([
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'arrival_airport' => 'required',
            'arrival_date' => 'required|date',
            'arrival_time' => 'required',
            'travel_with' => ['required', Rule::in(['مطار', 'نقل جماعي', 'قطار'])],
            'flight_company' => 'nullable|string|max:255',
            'flight_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:admins,id',
            'assignment_note' => 'nullable|string|max:2000',
            'files' => 'nullable|array',
            'files.*' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ], [
            'contract_id.required' => 'يجب اختيار العقد.',
            'contract_id.integer' => 'يجب اختيار عقد واحد فقط لرحلة الترحيل.',
            'contract_id.exists' => 'العقد غير موجود في النظام.',
            'arrival_airport.required' => 'يجب اختيار المطار.',
            'arrival_date.required' => 'تاريخ الرحلة مطلوب.',
            'arrival_date.date' => 'صيغة تاريخ الرحلة غير صحيحة.',
            'arrival_time.required' => 'وقت الرحلة مطلوب.',
            'travel_with.required' => 'يجب تحديد وسيلة السفر.',
            'travel_with.in' => 'وسيلة السفر المختارة غير صحيحة.',
            'assigned_to.exists' => 'السائق أو الموظف المختار غير موجود.',
            'assignment_note.max' => 'ملاحظة التكليف يجب ألا تتجاوز 2000 حرف.',
            'files.*.file' => 'أحد المرفقات المرفوعة غير صالح.',
            'files.*.mimes' => 'المرفقات المسموحة هي PDF أو JPG أو PNG فقط.',
            'files.*.max' => 'حجم كل مرفق يجب ألا يتجاوز 4 ميجابايت.',
        ]);

        $travelId = $request->route('id');
        $alreadyScheduled = TravelHistory::query()
            ->where('travel_type', 'deportation')
            ->where('order_id', $validated['contract_id'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->when($travelId, fn ($query) => $query->where('id', '!=', $travelId))
            ->exists();

        if ($alreadyScheduled) {
            throw ValidationException::withMessages([
                'contract_id' => 'هذا العقد مرتبط بالفعل برحلة ترحيل غير مكتملة.',
            ]);
        }

        return $validated;
    }

    private function assignableStaff(): Collection
    {
        return Admin::query()
            ->activeForOperations()
            ->select('id', 'name', 'phone', 'admin_type', 'status')
            ->orderBy('name')
            ->get();
    }

    private function storeTravelFiles(Request $request, TravelHistory $travel): void
    {
        foreach ($request->file('files', []) as $file) {
            $fileName = app(ManagedFileStorage::class)->uploadFile($file, 'travel');

            TravelHistoryFiles::create([
                'travel_id' => $travel->id,
                'file' => $fileName,
            ]);
        }
    }

    private function notifyAssignee(TravelHistory $travel, Contract $contract): void
    {
        $assigneeId = $travel->assigned_to;
        if (! $assigneeId) {
            return;
        }

        $message = 'تم إسناد رحلة ترحيل العقد #' . $contract->id
            . ' للعاملة ' . ($contract->maid?->name ?: 'غير محدد')
            . ' بتاريخ ' . ($travel->arrival_date ?: 'غير محدد')
            . ' الساعة ' . ($travel->arrival_time ?: 'غير محدد') . '.';

        if ($travel->assignment_note) {
            $message .= ' ملاحظة الإسناد: ' . $travel->assignment_note;
        }

        AdminNotification::create([
            'admin_id' => $assigneeId,
            'created_by' => auth('admin')->id(),
            'title' => 'إسناد رحلة ترحيل',
            'message' => $message,
            'type' => 'travel_assignment',
            'link' => route('DeportationTravel.index'),
            'data' => [
                'trip_id' => $travel->id,
                'travel_type' => 'deportation',
                'order_id' => $contract->id,
                'arrival_date' => $travel->arrival_date,
                'arrival_time' => $travel->arrival_time,
                'arrival_airport' => TravelAirports::label($travel->arrival_airport),
            ],
        ]);
    }

    /**
     * عقود مرحلة الترحيل التي لا ترتبط برحلة نشطة. يظل العقد الحالي ظاهراً أثناء التعديل
     * أو عند فتح النموذج مباشرة من صفحة إجراءات الترحيل.
     */
    private function availableDeportationContracts(?Contract $selectedContract = null): Collection
    {
        $contracts = Contract::query()
            ->where(function ($query) {
                $query->whereHas('deportationProcedure', function ($procedureQuery) {
                    $procedureQuery->whereNotIn('status', ['completed', 'cancelled']);
                })->orWhere(function ($stageQuery) {
                    $stageQuery->insideShelter()
                        ->whereHas('info', function ($infoQuery) {
                            $infoQuery->where('wants_to_work', 'no');
                        });
                });
            })
            ->whereDoesntHave('travelHistory', function ($query) {
                $query->where('travel_type', 'deportation')
                    ->whereNotIn('status', ['completed', 'cancelled']);
            })
            ->with(['maid', 'user', 'companyProfile', 'info'])
            ->latest('id')
            ->get();

        if ($selectedContract && ! $contracts->contains('id', $selectedContract->id)) {
            $contracts->prepend($selectedContract->loadMissing(['maid', 'user', 'companyProfile', 'info']));
        }

        return $contracts;
    }

    private function deportationTravelQuery(Request $request)
    {
        $query = TravelHistory::with([
            'user',
            'companyProfile',
            'maid',
            'maid.nationality.translations',
            'order',
            'assignedTo',
        ])->where('travel_type', 'deportation')
            ->orderBy('id', 'desc');

        if (!$request->filled('status') || $request->status === '') {
            $query->whereIn('status', [
                'new', 'active', 'scheduled', 'in_progress', 'pending', 'confirmed'
            ]);
        } elseif ($request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('contract_number')) {
            $contractNumber = (int) $request->contract_number;
            $query->where(function ($q) use ($contractNumber) {
                $q->whereHas('order', function ($sub) use ($contractNumber) {
                    $sub->where('id', $contractNumber);
                })->orWhere('order_id', $contractNumber);
            });
        }

        if ($request->filled('maid_name')) {
            $query->whereHas('maid', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->maid_name . '%');
            });
        }

        if ($request->filled('travel_date')) {
            $query->whereDate('arrival_date', $request->travel_date);
        }

        return $query;
    }

    private function deportationExportHeadings(): array
    {
        return [
            '#',
            'رقم العقد',
            'العاملة',
            'رقم الجواز',
            'العميل',
            'الجوال',
            'المطار',
            'تاريخ السفر',
            'وقت السفر',
            'نوع السفر',
            'شركة الطيران',
            'رقم الرحلة',
            'الحالة',
        ];
    }

    private function deportationExportRow(TravelHistory $row): array
    {
        $statuses = __('travel_types.status') ?: [];

        return [
            $row->id,
            $row->order_id ? '#' . $row->order_id : 'غير محدد',
            optional($row->maid)->name ?: 'غير محدد',
            optional($row->maid)->passport_num ?: '-',
            optional($row->client)->name ?: 'غير محدد',
            optional($row->client)->phone ?: '-',
            $row->arrival_airport ?: 'غير محدد',
            $row->arrival_date ? \Carbon\Carbon::parse($row->arrival_date)->format('Y-m-d') : 'غير محدد',
            $row->arrival_time ?: 'غير محدد',
            $row->travel_with ?: '-',
            $row->flight_company ?: '-',
            $row->flight_number ?: '-',
            $statuses[$row->status] ?? ($row->status === 'completed' ? 'مكتملة' : 'مجدولة'),
        ];
    }

    private function csvDownload(string $filename, array $headings, Collection $rows)
    {
        $callback = function () use ($headings, $rows) {
            $handle = fopen('php://output', 'w');
            echo "\xEF\xBB\xBF";
            fputcsv($handle, $headings);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return Response::streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
