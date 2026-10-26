<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyRentalOrder;
use App\Services\Hourly\EarlyFinish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\Rule;

/** الإنهاء المبكر للزيارة: رد العميل (التطبيق والموقع) ومراجعة الإدارة وقرارها اليدوي. */
class EarlyFinishController extends Controller
{
    public function respond(Request $request, int $id, int $visit, int $finish, EarlyFinish $service)
    {
        $data = $request->validate(['response' => ['required', Rule::in(['confirmed', 'disputed'])], 'note' => ['nullable', 'string', 'max:1000']]);
        $order = HourlyRentalOrder::where('user_id', auth('api')->id())->findOrFail($id);
        $result = $service->respond($order, $visit, $finish, $data['response'], $data['note'] ?? null);
        return response()->json(['data' => $result, 'message' => $result['message']]);
    }

    public function index(Request $request, EarlyFinish $service)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_view'), 403);
        abort_unless(EarlyFinish::ready(), 503, 'يجب تشغيل ترحيل الإنهاء المبكر.');
        $filter = $request->validate(['status' => ['nullable', Rule::in(['review', 'awaiting_customer', 'confirmed', 'resolved', 'all'])]])['status'] ?? 'review';
        $query = DB::table('hourly_early_finishes as f')
            ->leftJoin('maids as m', 'm.id', '=', 'f.maid_id')
            ->leftJoin('hourly_rental_orders as o', 'o.id', '=', 'f.order_id')
            ->leftJoin('hourly_visits as v', 'v.id', '=', 'f.visit_id')
            ->select('f.*', 'm.name as worker_name', 'o.order_number', 'v.visit_number', 'v.scheduled_start_at');
        match ($filter) {
            'review' => $query->where(fn ($q) => $q->where('f.status', 'disputed')->orWhere(fn ($w) => $w->where('f.status', 'awaiting_customer')->where('f.respond_until', '<', now()))),
            'awaiting_customer' => $query->where('f.status', 'awaiting_customer')->where('f.respond_until', '>=', now()),
            'confirmed', 'resolved' => $query->where('f.status', $filter),
            default => null,
        };
        $finishes = $query->orderByDesc('f.id')->paginate(25)->withQueryString();
        $counts = [
            'review' => $service->reviewCount(),
            'awaiting_customer' => DB::table('hourly_early_finishes')->where('status', 'awaiting_customer')->where('respond_until', '>=', now())->count(),
            'confirmed' => DB::table('hourly_early_finishes')->where('status', 'confirmed')->count(),
            'resolved' => DB::table('hourly_early_finishes')->where('status', 'resolved')->count(),
        ];
        $counts['all'] = DB::table('hourly_early_finishes')->count();
        $admins = Schema::hasTable('admins') ? DB::table('admins')->whereIn('id', $finishes->pluck('reviewed_by')->filter())->pluck('name', 'id') : collect();
        return view('admin.hourly.early-finishes', compact('finishes', 'counts', 'filter', 'admins'));
    }

    public function review(Request $request, int $finish, EarlyFinish $service)
    {
        abort_unless(AdminGuardHasPerm('hourly_orders_create'), 403);
        $data = $request->validate(['decision' => ['required', Rule::in(array_keys(EarlyFinish::DECISIONS))], 'decision_note' => ['required', 'string', 'max:2000']]);
        $service->review($finish, $data, (int) auth('admin')->id());
        return back()->with('success', 'تم تسجيل قرار الإنهاء المبكر.');
    }
}
