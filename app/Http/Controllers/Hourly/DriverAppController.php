<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyDriver;
use App\Services\Hourly\{DriverAppService, DriverStopActions, StaffNotifications, StaffSos};
use App\Support\HourlyStaffOtp;
use Illuminate\Http\Request;

/**
 * مسارات تطبيق السائق (تبويبات: مناوبتي · العروض · مشوار اليوم · الاستلام والعودة · مستحقاتي).
 * الغلاف الموحد يضيفه وسيط HourlyStaffApiResponse، فالدوال تعيد البيانات فقط.
 */
class DriverAppController extends Controller
{
    public function __construct(private DriverAppService $board, private DriverStopActions $stops) {}

    private function driver(): HourlyDriver { return auth('hourly_driver_api')->user(); }

    private function gps(Request $request): array
    {
        return $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'], 'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'speed' => ['nullable', 'numeric', 'min:0', 'max:400'],
        ]);
    }

    public function shift(Request $request)
    {
        $data = $request->validate(['is_online' => ['required', 'boolean']]);
        return response()->json(['data' => $this->board->shift($this->driver(), (bool) $data['is_online'])]);
    }

    public function offers() { return response()->json(['data' => $this->board->offers($this->driver())]); }

    public function today(Request $request)
    {
        $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        return response()->json(['data' => $this->board->today($this->driver(), $data['date'] ?? null)]);
    }

    /**
     * مشاوير مدى تواريخ: السابقة والحالية والقادمة. الافتراضي آخر 7 أيام حتى اليوم.
     * الرد كائن فيه days و trips و diagnostics (أرقام تشرح سبب الفراغ إن حدث).
     */
    public function trips(Request $request)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d'],
            'date' => ['nullable', 'date_format:Y-m-d'], 'days' => ['nullable', 'integer', 'between:1,62'],
        ]);
        $to = $data['to'] ?? $data['date'] ?? now()->toDateString();
        $from = $data['from'] ?? $data['date'] ?? \Carbon\Carbon::parse($to)->subDays((int) ($data['days'] ?? 7))->toDateString();
        return response()->json(['data' => $this->board->history($this->driver(), $from, $to)]);
    }

    /** تشخيص مباشر: كم زيارة مُسنَدة لهذا السائق ولماذا لا تظهر. */
    public function diagnostics()
    {
        return response()->json(['data' => $this->board->diagnostics($this->driver())]);
    }

    public function accept(int $trip) { return response()->json(['data' => $this->board->accept($this->driver(), $trip)]); }

    public function reject(Request $request, int $trip)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);
        return response()->json(['data' => $this->board->reject($this->driver(), $trip, $data['reason'])]);
    }

    public function start(int $trip) { return response()->json(['data' => $this->board->start($this->driver(), $trip)]); }

    public function arrived(Request $request, int $stop) { return response()->json(['data' => $this->stops->arrived($this->driver(), $stop, $this->gps($request))]); }

    public function shelterPickup(Request $request, int $stop) { return response()->json(['data' => $this->stops->shelterPickup($this->driver(), $stop, $this->gps($request))]); }

    public function deliver(Request $request, int $stop)
    {
        // otp_code هو رمز التسليم الذي يراه العميل في تطبيقه؛ delivery_code اسم بديل مقبول.
        $data = $request->validate(['otp_code' => ['nullable', 'string', 'max:10'], 'delivery_code' => ['nullable', 'string', 'max:10'],
            'cash_collected_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000']]);
        return response()->json(['data' => $this->stops->deliver($this->driver(), $stop, $this->gps($request),
            $data['otp_code'] ?? $data['delivery_code'] ?? null,
            isset($data['cash_collected_amount']) ? (float) $data['cash_collected_amount'] : null)]);
    }

    public function pickUp(Request $request, int $stop) { return response()->json(['data' => $this->stops->pickUp($this->driver(), $stop, $this->gps($request))]); }

    public function shelterReturn(Request $request, int $stop) { return response()->json(['data' => $this->stops->shelterReturn($this->driver(), $stop, $this->gps($request))]); }

    public function fail(Request $request, int $stop)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);
        return response()->json(['data' => $this->stops->fail($this->driver(), $stop, $this->gps($request), $data['reason'])]);
    }

    public function pickupRequests() { return response()->json(['data' => $this->board->pickupRequests($this->driver())]); }

    /** نداء النجدة: بلاغاتي · إطلاق · إلغاء (نفس عقد العاملة). */
    public function sos(StaffSos $sos)
    {
        return response()->json(['data' => $sos->alerts(HourlyStaffOtp::DRIVER, (int) $this->driver()->id)]);
    }

    public function storeSos(Request $request, StaffSos $sos)
    {
        $data = $request->validate([
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'message' => ['nullable', 'string', 'max:500'],
            'visit_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $result = $sos->trigger(HourlyStaffOtp::DRIVER, (int) $this->driver()->id, $data);
        // message في الغلاف = تأكيد للسائق، و data.message = نص بلاغه كما أرسله.
        $confirmation = $result['confirmation'];
        unset($result['confirmation']);
        return response()->json(['data' => $result, 'message' => $confirmation], $result['created'] ? 201 : 200);
    }

    public function cancelSos(Request $request, int $alert, StaffSos $sos)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        return response()->json(['data' => $sos->cancel(HourlyStaffOtp::DRIVER, (int) $this->driver()->id, $alert, $data['reason'] ?? null),
            'message' => 'تم إلغاء نداء النجدة.']);
    }

    /** صندوق الإشعارات: ما أُرسل له فعلًا، حتى لو لم يصل الإشعار المدفوع للجهاز. */
    public function notifications(Request $request, StaffNotifications $inbox)
    {
        $data = $request->validate(['limit' => ['nullable', 'integer', 'between:1,100']]);
        return response()->json(['data' => $inbox->feed(HourlyStaffOtp::DRIVER, (int) $this->driver()->id, (int) ($data['limit'] ?? 50))]);
    }

    /** تصفير شارة غير المقروء (يُستدعى عند فتح شاشة الإشعارات). */
    public function readNotifications(Request $request, StaffNotifications $inbox)
    {
        $data = $request->validate(['until' => ['nullable', 'date']]);
        return response()->json(['data' => $inbox->markRead(HourlyStaffOtp::DRIVER, (int) $this->driver()->id, $data['until'] ?? null)]);
    }

    public function earnings() { return response()->json(['data' => $this->board->earnings($this->driver())]); }
}
