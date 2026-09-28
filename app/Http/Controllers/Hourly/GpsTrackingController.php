<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Services\Hourly\LocationPings;
use App\Services\Hourly\TrackingSocket;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * «تتبع GPS»: خريطة بآخر موقع لكل عاملة/سائق والعملاء من عناوين طلباتهم، وتتحدث تلقائيًا لليوم الحالي.
 * «الحالة الآن» و«عرض المسار» تُجلب عند الطلب لكل شخص.
 */
class GpsTrackingController extends Controller
{
    public function index()
    {
        return view('admin.hourly_gps.index', [
            'cities' => \App\Models\City::saudi()->active()->get(),
            'types' => __('hourly_routing.gps.types'),
            'refreshSeconds' => 5,
            'activeMinutes' => (int) HourlyRentalSettings::get('tracking_active_minutes', 60),
            'adminId' => (int) auth('admin')->id(),
            'socketConfig' => app(TrackingSocket::class)->browserConfig(),
        ]);
    }

    public function socketAuth(Request $request, TrackingSocket $socket)
    {
        abort_unless(auth('admin')->check() && AdminGuardHasPerm('hourly_gps_view'), 403);
        abort_unless($socket->enabled(), 503);
        $data = $request->validate(['socket_id' => ['required', 'string', 'max:100', 'regex:/^\d+\.\d+$/'],
            'channel_name' => ['required', Rule::in([TrackingSocket::CHANNEL])]]);
        return response($socket->authorize($data['socket_id']), 200)->header('Content-Type', 'application/json')->header('Cache-Control', 'no-store');
    }

    public function data(Request $request, LocationPings $pings)
    {
        $filters = $request->validate([
            'date' => 'nullable|date_format:Y-m-d', 'city_ids' => 'nullable|array', 'city_ids.*' => 'integer',
            'types' => 'nullable|array', 'types.*' => [Rule::in(LocationPings::TYPES)],
            'status' => 'nullable|in:all,active,inactive',
        ]);
        $date = Carbon::parse($filters['date'] ?? today()->toDateString());

        return response()->json(['points' => $pings->latest($date, $filters), 'server_time' => now()->format('H:i:s'), 'live' => $date->isToday(), 'past' => $date->lt(today())]);
    }

    /** المهمة الحالية والتالية للشخص اليوم. */
    public function status(Request $request, LocationPings $pings)
    {
        $data = $request->validate(['type' => ['required', Rule::in(LocationPings::TYPES)], 'id' => 'required|integer']);

        return response()->json($pings->now($data['type'], (int) $data['id']));
    }

    /** خط تحركات العاملة أو السائق خلال يوم. */
    public function trail(Request $request, LocationPings $pings)
    {
        $data = $request->validate(['type' => ['required', Rule::in(LocationPings::TRACKED)], 'id' => 'required|integer', 'date' => 'required|date_format:Y-m-d']);

        return response()->json(['points' => $pings->trail($data['type'], (int) $data['id'], Carbon::parse($data['date']))]);
    }
}
