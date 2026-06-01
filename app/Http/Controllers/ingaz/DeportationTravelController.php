<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\Contract;
use App\Models\DeportationProcedure;
use App\Models\TravelHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
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
                    if ($row->user) {
                        $html .= '<span>' . $row->user->name . '</span><br>';
                        $html .= '<small class="text-muted">' . $row->user->phone . '</small>';
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
                    $html .= '<i class="fa fa-clock"></i> ' . ($row->arrival_time ?? 'غير محدد');
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
        $contract = null;
        $travel = null;
        if ($request->has('contract_id')) {
            $contract = Contract::with(['maid', 'user', 'info'])->find($request->contract_id);
        }

        return view('admin.ingaz.DeportationTravel.create', compact('contract', 'travel'));
    }

    /**
     * حفظ رحلة الترحيل
     */
    public function store(Request $request)
    {
        $validated = $this->validateTravelData($request);
        $contract = Contract::with(['maid', 'user'])->findOrFail($validated['contract_id']);

        // إنشاء رحلة الترحيل
        $travel = TravelHistory::create([
            'user_id' => $contract->user_id,
            'maid_id' => $contract->maid_id,
            'order_id' => $contract->id,
            'travel_type' => 'deportation',
            'travel_with' => $validated['travel_with'],
            'status' => 'scheduled',
            'arrival_airport' => $validated['arrival_airport'],
            'arrival_date' => $validated['arrival_date'],
            'arrival_time' => $validated['arrival_time'],
            'flight_company' => $validated['flight_company'] ?? null,
            'flight_number' => $validated['flight_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // تحديث إجراءات الترحيل
        $procedure = DeportationProcedure::where('contract_id', $contract->id)->first();
        if ($procedure) {
            $procedure->update([
                'trip_scheduled' => true,
                'trip_id' => $travel->id
            ]);
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
        $travel = TravelHistory::with(['order.maid', 'order.user', 'order.info'])
            ->where('travel_type', 'deportation')
            ->findOrFail($id);

        if ($travel->status === 'completed') {
            return redirect()->route('DeportationTravel.index')
                ->with('error', 'لا يمكن تعديل رحلة ترحيل مكتملة');
        }

        $contract = $travel->order;

        return view('admin.ingaz.DeportationTravel.create', compact('contract', 'travel'));
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

        $contract = Contract::with(['maid', 'user'])->findOrFail($validated['contract_id']);

        $travel->update([
            'user_id' => $contract->user_id,
            'maid_id' => $contract->maid_id,
            'order_id' => $contract->id,
            'travel_with' => $validated['travel_with'],
            'arrival_airport' => $validated['arrival_airport'],
            'arrival_date' => $validated['arrival_date'],
            'arrival_time' => $validated['arrival_time'],
            'flight_company' => $validated['flight_company'] ?? null,
            'flight_number' => $validated['flight_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

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

        // تحديث إجراءات الترحيل إلى مكتملة
        $procedure = DeportationProcedure::where('trip_id', $travel->id)->first();
        if ($procedure && $procedure->isAllProceduresCompleted()) {
            $procedure->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth('admin')->id()
            ]);

            // تحديث حالة العقد
            $procedure->contract->update([
                'status' => \App\Enums\ingaz\ContractStatusEnum::Back->value
            ]);

            // إزالة من الإيواء
            if ($procedure->contract->info) {
                $procedure->contract->info->update([
                    'shelter_id' => null,
                    'wants_to_work' => null,
                    'maid_date_back' => now(),
                    'receipt_source' => null
                ]);
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
        return $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'arrival_airport' => 'required',
            'arrival_date' => 'required|date',
            'arrival_time' => 'required',
            'travel_with' => 'required',
            'flight_company' => 'nullable|string|max:255',
            'flight_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'contract_id.required' => 'يجب اختيار العقد.',
            'contract_id.exists' => 'العقد غير موجود في النظام.',
            'arrival_airport.required' => 'حقل مطار الوصول مطلوب.',
            'arrival_date.required' => 'حقل تاريخ الوصول مطلوب.',
            'arrival_date.date' => 'صيغة تاريخ الوصول غير صحيحة.',
            'arrival_time.required' => 'حقل وقت الوصول مطلوب.',
            'travel_with.required' => 'يجب تحديد المرافق في السفر.',
        ]);
    }

    private function deportationTravelQuery(Request $request)
    {
        $query = TravelHistory::with([
            'user',
            'maid',
            'maid.nationality.translations',
            'order'
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
            optional($row->user)->name ?: 'غير محدد',
            optional($row->user)->phone ?: '-',
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
