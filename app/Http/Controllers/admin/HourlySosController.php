<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyWorkerSosAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class HourlySosController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(AdminGuardHasPerm('hourly_sos_view'), 403);
        abort_unless(Schema::hasTable('hourly_worker_sos_alerts'), 503, 'نداء النجدة غير مهيأ على الخادم.');
        $filters = $request->validate([
            'status' => ['nullable', 'in:open,triggered,acknowledged,resolved,cancelled'],
            'maid_id' => ['nullable', 'integer', 'min:1'],
            // البلاغات تأتي من العاملة والسائق على نفس الشاشة؛ المرشّح للفصل بينهما عند الحاجة.
            'guard' => ['nullable', 'in:worker,driver'],
        ]);
        $query = HourlyWorkerSosAlert::with(['maid:id,name,hourly_login_phone,phone_1', 'visit:id,visit_number,hourly_rental_order_id', 'acknowledger:id,name', 'resolver:id,name'])
            ->when($filters['guard'] ?? null, fn ($q, $guard) => $guard === 'driver'
                ? $q->where('guard', 'driver')
                : $q->where(fn ($inner) => $inner->whereNull('guard')->orWhere('guard', 'worker')))
            ->when(($filters['status'] ?? null) === 'open', fn ($q) => $q->whereIn('status', ['triggered', 'acknowledged']))
            ->when(($filters['status'] ?? null) && $filters['status'] !== 'open', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['maid_id'] ?? null, fn ($q, $maid) => $q->where('maid_id', $maid));
        $alerts = $query->orderByRaw("CASE WHEN status = 'triggered' THEN 0 WHEN status = 'acknowledged' THEN 1 ELSE 2 END")
            ->latest('id')->paginate(25)->withQueryString();
        $stats = HourlyWorkerSosAlert::selectRaw("SUM(CASE WHEN status = 'triggered' THEN 1 ELSE 0 END) AS triggered, SUM(CASE WHEN status = 'acknowledged' THEN 1 ELSE 0 END) AS acknowledged, SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved, SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled, SUM(CASE WHEN guard = 'driver' THEN 1 ELSE 0 END) AS drivers")->first();

        return view('admin.hourly_sos.index', compact('alerts', 'stats', 'filters'));
    }

    public function update(Request $request, HourlyWorkerSosAlert $alert)
    {
        abort_unless(AdminGuardHasPerm('hourly_sos_view') && AdminGuardHasPerm('hourly_sos_edit'), 403);
        $data = $request->validate([
            'status' => ['required', 'in:acknowledged,resolved'],
            'resolution_note' => ['nullable', 'string', 'max:500'],
        ]);
        if (!in_array($alert->status, ['triggered', 'acknowledged'], true)) {
            throw ValidationException::withMessages(['status' => 'تم إغلاق هذا البلاغ مسبقًا.']);
        }
        $adminId = auth('admin')->id();
        if ($data['status'] === 'acknowledged') {
            $alert->update(['status' => 'acknowledged', 'acknowledged_by' => $adminId, 'acknowledged_at' => now()]);
            $message = 'تم تأكيد استلام نداء النجدة.';
        } else {
            $alert->update([
                'status' => 'resolved', 'resolved_by' => $adminId, 'resolved_at' => now(),
                'resolution_note' => $data['resolution_note'] ?? null,
                'acknowledged_by' => $alert->acknowledged_by ?: $adminId,
                'acknowledged_at' => $alert->acknowledged_at ?: now(),
            ]);
            $message = 'تم إغلاق نداء النجدة وتسجيل المعالجة.';
        }
        return back()->with('success', $message);
    }
}
