<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyDriver, HourlyServiceCategory, HourlyTeam};
use App\Services\Hourly\{RouteBoard, RouteCapacityExceeded};
use App\Support\HourlyRouteWindows;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * «إدارة المسار»: مهام التوصيل والاستلام لكل سائق في يوم واحد.
 * كل تحويل (مهمة، مختارة، أو كل مهام سائق) يمر من هنا إلى assign_driver القائم مع فحص مقاعد المركبة.
 * الصلاحيات من config/route_permissions.php: التحويل والترتيب والسعة مستقلة.
 */
class RoutingController extends Controller
{
    public function index(Request $request, RouteBoard $board)
    {
        $filters = $this->filters($request);
        $date = Carbon::parse($filters['date'] ?? today()->toDateString())->startOfDay();

        return view('admin.hourly_routing.index', [
            'date' => $date, 'filters' => $filters, 'board' => $board->build($date, $filters),
            'view' => $filters['view'] ?? 'flat', 'windows' => HourlyRouteWindows::all(),
            'services' => HourlyServiceCategory::ordered()->get(),
            'teams' => HourlyTeam::orderBy('name')->get(['id', 'name']),
            // قائمة التحويل: السائقون النشطون مع فرقهم، والخادم يتحقق من عضوية فريق الزيارة عند التنفيذ.
            'drivers' => HourlyDriver::where('is_active', true)->with('teamMemberships:id,member_id,hourly_team_id,is_active')->orderBy('name')->get(['id', 'name'])
                ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'teams' => $d->teamMemberships->where('is_active', true)->pluck('hourly_team_id')->values()]),
            'canEdit' => AdminGuardHasPerm('hourly_routing_windows'),
            'canTransfer' => AdminGuardHasPerm('hourly_routing_transfer'),
            'canReorder' => AdminGuardHasPerm('hourly_routing_reorder'),
            'canCapacity' => AdminGuardHasPerm('hourly_routing_capacity'),
        ]);
    }

    public function export(Request $request, RouteBoard $board)
    {
        $filters = $this->filters($request);
        $date = Carbon::parse($filters['date'] ?? today()->toDateString())->startOfDay();
        $data = $board->build($date, $filters);
        $rows = [];
        // نفس ترتيب المسار المعروض (المحفوظ بالسحب أولًا)، ورقم التسلسل داخل مسار كل سائق.
        foreach ($data['drivers'] as $driver) foreach ($driver['tasks'] as $i => $task) $rows[] = $this->row($task, $driver['name'], $driver['team'], $i + 1);
        foreach ($data['unassigned'] as $task) $rows[] = $this->row($task, __('hourly_routing.no_driver'), null, null);

        $sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $sheet->getActiveSheet();
        $ws->setRightToLeft(app()->getLocale() === 'ar');
        $ws->fromArray(__('hourly_routing.export_headers'), null, 'A1');
        foreach ($rows as $i => $row) {
            foreach (array_values($row) as $col => $value) {
                $ws->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1).($i + 2), (string) ($value ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
        foreach (range('A', 'P') as $column) $ws->getColumnDimension($column)->setAutoSize(true);
        $ws->freezePane('A2');

        return response()->streamDownload(function () use ($sheet) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet))->save('php://output');
            $sheet->disconnectWorksheets();
        }, 'hourly-routes-'.$date->toDateString().'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** تحويل مهمة أو عدة مهام (مختارة، أو كل مهام سائق) إلى سائق واحد. */
    public function transfer(Request $request, RouteBoard $board)
    {
        $data = $request->validate([
            'to_driver_id' => 'required|integer',
            'tasks' => 'required|array|min:1|max:200', 'tasks.*' => ['required', 'string', 'regex:/^\d+-(delivery|pickup)$/', 'distinct'],
            // السائق الحالي لكل مهمة كما رأته الصفحة؛ أي اختلاف يعني أن غيرك عدّلها فيُرفض التحويل كله.
            'expected' => 'present|array', 'expected.*' => 'nullable|integer',
            'same_driver' => 'sometimes|boolean', 'confirm_capacity' => 'sometimes|boolean',
            'reason' => 'required|string|max:2000',
        ]);
        try {
            $count = $board->transfer($data['tasks'], (int) $data['to_driver_id'], $data['reason'], auth('admin')->id(), $data['expected'],
                !empty($data['same_driver']), !empty($data['confirm_capacity']));
        } catch (RouteCapacityExceeded $e) {
            return response()->json(['message' => $e->getMessage(), 'needs_confirmation' => true,
                'capacity' => ['driver' => $e->driver, 'date' => $e->date, 'assigned' => $e->assigned, 'seats' => $e->seats]], 409);
        }

        return response()->json(['message' => __('hourly_routing.messages.transferred', ['count' => $count])]);
    }

    /** حفظ ترتيب السحب لمهام سائق في يوم. */
    public function reorder(Request $request, RouteBoard $board)
    {
        $data = $request->validate([
            'driver_id' => 'required|integer', 'date' => 'required|date_format:Y-m-d',
            'tasks' => 'required|array|min:1|max:200', 'tasks.*' => ['required', 'string', 'regex:/^\d+-(delivery|pickup)$/', 'distinct'],
        ]);
        $order = $board->reorder((int) $data['driver_id'], Carbon::parse($data['date']), $data['tasks']);

        return response()->json(['message' => __('hourly_routing.messages.reordered'), 'order' => $order]);
    }

    /** مقاعد مركبة السائق (vehicle_capacity) من كرت السائق؛ نفس حدود نموذج السائق. */
    public function capacity(Request $request)
    {
        $data = $request->validate(['driver_id' => 'required|integer|exists:hourly_drivers,id', 'seats' => 'required|integer|between:3,50']);
        HourlyDriver::whereKey($data['driver_id'])->update(['vehicle_capacity' => $data['seats']]);

        return response()->json(['message' => __('hourly_routing.messages.capacity_saved'), 'seats' => (int) $data['seats']]);
    }

    public function windows(Request $request)
    {
        $data = $request->validate([
            'windows' => 'present|array|max:'.HourlyRouteWindows::MAX,
            'windows.*.name' => 'required|string|max:40|distinct',
            'windows.*.from' => 'required|date_format:H:i',
            'windows.*.to' => 'required|date_format:H:i|after:windows.*.from',
        ], ['windows.*.to.after' => __('hourly_routing.messages.window_order'), 'windows.*.name.distinct' => __('hourly_routing.messages.window_names')]);

        return response()->json(['message' => __('hourly_routing.messages.windows_saved'), 'windows' => HourlyRouteWindows::persist($data['windows'])]);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'date' => 'nullable|date_format:Y-m-d', 'service_id' => 'nullable|integer', 'driver_id' => 'nullable|integer', 'team_id' => 'nullable|integer',
            'period' => ['nullable', Rule::in(RouteBoard::PERIODS)], 'view' => 'nullable|in:bands,flat',
            'from' => 'nullable|required_if:period,custom|date_format:H:i', 'to' => 'nullable|required_if:period,custom|date_format:H:i|after:from',
            'window' => 'nullable|string|max:40',
        ]);
    }

    private function row(array $task, string $driver, ?string $team, ?int $sequence): array
    {
        return [$driver, $team, $sequence, $task['clock'], $task['role_label'], $task['workers'], $task['customer'], $task['phone'], $task['order_number'], $task['visit_number'],
            $task['city'], $task['district'], $task['address'], $task['state_label'], $task['updated'], $task['km'] !== null ? number_format($task['km'], 2, '.', '') : null];
    }
}
