<?php

namespace App\Http\Controllers\admin;

use App\Constants;
use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\ingaz\RecruitmentContractExternalOfficeDispatchController;
use App\Http\Traits\SendSMSTrait;
use App\Models\AdminNotification;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\Order;
use App\Models\TravelHistory;
use App\Models\TravelHistoryFiles;
use App\Services\ingaz\RecruitmentTripAccountingService;
use App\Services\ingaz\RentalMaidArrivalService;
use App\Support\AutomationSettings;
use App\Support\CommunicationSettings;
use App\Support\TravelAirports;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class AdminTravelHistoryController extends Controller
{
    use SendSMSTrait;

    public function __construct()
    {
        $this->middleware('permission:travel_view,admin', ['only' => ['index', 'statistics', 'downloadFile', 'export', 'print']]);
        $this->middleware('permission:travel_view|dashboard_calendar_events,admin', ['only' => ['calendarEvents']]);
        $this->middleware('permission:travel_control,admin', ['only' => ['assignableStaff', 'assignTrip', 'completeTrip']]);
        $this->middleware('permission:travel_delete,admin', ['only' => ['destroy', 'deleteFile']]);
        $this->middleware('permission:travel_create,admin', ['only' => ['storeTravelHistory']]);
        $this->middleware('permission:travel_create|travel_edit,admin', ['only' => ['checkTripExists']]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->travelIndexQuery($request);

            [$groupedTrips, $filteredCount] = $this->paginatedGroupedTrips($query, $request);

            return DataTables::of($groupedTrips)
                ->skipPaging()
                ->setTotalRecords($filteredCount)
                ->setFilteredRecords($filteredCount)
                ->addIndexColumn()
                ->addColumn('user_data', function($row) {
                    $users = collect(data_get($row, 'users', []));
                    if ($users->isEmpty()) {
                        return '<span class="text-muted">غير محدد</span>';
                    }

                    $html = '<div class="user-info">';
                    $contractsCount = (int) data_get($row, 'contracts_count', 0);
                    if ($contractsCount > 1) {
                        $html .= '<span class="badge bg-primary mb-1">رحلة مجمعة (' . $contractsCount . ' عقود)</span><br>';
                    }

                    $users->take(3)->each(function ($user) use (&$html) {
                        $html .= '<div class="mb-1">';
                        $html .= '<span class="fw-bold">' . e($user->name ?? 'غير محدد') . '</span><br>';
                        $html .= '<small class="text-muted">رقم: ' . e($user->id ?? '-') . '</small>';
                        if (!empty($user->mobile)) {
                            $html .= '<br><small class="text-muted">' . e($user->mobile) . '</small>';
                        }
                        $html .= '</div>';
                    });

                    if ($users->count() > 3) {
                        $html .= '<small class="text-muted">+' . ($users->count() - 3) . ' عميلات إضافية</small>';
                    }

                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('maid_data', function($row) {
                    $maids = collect(data_get($row, 'maids', []));
                    $html = '<div class="maid-info">';
                    if ($maids->isNotEmpty()) {
                        $maids->take(3)->each(function ($maid) use (&$html) {
                            $html .= '<div class="mb-1">';
                            $html .= '<span class="fw-bold">' . e($maid->name ?? 'غير محدد') . '</span><br>';
                            $html .= '<small class="text-muted">الجواز: ' . e($maid->passport_num ?? 'غير محدد') . '</small>';

                            if ($maid->nationality) {
                                $nationality = 'غير محدد';
                                if ($maid->nationality->translations && $maid->nationality->translations->count() > 0) {
                                    $translation = $maid->nationality->translations->where('locale', app()->getLocale())->first();
                                    if ($translation) {
                                        $nationality = $translation->sub_title;
                                    }
                                }
                                $html .= '<br><small class="text-muted">الجنسية: ' . e($nationality) . '</small>';
                            }
                            $html .= '</div>';
                        });

                        if ($maids->count() > 3) {
                            $html .= '<small class="text-muted">+' . ($maids->count() - 3) . ' عاملات إضافية</small>';
                        }
                    } else {
                        $html .= '<span class="text-muted">غير محدد</span>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('created_meta', function ($row) {
                    $createdAt = data_get($row, 'created_at');
                    $createdAtLabel = $createdAt ? Carbon::parse($createdAt)->format('Y-m-d H:i') : '-';
                    $creatorName = data_get($row, 'creator_name') ?: 'غير محدد';

                    return '<div class="small">'
                        . '<div><i class="fa fa-calendar-plus"></i> ' . e($createdAtLabel) . '</div>'
                        . '<div class="text-muted"><i class="fa fa-user"></i> ' . e($creatorName) . '</div>'
                        . '</div>';
                })
                ->addColumn('travel_details', function($row) {
                    $html = '<div class="travel-details">';

                    $arrivalAirport = data_get($row, 'arrival_airport');
                    $airport = $this->travelAirportLabel($arrivalAirport);
                    $html .= '<i class="fa fa-plane"></i> ' . e($airport) . '<br>';

                    // التاريخ
                    $arrivalDate = data_get($row, 'arrival_date');
                    if ($arrivalDate) {
                        $html .= '<i class="fa fa-calendar"></i> ' . Carbon::parse($arrivalDate)->format('Y-m-d') . '<br>';
                    }

                    // الوقت
                    if (data_get($row, 'arrival_time')) {
                        $html .= '<i class="fa fa-clock"></i> ' . data_get($row, 'arrival_time');
                    }

                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('order_id', function ($row) {
                    $orderIds = collect(data_get($row, 'order_ids', []))->filter();
                    if ($orderIds->isEmpty()) {
                        return '<span class="text-muted">-</span>';
                    }

                    $html = $orderIds->take(4)->map(function ($orderId) {
                        return '<span class="badge bg-light text-dark border me-1">#' . e($orderId) . '</span>';
                    })->implode(' ');

                    if ($orderIds->count() > 4) {
                        $html .= '<span class="badge bg-secondary">+' . ($orderIds->count() - 4) . '</span>';
                    }

                    return $html;
                })
                ->addColumn('actions', function($row) {
                    $tripId = data_get($row, 'id');
                    $status = data_get($row, 'status');
                    $canEdit = auth('admin')->check() && \AdminGuardHasPerm(\App\PermKeys::TRAVEL_EDIT);
                    $canDelete = auth('admin')->check() && \AdminGuardHasPerm(\App\PermKeys::TRAVEL_DELETE);
                    $canControl = auth('admin')->check() && \AdminGuardHasPerm(\App\PermKeys::TRAVEL_CONTROL);
                    $menuItems = [];

                    if ($canEdit) {
                        $menuItems[] = '<li><a class="dropdown-item" href="' . route('travel.edit', $tripId) . '">
                                            <i class="fas fa-edit"></i> ' . __('admin.edit') . '
                                        </a></li>';
                    }

                    if ($canControl && in_array($status, ['pending', 'scheduled', 'new', 'active', 'in_progress', 'confirmed'], true)) {
                        $menuItems[] = '<li><a class="dropdown-item text-success" href="javascript:void(0)" onclick="completeTrip(' . $tripId . ')">
                                            <i class="fas fa-check-circle"></i> إكتمال الرحلة
                                        </a></li>';
                    }

                    if ($canDelete) {
                        $menuItems[] = '<li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteTravel(' . $tripId . ')">
                                            <i class="fas fa-trash"></i> ' . __('admin.delete') . '
                                        </a></li>';
                    }

                    if ($menuItems === []) {
                        return '<span class="text-muted">لا توجد إجراءات</span>';
                    }

                    return '<div class="dropdown actions-dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false" title="الإجراءات المتاحة">
                                    <i class="fas fa-cogs me-1"></i> الإجراءات
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg">'
                                . implode('', $menuItems) .
                                '</ul></div>';
                })
                ->rawColumns(['user_data', 'maid_data', 'created_meta', 'travel_details', 'order_id', 'actions'])
                ->make(true);
        }

        // For non-AJAX requests (initial page load)
        return view('admin.travel_history.index', [
            'travelCreators' => Admin::query()
                ->notSuspended()
                ->orderBy('name')
                ->get(['id', 'name']),
            'arrivalAirports' => $this->travelAirportOptions(),
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->buildGroupedTripsCollection($this->travelIndexQuery($request)->get());
        $filename = 'travel-trips-' . now()->format('Y-m-d-His') . '.csv';

        return $this->csvDownload($filename, $this->travelExportHeadings(), $rows->map(function ($row) {
            return $this->travelExportRow($row);
        }));
    }

    public function print(Request $request)
    {
        $rows = $this->buildGroupedTripsCollection($this->travelIndexQuery($request)->get());

        return view('admin.travel_history.print', [
            'title' => __('admin.travel_history'),
            'headings' => $this->travelExportHeadings(),
            'rows' => $rows->map(fn ($row) => $this->travelExportRow($row)),
        ]);
    }

    public function statistics()
    {
        try {
            $groupKey = $this->tripGroupSqlExpression();
            $tripGroups = TravelHistory::query()
                ->where('travel_type', '!=', 'deportation')
                ->selectRaw($groupKey . ' AS group_key')
                ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 0 ELSE 1 END) AS non_completed_count")
                ->selectRaw("SUM(CASE WHEN status IN ('new', 'active', 'scheduled', 'in_progress', 'pending', 'confirmed') THEN 1 ELSE 0 END) AS active_count")
                ->selectRaw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count")
                ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS current_active_count")
                ->selectRaw("SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) AS scheduled_count")
                ->selectRaw("SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_count")
                ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count")
                ->selectRaw("SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) AS new_count")
                ->selectRaw('MIN(arrival_date) AS first_arrival_date')
                ->groupByRaw($groupKey);

            $stats = DB::query()
                ->fromSub($tripGroups, 'travel_trip_groups')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw('COALESCE(SUM(CASE WHEN non_completed_count = 0 THEN 1 ELSE 0 END), 0) AS completed')
                ->selectRaw('COALESCE(SUM(CASE WHEN active_count > 0 THEN 1 ELSE 0 END), 0) AS active')
                ->selectRaw("COALESCE(SUM(CASE WHEN first_arrival_date > ? AND (scheduled_count > 0 AND in_progress_count = 0 AND current_active_count = 0 OR pending_count > 0 AND in_progress_count = 0 AND current_active_count = 0 AND scheduled_count = 0 AND confirmed_count = 0 OR new_count > 0 AND in_progress_count = 0 AND current_active_count = 0 AND scheduled_count = 0 AND confirmed_count = 0 AND pending_count = 0) THEN 1 ELSE 0 END), 0) AS upcoming", [now()->toDateString()])
                ->first();

            return response()->json([
                'total' => (int) ($stats->total ?? 0),
                'active' => (int) ($stats->active ?? 0),
                'completed' => (int) ($stats->completed ?? 0),
                'upcoming' => (int) ($stats->upcoming ?? 0),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'total' => 0,
                'active' => 0,
                'completed' => 0,
                'upcoming' => 0
            ]);
        }
    }

    public function calendarEvents(Request $request)
    {
        [$rangeStart, $rangeEnd] = $this->calendarRequestRange($request);

        $query = TravelHistory::with([
            'user:id,name,phone',
            'maid:id,name',
            'assignedTo:id,name',
        ])->orderBy('arrival_date', 'asc')
            ->orderBy('arrival_time', 'asc');

        if ($rangeStart && $rangeEnd) {
            $query->where('arrival_date', '>=', $rangeStart->toDateString())
                ->where('arrival_date', '<', $rangeEnd->toDateString());
        }

        if ($request->filled('travel_scope')) {
            if ($request->travel_scope === 'arrival') {
                $query->where('travel_type', '!=', 'deportation');
            } elseif ($request->travel_scope === 'deportation') {
                $query->where('travel_type', 'deportation');
            }
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('travel_type') && $request->travel_type !== 'all') {
            $query->where('travel_type', $request->travel_type);
        }

        $now = Carbon::now();
        $events = [];
        $tripGroups = $query->get()->groupBy(fn (TravelHistory $trip) => $this->resolveTripGroupKey($trip));

        foreach ($tripGroups as $group) {
            $trip = $group->sortByDesc('id')->first();
            if (!$trip) {
                continue;
            }

            $arrivalAt = $this->resolveArrivalDateTime($trip);
            if (!$arrivalAt && $trip->created_at) {
                $arrivalAt = Carbon::parse($trip->created_at);
            }

            if (!$arrivalAt) {
                continue;
            }

            $remainingSeconds = $now->diffInSeconds($arrivalAt, false);
            $travelTypeLabel = $trip->travel_type === 'deportation' ? 'ترحيل' : 'وصول';
            $status = $this->resolveGroupStatus($group);
            $statusLabel = __('travel_types.status.' . $status);
            if ($statusLabel === 'travel_types.status.' . $status) {
                $statusLabel = $status;
            }

            $orderIds = $group->pluck('order_id')->filter()->unique()->sort()->values();
            $maidNames = $group->map(fn (TravelHistory $item) => optional($item->maid)->name)
                ->filter()
                ->unique()
                ->values();
            $clientNames = $group->map(fn (TravelHistory $item) => optional($item->user)->name)
                ->filter()
                ->unique()
                ->values();

            $contractsCount = $orderIds->count();
            $singleTitleName = $maidNames->first() ?: ('رحلة #' . $trip->id);
            if ($contractsCount > 1) {
                $title = $travelTypeLabel . ' مجمعة (' . $contractsCount . ' عقود)';
            } else {
                $title = $travelTypeLabel . ' - ' . $singleTitleName;
            }

            $events[] = [
                'id' => (string) $trip->id,
                'title' => $title,
                'start' => $arrivalAt->toIso8601String(),
                'allDay' => false,
                'url' => route('travel'),
                'backgroundColor' => $this->calendarEventColor($trip),
                'borderColor' => $this->calendarEventColor($trip),
                'extendedProps' => [
                    'trip_id' => $trip->id,
                    'travel_type' => $trip->travel_type,
                    'travel_type_label' => $travelTypeLabel,
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'client_name' => $clientNames->count() > 1 ? ('متعدد (' . $clientNames->count() . ')') : ($clientNames->first() ?? null),
                    'maid_name' => $maidNames->count() > 1 ? ('متعدد (' . $maidNames->count() . ')') : ($maidNames->first() ?? null),
                    'order_id' => $trip->order_id,
                    'order_ids' => $orderIds->all(),
                    'contracts_count' => $contractsCount,
                    'order_ids_text' => $orderIds->map(fn ($id) => '#' . $id)->implode(' • '),
                    'is_grouped' => $contractsCount > 1,
                    'arrival_airport' => $trip->arrival_airport,
                    'arrival_date' => $arrivalAt->format('Y-m-d'),
                    'arrival_time' => $arrivalAt->format('H:i'),
                    'remaining_seconds' => $remainingSeconds,
                    'remaining_label' => $this->formatRemainingTimeLabel($remainingSeconds),
                    'assigned_to' => optional($trip->assignedTo)->name,
                    'assigned_to_id' => $trip->assigned_to,
                    'assignment_note' => $trip->assignment_note,
                ],
            ];
        }

        $this->appendTaskCalendarEvents($events, $rangeStart, $rangeEnd);

        return response()->json([
            'success' => true,
            'events' => $events,
        ]);
    }

    private function calendarRequestRange(Request $request): array
    {
        if (!$request->filled('start') || !$request->filled('end')) {
            return [null, null];
        }

        try {
            return [
                Carbon::parse($request->start)->startOfDay(),
                Carbon::parse($request->end)->startOfDay(),
            ];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    private function appendTaskCalendarEvents(array &$events, ?Carbon $rangeStart, ?Carbon $rangeEnd): void
    {
        if (!Schema::hasTable('tasks') || !Schema::hasColumn('tasks', 'due_date')) {
            return;
        }

        $query = DB::table('tasks')
            ->select('id', 'title', 'description', 'priority', 'status', 'due_date', 'assigned_to')
            ->whereNotNull('due_date')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('due_date');

        if ($rangeStart && $rangeEnd) {
            $query->whereDate('due_date', '>=', $rangeStart->toDateString())
                ->whereDate('due_date', '<', $rangeEnd->toDateString());
        }

        $tasks = $query->limit(300)->get();

        foreach ($tasks as $task) {
            $dueDate = Carbon::parse($task->due_date);
            $color = match ($task->priority) {
                'urgent' => '#dc3545',
                'high' => '#fd7e14',
                'low' => '#198754',
                default => '#6f42c1',
            };

            $events[] = [
                'id' => 'task-' . $task->id,
                'title' => 'تذكير - ' . ($task->title ?: ('مهمة #' . $task->id)),
                'start' => $dueDate->toDateString(),
                'allDay' => true,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'task_id' => $task->id,
                    'event_type' => 'task',
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'description' => $task->description,
                    'assigned_to_id' => $task->assigned_to,
                    'remaining_seconds' => now()->diffInSeconds($dueDate, false),
                    'remaining_label' => $this->formatRemainingTimeLabel(now()->diffInSeconds($dueDate, false)),
                ],
            ];
        }
    }

    public function assignableStaff()
    {
        $staff = $this->assignableStaffCollection()
            ->map(fn (Admin $admin) => $this->formatAssignableStaff($admin));

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    public function assignTrip(Request $request, $id)
    {
        $request->validate([
            'assigned_to' => 'required|exists:admins,id',
            'assignment_note' => 'nullable|string|max:2000',
        ]);

        try {
            $trip = TravelHistory::with(['user', 'maid'])->findOrFail($id);
            $assignee = Admin::findOrFail($request->assigned_to);
            $groupTrips = $this->resolveTripRecords($trip);
            $groupTrips->each(fn (TravelHistory $tripRecord) => $tripRecord->load(['user', 'maid']));
            $orderIds = $groupTrips->pluck('order_id')->filter()->unique()->values();

            DB::beginTransaction();

            foreach ($groupTrips as $item) {
                $item->update([
                    'assigned_to' => $assignee->id,
                    'assigned_by' => auth('admin')->id(),
                    'assigned_at' => now(),
                    'assignment_note' => $request->assignment_note,
                ]);
            }

            $notificationText = $this->createTripAssignmentNotification($groupTrips, $assignee, $request->assignment_note);
            $smsResult = $this->sendAssignmentSms($assignee, $notificationText);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $smsResult['sent']
                    ? __('ingaz.trip_assigned_and_notification_sent')
                    : __('ingaz.trip_assigned_sms_failed', ['reason' => $smsResult['error']]),
                'alert_type' => $smsResult['sent'] ? 'success' : 'warning',
                'sms' => $smsResult,
            ]);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            \Log::error('Trip assignment failed', [
                'trip_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('ingaz.trip_assignment_error_details', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    private function resolveArrivalDateTime(TravelHistory $trip): ?Carbon
    {
        if (empty($trip->arrival_date)) {
            return null;
        }

        try {
            if (!empty($trip->arrival_time)) {
                return Carbon::parse($trip->arrival_date . ' ' . $trip->arrival_time);
            }

            return Carbon::parse($trip->arrival_date);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function calendarEventColor(TravelHistory $trip): string
    {
        if ($trip->status === 'completed') {
            return '#6c757d';
        }

        if ($trip->travel_type === 'deportation') {
            return '#dc3545';
        }

        return '#0d6efd';
    }

    private function formatRemainingTimeLabel(int $seconds): string
    {
        if ($seconds === 0) {
            return 'لحظات للوصول';
        }

        $isFuture = $seconds > 0;
        $seconds = abs($seconds);

        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' يوم';
        }
        if ($hours > 0) {
            $parts[] = $hours . ' ساعة';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' دقيقة';
        }

        if (empty($parts)) {
            $parts[] = 'أقل من دقيقة';
        }

        return ($isFuture ? 'متبقي ' : 'متأخر ') . implode(' و ', array_slice($parts, 0, 2));
    }

    private function buildAssignmentMessage(TravelHistory $trip, ?Collection $groupTrips = null, ?string $completionUrl = null): string
    {
        $travelType = $trip->travel_type === 'deportation' ? 'ترحيل' : 'استقدام';
        $groupTrips = $groupTrips ?: collect([$trip]);
        $maidNames = $groupTrips->map(fn (TravelHistory $item) => optional($item->maid)->name)
            ->filter()
            ->unique()
            ->values();
        $contractIds = $groupTrips->pluck('order_id')->filter()->unique()->values();
        $maidName = $maidNames->isNotEmpty() ? $maidNames->take(4)->implode('، ') : 'غير محدد';
        $arrivalDate = $trip->arrival_date ?: 'غير محدد';
        $arrivalTime = $trip->arrival_time ?: 'غير محدد';
        $airport = $this->travelAirportLabel($trip->arrival_airport);
        $flightCompany = $trip->flight_company ?: 'غير محدد';
        $flightNumber = $trip->flight_number ?: 'غير محدد';
        $contractsText = $contractIds->isNotEmpty()
            ? $contractIds->map(fn ($id) => '#' . $id)->implode('، ')
            : 'غير محدد';

        $message = "تم إسناد رحلة {$travelType} لكم. العقود: {$contractsText}. العاملة: {$maidName}. موعد الوصول: {$arrivalDate} {$arrivalTime}. المطار: {$airport}. شركة الطيران: {$flightCompany}. رقم الرحلة: {$flightNumber}.";

        if ($completionUrl) {
            $message .= " رابط إكمال الرحلة بعد الاستلام: {$completionUrl}";
        }

        return $message;
    }

    private function sendAssignmentSms(Admin $assignee, string $message): array
    {
        $phone = $assignee->formatted_verification_phone;

        if (empty($phone)) {
            return [
                'sent' => false,
                'error' => __('ingaz.trip_assignment_sms_missing_phone'),
                'response' => null,
            ];
        }

        if (! CommunicationSettings::smsGatewayIsEnabled()) {
            return [
                'sent' => false,
                'error' => __('ingaz.trip_assignment_sms_gateway_disabled'),
                'response' => null,
            ];
        }

        if (! CommunicationSettings::smsIsConfigured()) {
            return [
                'sent' => false,
                'error' => __('ingaz.trip_assignment_sms_gateway_not_configured'),
                'response' => null,
            ];
        }

        try {
            $response = $this->sendSMS($phone, $message);
            $failureReason = $this->smsGatewayFailureReason($response);

            if ($failureReason) {
                \Log::warning('Failed to send assignment SMS', [
                    'admin_id' => $assignee->id,
                    'phone' => $phone,
                    'error' => $failureReason,
                    'response' => $response,
                ]);

                return [
                    'sent' => false,
                    'error' => $failureReason,
                    'response' => $response,
                ];
            }

            return [
                'sent' => true,
                'error' => null,
                'response' => $response,
            ];
        } catch (\Throwable $e) {
            \Log::warning('Failed to send assignment SMS', [
                'admin_id' => $assignee->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'error' => __('ingaz.trip_assignment_sms_exception', ['error' => $e->getMessage()]),
                'response' => null,
            ];
        }
    }

    private function smsGatewayFailureReason($response): ?string
    {
        if ($response === null || trim((string) $response) === '') {
            return __('ingaz.trip_assignment_sms_empty_response');
        }

        $body = trim((string) $response);
        $decoded = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $code = trim((string) data_get($decoded, 'code', data_get($decoded, 'statusCode', '')));
            $status = strtolower(trim((string) data_get($decoded, 'status', data_get($decoded, 'state', ''))));
            $success = data_get($decoded, 'success');
            $message = trim((string) data_get($decoded, 'message', data_get($decoded, 'error', data_get($decoded, 'errors.0.message', ''))));

            if ($success === true || in_array($status, ['success', 'sent', 'queued', 'accepted'], true) || in_array($code, ['1', '200', '201'], true)) {
                return null;
            }

            if ($success === false || in_array($status, ['error', 'failed', 'failure', 'rejected'], true) || ($code !== '' && ! in_array($code, ['1', '200', '201'], true))) {
                $detail = $message !== '' ? $message : Str::limit($body, 200);
                $reference = $code !== '' ? $code : ($status !== '' ? $status : null);

                return $reference
                    ? __('ingaz.trip_assignment_sms_gateway_response_with_reference', ['response' => $detail, 'reference' => $reference])
                    : __('ingaz.trip_assignment_sms_gateway_response', ['response' => $detail]);
            }
        }

        $normalizedBody = strtolower($body);
        if (in_array($normalizedBody, ['1', 'ok', 'success'], true)) {
            return null;
        }

        if (Str::contains($normalizedBody, ['error', 'fail', 'failed', 'reject', 'rejected', 'invalid'])) {
            return __('ingaz.trip_assignment_sms_gateway_response', ['response' => Str::limit($body, 200)]);
        }

        return null;
    }

    private function assignableStaffCollection(): Collection
    {
        return Admin::query()
            ->activeForOperations()
            ->select('id', 'name', 'phone', 'admin_type', 'status')
            ->orderBy('name')
            ->get();
    }

    private function formatAssignableStaff(Admin $admin): array
    {
        $typeLabel = $admin->admin_type ?: 'employee';

        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'phone' => $admin->contact_phone,
            'admin_type' => $admin->admin_type,
            'label' => $admin->name . ' (' . $typeLabel . ')',
        ];
    }

    private function createTripAssignmentNotification(Collection $tripRecords, Admin $assignee, ?string $assignmentNote = null): string
    {
        $notificationTrip = $tripRecords->first();
        if (!$notificationTrip) {
            return '';
        }

        $orderIds = $tripRecords->pluck('order_id')->filter()->unique()->values();
        $completionUrl = route('travel', [
            'trip_id' => $notificationTrip->id,
            'complete_trip' => $notificationTrip->id,
        ]);
        $notificationText = $this->buildAssignmentMessage($notificationTrip, $tripRecords, $completionUrl);

        if ($assignmentNote) {
            $notificationText .= ' ملاحظة الإسناد: ' . $assignmentNote;
        }

        AdminNotification::create([
            'admin_id' => $assignee->id,
            'created_by' => auth('admin')->id(),
            'title' => 'إسناد رحلة جديدة',
            'message' => $notificationText,
            'type' => 'travel_assignment',
            'link' => $completionUrl,
            'data' => [
                'trip_id' => $notificationTrip->id,
                'travel_type' => $notificationTrip->travel_type,
                'arrival_date' => $notificationTrip->arrival_date,
                'arrival_time' => $notificationTrip->arrival_time,
                'arrival_airport' => $this->travelAirportLabel($notificationTrip->arrival_airport),
                'flight_company' => $notificationTrip->flight_company,
                'flight_number' => $notificationTrip->flight_number,
                'order_id' => $notificationTrip->order_id,
                'order_ids' => $orderIds->all(),
                'contracts_count' => $orderIds->count(),
                'completion_url' => $completionUrl,
            ],
        ]);

        return $notificationText;
    }

            /**
     * إكتمال الرحلة
     */
    public function completeTrip(
        Request $request,
        RecruitmentTripAccountingService $tripAccountingService,
        RentalMaidArrivalService $rentalMaidArrivalService,
        $id
    )
    {
        try {
            $travel = TravelHistory::findOrFail($id);
            $tripRecords = $this->resolveTripRecords($travel);
            $updatedContracts = 0;
            $accountingCreatedContracts = 0;
            $accountingSkippedContracts = [];
            $accountingWarnings = [];
            $supplierPurchases = [];
            $rentalMaidsMovedToShelter = 0;

            $dispatchBlockingContracts = [];

            foreach ($tripRecords as $tripRecord) {
                if (!$tripRecord->order_id) {
                    continue;
                }

                $linkedContract = Contract::find($tripRecord->order_id);
                if (!$linkedContract) {
                    continue;
                }

                $dispatchError = RecruitmentContractExternalOfficeDispatchController::ensureDispatchCompleted($linkedContract);
                if ($dispatchError) {
                    $dispatchBlockingContracts[] = '#' . $linkedContract->id;
                }
            }

            if (!empty($dispatchBlockingContracts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إكمال الرحلة قبل إتمام إرسالية المكتب الخارجي للعقود: ' . implode('، ', array_unique($dispatchBlockingContracts))
                ], 422);
            }

            \DB::beginTransaction();

            foreach ($tripRecords as $tripRecord) {
                // تحديث حالة الرحلة إلى مكتملة
                $tripRecord->status = 'completed';
                if (!$tripRecord->arrival_date) {
                    $tripRecord->arrival_date = now()->toDateString();
                }
                if (!$tripRecord->arrival_time) {
                    $tripRecord->arrival_time = now()->format('H:i:s');
                }
                $tripRecord->arrived_at = now();
                $tripRecord->arrival_confirmed_by = auth('admin')->id();

                // البحث عن العقد المرتبط بالرحلة
                $contract = null;
                if ($tripRecord->order_id) {
                    // البحث في جدول العقود العادية
                    $contract = \App\Models\Contract::where('id', $tripRecord->order_id)->first();

                    // إذا لم يوجد في العقود العادية، البحث في عقود الإيجار
                    if (!$contract) {
                        $contract = \App\Models\RentalContract::where('id', $tripRecord->order_id)->first();
                    }
                }

                // إذا وجد العقد، تحديث حالته وتفعيل الضمان
                if ($contract) {
                    if ($contract instanceof \App\Models\Contract) {
                        RecruitmentContractExternalOfficeDispatchController::syncArrivalStationFromDispatch($contract, $tripRecord);
                    }

                    $tripRecord->save();

                    // بعد إكمال الرحلة ينتقل العقد أولاً إلى الوصول، ثم يحدد الضمان الحالة النهائية
                    $contract->update(['status' => ContractStatusEnum::Arrival->value]);
                    $updatedContracts++;

                    // حساب وتفعيل الضمان إذا كان العقد من نوع Contract
                    if ($contract instanceof \App\Models\Contract) {
                        $contract->calculateWarrantyStatus();
                        $contract->refresh();

                        $statusLogNote = $contract->status === ContractStatusEnum::Completed->value
                            ? 'تم إكمال الرحلة وانتهاء فترة الضمان، لذلك تم تحديث العقد إلى مكتمل'
                            : 'تم إكمال الرحلة ونقل العقد إلى مرحلة الوصول وتفعيل الضمان';

                        // إنشاء سجل حالة العقد
                        $contract->contract_status_logs()->create([
                            'status' => $contract->status,
                            'date' => now(),
                            'notes' => $statusLogNote
                        ]);

                        $accountingResult = $tripAccountingService->recordCompletionEntries($contract, $tripRecord);

                        if ($rentalMaidArrivalService->moveToShelter($contract, $tripRecord)) {
                            $rentalMaidsMovedToShelter++;
                        }

                        if (($accountingResult['created_revenue_constraint'] ?? false) || ($accountingResult['created_external_office_cost_constraint'] ?? false)) {
                            $accountingCreatedContracts++;
                        }

                        if (($accountingResult['skipped_reason'] ?? null) === 'missing_invoice') {
                            $accountingSkippedContracts[] = '#' . $contract->id;
                        }

                        foreach (($accountingResult['warnings'] ?? []) as $warning) {
                            $accountingWarnings[] = 'العقد #' . $contract->id . ': ' . $warning;
                        }

                        if ($accountingResult['supplier_purchase_id'] ?? null) {
                            $supplierPurchases[] = '#'
                                . ($accountingResult['supplier_purchase_code'] ?: $accountingResult['supplier_purchase_id'])
                                . ' (قيد #'
                                . ($accountingResult['external_office_cost_constraint_code'] ?: $accountingResult['external_office_cost_constraint_id'])
                                . ')';
                        }
                    }
                    // إذا كان عقد إيجار، تفعيل الضمان بشكل مختلف
                    else if ($contract instanceof \App\Models\RentalContract) {
                        $contract->update([
                            'warranty_status' => 'active',
                            'warranty_start_date' => now(),
                            'warranty_end_date' => now()->addDays(AutomationSettings::warrantyPeriodDays()),
                        ]);
                    }

                    \Log::info('Trip completed and contract updated', [
                        'travel_id' => $tripRecord->id,
                        'contract_id' => $contract->id,
                        'contract_type' => get_class($contract),
                        'contract_status' => $contract->status,
                        'warranty_status' => $contract->warranty_status ?? 'N/A'
                    ]);
                } else {
                    $tripRecord->save();
                }
            }

            \DB::commit();

            $orderCount = $tripRecords->pluck('order_id')->filter()->unique()->count();
            $isGrouped = $orderCount > 1;

            $message = $updatedContracts > 0
                ? ($isGrouped
                    ? 'تم إكتمال الرحلة المجمعة وتحديث ' . $updatedContracts . ' عقد بنجاح'
                    : 'تم إكتمال الرحلة وتحديث حالة العقد إلى مرحلة الوصول وتفعيل الضمان بنجاح')
                : 'تم إكتمال الرحلة بنجاح';

            if ($accountingCreatedContracts > 0) {
                $message .= $isGrouped
                    ? ' وتم إنشاء القيود المحاسبية لـ ' . $accountingCreatedContracts . ' عقد.'
                    : ' وتم إنشاء القيود المحاسبية المرتبطة بالفاتورة.';
            }

            if (!empty($supplierPurchases)) {
                $message .= ' فواتير المورد وقيود الإغلاق: ' . implode('، ', array_unique($supplierPurchases)) . '.';
            }

            if (!empty($accountingSkippedContracts)) {
                $message .= ' لم تُنشأ القيود المحاسبية للعقود ' . implode('، ', array_unique($accountingSkippedContracts)) . ' لعدم وجود فاتورة.';
            }

            if (!empty($accountingWarnings)) {
                $message .= ' تنبيه: ' . implode(' ', array_unique($accountingWarnings));
            }

            if ($rentalMaidsMovedToShelter > 0) {
                $message .= ' وتمت إضافة عاملات الإيجار إلى الإيواء وإتاحتهن للتأجير: ' . $rentalMaidsMovedToShelter . '.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'accounting_warnings' => array_values(array_unique($accountingWarnings)),
            ]);

        } catch (\Exception $e) {
            \DB::rollback();

            \Log::error('Error in completeTrip', [
                'travel_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إكتمال الرحلة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function create(Request $request, $id = null)
    {
        $id = $id ?: $request->get('id');
        $userId = $request->get('userId');
        $maidId = $request->get('maidId');
        $orderId = $request->get('orderId');
        $contractId = $request->get('contract_id');
        $data = null;
        $order = null;
        $selectedOrderIds = collect($request->input('order_ids', []))
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->values();

        // First check if there's an existing travel record for this order
        if ($orderId) {
            $data = TravelHistory::where('order_id', $orderId)
                ->with(['user', 'maid', 'order.user', 'order.maid'])
                ->first();
        }

        // If no data found by order_id, check by id parameter
        if (!$data && $id) {
            $data = TravelHistory::with([
                'user',
                'maid',
                'order.user',
                'order.maid'
            ])->find($id);
        }

        // If we found data, use its values
        if ($data) {
            $userId = $data->user_id;
            $maidId = $data->maid_id;
            $orderId = $data->order_id;
            $order = $data->order;

            if ($data->trip_group_code) {
                $selectedOrderIds = TravelHistory::where('trip_group_code', $data->trip_group_code)
                    ->pluck('order_id')
                    ->filter()
                    ->unique()
                    ->map(fn ($value) => (int) $value)
                    ->values();
            } elseif ($data->order_id) {
                $selectedOrderIds = collect([(int) $data->order_id]);
            }
        }

        $this->authorizeTravelFormAccess((bool) $data);

        if ($selectedOrderIds->isEmpty() && $orderId) {
            $selectedOrderIds = collect([(int) $orderId]);
        }

        $clientsList = $this->usersList();
        $maidsList = $this->maidsList();

        // Get contracts that can be used for recruitment travel scheduling.
        $contracts = Contract::whereIn('status', [
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
        ])
            ->with(['user', 'maid', 'maid.nationality', 'maid.religion', 'maid.jobTitles', 'info'])
            ->get();

        // If we have an order from the travel record or from the request, get it
        if ($contractId) {
            $selectedContract = Contract::with(['user', 'maid', 'maid.nationality', 'maid.religion', 'maid.jobTitles', 'info'])
                ->where('id', $contractId)
                ->first();

            if ($selectedContract) {
                $orderId = $contractId; // Use contract_id as order_id
                $userId = $selectedContract->user_id;
                $maidId = $selectedContract->maid_id;
                $order = $selectedContract; // Set the contract as order for display
                $selectedOrderIds = collect([(int) $selectedContract->id]);

                if (!$contracts->contains('id', $selectedContract->id)) {
                    $contracts->push($selectedContract);
                }
            }
        }

        if ($selectedOrderIds->isNotEmpty()) {
            $selectedContracts = Contract::with(['user', 'maid', 'maid.nationality', 'maid.religion', 'maid.jobTitles', 'info'])
                ->whereIn('id', $selectedOrderIds->all())
                ->get();

            foreach ($selectedContracts as $selectedContract) {
                if (!$contracts->contains('id', $selectedContract->id)) {
                    $contracts->push($selectedContract);
                }
            }

            if (!$order) {
                $order = $selectedContracts->first();
            }

            if (!$userId && $selectedContracts->count() === 1) {
                $userId = optional($selectedContracts->first())->user_id;
            }
            if (!$maidId && $selectedContracts->count() === 1) {
                $maidId = optional($selectedContracts->first())->maid_id;
            }
        }

        // Debug: Log contracts data to see if user data is loaded
        \Log::info('Contracts data for travel form:', [
            'contracts_count' => $contracts->count(),
            'contracts_with_user' => $contracts->whereNotNull('user')->count(),
            'sample_contract' => $contracts->first() ? [
                'id' => $contracts->first()->id,
                'user_id' => $contracts->first()->user_id,
                'user_name' => $contracts->first()->user->name ?? 'null',
                'user_city' => $contracts->first()->user->city ?? 'null',
            ] : 'no contracts'
        ]);

        $ordersList = [];
        $assignableStaff = $this->assignableStaffCollection()
            ->map(fn (Admin $admin) => $this->formatAssignableStaff($admin));

        $arrivalAirports = $this->travelAirportOptions();
        $currentArrivalAirport = $data && $data->arrival_airport
            ? $this->travelAirportLabel($data->arrival_airport)
            : null;

        if ($currentArrivalAirport && !collect($arrivalAirports)->contains('value', $currentArrivalAirport)) {
            array_unshift($arrivalAirports, [
                'value' => $currentArrivalAirport,
                'label' => $currentArrivalAirport,
            ]);
        }

        return view('admin.travel_history.control', compact('data', 'clientsList', 'maidsList', 'ordersList', 'contracts', 'id', 'userId', 'maidId', 'order', 'orderId', 'selectedOrderIds', 'assignableStaff', 'arrivalAirports'));
    }

    public function store(Request $request, $id = null)
    {
        // Validation
        $request->validate([
            'travel_type' => 'required',
            'travel_with' => 'required',
            'order_ids' => 'nullable|array|min:1',
            'order_ids.*' => 'nullable|exists:contracts,id',
            'order_id' => 'nullable|exists:contracts,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'assigned_to' => 'nullable|exists:admins,id',
            'assignment_note' => 'nullable|string|max:2000',
            'files.*' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ]);

        $orderIds = collect($request->input('order_ids', []))
            ->merge(array_filter([$request->order_id, $request->contract_id]))
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($orderIds->isEmpty()) {
            return redirect()->back()
                ->withErrors(['order_ids' => 'يجب اختيار عقد واحد على الأقل'])
                ->withInput();
        }

        $contracts = Contract::with('info')
            ->whereIn('id', $orderIds->all())
            ->get()
            ->keyBy('id');

        if ($contracts->isEmpty()) {
            return redirect()->back()
                ->withErrors(['order_ids' => 'العقود المختارة غير متاحة'])
                ->withInput();
        }

        $editingTrip = null;
        if ($id || $request->id) {
            $editingTrip = TravelHistory::find($id ?: $request->id);
        }

        $this->authorizeTravelFormAccess((bool) $editingTrip);

        $savedTrips = collect();
        $assignee = $request->filled('assigned_to')
            ? Admin::find($request->assigned_to)
            : null;

        DB::transaction(function () use (
            $request,
            $orderIds,
            $contracts,
            $editingTrip,
            &$savedTrips,
            $assignee
        ) {
            $existingGroupRecords = collect();
            if ($editingTrip) {
                $existingGroupRecords = $this->resolveTripRecords($editingTrip);
            }

            $tripGroupCode = null;
            if ($orderIds->count() > 1) {
                $tripGroupCode = $editingTrip && $editingTrip->trip_group_code
                    ? $editingTrip->trip_group_code
                    : $this->generateTripGroupCode();
            }

            foreach ($orderIds as $orderId) {
                $contract = $contracts->get($orderId);
                if (!$contract) {
                    continue;
                }

                $tripRecord = $existingGroupRecords->firstWhere('order_id', $orderId);
                if (!$tripRecord) {
                    $tripRecord = TravelHistory::where('order_id', $orderId)
                        ->where('travel_type', '!=', 'deportation')
                        ->latest('id')
                        ->first();
                }

                $statusValue = $request->filled('status')
                    ? $request->status
                    : ($tripRecord ? $tripRecord->status : 'pending');

                $payload = [
                    'user_id' => $contract->user_id ?: ($request->user_id ?: 0),
                    'maid_id' => $contract->maid_id ?: ($request->maid_id ?: 0),
                    'order_id' => $orderId,
                    'trip_group_code' => $tripGroupCode,
                    'travel_type' => $request->travel_type,
                    'travel_with' => $request->travel_with,
                    'status' => $statusValue,
                    'notes' => $request->notes,
                    'arrival_airport' => $request->arrival_airport,
                    'arrival_date' => $request->arrival_date,
                    'arrival_time' => $request->arrival_time,
                    'flight_company' => $request->flight_company,
                    'flight_number' => $request->flight_number,
                    'assigned_to' => $assignee?->id,
                    'assigned_by' => $assignee ? auth('admin')->id() : null,
                    'assigned_at' => $assignee ? now() : null,
                    'assignment_note' => $assignee ? $request->assignment_note : null,
                ];

                if ($tripRecord) {
                    $tripRecord->update($payload);
                } else {
                    $tripRecord = TravelHistory::create($payload);
                }

                if ($contract->status === ContractStatusEnum::Taffez->value) {
                    $contract->update(['status' => ContractStatusEnum::Tazker->value]);
                    $contract->contract_status_logs()->create([
                        'status' => ContractStatusEnum::Tazker->value,
                        'date' => now()->toDateString(),
                        'notes' => 'تم إضافة رحلة للعقد ونقله تلقائياً من التفييز إلى مرحلة التذاكر',
                    ]);
                }

                $savedTrips->push($tripRecord);
            }

            if ($existingGroupRecords->isNotEmpty()) {
                $existingGroupRecords
                    ->whereNotIn('order_id', $orderIds->all())
                    ->each(function (TravelHistory $oldTrip) {
                        $oldTrip->delete();
                    });
            }

            if (!empty($request->file('files')) && $savedTrips->isNotEmpty()) {
                foreach ($request->file('files') as $k => $file) {
                    $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $fileExtension = strtolower((string) $file->getClientOriginalExtension());
                    $fileName = $originalFileName . '-' . time() . $k . ($fileExtension !== '' ? '.' . $fileExtension : '');
                    $file->move(Constants::PUBLIC_TRAVEL_DIR, $fileName);

                    foreach ($savedTrips as $savedTrip) {
                        TravelHistoryFiles::create([
                            'travel_id' => $savedTrip->id,
                            'file' => $fileName,
                        ]);
                    }
                }
            }

            if ($request->arrival_date) {
                foreach ($orderIds as $orderId) {
                    $contract = $contracts->get($orderId);
                    if (!$contract) {
                        continue;
                    }

                    if ($contract->info) {
                        $contract->info->update([
                            'arrival_date' => $request->arrival_date
                        ]);
                    } else {
                        $contract->info()->create([
                            'arrival_date' => $request->arrival_date
                        ]);
                    }

                    \Log::info('Updated contract_info arrival_date', [
                        'contract_id' => $contract->id,
                        'arrival_date' => $request->arrival_date,
                    ]);
                }
            }
        });

        $assignmentSmsWarning = null;

        if ($assignee && $savedTrips->isNotEmpty()) {
            $savedTrips->each(fn (TravelHistory $trip) => $trip->load(['user', 'maid']));
            $notificationText = $this->createTripAssignmentNotification($savedTrips, $assignee, $request->assignment_note);
            $smsResult = $this->sendAssignmentSms($assignee, $notificationText);

            if (! $smsResult['sent']) {
                $assignmentSmsWarning = __('ingaz.trip_assigned_sms_failed', ['reason' => $smsResult['error']]);
            }
        }

        $redirect = redirect(route('travel'))->with('success', __('admin.success'));

        if ($assignmentSmsWarning) {
            $redirect->with('warning', $assignmentSmsWarning);
        }

        return $redirect;
    }

    public function edit(Request $request, $id)
    {
        return $this->create($request, $id);
    }

    public function update(Request $request, $id)
    {
        return $this->store($request, $id);
    }

    public function destroy(string $id)
    {
        $data = TravelHistory::find($id);
        if (!$data) {
            return redirect()->back()->with('error', __('admin.not_found'));
        }

        $tripRecords = $this->resolveTripRecords($data);
        foreach ($tripRecords as $tripRecord) {
            $tripRecord->delete();
        }

        return redirect()->back()->with('success', __('admin.success'));
    }

    private function buildGroupedTripsCollection(Collection $trips): Collection
    {
        $tripGroups = $trips->groupBy(fn (TravelHistory $trip) => $this->resolveTripGroupKey($trip));
        $primaryTrips = $tripGroups->map(fn (Collection $group) => $group->sortByDesc('id')->first());
        $creatorNames = $this->resolveTravelCreatorNames($primaryTrips->pluck('id')->filter()->all());

        return $tripGroups
            ->map(function (Collection $group) use ($creatorNames) {
                $primaryTrip = $group->sortByDesc('id')->first();
                $orderIds = $group->pluck('order_id')->filter()->unique()->values();
                $users = $group->map(fn (TravelHistory $trip) => $trip->user)
                    ->filter()
                    ->unique('id')
                    ->values();
                $maids = $group->map(fn (TravelHistory $trip) => $trip->maid)
                    ->filter()
                    ->unique('id')
                    ->values();

                return (object) [
                    'id' => $primaryTrip->id,
                    'trip_group_code' => $primaryTrip->trip_group_code,
                    'contracts_count' => $orderIds->count(),
                    'order_ids' => $orderIds->all(),
                    'order_id' => $orderIds->implode(', '),
                    'created_at' => $primaryTrip->created_at,
                    'creator_name' => $creatorNames->get($primaryTrip->id),
                    'travel_type' => $primaryTrip->travel_type,
                    'travel_with' => $primaryTrip->travel_with,
                    'arrival_airport' => $primaryTrip->arrival_airport,
                    'arrival_date' => $primaryTrip->arrival_date,
                    'arrival_time' => $primaryTrip->arrival_time,
                    'flight_company' => $primaryTrip->flight_company,
                    'flight_number' => $primaryTrip->flight_number,
                    'status' => $this->resolveGroupStatus($group),
                    'users' => $users,
                    'maids' => $maids,
                ];
            })
            ->sortByDesc('id')
            ->values();
    }

    /**
     * Paginate trip groups in SQL before loading models and their relationships.
     *
     * DataTables sends a page offset, but the rows displayed here can represent a
     * group of travel_histories records. Fetching every matching model before
     * grouping made the completed-trips view increasingly slow as data grew.
     */
    private function paginatedGroupedTrips($query, Request $request): array
    {
        $groupKey = $this->tripGroupSqlExpression();
        $groupedQuery = clone $query;
        $groupedQuery->setEagerLoads([])
            ->reorder()
            ->selectRaw($groupKey . ' AS group_key')
            ->selectRaw('MAX(travel_histories.id) AS sort_id')
            ->groupByRaw($groupKey);

        $filteredCount = DB::query()
            ->fromSub(clone $groupedQuery, 'filtered_travel_groups')
            ->count();

        $length = max(1, min(100, (int) $request->input('length', 10)));
        $start = max(0, (int) $request->input('start', 0));
        $pageGroups = $groupedQuery
            ->orderByDesc('sort_id')
            ->offset($start)
            ->limit($length)
            ->get();

        if ($pageGroups->isEmpty()) {
            return [collect(), (int) $filteredCount];
        }

        $groupCodes = $pageGroups
            ->pluck('group_key')
            ->filter(fn ($key) => str_starts_with((string) $key, 'group:'))
            ->map(fn ($key) => substr((string) $key, 6))
            ->values();
        $singleIds = $pageGroups
            ->pluck('group_key')
            ->filter(fn ($key) => str_starts_with((string) $key, 'single:'))
            ->map(fn ($key) => (int) substr((string) $key, 7))
            ->values();

        $query->where(function ($pageQuery) use ($groupCodes, $singleIds) {
            if ($groupCodes->isNotEmpty()) {
                $pageQuery->whereIn('trip_group_code', $groupCodes->all());
            }

            if ($singleIds->isNotEmpty()) {
                $method = $groupCodes->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                $pageQuery->{$method}('travel_histories.id', $singleIds->all());
            }
        });

        return [
            $this->buildGroupedTripsCollection($query->get()),
            (int) $filteredCount,
        ];
    }

    private function tripGroupSqlExpression(): string
    {
        return "CASE
            WHEN trip_group_code IS NOT NULL AND trip_group_code <> ''
                THEN CONCAT('group:', trip_group_code)
            ELSE CONCAT('single:', travel_histories.id)
        END";
    }

    private function travelIndexQuery(Request $request)
    {
        $query = TravelHistory::with(['user', 'maid', 'maid.nationality', 'maid.nationality.translations'])
            ->where('travel_type', '!=', 'deportation')
            ->orderBy('id', 'desc');

        if (!$request->filled('status') || $request->status === '') {
            $query->whereIn('status', ['new', 'active', 'scheduled', 'in_progress', 'pending', 'confirmed']);
        } elseif ($request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('travel_type', 'like', '%' . $search . '%')
                    ->orWhere('travel_with', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('arrival_airport', 'like', '%' . $search . '%')
                    ->orWhere('arrival_date', 'like', '%' . $search . '%')
                    ->orWhere('arrival_time', 'like', '%' . $search . '%')
                    ->orWhere('flight_company', 'like', '%' . $search . '%')
                    ->orWhere('flight_number', 'like', '%' . $search . '%')
                    ->orWhere('order_id', 'like', '%' . $search . '%')
                    ->orWhere('created_at', 'like', '%' . $search . '%')
                    ->orWhereExists(function ($subQuery) use ($search) {
                        $activityTable = config('activitylog.table_name', 'activity_log');
                        $subQuery->selectRaw('1')
                            ->from($activityTable)
                            ->join('admins', 'admins.id', '=', $activityTable . '.causer_id')
                            ->whereColumn($activityTable . '.subject_id', 'travel_histories.id')
                            ->where($activityTable . '.subject_type', TravelHistory::class)
                            ->where($activityTable . '.causer_type', Admin::class)
                            ->where($activityTable . '.event', 'created')
                            ->where('admins.name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('maid', function ($q) use ($search) {
                        $q->where('passport_num', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('travel_type')) {
            $query->where('travel_type', $request->travel_type);
        }

        if ($request->filled('travel_with')) {
            $query->where('travel_with', $request->travel_with);
        }

        if ($request->filled('arrival_airport')) {
            $query->whereIn('arrival_airport', TravelAirports::valuesFor($request->arrival_airport));
        }

        if ($request->filled('travel_date')) {
            $query->whereDate('arrival_date', $request->travel_date);
        }

        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }

        if ($request->filled('created_by')) {
            $activityTable = config('activitylog.table_name', 'activity_log');
            $query->whereExists(function ($subQuery) use ($activityTable, $request) {
                $subQuery->selectRaw('1')
                    ->from($activityTable)
                    ->whereColumn($activityTable . '.subject_id', 'travel_histories.id')
                    ->where($activityTable . '.subject_type', TravelHistory::class)
                    ->where($activityTable . '.causer_type', Admin::class)
                    ->where($activityTable . '.event', 'created')
                    ->where($activityTable . '.causer_id', $request->created_by);
            });
        }

        if ($request->filled('flight_company')) {
            $query->where('flight_company', 'like', '%' . $request->flight_company . '%');
        }

        if ($request->filled('flight_number')) {
            $query->where('flight_number', 'like', '%' . $request->flight_number . '%');
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', 'like', '%' . $request->order_id . '%');
        }

        return $query;
    }

    private function travelExportHeadings(): array
    {
        return [
            '#',
            __('admin.client_id'),
            __('admin.maid_id'),
            helperTrans('ingaz.contracts'),
            __('admin.travel_type'),
            __('admin.travel_with'),
            __('admin.flight_company'),
            __('admin.flight_number'),
            __('admin.status'),
            'تاريخ الإنشاء',
            'المنشئ',
            helperTrans('ingaz.arrival_datetime_prefix'),
            'المطار',
        ];
    }

    private function travelExportRow($row): array
    {
        $types = __('travel_types.travel_types') ?: [];
        $statuses = __('travel_types.status') ?: [];
        $travelWith = __('travel_types.travel_with') ?: [];
        $travelWith += [
            'group_transport' => helperTrans('ingaz.travel_with_group_transport'),
        ];

        return [
            $row->id,
            collect($row->users)->pluck('name')->filter()->implode('، ') ?: 'غير محدد',
            collect($row->maids)->pluck('name')->filter()->implode('، ') ?: 'غير محدد',
            collect($row->order_ids)->map(fn ($id) => '#' . $id)->implode('، ') ?: '-',
            $types[$row->travel_type] ?? $row->travel_type ?? '-',
            $travelWith[$row->travel_with] ?? $row->travel_with ?? '-',
            $row->flight_company ?: '-',
            $row->flight_number ?: '-',
            $statuses[$row->status] ?? $row->status ?? '-',
            optional($row->created_at)->format('Y-m-d H:i') ?: '-',
            $row->creator_name ?: '-',
            trim(($row->arrival_date ?: '-') . ' ' . ($row->arrival_time ?: '')),
            $this->travelAirportLabel($row->arrival_airport),
        ];
    }

    private function travelAirportLabel(?string $airport): string
    {
        if (!$airport) {
            return 'غير محدد';
        }

        return TravelAirports::label($airport);
    }

    private function travelAirportOptions(): array
    {
        return TravelAirports::options();
    }

    private function resolveTravelCreatorNames(array $tripIds): Collection
    {
        $tripIds = array_values(array_unique(array_filter($tripIds)));
        if ($tripIds === [] || !Schema::hasTable(config('activitylog.table_name', 'activity_log'))) {
            return collect();
        }

        return Activity::query()
            ->with('causer')
            ->where('subject_type', TravelHistory::class)
            ->whereIn('subject_id', $tripIds)
            ->where('causer_type', Admin::class)
            ->where('event', 'created')
            ->orderBy('subject_id')
            ->orderBy('id')
            ->get()
            ->groupBy('subject_id')
            ->map(fn (Collection $activities) => optional($activities->first()?->causer)->name)
            ->filter();
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

    private function resolveTripGroupKey(TravelHistory $trip): string
    {
        if (!empty($trip->trip_group_code)) {
            return 'group:' . $trip->trip_group_code;
        }

        return 'single:' . $trip->id;
    }

    private function resolveGroupStatus(Collection $group): string
    {
        if ($group->isEmpty()) {
            return 'pending';
        }

        if ($group->every(fn (TravelHistory $trip) => $trip->status === 'completed')) {
            return 'completed';
        }

        $priorityStatuses = ['in_progress', 'active', 'scheduled', 'confirmed', 'pending', 'new'];
        foreach ($priorityStatuses as $status) {
            if ($group->contains(fn (TravelHistory $trip) => $trip->status === $status)) {
                return $status;
            }
        }

        return (string) ($group->first()->status ?? 'pending');
    }

    private function resolveTripRecords(TravelHistory $trip): Collection
    {
        if (!empty($trip->trip_group_code)) {
            return TravelHistory::where('trip_group_code', $trip->trip_group_code)->get();
        }

        return collect([$trip]);
    }

    private function generateTripGroupCode(): string
    {
        do {
            $code = 'GRP-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        } while (TravelHistory::where('trip_group_code', $code)->exists());

        return $code;
    }

    public function storeTravelHistory(Request $request)
    {
        // Validation
        $request->validate([
            'travel_type' => 'required',
            'travel_with' => 'required',
            'files.*' => 'file|mimes:jpeg,png,jpg,pdf|max:4096', // Adjust mime types and max size as needed
        ]);

        $order = Order::find($request->order_id);

        // Create a new TravelHistory record
        $data = TravelHistory::create([
            'user_id' => $order->user_id ?? 0,
            'maid_id' => $order->maid_id ?? 0,
            'order_id' => $request->order_id,
            'travel_type' => $request->travel_type,
            'travel_with' => $request->travel_with,
            'status' => 'pending',
            'notes' => $request->notes,
            //** */
            'arrival_airport' => $request->arrival_airport,
            'arrival_date' => $request->arrival_date,
            'arrival_time' => $request->arrival_time,
            'flight_company' => $request->flight_company,
            'flight_number' => $request->flight_number,
        ]);

        // Handle file uploads
        if (!empty($request->file('files'))) {
            foreach ($request->file('files') as $k => $file) {
                $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Get the original filename without extension
                $fileExtension = strtolower((string) $file->getClientOriginalExtension());
                $fileName = $originalFileName . '-' . time() . $k . ($fileExtension !== '' ? '.' . $fileExtension : '');
                $file->move(Constants::PUBLIC_TRAVEL_DIR, $fileName);
                TravelHistoryFiles::create([
                    'travel_id' => $data->id,
                    'file' => $fileName,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function downloadFile($id)
    {
        $file = TravelHistoryFiles::find($id);

        if (!$file) {
            return redirect()->back()->with('error', __('admin.file_not_found'));
        }

        $filePath = Constants::PUBLIC_TRAVEL_DIR . '/' . $file->file;

        if (File::exists($filePath)) {
            return Response::download($filePath, $file->file);
        }

        return redirect()->back()->with('error', __('admin.file_not_found'));
    }

    public function deleteFile($id)
    {
        try {
            $file = TravelHistoryFiles::findOrFail($id);
            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الملف بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الملف'
            ], 500);
        }
    }

    public function checkTripExists(Request $request)
    {
        try {
            $orderId = $request->get('order_id');

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف العقد مطلوب'
                ], 400);
            }

            $exists = TravelHistory::where('order_id', $orderId)->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'يوجد رحلة لهذا العقد' : 'لا يوجد رحلة لهذا العقد'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحقق من وجود الرحلة'
            ], 500);
        }
    }

    private function authorizeTravelFormAccess(bool $editing): void
    {
        $requiredPermission = $editing ? \App\PermKeys::TRAVEL_EDIT : \App\PermKeys::TRAVEL_CREATE;

        abort_unless(\AdminGuardHasPerm($requiredPermission), 403);
    }
}
