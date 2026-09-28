<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\ingaz\Maid;
use App\Services\Hourly\{StaffNotifications, WorkerAppService};
use App\Support\HourlyStaffOtp;
use Illuminate\Http\Request;

/**
 * مسارات تطبيق العاملة (تبويبات: ساعاتي · إجازاتي · أرباحي).
 * المعرّف في مسارات الزيارة هو hourly_visit_workers.id لا معرّف الزيارة.
 */
class WorkerAppController extends Controller
{
    public function __construct(private WorkerAppService $service) {}

    /** حارس العاملة يعيد HourlyMaidAccount؛ الخدمة تحتاج موديل Maid كاملًا بعلاقاته. */
    private function worker(): Maid { return Maid::findOrFail(auth('hourly_maid_api')->id()); }

    private function gps(Request $request): array
    {
        return $request->validate(['latitude' => ['nullable', 'numeric', 'between:-90,90'], 'longitude' => ['nullable', 'numeric', 'between:-180,180']]);
    }

    public function hours() { return response()->json(['data' => $this->service->hours($this->worker())]); }

    public function today(Request $request)
    {
        $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        return response()->json(['data' => $this->service->today($this->worker(), $data['date'] ?? null)]);
    }

    public function arrived(Request $request, int $visit)
    {
        return response()->json(['data' => $this->service->arrived($this->worker(), $visit, $this->gps($request))]);
    }

    public function finish(Request $request, int $visit)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        return response()->json(['data' => $this->service->finish($this->worker(), $visit, $data['note'] ?? null, $this->gps($request))]);
    }

    public function leaves() { return response()->json(['data' => $this->service->leaves($this->worker())]); }

    public function storeLeave(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:leave,sick'],
            'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        $result = $this->service->requestLeave($this->worker(), $data);
        return response()->json(['data' => $result, 'message' => $result['message']], 201);
    }

    public function sos()
    {
        return response()->json(['data' => $this->service->sosAlerts($this->worker())]);
    }

    public function storeSos(Request $request)
    {
        $data = $request->validate([
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'message' => ['nullable', 'string', 'max:500'],
            'visit_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $result = $this->service->triggerSos($this->worker(), $data);
        // message في الغلاف = تأكيد للعاملة، و data.message = نص بلاغها كما أرسلته.
        $confirmation = $result['confirmation'];
        unset($result['confirmation']);
        return response()->json(['data' => $result, 'message' => $confirmation], $result['created'] ? 201 : 200);
    }

    public function cancelSos(Request $request, int $alert)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $result = $this->service->cancelSos($this->worker(), $alert, $data['reason'] ?? null);
        return response()->json(['data' => $result, 'message' => 'تم إلغاء نداء النجدة.']);
    }

    /** صندوق الإشعارات: ما أُرسل لها فعلًا، حتى لو لم يصل الإشعار المدفوع للجهاز. */
    public function notifications(Request $request, StaffNotifications $inbox)
    {
        $data = $request->validate(['limit' => ['nullable', 'integer', 'between:1,100']]);
        return response()->json(['data' => $inbox->feed(HourlyStaffOtp::WORKER, (int) auth('hourly_maid_api')->id(), (int) ($data['limit'] ?? 50))]);
    }

    /** تصفير شارة غير المقروء (يُستدعى عند فتح شاشة الإشعارات). */
    public function readNotifications(Request $request, StaffNotifications $inbox)
    {
        $data = $request->validate(['until' => ['nullable', 'date']]);
        return response()->json(['data' => $inbox->markRead(HourlyStaffOtp::WORKER, (int) auth('hourly_maid_api')->id(), $data['until'] ?? null)]);
    }

    public function earnings() { return response()->json(['data' => $this->service->earnings($this->worker())]); }
}
