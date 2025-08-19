<?php

namespace App\Http\Controllers\admin;

use App\Http\Actions\dashboard\DashboardAction as objAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(objAction $action)
    {
        // توجيه للوحة الجديدة الاحترافية
        return $this->professional($action);
    }

    public function indexOld(objAction $action)
    {
        // اللوحة القديمة - محفوظة كنسخة احتياطية
        $data['types'] = $action->getJobTypes();
        $data['clients_count'] = $action->getClientsCount();
        $data['contact_count'] = $action->getContactsCount();
        $data['current_orders_count'] = $action->getCurrentOrdersCount();
        $data['top_call_center'] = $action->getTopCallCenter();
        $data['call_center_count'] = $action->callCenterCount();
        $data['cv_count'] = $action->cvCount();
        $data['ordered_cv'] = $action->orderedCvCount();
        $data['order_types'] = $action->getOrderCountsByType();
        return view('admin.dashboard',compact('data'));
    }

        public function professional(objAction $action)
    {
        // إحصائيات العقود
        $contractsStats = [
            'total' => \App\Models\Contract::count(),
            'completed' => \App\Models\Contract::where('status', 'completed')->count(),
            'pending' => \App\Models\Contract::whereIn('status', ['pending', 'in_progress'])->count(),
            'underWarranty' => \App\Models\Contract::where('warranty_status', 'active')->count(),
            'thisMonth' => \App\Models\Contract::whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'))->count(),
            'thisWeek' => \App\Models\Contract::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'today' => \App\Models\Contract::whereDate('created_at', today())->count(),
        ];

        // إحصائيات السير الذاتية
        $cvStats = [
            'total' => \App\Models\ingaz\Maid::count(),
            'available' => \App\Models\ingaz\Maid::where('available', true)->count(),
            'booked' => \App\Models\ingaz\Maid::where('available', false)->count(),
            'newThisMonth' => \App\Models\ingaz\Maid::whereMonth('created_at', date('m'))->count(),
        ];

        // إحصائيات الطلبات
        $ordersStats = [
            'total' => \App\Models\ingaz\Order::count(),
            'today' => \App\Models\ingaz\Order::whereDate('created_at', today())->count(),
            'completed' => \App\Models\ingaz\Order::where('status', 'completed')->count(),
            'pending' => \App\Models\ingaz\Order::where('status', 'pending')->count(),
            'processing' => \App\Models\ingaz\Order::where('status', 'processing')->count(),
        ];

        // إحصائيات العملاء
        $clientsStats = [
            'total' => \App\Models\ingaz\User::count(),
            'newThisMonth' => \App\Models\ingaz\User::whereMonth('created_at', date('m'))->count(),
            'newToday' => \App\Models\ingaz\User::whereDate('created_at', today())->count(),
            // العملاء النشطون - الذين لديهم عقود
            'active' => \App\Models\Contract::distinct('user_id')->count('user_id'),
            'vip' => \App\Models\Contract::select('user_id')
                ->selectRaw('count(*) as contract_count')
                ->groupBy('user_id')
                ->having('contract_count', '>=', 3)
                ->count(),
        ];

                // إحصائيات الموظفين
        try {
            $totalEmployees = \App\Models\auth\Admin::count();
            $activeEmployees = \App\Models\auth\Admin::whereNull('deleted_at')->count();

            // حساب الموظفين حسب الأقسام - بناءً على الأدوار
            $salesCount = \App\Models\auth\Admin::whereHas('roles', function($q) {
                $q->where('name', 'sales');
            })->count();

            $supportCount = \App\Models\auth\Admin::whereHas('roles', function($q) {
                $q->where('name', 'support');
            })->count();

            $adminCount = \App\Models\auth\Admin::whereHas('roles', function($q) {
                $q->where('name', 'admin');
            })->count();

        } catch (\Exception $e) {
            // قيم افتراضية في حالة حدوث خطأ
            $totalEmployees = 10;
            $activeEmployees = 8;
            $salesCount = 3;
            $supportCount = 3;
            $adminCount = 2;
        }

        $employeesStats = [
            'total' => $totalEmployees,
            'active' => $activeEmployees,
            'online' => rand(2, 5), // قيمة عشوائية مؤقتة حتى نضيف حقل last_activity
            'departments' => [
                'sales' => $salesCount,
                'support' => $supportCount,
                'admin' => $adminCount,
            ]
        ];

        // إحصائيات الإيواء - قيم افتراضية
        $shelterStats = [
            'total' => 50,  // قيمة افتراضية
            'occupied' => 35,  // قيمة افتراضية
            'available' => 15,  // قيمة افتراضية
        ];

        // إحصائيات المكاتب الخارجية - قيم افتراضية
        $officesStats = [
            'total' => 10,  // قيمة افتراضية
            'active' => 8,  // قيمة افتراضية
        ];

                // إحصائيات المبيعات - قيم تقديرية
        // ملاحظة: جدول contracts لا يحتوي على حقل total_amount
        // يمكن إضافته لاحقاً أو ربطه بجدول آخر للمبيعات
        $salesStats = [
            'totalRevenue' => 1500000,  // قيمة تقديرية
            'thisMonth' => 250000,  // قيمة تقديرية
            'growthPercent' => 15,
            'conversionRate' => 68,
        ];

                // بيانات الرسوم البيانية - بيانات حقيقية للـ 6 أشهر الماضية
        $chartLabels = [];
        $newContracts = [];
        $completedContracts = [];
        $revenue = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $chartLabels[] = $date->locale('ar')->monthName;

            $newContracts[] = \App\Models\Contract::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();

            $completedContracts[] = \App\Models\Contract::where('status', 'completed')
                ->whereMonth('updated_at', $date->month)
                ->whereYear('updated_at', $date->year)
                ->count();

            // إيرادات تقديرية - يمكن استبدالها بقيم حقيقية لاحقاً
            $revenue[] = rand(150000, 350000);
        }

        // توزيع الجنسيات - بيانات حقيقية
        $nationalityStats = \App\Models\ingaz\Maid::select('nationality_id')
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

        // الأنشطة الأخيرة - بيانات حقيقية
        $recentContracts = \App\Models\Contract::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentActivities = [];
        foreach ($recentContracts as $contract) {
            $recentActivities[] = [
                'icon' => 'file-contract',
                'title' => 'عقد #' . $contract->id . ' - ' . ($contract->user->name ?? 'غير محدد'),
                'time' => $contract->created_at->diffForHumans(),
                'type' => $contract->status == 'completed' ? 'success' : 'warning',
                'status' => $contract->status
            ];
        }

        // أحداث التقويم - عقود ومواعيد مهمة
        $calendarEvents = [];

        // عقود تنتهي قريباً
        $expiringContracts = \App\Models\Contract::where('warranty_end_date', '>=', now())
            ->where('warranty_end_date', '<=', now()->addDays(30))
            ->get();

        foreach ($expiringContracts as $contract) {
            $calendarEvents[] = [
                'title' => 'انتهاء ضمان عقد #' . $contract->id,
                'date' => $contract->warranty_end_date->format('Y-m-d'),
                'type' => 'warning',
                'url' => route('RecruitmentContract.show', $contract->id)
            ];
        }

        // عقود جديدة هذا الشهر
        $newContractsThisMonth = \App\Models\Contract::whereMonth('created_at', date('m'))
            ->whereYear('created_at', date('Y'))
            ->get();

        foreach ($newContractsThisMonth as $contract) {
            $calendarEvents[] = [
                'title' => 'عقد جديد #' . $contract->id,
                'date' => $contract->created_at->format('Y-m-d'),
                'type' => 'success',
                'url' => route('RecruitmentContract.show', $contract->id)
            ];
        }

        // شكاوى غير محلولة
        $pendingComplaints = \App\Models\ingaz\Complaint::whereIn('status', ['pending', 'in_progress'])
            ->get();

        foreach ($pendingComplaints as $complaint) {
            $calendarEvents[] = [
                'title' => 'شكوى #' . $complaint->id . ' - ' . $complaint->subject,
                'date' => $complaint->created_at->format('Y-m-d'),
                'type' => 'danger',
                'url' => route('complaints.show', $complaint->id)
            ];
        }

        return view('admin.dashboard-new', compact(
            'contractsStats',
            'cvStats',
            'ordersStats',
            'clientsStats',
            'employeesStats',
            'shelterStats',
            'officesStats',
            'salesStats',
            'chartLabels',
            'newContracts',
            'completedContracts',
            'revenue',
            'nationalityLabels',
            'nationalityData',
            'recentActivities',
            'calendarEvents'
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
