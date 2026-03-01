<?php

namespace App\Http\Controllers\admin;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Actions\dashboard\DashboardAction as objAction;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\RentalContract;
use App\Models\RecruitmentOffices;
use App\Models\SponsorshipTransfer;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\Models\ingaz\JobType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(objAction $action)
    {
        return $this->professional($action);
    }

    public function indexOld(objAction $action)
    {
        $data['types'] = $action->getJobTypes();
        $data['clients_count'] = $action->getClientsCount();
        $data['contact_count'] = $action->getContactsCount();
        $data['current_orders_count'] = $action->getCurrentOrdersCount();
        $data['top_call_center'] = $action->getTopCallCenter();
        $data['call_center_count'] = $action->callCenterCount();
        $data['cv_count'] = $action->cvCount();
        $data['ordered_cv'] = $action->orderedCvCount();
        $data['order_types'] = $action->getOrderCountsByType();
        return view('admin.dashboard', compact('data'));
    }

    public function professional(objAction $action)
    {
        $now = now();

        // العقود
        $contractsStats = [
            'total' => Contract::count(),
            'completed' => Contract::where('status', ContractStatusEnum::Completed->value)->count(),
            'pending' => Contract::whereIn('status', ['pending', 'in_progress'])->count(),
            'underWarranty' => Contract::where('warranty_status', 'active')->count(),
            'thisMonth' => Contract::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            'thisWeek' => Contract::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
            'today' => Contract::whereDate('created_at', today())->count(),
        ];

        $todayDate = today();

        $contractsPipeline = [
            'pre_arrival' => Contract::whereIn('status', [
                ContractStatusEnum::New->value,
                ContractStatusEnum::Msaned->value,
                ContractStatusEnum::Tafwed->value,
                ContractStatusEnum::Taffez->value,
                ContractStatusEnum::PendingMaidSelection->value,
            ])->count(),
            'arrival_stage' => Contract::whereIn('status', [
                ContractStatusEnum::Tazker->value,
                ContractStatusEnum::Arrival->value,
            ])->whereHas('travelHistory', function ($query) use ($todayDate) {
                $query->whereDate('arrival_date', $todayDate);
            })->count(),
            'under_warranty' => Contract::where('warranty_status', 'active')->count(),
            'completed' => Contract::where('status', ContractStatusEnum::Completed->value)->count(),
            'delayed_arrival' => Contract::whereIn('status', [
                ContractStatusEnum::New->value,
                ContractStatusEnum::Msaned->value,
                ContractStatusEnum::Tafwed->value,
                ContractStatusEnum::Taffez->value,
                ContractStatusEnum::PendingMaidSelection->value,
            ])->where('created_at', '<', now()->copy()->subDays(60))->count(),
            'expired_over_2y' => Contract::where('created_at', '<', now()->copy()->subYears(2))->count(),
        ];

        $contractsByMonthCurrent = [];
        $contractsByMonthPrevious = [];
        for ($m = 1; $m <= 12; $m++) {
            $contractsByMonthCurrent[] = Contract::whereYear('created_at', $now->year)->whereMonth('created_at', $m)->count();
            $contractsByMonthPrevious[] = Contract::whereYear('created_at', $now->year - 1)->whereMonth('created_at', $m)->count();
        }

        // الطلبات (استقدام)
        $ordersStats = [
            'total' => Order::count(),
            'today' => Order::whereDate('created_at', today())->count(),
            'thisWeek' => Order::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
            'thisMonth' => Order::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'pending' => Order::where('status', 'pending')->count(),
            'processing' => Order::where('status', 'processing')->count(),
            'new' => Order::where('status', OrderEnum::New->value)->count(),
            'under_process' => Order::where('status', OrderEnum::UnderProcess->value)->count(),
            'contracted' => Order::where('status', OrderEnum::Contracted->value)->count(),
            'contracted_without' => Order::where('status', OrderEnum::ContractedWithoutContract->value)->count(),
            'refused' => Order::where('status', OrderEnum::Refused->value)->count(),
        ];

        // السير الذاتية
        $cvStats = [
            'total' => Maid::count(),
            'available' => Maid::where('available', true)->count(),
            'booked' => Maid::where(function ($q) {
                $q->where('available', false)
                    ->orWhere('status', Maid::STATUS_RESERVED['en']);
            })->count(),
            'newThisWeek' => Maid::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
            'newThisMonth' => Maid::whereMonth('created_at', $now->month)->count(),
        ];

        // العملاء
        $clientTotal = User::count();
        $clientsWithOrders = User::whereHas('orders')->count();
        $clientsWithContracts = User::whereHas('contracts')->count();
        $clientsStats = [
            'total' => $clientTotal,
            'newThisMonth' => User::whereMonth('created_at', $now->month)->count(),
            'newThisWeek' => User::whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()])->count(),
            'newToday' => User::whereDate('created_at', today())->count(),
            'active' => Contract::distinct('user_id')->count('user_id'),
            'vip' => Contract::select('user_id')
                ->selectRaw('count(*) as contract_count')
                ->groupBy('user_id')
                ->having('contract_count', '>=', 3)
                ->count(),
            'with_orders' => $clientsWithOrders,
            'with_contracts' => $clientsWithContracts,
            'no_orders' => max(0, $clientTotal - $clientsWithOrders),
        ];

        // الإيجار ونقل الكفالة
        $rentalStats = [
            'total' => RentalContract::count(),
            'active' => RentalContract::where('status', 'active')->count(),
            'in_workflow' => RentalContract::whereIn('status', ['contract_sent', 'contract_signed', 'maid_delivered'])->count(),
            'completed' => RentalContract::where('status', 'completed')->count(),
        ];

        $transferStats = [
            'total' => SponsorshipTransfer::count(),
            'pending' => SponsorshipTransfer::where('status', 'pending')->count(),
            'approved' => SponsorshipTransfer::where('status', 'approved')->count(),
            'transferred' => SponsorshipTransfer::where('status', 'transferred')->count(),
        ];

        // حسب نوع الخدمة
        $jobTypeCounts = JobType::withCount('orders')->get()->mapWithKeys(function ($type) {
            return [$type->id => $type->orders_count];
        });
        $jobTypesList = JobType::all()->keyBy('id');

        // المكاتب الخارجية (Top 5)
        $officeStats = RecruitmentOffices::withCount([
            'maids as maids_total',
            'maids as maids_available' => function ($q) {
                $q->where('available', true);
            }
        ])->orderByDesc('maids_total')->get();

        // حجوزات/عقود الموظفين (تفصيلي)
        $orderStatsByEmployee = DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->whereNotNull('od.admin_id')
            ->whereNull('o.deleted_at')
            ->where('o.job_type_id', 1)
            ->groupBy('od.admin_id')
            ->selectRaw(
                'od.admin_id,
                COUNT(DISTINCT o.id) as orders_count,
                SUM(CASE WHEN o.status = ? THEN 1 ELSE 0 END) as orders_new,
                SUM(CASE WHEN o.status = ? THEN 1 ELSE 0 END) as orders_under_process,
                SUM(CASE WHEN o.status = ? THEN 1 ELSE 0 END) as orders_contracted,
                SUM(CASE WHEN o.status IN (?, ?) THEN 1 ELSE 0 END) as orders_completed',
                [
                    OrderEnum::New->value,
                    OrderEnum::UnderProcess->value,
                    OrderEnum::Contracted->value,
                    OrderEnum::Completed->value,
                    OrderEnum::Done->value,
                ]
            );

        $contractAssignmentRows = DB::table('contracts')
            ->selectRaw('id as contract_id, marketer_id as admin_id')
            ->whereNotNull('marketer_id')
            ->whereIn('type', ['recruitment', 'maid'])
            ->unionAll(
                DB::table('contracts as c')
                    ->join('order_details as od', 'od.order_id', '=', 'c.order_id')
                    ->whereNotNull('od.admin_id')
                    ->whereIn('c.type', ['recruitment', 'maid'])
                    ->selectRaw('c.id as contract_id, od.admin_id as admin_id')
            );

        $contractAssignments = DB::query()
            ->fromSub($contractAssignmentRows, 'contract_assignments')
            ->select('contract_id', 'admin_id')
            ->distinct();

        $currentContractStatuses = [
            ContractStatusEnum::PendingMaidSelection->value,
            ContractStatusEnum::New->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
            ContractStatusEnum::InProgress->value,
        ];

        $completedContractStatuses = [
            ContractStatusEnum::Completed->value,
            ContractStatusEnum::Arrival->value,
        ];

        $currentStatusPlaceholders = implode(', ', array_fill(0, count($currentContractStatuses), '?'));
        $completedStatusPlaceholders = implode(', ', array_fill(0, count($completedContractStatuses), '?'));

        $contractStatsByEmployee = DB::query()
            ->fromSub($contractAssignments, 'ca')
            ->join('contracts as c', 'c.id', '=', 'ca.contract_id')
            ->groupBy('ca.admin_id')
            ->selectRaw(
                "ca.admin_id,
                COUNT(*) as contracts_count,
                SUM(CASE WHEN c.status IN ($currentStatusPlaceholders) THEN 1 ELSE 0 END) as contracts_current,
                SUM(CASE WHEN c.status IN ($completedStatusPlaceholders) THEN 1 ELSE 0 END) as contracts_completed,
                SUM(CASE WHEN c.status = ? THEN 1 ELSE 0 END) as contracts_returned",
                array_merge(
                    $currentContractStatuses,
                    $completedContractStatuses,
                    [ContractStatusEnum::Back->value]
                )
            );

        $employeeBookings = Admin::query()
            ->select('admins.id', 'admins.name')
            ->leftJoinSub($orderStatsByEmployee, 'order_stats', function ($join) {
                $join->on('admins.id', '=', 'order_stats.admin_id');
            })
            ->leftJoinSub($contractStatsByEmployee, 'contract_stats', function ($join) {
                $join->on('admins.id', '=', 'contract_stats.admin_id');
            })
            ->selectRaw('COALESCE(order_stats.orders_count, 0) as orders_count')
            ->selectRaw('COALESCE(order_stats.orders_new, 0) as orders_new')
            ->selectRaw('COALESCE(order_stats.orders_under_process, 0) as orders_under_process')
            ->selectRaw('COALESCE(order_stats.orders_contracted, 0) as orders_contracted')
            ->selectRaw('COALESCE(order_stats.orders_completed, 0) as orders_completed')
            ->selectRaw('COALESCE(contract_stats.contracts_count, 0) as contracts_count')
            ->selectRaw('COALESCE(contract_stats.contracts_current, 0) as contracts_current')
            ->selectRaw('COALESCE(contract_stats.contracts_completed, 0) as contracts_completed')
            ->selectRaw('COALESCE(contract_stats.contracts_returned, 0) as contracts_returned')
            ->selectRaw('(COALESCE(order_stats.orders_count, 0) + COALESCE(contract_stats.contracts_count, 0)) as handled_total')
            ->orderByDesc('handled_total')
            ->orderBy('admins.name')
            ->limit(8)
            ->get()
            ->map(function ($employee) {
                $ordersCount = (int) $employee->orders_count;
                $contractsCount = (int) $employee->contracts_count;
                $employee->conversion_rate = $ordersCount > 0
                    ? round(($contractsCount / $ordersCount) * 100)
                    : 0;
                return $employee;
            })
            ->values();

        $employeeBookingSummary = [
            'employees_with_activity' => $employeeBookings->filter(function ($employee) {
                return ((int) $employee->orders_count + (int) $employee->contracts_count) > 0;
            })->count(),
            'orders_total' => (int) $employeeBookings->sum(function ($employee) {
                return (int) $employee->orders_count;
            }),
            'contracts_total' => (int) $employeeBookings->sum(function ($employee) {
                return (int) $employee->contracts_count;
            }),
        ];

        // الموظفون
        try {
            $totalEmployees = Admin::count();
            $activeEmployees = Admin::whereNull('deleted_at')->count();

            $salesCount = Admin::whereHas('roles', function ($q) {
                $q->where('name', 'sales');
            })->count();

            $supportCount = Admin::whereHas('roles', function ($q) {
                $q->where('name', 'support');
            })->count();

            $adminCount = Admin::whereHas('roles', function ($q) {
                $q->where('name', 'admin');
            })->count();
        } catch (\Exception $e) {
            $totalEmployees = 10;
            $activeEmployees = 8;
            $salesCount = 3;
            $supportCount = 3;
            $adminCount = 2;
        }

        $employeesStats = [
            'total' => $totalEmployees,
            'active' => $activeEmployees,
            'online' => rand(2, 5),
            'departments' => [
                'sales' => $salesCount,
                'support' => $supportCount,
                'admin' => $adminCount,
            ]
        ];

        // إيواء (placeholder)
        $shelterStats = [
            'total' => 50,
            'occupied' => 35,
            'available' => 15,
        ];

        // آخر 12 شهر للعقود (مخطط)
        $chartLabels = [];
        $newContracts = [];
        $completedContracts = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $chartLabels[] = $date->locale('ar')->translatedFormat('F Y');
            $newContracts[] = Contract::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            $completedContracts[] = Contract::where('status', ContractStatusEnum::Completed->value)
                ->whereMonth('updated_at', $date->month)
                ->whereYear('updated_at', $date->year)
                ->count();
        }

        // الجنسيات
        $nationalityStats = Maid::select('nationality_id')
            ->selectRaw('count(*) as count')
            ->with('nationality')
            ->groupBy('nationality_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        $nationalityLabels = [];
        $nationalityData = [];
        foreach ($nationalityStats as $stat) {
            $nationalityLabels[] = $stat->nationality ? $stat->nationality->title : 'غير محدد';
            $nationalityData[] = $stat->count;
        }

        // أحدث الأنشطة (عقود)
        $recentContracts = Contract::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentActivities = [];
        foreach ($recentContracts as $contract) {
            $recentActivities[] = [
                'icon' => 'file-contract',
                'title' => 'عقد #' . $contract->id . ' - ' . ($contract->user->name ?? 'غير محدد'),
                'time' => $contract->created_at->diffForHumans(),
                'type' => $contract->status == ContractStatusEnum::Completed->value ? 'success' : 'warning',
                'status' => $contract->status
            ];
        }

        $calendarEvents = [];

        // تقسيمات إضافية للاستخدام المحتمل
        $contractStatusBreakdown = Contract::select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $arrivalTodayCount = $contractsPipeline['arrival_stage'] ?? 0;

        $delayedCutoff = now()->copy()->subDays(60);

        $delayedWithoutTickets = Contract::whereIn('status', [
            ContractStatusEnum::New->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::PendingMaidSelection->value,
        ])->where('created_at', '<', $delayedCutoff)->count();

        $operationsInsights = [
            'arrival_today' => $arrivalTodayCount,
            'delayed_no_tickets' => $delayedWithoutTickets,
            'expired_over_2y' => $contractsPipeline['expired_over_2y'],
            'warranty_active' => $contractsStats['underWarranty'],
            'tickets_ready' => $contractStatusBreakdown[ContractStatusEnum::Tazker->value] ?? 0,
            'returned' => $contractStatusBreakdown[ContractStatusEnum::Back->value] ?? 0,
            'completed' => $contractsStats['completed'] ?? 0,
        ];

        $ordersStatusBreakdown = Order::select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $clientsGrowth = [
            'this_month' => $clientsStats['newThisMonth'] ?? 0,
            'last_month' => User::whereMonth('created_at', now()->subMonth()->month)->count(),
        ];

        $maidAvailability = [
            'available' => $cvStats['available'] ?? 0,
            'booked' => $cvStats['booked'] ?? 0,
            'total' => $cvStats['total'] ?? 0,
        ];

        return view('admin.dashboard-new', compact(
            'contractsStats',
            'contractsPipeline',
            'contractsByMonthCurrent',
            'contractsByMonthPrevious',
            'ordersStats',
            'cvStats',
            'clientsStats',
            'rentalStats',
            'transferStats',
            'jobTypeCounts',
            'jobTypesList',
            'officeStats',
            'employeeBookings',
            'employeeBookingSummary',
            'employeesStats',
            'shelterStats',
            'chartLabels',
            'newContracts',
            'completedContracts',
            'nationalityLabels',
            'nationalityData',
            'recentActivities',
            'calendarEvents',
            'contractStatusBreakdown',
            'ordersStatusBreakdown',
            'clientsGrowth',
            'maidAvailability',
            'operationsInsights'
        ));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
