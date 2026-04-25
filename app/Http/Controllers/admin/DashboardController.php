<?php

namespace App\Http\Controllers\admin;

use App\Enums\auth\AdminTypes;
use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Actions\dashboard\DashboardAction as objAction;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\Models\RecruitmentOffices;
use App\Models\RentalContract;
use App\Models\SponsorshipTransfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request, objAction $action)
    {
        return $this->professional($request, $action);
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

    public function professional(Request $request, objAction $action)
    {
        $now = now();
        $todayDate = $now->toDateString();
        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();
        $currentMonth = (int) $now->month;
        $currentYear = (int) $now->year;

        // العقود تُحسب لاحقاً اعتماداً على تاريخ العقد الفعلي بدل created_at
        $contractsStats = [];
        $contractsPipeline = [];
        $contractsByMonthCurrent = [];
        $contractsByMonthPrevious = [];

        $buildOrderStats = function (int $jobTypeId) use ($todayDate, $weekStart, $weekEnd, $currentMonth, $currentYear) {
            $baseQuery = Order::query()->where('job_type_id', $jobTypeId);

            return [
                'total' => (clone $baseQuery)->count(),
                'today' => (clone $baseQuery)->whereDate('created_at', $todayDate)->count(),
                'thisWeek' => (clone $baseQuery)->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
                'thisMonth' => (clone $baseQuery)->whereMonth('created_at', $currentMonth)->whereYear('created_at', $currentYear)->count(),
                'completed' => (clone $baseQuery)->whereIn('status', [OrderEnum::Completed->value, OrderEnum::Done->value, OrderEnum::Contracted->value])->count(),
                'new' => (clone $baseQuery)->where('status', OrderEnum::New->value)->count(),
                'under_process' => (clone $baseQuery)->where('status', OrderEnum::UnderProcess->value)->count(),
                'contracted' => (clone $baseQuery)->where('status', OrderEnum::Contracted->value)->count(),
                'contracted_without' => (clone $baseQuery)->where('status', OrderEnum::ContractedWithoutContract->value)->count(),
                'refused' => (clone $baseQuery)->where('status', OrderEnum::Refused->value)->count(),
            ];
        };

        // الطلبات (استقدام)
        $ordersStats = $buildOrderStats(1);

        // السير الذاتية
        $cvStats = [
            'total' => Maid::count(),
            'available' => Maid::where('available', true)->count(),
            'booked' => Maid::where(function ($q) {
                $q->where('available', false)
                    ->orWhere('status', Maid::STATUS_RESERVED['en']);
            })->count(),
            'newThisWeek' => Maid::whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            'newThisMonth' => Maid::whereMonth('created_at', $currentMonth)->whereYear('created_at', $currentYear)->count(),
        ];

        // العملاء
        $clientTotal = User::count();
        $clientsWithOrders = User::whereHas('orders')->count();
        $clientsWithContracts = User::whereHas('contracts')->count();
        $clientsStats = [
            'total' => $clientTotal,
            'newThisMonth' => User::whereMonth('created_at', $currentMonth)->whereYear('created_at', $currentYear)->count(),
            'newThisWeek' => User::whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            'newToday' => User::whereDate('created_at', $todayDate)->count(),
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

        $rentalOrderStats = $buildOrderStats(2);

        $transferWebRequestQuery = Order::query()->where('job_type_id', 3);
        $transferWebRequestStats = [
            'total' => (clone $transferWebRequestQuery)->count(),
            'new' => (clone $transferWebRequestQuery)->where('status', 'new')->count(),
            'paid' => (clone $transferWebRequestQuery)->where('status', 'paid')->count(),
            'completed' => (clone $transferWebRequestQuery)->whereIn('status', ['completed', 'done'])->count(),
        ];

        // حسب نوع الخدمة
        $jobTypeCounts = JobType::withCount('orders')->get()->mapWithKeys(function ($type) {
            return [$type->id => $type->orders_count];
        });
        $jobTypesList = JobType::all()->keyBy('id');

        // المكاتب الخارجية
        $latestContractPerMaid = DB::table('contracts as latest_contracts')
            ->selectRaw('latest_contracts.maid_id, MAX(latest_contracts.id) as latest_id')
            ->whereNotNull('latest_contracts.maid_id')
            ->groupBy('latest_contracts.maid_id');

        $latestTravelHistoryPerContract = DB::table('travel_histories as latest_travel_histories')
            ->selectRaw('latest_travel_histories.order_id, MAX(latest_travel_histories.id) as latest_id')
            ->whereNull('latest_travel_histories.deleted_at')
            ->groupBy('latest_travel_histories.order_id');

        $officeStats = Cache::remember('admin_dashboard.office_stats.v3', now()->addMinutes(10), function () use ($latestContractPerMaid, $latestTravelHistoryPerContract) {
            return RecruitmentOffices::query()
                ->with('nationality')
                ->withCount([
                    'maids as maids_total',
                    'maids as maids_backout' => function ($query) {
                        $query->where('status', 'backout');
                    },
                    'maids as maids_available' => function ($query) use ($latestContractPerMaid) {
                        $query->leftJoinSub($latestContractPerMaid, 'latest_contract_map', function ($join) {
                            $join->on('latest_contract_map.maid_id', '=', 'maids.id');
                        })
                            ->leftJoin('contracts as latest_contract', 'latest_contract.id', '=', 'latest_contract_map.latest_id')
                            ->where('maids.available', true)
                            ->where(function ($statusQuery) {
                                $statusQuery->whereNull('maids.status')
                                    ->orWhere('maids.status', '!=', 'backout');
                            })
                            ->where(function ($contractQuery) {
                                $contractQuery->whereNull('latest_contract.id')
                                    ->orWhere('latest_contract.status', '!=', ContractStatusEnum::Back->value);
                            });
                    },
                ])
                ->selectSub(function ($query) use ($latestContractPerMaid) {
                    $query->from('maids')
                        ->leftJoinSub($latestContractPerMaid, 'latest_contract_map', function ($join) {
                            $join->on('latest_contract_map.maid_id', '=', 'maids.id');
                        })
                        ->leftJoin('contracts as latest_contract', 'latest_contract.id', '=', 'latest_contract_map.latest_id')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('maids.office_id', 'recruitment_offices.id')
                        ->whereNull('maids.deleted_at')
                        ->where(function ($statusQuery) {
                            $statusQuery->whereNull('maids.status')
                                ->orWhere('maids.status', '!=', 'backout');
                        })
                        ->where('latest_contract.status', ContractStatusEnum::Back->value);
                }, 'maids_returned')
                ->selectSub(function ($query) use ($latestTravelHistoryPerContract) {
                    $query->from('contracts as c')
                        ->leftJoin('contract_infos as ci', 'ci.contract_id', '=', 'c.id')
                        ->join('maids as m', 'm.id', '=', 'c.maid_id')
                        ->joinSub($latestTravelHistoryPerContract, 'latest_travel_map', function ($join) {
                            $join->on('latest_travel_map.order_id', '=', 'c.id');
                        })
                        ->join('travel_histories as latest_travel', 'latest_travel.id', '=', 'latest_travel_map.latest_id')
                        ->selectRaw("ROUND(AVG(GREATEST(DATEDIFF(DATE(latest_travel.arrival_date), DATE(COALESCE(NULLIF(ci.contract_date, '0000-00-00'), NULLIF(c.contract_date, '0000-00-00')))), 0)))")
                        ->whereColumn('m.office_id', 'recruitment_offices.id')
                        ->whereNull('m.deleted_at')
                        ->whereRaw("COALESCE(NULLIF(ci.contract_date, '0000-00-00'), NULLIF(c.contract_date, '0000-00-00')) IS NOT NULL")
                        ->whereNotNull('latest_travel.arrival_date');
                }, 'avg_recruitment_days')
                ->selectSub(function ($query) use ($latestTravelHistoryPerContract) {
                    $query->from('contracts as c')
                        ->join('maids as m', 'm.id', '=', 'c.maid_id')
                        ->joinSub($latestTravelHistoryPerContract, 'latest_travel_map', function ($join) {
                            $join->on('latest_travel_map.order_id', '=', 'c.id');
                        })
                        ->join('travel_histories as latest_travel', 'latest_travel.id', '=', 'latest_travel_map.latest_id')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('m.office_id', 'recruitment_offices.id')
                        ->whereNull('m.deleted_at')
                        ->whereNotNull('latest_travel.arrival_date');
                }, 'arrivals_total')
                ->orderByDesc('maids_total')
                ->get()
                ->map(function ($office) {
                    $office->maids_total = (int) ($office->maids_total ?? 0);
                    $office->maids_available = (int) ($office->maids_available ?? 0);
                    $office->maids_backout = (int) ($office->maids_backout ?? 0);
                    $office->maids_returned = (int) ($office->maids_returned ?? 0);
                    $office->arrivals_total = (int) ($office->arrivals_total ?? 0);
                    $office->avg_recruitment_days = $office->avg_recruitment_days !== null
                        ? (int) round((float) $office->avg_recruitment_days)
                        : null;
                    $office->maids_other = max(
                        $office->maids_total - $office->maids_available - $office->maids_returned - $office->maids_backout,
                        0
                    );

                    $flagPath = trim((string) (optional($office->nationality)->image ?: optional($office->nationality)->logo ?: ''));
                    $office->nationality_flag_url = $flagPath !== '' ? showFile($flagPath) : null;

                    return $office;
                });
        });

        $dashboardLocale = app()->getLocale();
        $selectedContractNationalityId = $request->filled('contract_nationality_id')
            ? (int) $request->query('contract_nationality_id')
            : null;
        $selectedContractProfessionId = $request->filled('contract_profession_id')
            ? (int) $request->query('contract_profession_id')
            : null;
        $contractFiltersApplied = $selectedContractNationalityId !== null || $selectedContractProfessionId !== null;
        $contractWeekStart = $now->copy()->startOfWeek();
        $contractWeekEnd = $now->copy()->endOfWeek();
        $contractToday = $now->copy()->toDateString();
        $currentYear = (int) $now->year;
        $contractWeekStartDate = $contractWeekStart->toDateString();
        $contractWeekEndDate = $contractWeekEnd->toDateString();

        $maidPrimaryJobTitle = DB::table('maid_job_titles')
            ->selectRaw('maid_id, MIN(job_title_id) as job_title_id')
            ->groupBy('maid_id');

        $effectiveContractDateExpression = "DATE(COALESCE(NULLIF(ci.contract_date, '0000-00-00'), NULLIF(c.contract_date, '0000-00-00')))";

        $contractAnalyticsBaseSource = DB::table('contracts as c')
            ->leftJoin('contract_infos as ci', 'ci.contract_id', '=', 'c.id')
            ->leftJoin('maids as m', 'm.id', '=', 'c.maid_id')
            ->leftJoinSub($maidPrimaryJobTitle, 'mjt', function ($join) {
                $join->on('mjt.maid_id', '=', 'c.maid_id');
            })
            ->selectRaw('
                c.id,
                c.status,
                c.warranty_status,
                ' . $effectiveContractDateExpression . ' as contract_reference_date,
                COALESCE(c.required_maid_nationality_id, m.nationality_id) as contract_nationality_id,
                COALESCE(c.required_maid_job_title_id, mjt.job_title_id) as contract_profession_id
            ');

        $contractAnalyticsBaseQuery = function () use ($contractAnalyticsBaseSource) {
            return DB::query()->fromSub($contractAnalyticsBaseSource, 'contract_analytics');
        };

        $applyContractAnalyticsBaseFilters = function ($query, string $alias = 'contract_analytics', bool $applyNationality = true, bool $applyProfession = true) use ($selectedContractNationalityId, $selectedContractProfessionId) {
            if ($applyNationality && $selectedContractNationalityId !== null) {
                $query->where($alias . '.contract_nationality_id', $selectedContractNationalityId);
            }

            if ($applyProfession && $selectedContractProfessionId !== null) {
                $query->where($alias . '.contract_profession_id', $selectedContractProfessionId);
            }

            return $query;
        };

        $contractPendingStatuses = ['pending', 'in_progress'];
        $contractPreArrivalStatuses = [
            ContractStatusEnum::New->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::PendingMaidSelection->value,
        ];
        $contractArrivalStatuses = [
            ContractStatusEnum::Tazker->value,
            ContractStatusEnum::Arrival->value,
        ];
        $pendingStatusPlaceholders = implode(', ', array_fill(0, count($contractPendingStatuses), '?'));
        $preArrivalStatusPlaceholders = implode(', ', array_fill(0, count($contractPreArrivalStatuses), '?'));
        $delayedCutoffForAnalytics = $now->copy()->subDays(60)->toDateString();
        $expiredCutoffForAnalytics = $now->copy()->subYears(2)->toDateString();

        $buildContractAnalyticsAggregate = function ($query) use (
            $pendingStatusPlaceholders,
            $contractPendingStatuses,
            $currentYear,
            $now,
            $contractWeekStartDate,
            $contractWeekEndDate,
            $contractToday,
            $preArrivalStatusPlaceholders,
            $contractPreArrivalStatuses,
            $delayedCutoffForAnalytics,
            $expiredCutoffForAnalytics
        ) {
            return $query
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_total', [ContractStatusEnum::Completed->value])
                ->selectRaw("SUM(CASE WHEN status IN ($pendingStatusPlaceholders) THEN 1 ELSE 0 END) as pending_total", $contractPendingStatuses)
                ->selectRaw('SUM(CASE WHEN warranty_status = ? THEN 1 ELSE 0 END) as under_warranty_total', ['active'])
                ->selectRaw('SUM(CASE WHEN contract_reference_date IS NULL THEN 1 ELSE 0 END) as missing_contract_date_total')
                ->selectRaw('SUM(CASE WHEN YEAR(contract_reference_date) = ? AND MONTH(contract_reference_date) = ? THEN 1 ELSE 0 END) as this_month_total', [$currentYear, $now->month])
                ->selectRaw('SUM(CASE WHEN contract_reference_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as this_week_total', [$contractWeekStartDate, $contractWeekEndDate])
                ->selectRaw('SUM(CASE WHEN contract_reference_date = ? THEN 1 ELSE 0 END) as today_total', [$contractToday])
                ->selectRaw("SUM(CASE WHEN status IN ($preArrivalStatusPlaceholders) THEN 1 ELSE 0 END) as pre_arrival_total", $contractPreArrivalStatuses)
                ->selectRaw("SUM(CASE WHEN status IN ($preArrivalStatusPlaceholders) AND contract_reference_date < ? THEN 1 ELSE 0 END) as delayed_arrival_total", array_merge($contractPreArrivalStatuses, [$delayedCutoffForAnalytics]))
                ->selectRaw('SUM(CASE WHEN contract_reference_date < ? THEN 1 ELSE 0 END) as expired_over_2y_total', [$expiredCutoffForAnalytics])
                ->first();
        };

        $buildContractArrivalStageCount = function (bool $applyFilters = true) use (
            $contractAnalyticsBaseSource,
            $latestTravelHistoryPerContract,
            $applyContractAnalyticsBaseFilters,
            $contractArrivalStatuses,
            $contractToday
        ) {
            $contractAnalyticsArrivalSource = DB::query()
                ->fromSub($contractAnalyticsBaseSource, 'contract_analytics')
                ->leftJoinSub($latestTravelHistoryPerContract, 'latest_travel_map', function ($join) {
                    $join->on('latest_travel_map.order_id', '=', 'contract_analytics.id');
                })
                ->leftJoin('travel_histories as latest_travel', 'latest_travel.id', '=', 'latest_travel_map.latest_id')
                ->selectRaw('contract_analytics.*, DATE(latest_travel.arrival_date) as arrival_date');

            $arrivalQuery = DB::query()->fromSub($contractAnalyticsArrivalSource, 'contract_analytics_arrival');

            if ($applyFilters) {
                $arrivalQuery = $applyContractAnalyticsBaseFilters(
                    $arrivalQuery,
                    'contract_analytics_arrival'
                );
            }

            return $arrivalQuery
                ->whereIn('contract_analytics_arrival.status', $contractArrivalStatuses)
                ->whereDate('contract_analytics_arrival.arrival_date', $contractToday)
                ->count();
        };

        $globalContractAnalyticsAggregateRow = $buildContractAnalyticsAggregate($contractAnalyticsBaseQuery());
        $contractAnalyticsAggregateRow = $buildContractAnalyticsAggregate($applyContractAnalyticsBaseFilters($contractAnalyticsBaseQuery()));

        $globalArrivalStageCount = $buildContractArrivalStageCount(false);
        $arrivalStageCount = $buildContractArrivalStageCount();

        $contractsStats = [
            'total' => (int) ($globalContractAnalyticsAggregateRow->total ?? 0),
            'completed' => (int) ($globalContractAnalyticsAggregateRow->completed_total ?? 0),
            'pending' => (int) ($globalContractAnalyticsAggregateRow->pending_total ?? 0),
            'underWarranty' => (int) ($globalContractAnalyticsAggregateRow->under_warranty_total ?? 0),
            'missingContractDate' => (int) ($globalContractAnalyticsAggregateRow->missing_contract_date_total ?? 0),
            'thisMonth' => (int) ($globalContractAnalyticsAggregateRow->this_month_total ?? 0),
            'thisWeek' => (int) ($globalContractAnalyticsAggregateRow->this_week_total ?? 0),
            'today' => (int) ($globalContractAnalyticsAggregateRow->today_total ?? 0),
        ];

        $contractsPipeline = [
            'pre_arrival' => (int) ($globalContractAnalyticsAggregateRow->pre_arrival_total ?? 0),
            'arrival_stage' => (int) $globalArrivalStageCount,
            'under_warranty' => (int) ($globalContractAnalyticsAggregateRow->under_warranty_total ?? 0),
            'completed' => (int) ($globalContractAnalyticsAggregateRow->completed_total ?? 0),
            'delayed_arrival' => (int) ($globalContractAnalyticsAggregateRow->delayed_arrival_total ?? 0),
            'expired_over_2y' => (int) ($globalContractAnalyticsAggregateRow->expired_over_2y_total ?? 0),
        ];

        $globalContractsYearBuckets = $contractAnalyticsBaseQuery()
            ->selectRaw('YEAR(contract_reference_date) as year, MONTH(contract_reference_date) as month, COUNT(*) as monthly_total')
            ->where('contract_reference_date', '>=', \Carbon\Carbon::create($currentYear - 1, 1, 1)->toDateString())
            ->groupBy('year', 'month')
            ->get()
            ->groupBy('year')
            ->map(function ($rows) {
                return $rows->keyBy('month');
            });

        for ($m = 1; $m <= 12; $m++) {
            $contractsByMonthCurrent[] = (int) optional(optional($globalContractsYearBuckets->get($currentYear))->get($m))->monthly_total;
            $contractsByMonthPrevious[] = (int) optional(optional($globalContractsYearBuckets->get($currentYear - 1))->get($m))->monthly_total;
        }

        $contractAnalyticsStats = [
            'total' => (int) ($contractAnalyticsAggregateRow->total ?? 0),
            'completed' => (int) ($contractAnalyticsAggregateRow->completed_total ?? 0),
            'pending' => (int) ($contractAnalyticsAggregateRow->pending_total ?? 0),
            'underWarranty' => (int) ($contractAnalyticsAggregateRow->under_warranty_total ?? 0),
            'missingContractDate' => (int) ($contractAnalyticsAggregateRow->missing_contract_date_total ?? 0),
            'thisMonth' => (int) ($contractAnalyticsAggregateRow->this_month_total ?? 0),
            'thisWeek' => (int) ($contractAnalyticsAggregateRow->this_week_total ?? 0),
            'today' => (int) ($contractAnalyticsAggregateRow->today_total ?? 0),
        ];

        $contractAnalyticsPipeline = [
            'pre_arrival' => (int) ($contractAnalyticsAggregateRow->pre_arrival_total ?? 0),
            'arrival_stage' => (int) $arrivalStageCount,
            'under_warranty' => (int) ($contractAnalyticsAggregateRow->under_warranty_total ?? 0),
            'completed' => (int) ($contractAnalyticsAggregateRow->completed_total ?? 0),
            'delayed_arrival' => (int) ($contractAnalyticsAggregateRow->delayed_arrival_total ?? 0),
            'expired_over_2y' => (int) ($contractAnalyticsAggregateRow->expired_over_2y_total ?? 0),
        ];

        $contractAnalyticsStatusBreakdown = $applyContractAnalyticsBaseFilters($contractAnalyticsBaseQuery())
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $contractsChartLabels = [];
        for ($month = 1; $month <= 12; $month++) {
            $contractsChartLabels[] = \Carbon\Carbon::create($currentYear, $month, 1)
                ->locale($dashboardLocale)
                ->translatedFormat('F');
        }

        $contractsYearBuckets = $applyContractAnalyticsBaseFilters($contractAnalyticsBaseQuery())
            ->selectRaw('YEAR(contract_reference_date) as year, MONTH(contract_reference_date) as month, COUNT(*) as total')
            ->where('contract_reference_date', '>=', \Carbon\Carbon::create($currentYear - 2, 1, 1)->toDateString())
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $contractsYearSeries = collect(range(2, 0))->map(function ($yearsBack) use ($currentYear, $contractsYearBuckets) {
            $year = $currentYear - $yearsBack;
            $yearRows = $contractsYearBuckets->where('year', $year)->keyBy('month');
            $monthlySeries = [];

            for ($month = 1; $month <= 12; $month++) {
                $monthlySeries[] = (int) optional($yearRows->get($month))->total;
            }

            return [
                'year' => $year,
                'label' => 'عقود ' . $year,
                'total' => array_sum($monthlySeries),
                'data' => $monthlySeries,
            ];
        })->values();

        $contractsThreeYearsTotal = (int) $contractsYearSeries->sum('total');

        $contractNationalityOptions = $applyContractAnalyticsBaseFilters($contractAnalyticsBaseQuery(), 'contract_analytics', false, true)
            ->leftJoin('nationalities_translations as nationality_locale', function ($join) use ($dashboardLocale) {
                $join->on('nationality_locale.nationality_id', '=', 'contract_analytics.contract_nationality_id')
                    ->where('nationality_locale.locale', $dashboardLocale);
            })
            ->leftJoin('nationalities_translations as nationality_ar', function ($join) {
                $join->on('nationality_ar.nationality_id', '=', 'contract_analytics.contract_nationality_id')
                    ->where('nationality_ar.locale', 'ar');
            })
            ->selectRaw('contract_analytics.contract_nationality_id as id')
            ->selectRaw('COALESCE(nationality_locale.title, nationality_ar.title, ?) as title', ['غير محدد'])
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('contract_analytics.contract_nationality_id')
            ->groupBy('id', 'title')
            ->orderBy('title')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'title' => trim((string) ($row->title ?? '')) ?: 'غير محدد',
                    'total' => (int) ($row->total ?? 0),
                ];
            })
            ->values();

        $contractProfessionOptions = $applyContractAnalyticsBaseFilters($contractAnalyticsBaseQuery(), 'contract_analytics', true, false)
            ->leftJoin('job_titles_translations as profession_locale', function ($join) use ($dashboardLocale) {
                $join->on('profession_locale.job_title_id', '=', 'contract_analytics.contract_profession_id')
                    ->where('profession_locale.locale', $dashboardLocale);
            })
            ->leftJoin('job_titles_translations as profession_ar', function ($join) {
                $join->on('profession_ar.job_title_id', '=', 'contract_analytics.contract_profession_id')
                    ->where('profession_ar.locale', 'ar');
            })
            ->selectRaw('contract_analytics.contract_profession_id as id')
            ->selectRaw('COALESCE(profession_locale.title, profession_ar.title, ?) as title', ['غير محدد'])
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('contract_analytics.contract_profession_id')
            ->groupBy('id', 'title')
            ->orderBy('title')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'title' => trim((string) ($row->title ?? '')) ?: 'غير محدد',
                    'total' => (int) ($row->total ?? 0),
                ];
            })
            ->values();

        $contractProfessionStats = $applyContractAnalyticsBaseFilters($contractAnalyticsBaseQuery())
            ->leftJoin('job_titles_translations as profession_locale', function ($join) use ($dashboardLocale) {
                $join->on('profession_locale.job_title_id', '=', 'contract_analytics.contract_profession_id')
                    ->where('profession_locale.locale', $dashboardLocale);
            })
            ->leftJoin('job_titles_translations as profession_ar', function ($join) {
                $join->on('profession_ar.job_title_id', '=', 'contract_analytics.contract_profession_id')
                    ->where('profession_ar.locale', 'ar');
            })
            ->selectRaw('contract_analytics.contract_profession_id as profession_id')
            ->selectRaw('COALESCE(profession_locale.title, profession_ar.title, ?) as profession', ['غير محدد'])
            ->selectRaw('COUNT(*) as total')
            ->groupBy('profession_id', 'profession')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(function ($row) use ($contractAnalyticsStats) {
                $contractsTotal = max((int) ($contractAnalyticsStats['total'] ?? 0), 1);
                $profession = trim((string) ($row->profession ?? '')) ?: 'غير محدد';
                $total = (int) ($row->total ?? 0);
                $professionId = isset($row->profession_id) ? (int) $row->profession_id : null;

                return [
                    'id' => $professionId ?: null,
                    'profession' => $profession,
                    'total' => $total,
                    'share' => round(($total / $contractsTotal) * 100),
                    'is_unknown' => $professionId === null || $profession === 'غير محدد',
                ];
            })
            ->values();

        $contractProfessionSummary = [
            'tracked_professions' => $contractProfessionStats->filter(function ($row) {
                return !($row['is_unknown'] ?? false);
            })->count(),
            'known_contracts' => (int) $contractProfessionStats->filter(function ($row) {
                return !($row['is_unknown'] ?? false);
            })->sum('total'),
            'unknown_contracts' => (int) $contractProfessionStats->filter(function ($row) {
                return $row['is_unknown'] ?? false;
            })->sum('total'),
            'top_profession' => $contractProfessionStats->first()['profession'] ?? null,
        ];

        $selectedContractNationalityOption = $contractNationalityOptions->firstWhere('id', $selectedContractNationalityId);
        $selectedContractProfessionOption = $contractProfessionOptions->firstWhere('id', $selectedContractProfessionId);
        $selectedContractNationalityTitle = $selectedContractNationalityOption['title'] ?? null;
        $selectedContractProfessionTitle = $selectedContractProfessionOption['title'] ?? null;

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

        $employeeAnalyticsAdminTypes = [
            AdminTypes::CustomerService->value,
            'call_center',
        ];

        $employeeBookingCache = Cache::remember('admin_dashboard.employee_bookings.v3', now()->addMinutes(5), function () use ($orderStatsByEmployee, $contractStatsByEmployee, $employeeAnalyticsAdminTypes) {
            $employeeBookings = Admin::query()
                ->whereIn('admins.admin_type', $employeeAnalyticsAdminTypes)
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

            return [
                'employeeBookings' => $employeeBookings,
                'employeeBookingSummary' => $employeeBookingSummary,
            ];
        });

        $employeeBookings = $employeeBookingCache['employeeBookings'];
        $employeeBookingSummary = $employeeBookingCache['employeeBookingSummary'];

        // الموظفون
        try {
            $employeeStatsBaseQuery = Admin::query()
                ->whereIn('admin_type', $employeeAnalyticsAdminTypes);

            $totalEmployees = (clone $employeeStatsBaseQuery)->count();
            $activeEmployees = (clone $employeeStatsBaseQuery)
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', 'active');
                })
                ->count();

            $salesCount = (clone $employeeStatsBaseQuery)->whereHas('roles', function ($q) {
                $q->where('name', 'sales');
            })->count();

            $supportCount = (clone $employeeStatsBaseQuery)->whereHas('roles', function ($q) {
                $q->where('name', 'support');
            })->count();

            $adminCount = (clone $employeeStatsBaseQuery)->whereHas('roles', function ($q) {
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
        $delayedWithoutTickets = $contractsPipeline['delayed_arrival'] ?? 0;

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
            'rentalOrderStats',
            'transferStats',
            'transferWebRequestStats',
            'jobTypeCounts',
            'jobTypesList',
            'officeStats',
            'contractAnalyticsStats',
            'contractAnalyticsPipeline',
            'contractAnalyticsStatusBreakdown',
            'contractsChartLabels',
            'contractsYearSeries',
            'contractsThreeYearsTotal',
            'contractNationalityOptions',
            'contractProfessionOptions',
            'selectedContractNationalityId',
            'selectedContractProfessionId',
            'selectedContractNationalityTitle',
            'selectedContractProfessionTitle',
            'contractFiltersApplied',
            'contractProfessionStats',
            'contractProfessionSummary',
            'employeeBookings',
            'employeeBookingSummary',
            'employeesStats',
            'shelterStats',
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

    public function employeeShow(Request $request, Admin $employee)
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'range' => ['nullable', 'string', 'in:7d,30d,90d,month,year,all,custom'],
        ]);

        $employee->loadMissing(['roles', 'role']);

        $ordersBaseQuery = Order::query()
            ->where('job_type_id', 1)
            ->whereResponsibleAdmin((int) $employee->id);

        $contractsBaseQuery = Contract::query()
            ->whereIn('type', ['recruitment', 'maid'])
            ->where(function ($query) use ($employee) {
                $query->where('marketer_id', $employee->id)
                    ->orWhereHas('order.details', function ($detailsQuery) use ($employee) {
                        $detailsQuery->where('admin_id', $employee->id);
                    });
            });

        $firstOrderAt = ($firstOrderValue = (clone $ordersBaseQuery)->min('created_at'))
            ? Carbon::parse($firstOrderValue)
            : null;
        $firstContractAt = ($firstContractValue = (clone $contractsBaseQuery)->min('created_at'))
            ? Carbon::parse($firstContractValue)
            : null;
        $firstActivityAt = collect([$firstOrderAt, $firstContractAt])
            ->filter()
            ->sortBy(fn (Carbon $date) => $date->timestamp)
            ->first();

        $lastOrderAt = ($lastOrderValue = (clone $ordersBaseQuery)->max('created_at'))
            ? Carbon::parse($lastOrderValue)
            : null;
        $lastContractAt = ($lastContractValue = (clone $contractsBaseQuery)->max('created_at'))
            ? Carbon::parse($lastContractValue)
            : null;
        $lastActivityAt = collect([$lastOrderAt, $lastContractAt])
            ->filter()
            ->sortByDesc(fn (Carbon $date) => $date->timestamp)
            ->first();

        [$startDate, $endDate, $selectedRangeKey] = $this->resolveEmployeeDashboardDateRange($request, $firstActivityAt);

        $filteredOrdersQuery = $this->applyCreatedAtRange(clone $ordersBaseQuery, $startDate, $endDate);
        $filteredContractsQuery = $this->applyCreatedAtRange(clone $contractsBaseQuery, $startDate, $endDate);

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
            ContractStatusEnum::Arrival->value,
            ContractStatusEnum::Completed->value,
        ];
        $currentContractPlaceholders = implode(', ', array_fill(0, count($currentContractStatuses), '?'));
        $completedContractPlaceholders = implode(', ', array_fill(0, count($completedContractStatuses), '?'));

        $ordersAggregate = (clone $filteredOrdersQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as new_total', [OrderEnum::New->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as under_process_total', [OrderEnum::UnderProcess->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as contracted_total', [OrderEnum::Contracted->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as contracted_without_total', [OrderEnum::ContractedWithoutContract->value])
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as completed_total', [OrderEnum::Completed->value, OrderEnum::Done->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as refused_total', [OrderEnum::Refused->value])
            ->first();

        $contractsAggregate = (clone $filteredContractsQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status IN ($currentContractPlaceholders) THEN 1 ELSE 0 END) as current_total", $currentContractStatuses)
            ->selectRaw("SUM(CASE WHEN status IN ($completedContractPlaceholders) THEN 1 ELSE 0 END) as completed_total", $completedContractStatuses)
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as returned_total', [ContractStatusEnum::Back->value])
            ->selectRaw('SUM(CASE WHEN warranty_status = ? THEN 1 ELSE 0 END) as warranty_active_total', ['active'])
            ->first();

        $ordersTotal = (int) ($ordersAggregate->total ?? 0);
        $contractsTotal = (int) ($contractsAggregate->total ?? 0);
        $handledTotal = $ordersTotal + $contractsTotal;
        $rangeDays = max($startDate->diffInDays($endDate) + 1, 1);

        $employeeStats = [
            'handled_total' => $handledTotal,
            'orders_total' => $ordersTotal,
            'orders_new' => (int) ($ordersAggregate->new_total ?? 0),
            'orders_under_process' => (int) ($ordersAggregate->under_process_total ?? 0),
            'orders_contracted' => (int) ($ordersAggregate->contracted_total ?? 0),
            'orders_contracted_without' => (int) ($ordersAggregate->contracted_without_total ?? 0),
            'orders_completed' => (int) ($ordersAggregate->completed_total ?? 0),
            'orders_refused' => (int) ($ordersAggregate->refused_total ?? 0),
            'contracts_total' => $contractsTotal,
            'contracts_current' => (int) ($contractsAggregate->current_total ?? 0),
            'contracts_completed' => (int) ($contractsAggregate->completed_total ?? 0),
            'contracts_returned' => (int) ($contractsAggregate->returned_total ?? 0),
            'contracts_warranty_active' => (int) ($contractsAggregate->warranty_active_total ?? 0),
            'conversion_rate' => $ordersTotal > 0 ? (int) round(($contractsTotal / $ordersTotal) * 100) : 0,
            'avg_daily_handled' => round($handledTotal / $rangeDays, 1),
        ];

        $allTimeOrdersTotal = (int) (clone $ordersBaseQuery)->count();
        $allTimeContractsTotal = (int) (clone $contractsBaseQuery)->count();
        $allTimeSummary = [
            'orders_total' => $allTimeOrdersTotal,
            'contracts_total' => $allTimeContractsTotal,
            'handled_total' => $allTimeOrdersTotal + $allTimeContractsTotal,
            'conversion_rate' => $allTimeOrdersTotal > 0 ? (int) round(($allTimeContractsTotal / $allTimeOrdersTotal) * 100) : 0,
        ];

        $ordersStatusBreakdown = (clone $filteredOrdersQuery)
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $contractsStatusBreakdown = (clone $filteredContractsQuery)
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $ordersStatusChart = $this->buildEmployeeStatusChart($ordersStatusBreakdown, 'orders');
        $contractsStatusChart = $this->buildEmployeeStatusChart($contractsStatusBreakdown, 'contracts');
        $activityChart = $this->buildEmployeeActivityChart(
            clone $filteredOrdersQuery,
            clone $filteredContractsQuery,
            $startDate,
            $endDate
        );

        $orders = $this->applyCreatedAtRange(
            Order::query()
                ->with(['user', 'maid', 'jobType', 'details.admin', 'contracts'])
                ->where('job_type_id', 1)
                ->whereResponsibleAdmin((int) $employee->id)
                ->orderByDesc('created_at'),
            $startDate,
            $endDate
        )
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString();

        $contracts = $this->applyCreatedAtRange(
            Contract::query()
                ->with(['user', 'maid', 'order.details.admin'])
                ->whereIn('type', ['recruitment', 'maid'])
                ->where(function ($query) use ($employee) {
                    $query->where('marketer_id', $employee->id)
                        ->orWhereHas('order.details', function ($detailsQuery) use ($employee) {
                            $detailsQuery->where('admin_id', $employee->id);
                        });
                })
                ->orderByDesc('created_at'),
            $startDate,
            $endDate
        )
            ->paginate(10, ['*'], 'contracts_page')
            ->withQueryString();

        $employeeRoleNames = $employee->roles
            ->map(function ($role) {
                return trim((string) ($role->display_name ?? $role->name));
            })
            ->filter()
            ->values();

        $employeePrimaryRole = $employeeRoleNames->first()
            ?: trim((string) (optional($employee->role)->display_name ?? optional($employee->role)->name ?? 'بدون دور'));

        $rangePresets = [
            '7d' => 'آخر 7 أيام',
            '30d' => 'آخر 30 يوماً',
            '90d' => 'آخر 90 يوماً',
            'month' => 'هذا الشهر',
            'year' => 'هذه السنة',
            'all' => 'كل الفترة',
        ];

        $dateRangeLabel = $selectedRangeKey === 'all'
            ? 'من أول نشاط حتى ' . $endDate->translatedFormat('d F Y')
            : $startDate->translatedFormat('d F Y') . ' - ' . $endDate->translatedFormat('d F Y');

        return view('admin.dashboard-employee', compact(
            'employee',
            'employeeStats',
            'allTimeSummary',
            'activityChart',
            'ordersStatusChart',
            'contractsStatusChart',
            'ordersStatusBreakdown',
            'contractsStatusBreakdown',
            'orders',
            'contracts',
            'startDate',
            'endDate',
            'selectedRangeKey',
            'rangePresets',
            'dateRangeLabel',
            'firstActivityAt',
            'lastActivityAt',
            'employeeRoleNames',
            'employeePrimaryRole'
        ));
    }

    protected function resolveEmployeeDashboardDateRange(Request $request, ?Carbon $firstActivityAt = null): array
    {
        $now = now();
        $startDate = $request->filled('from_date')
            ? Carbon::parse($request->query('from_date'))->startOfDay()
            : null;
        $endDate = $request->filled('to_date')
            ? Carbon::parse($request->query('to_date'))->endOfDay()
            : null;
        $selectedRangeKey = (string) $request->query('range', 'all');

        if ($startDate || $endDate) {
            $selectedRangeKey = 'custom';
        } else {
            switch ($selectedRangeKey) {
                case '7d':
                    $startDate = $now->copy()->subDays(6)->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    break;
                case '30d':
                    $startDate = $now->copy()->subDays(29)->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    break;
                case 'month':
                    $startDate = $now->copy()->startOfMonth();
                    $endDate = $now->copy()->endOfDay();
                    break;
                case 'year':
                    $startDate = $now->copy()->startOfYear();
                    $endDate = $now->copy()->endOfDay();
                    break;
                case 'all':
                    $startDate = $firstActivityAt?->copy()->startOfMonth() ?: $now->copy()->subMonths(5)->startOfMonth();
                    $endDate = $now->copy()->endOfDay();
                    break;
                case '90d':
                    $startDate = $now->copy()->subDays(89)->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    break;
                default:
                    $selectedRangeKey = 'all';
                    $startDate = $firstActivityAt?->copy()->startOfMonth() ?: $now->copy()->subMonths(5)->startOfMonth();
                    $endDate = $now->copy()->endOfDay();
                    break;
            }
        }

        if (!$startDate) {
            $startDate = $firstActivityAt?->copy()->startOfDay() ?: $now->copy()->subDays(89)->startOfDay();
        }

        if (!$endDate) {
            $endDate = $now->copy()->endOfDay();
        }

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate, $selectedRangeKey];
    }

    protected function applyCreatedAtRange($query, ?Carbon $startDate, ?Carbon $endDate)
    {
        if ($startDate) {
            $query->where('created_at', '>=', $startDate->copy()->startOfDay());
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate->copy()->endOfDay());
        }

        return $query;
    }

    protected function buildEmployeeActivityChart($ordersQuery, $contractsQuery, Carbon $startDate, Carbon $endDate): array
    {
        $diffDays = $startDate->diffInDays($endDate);
        $diffMonths = $startDate->diffInMonths($endDate);

        if ($diffDays <= 31) {
            $mode = 'daily';
            $orderBuckets = (clone $ordersQuery)
                ->selectRaw('DATE(created_at) as bucket, COUNT(*) as total')
                ->groupBy('bucket')
                ->pluck('total', 'bucket');
            $contractBuckets = (clone $contractsQuery)
                ->selectRaw('DATE(created_at) as bucket, COUNT(*) as total')
                ->groupBy('bucket')
                ->pluck('total', 'bucket');

            $cursor = $startDate->copy()->startOfDay();
            $limit = 0;
            $labels = [];
            $ordersSeries = [];
            $contractsSeries = [];

            while ($cursor->lte($endDate) && $limit < 400) {
                $bucketKey = $cursor->toDateString();
                $labels[] = $cursor->translatedFormat('d M');
                $ordersSeries[] = (int) ($orderBuckets[$bucketKey] ?? 0);
                $contractsSeries[] = (int) ($contractBuckets[$bucketKey] ?? 0);
                $cursor->addDay();
                $limit++;
            }
        } elseif ($diffMonths <= 36) {
            $mode = 'monthly';
            $orderBuckets = (clone $ordersQuery)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-01') as bucket, COUNT(*) as total")
                ->groupBy('bucket')
                ->pluck('total', 'bucket');
            $contractBuckets = (clone $contractsQuery)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-01') as bucket, COUNT(*) as total")
                ->groupBy('bucket')
                ->pluck('total', 'bucket');

            $cursor = $startDate->copy()->startOfMonth();
            $labels = [];
            $ordersSeries = [];
            $contractsSeries = [];

            while ($cursor->lte($endDate)) {
                $bucketKey = $cursor->format('Y-m-01');
                $labels[] = $cursor->translatedFormat('M Y');
                $ordersSeries[] = (int) ($orderBuckets[$bucketKey] ?? 0);
                $contractsSeries[] = (int) ($contractBuckets[$bucketKey] ?? 0);
                $cursor->addMonth();
            }
        } else {
            $mode = 'yearly';
            $orderBuckets = (clone $ordersQuery)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-01-01') as bucket, COUNT(*) as total")
                ->groupBy('bucket')
                ->pluck('total', 'bucket');
            $contractBuckets = (clone $contractsQuery)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-01-01') as bucket, COUNT(*) as total")
                ->groupBy('bucket')
                ->pluck('total', 'bucket');

            $cursor = $startDate->copy()->startOfYear();
            $labels = [];
            $ordersSeries = [];
            $contractsSeries = [];

            while ($cursor->lte($endDate)) {
                $bucketKey = $cursor->format('Y-01-01');
                $labels[] = $cursor->translatedFormat('Y');
                $ordersSeries[] = (int) ($orderBuckets[$bucketKey] ?? 0);
                $contractsSeries[] = (int) ($contractBuckets[$bucketKey] ?? 0);
                $cursor->addYear();
            }
        }

        return [
            'mode' => $mode,
            'labels' => $labels,
            'orders' => $ordersSeries,
            'contracts' => $contractsSeries,
        ];
    }

    protected function buildEmployeeStatusChart(array $breakdown, string $type): array
    {
        $resolvedBreakdown = collect($breakdown)
            ->mapToGroups(function ($total, $status) use ($type) {
                $label = $type === 'contracts'
                    ? getContractStatus($status)
                    : $this->resolveOrderStatusLabel((string) $status);

                return [$label => (int) $total];
            })
            ->map(function ($totals, $label) {
                return [
                    'label' => $label,
                    'total' => (int) collect($totals)->sum(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $visibleItems = $resolvedBreakdown->take(6)->values();
        $remainingTotal = (int) $resolvedBreakdown->slice(6)->sum('total');

        if ($remainingTotal > 0) {
            $visibleItems->push([
                'label' => 'أخرى',
                'total' => $remainingTotal,
            ]);
        }

        return [
            'labels' => $visibleItems->pluck('label')->all(),
            'data' => $visibleItems->pluck('total')->map(fn ($total) => (int) $total)->all(),
        ];
    }

    protected function resolveOrderStatusLabel(string $status): string
    {
        return match ($status) {
            OrderEnum::New->value => 'جديد',
            OrderEnum::UnderProcess->value => 'تحت الإجراء',
            OrderEnum::Contracted->value => 'تم التعاقد',
            OrderEnum::ContractedWithoutContract->value => 'تم التعاقد بدون عقد',
            OrderEnum::Completed->value, OrderEnum::Done->value => 'مكتملة',
            OrderEnum::Refused->value => 'مرفوضة',
            OrderEnum::Return->value => 'استرجاع',
            OrderEnum::CanceledBySystem->value => 'ملغاة آلياً',
            default => $status,
        };
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
