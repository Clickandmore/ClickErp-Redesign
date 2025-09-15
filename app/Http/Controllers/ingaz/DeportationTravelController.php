<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TravelHistory;
use App\Models\Contract;
use App\Models\ingaz\User;
use App\Models\ingaz\Maid;
use App\Models\DeportationProcedure;
use DataTables;

class DeportationTravelController extends Controller
{
    /**
     * عرض قائمة رحلات الترحيل
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TravelHistory::with([
                'user',
                'maid',
                'maid.nationality.translations',
                'order'
            ])->where('travel_type', 'deportation')
            ->orderBy('id', 'desc');

            // Default to show only active/new trips unless filtered
            if (!$request->filled('status') || $request->status === '') {
                $query->whereIn('status', ['new', 'active', 'scheduled', 'in_progress', 'pending', 'confirmed']);
            } elseif ($request->status !== 'all') {
                // If status is not 'all', filter by specific status
                $query->where('status', $request->status);
            }
            // If status is 'all', don't add any status filter

            // Apply filters
            if ($request->filled('contract_number')) {
                $query->whereHas('order', function($q) use ($request) {
                    $q->where('id', 'like', '%' . $request->contract_number . '%');
                });
            }

            if ($request->filled('maid_name')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->maid_name . '%');
                });
            }

            if ($request->filled('travel_date')) {
                $query->whereDate('arrival_date', $request->travel_date);
            }

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
                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button"  aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fa fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    // تأكيد إتمام الرحلة - فقط للرحلات غير المكتملة
                    if ($row->status != 'completed') {
                        $actions .= '<li><a class="dropdown-item" href="javascript:void(0)" onclick="completeTrip(' . $row->id . ')">
                                        <i class="fa fa-check-circle text-success"></i> تأكيد إتمام الرحلة
                                    </a></li>';
                    }

                    // إذا كانت الرحلة مكتملة، نعرض رسالة
                    if ($row->status == 'completed') {
                        $actions .= '<li><span class="dropdown-item text-muted">
                                        <i class="fa fa-info-circle"></i> الرحلة مكتملة
                                    </span></li>';
                    }

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->rawColumns(['maid_data', 'client', 'travel_details', 'status', 'actions'])
                ->toJson();
        }

        return view('admin.ingaz.DeportationTravel.index');
    }

    /**
     * إنشاء رحلة ترحيل جديدة
     */
    public function create(Request $request)
    {
        $contract = null;
        if ($request->has('contract_id')) {
            $contract = Contract::with(['maid', 'user', 'info'])->find($request->contract_id);
        }

        return view('admin.ingaz.DeportationTravel.create', compact('contract'));
    }

    /**
     * حفظ رحلة الترحيل
     */
    public function store(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'arrival_airport' => 'required',
            'arrival_date' => 'required|date',
            'arrival_time' => 'required',
            'travel_with' => 'required'
        ]);

        $contract = Contract::with(['maid', 'user'])->findOrFail($request->contract_id);

        // إنشاء رحلة الترحيل
        $travel = TravelHistory::create([
            'user_id' => $contract->user_id,
            'maid_id' => $contract->maid_id,
            'order_id' => $contract->id,
            'travel_type' => 'deportation',
            'travel_with' => $request->travel_with,
            'status' => 'scheduled',
            'arrival_airport' => $request->arrival_airport,
            'arrival_date' => $request->arrival_date,
            'arrival_time' => $request->arrival_time,
            'flight_company' => $request->flight_company,
            'flight_number' => $request->flight_number,
            'notes' => $request->notes
        ]);

        // تحديث إجراءات الترحيل
        $procedure = DeportationProcedure::where('contract_id', $contract->id)->first();
        if ($procedure) {
            $procedure->update([
                'trip_scheduled' => true,
                'trip_id' => $travel->id
            ]);
        }

        return redirect()->route('DeportationTravel.index')
            ->with('success', 'تم جدولة رحلة الترحيل بنجاح');
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
}
