<?php
namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyRentalOrder;
use App\Services\Hourly\OrderCompensation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderCompensationController extends Controller
{
    public function store(Request $request, HourlyRentalOrder $order, OrderCompensation $compensations)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_compensate'), 403);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'reason' => ['required', Rule::in(array_keys(OrderCompensation::REASONS))],
            'note' => ['nullable', 'required_if:reason,other', 'string', 'max:1000'],
            'destination' => ['required', Rule::in(array_keys(OrderCompensation::DESTINATIONS))],
            'visit_id' => ['nullable', 'integer'],
            'request_token' => ['required', 'uuid'],
        ]);
        $row = $compensations->create($order, $data, auth('admin')->user(), AdminGuardHasPerm('hourly_orders_compensate_override'));
        $message = $row->destination === 'wallet'
            ? 'أُضيف التعويض '.number_format((float) $row->amount, 2).' ر.س إلى محفظة العميل.'
            : 'سُجّل التعويض '.number_format((float) $row->amount, 2).' ر.س بانتظار صرفه للعميل.';
        return $request->expectsJson() ? response()->json(['message' => $message, 'data' => $row]) : back()->with('success', $message);
    }

    public function paidOut(Request $request, HourlyRentalOrder $order, int $compensation, OrderCompensation $compensations)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_collect'), 403);
        $data = $request->validate(['payout_reference' => ['nullable', 'string', 'max:100']]);
        $compensations->markPaidOut($order, $compensation, $data['payout_reference'] ?? null, auth('admin')->user());
        return back()->with('success', 'سُجّل صرف التعويض للعميل.');
    }

    /** Compensation report: what was given, why, by whom and through which channel. */
    public function index(Request $request)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_view'), 403);
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d'],
            'reason' => ['nullable', Rule::in(array_keys(OrderCompensation::REASONS))],
            'destination' => ['nullable', Rule::in(array_keys(OrderCompensation::DESTINATIONS))],
            'status' => ['nullable', Rule::in(array_keys(OrderCompensation::STATUSES))],
        ]);
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();
        $query = DB::table('hourly_order_compensations as c')
            ->join('hourly_rental_orders as o', 'o.id', '=', 'c.hourly_rental_order_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.user_id')->leftJoin('admins as a', 'a.id', '=', 'c.created_by')
            ->whereBetween('c.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($filters['reason'] ?? null, fn ($q, $v) => $q->where('c.reason', $v))
            ->when($filters['destination'] ?? null, fn ($q, $v) => $q->where('c.destination', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('c.status', $v));
        $totals = (clone $query)->selectRaw('COUNT(*) as count, COALESCE(SUM(c.amount),0) as total')->first();
        $byReason = (clone $query)->groupBy('c.reason')->selectRaw('c.reason, COUNT(*) as count, SUM(c.amount) as total')->orderByDesc('total')->get();
        $pending = (clone $query)->where('c.status', 'pending_payout')->sum('c.amount');
        $rows = $query->orderByDesc('c.id')->select(['c.*', 'o.order_number', 'u.name as customer_name', 'u.phone as customer_phone', 'a.name as created_by_name'])
            ->paginate(25)->withQueryString();
        return view('admin.hourly.compensations', compact('rows', 'totals', 'byReason', 'pending', 'from', 'to', 'filters'));
    }
}
