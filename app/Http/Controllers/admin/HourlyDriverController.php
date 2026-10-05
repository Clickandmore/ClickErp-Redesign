<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hourly\DriverRequest;
use App\Models\Hourly\HourlyDriver;
use App\Models\ingaz\Nationality;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HourlyDriverController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', 'in:active,inactive'], 'deleted' => ['nullable', 'boolean'],
            'team_id' => ['nullable', 'integer'], 'nationality_id' => ['nullable', 'integer'], 'gender' => ['nullable', 'in:male,female'],
            'account' => ['nullable', 'in:web,mobile,both,disabled'], 'documents' => ['nullable', 'in:expiring,expired,missing'],
        ]);
        $hasTeams = Schema::hasTable('hourly_team_members');
        $query = HourlyDriver::with('teamMemberships.team');
        if (($teamId = $filters['team_id'] ?? null) && $hasTeams) {
            $query->whereIn('id', DB::table('hourly_team_members')->select('member_id')
                ->where('member_type', 'driver')->where('hourly_team_id', $teamId)->whereNull('left_at'));
        }
        if ($nationalityId = $filters['nationality_id'] ?? null) $query->where('nationality_id', $nationalityId);
        if ($gender = $filters['gender'] ?? null) $query->where('gender', $gender);
        if ($account = $filters['account'] ?? null) {
            $account === 'disabled' ? $query->where('account_enabled', false) : $query->where('account_enabled', true)->where('login_access', $account);
        }
        if (($filters['documents'] ?? null) === 'missing') {
            $query->where(function ($q) {
                foreach (array_keys(HourlyDriver::REQUIRED_DOCUMENTS) as $field) $q->orWhereNull($field);
            });
        } elseif ($documents = $filters['documents'] ?? null) {
            $limit = $documents === 'expired' ? now()->subDay()->toDateString() : now()->addDays(30)->toDateString();
            $query->where(function ($q) use ($limit) {
                foreach (HourlyDriver::EXPIRY_DATES as $field) $q->orWhereDate($field, '<=', $limit);
            });
        }
        if ($request->boolean('deleted')) $query->onlyTrashed();
        if ($search = $filters['search'] ?? null) {
            $query->where(fn ($q) => $q->where('name', 'like', "%$search%")
                ->orWhere('employee_number', 'like', "%$search%")
                ->orWhere('phone', 'like', "%$search%")
                ->orWhere('phone2', 'like', "%$search%"));
        }
        if ($status = $filters['status'] ?? null) $query->where('is_active', $status === 'active');
        return view('admin.hourly_drivers.index', [
            'drivers' => $query->latest('id')->paginate(25)->withQueryString(),
            'total' => HourlyDriver::count(), 'active' => HourlyDriver::where('is_active', true)->count(),
            'canLogin' => HourlyDriver::where('is_active', true)->where('account_enabled', true)->count(),
            'deletedCount' => HourlyDriver::onlyTrashed()->count(),
            'filters' => $filters,
            'teams' => Schema::hasTable('hourly_teams') ? DB::table('hourly_teams')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']) : collect(),
            'nationalities' => Nationality::whereIn('id', HourlyDriver::withTrashed()->whereNotNull('nationality_id')->distinct()->select('nationality_id'))->get(),
        ]);
    }

    public function create() { return $this->form(new HourlyDriver(['is_active' => true, 'account_enabled' => true, 'login_access' => 'both', 'gender' => 'male', 'vehicle_capacity' => 12])); }
    public function edit(HourlyDriver $driver) { return $this->form($driver); }

    private function form(HourlyDriver $driver)
    {
        $members = $driver->exists ? $driver->teamMemberships()->get() : collect();
        return view('admin.hourly_drivers.form', [
            'driver' => $driver, 'nationalities' => Nationality::all(),
            'teams' => \App\Models\Hourly\HourlyTeam::where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $members->pluck('hourly_team_id')))->orderBy('name')->get(),
            'primaryTeamId' => optional($members->firstWhere('role', 'primary'))->hourly_team_id,
            'secondaryTeamIds' => $members->where('role', 'secondary')->pluck('hourly_team_id')->all(),
        ]);
    }

    public function store(DriverRequest $request)
    {
        $driver = DB::transaction(function () use ($request) {
            $driver = HourlyDriver::create($request->driverData() + ['username' => 'DR-'.Str::upper(Str::random(12))]);
            if ($request->has('primary_team_id') || $request->has('secondary_team_ids')) {
                app(\App\Services\Hourly\DriverTeamService::class)->sync($driver, $request->input('primary_team_id') ?: null, $request->input('secondary_team_ids', []));
            }
            return $driver;
        });
        return $this->saved($request, route('admin.hourly-drivers.edit', $driver), 'تم إنشاء السائق. اسم المستخدم: '.$driver->username);
    }

    public function update(DriverRequest $request, HourlyDriver $driver)
    {
        DB::transaction(function () use ($driver, $request) {
            $driver = HourlyDriver::lockForUpdate()->findOrFail($driver->id);
            $driver->fill($request->driverData());
            if ($driver->isDirty(['password', 'is_active', 'account_enabled', 'login_access', 'login_phone', 'email'])) $driver->auth_version++;
            $driver->save();
            if ($request->has('primary_team_id') || $request->has('secondary_team_ids')) {
                app(\App\Services\Hourly\DriverTeamService::class)->sync($driver, $request->input('primary_team_id') ?: null, $request->input('secondary_team_ids', []));
            }
        });
        return $this->saved($request, route('admin.hourly-drivers.edit', $driver), 'تم حفظ بيانات السائق.', true);
    }

    /** الحفظ من النموذج عبر fetch يُرجع رابط الانتقال، والنموذج العادي يُعاد توجيهه كما كان. */
    private function saved(Request $request, string $url, string $message, bool $back = false)
    {
        if ($request->ajax()) {
            session()->flash('success', $message);
            return response()->json(['message' => $message, 'redirect' => $url]);
        }
        return ($back ? back() : redirect($url))->with('success', $message);
    }

    /**
     * يفتح بوابة السائق بحسابه للمتابعة والدعم. حارس hourly_driver منفصل عن حارس admin،
     * فيبقى المشرف مسجلًا في اللوحة، ويحمل hourly_driver_impersonator رقمه لإظهار شريط التنبيه
     * في البوابة وإعادته إلى جدول السائقين عند الخروج.
     */
    public function loginAs(Request $request, HourlyDriver $driver)
    {
        if (!$driver->canLoginVia('web')) {
            return back()->withErrors(['driver' => 'لا يمكن الدخول بحساب '.$driver->name.': الحساب متوقف أو معطّل أو لا يسمح بالدخول من الموقع.']);
        }
        $admin = auth('admin')->user();
        activity('impersonation')->causedBy($admin)->performedOn($driver)
            ->withProperties(['ip' => $request->ip(), 'user_agent' => $request->userAgent(), 'guard' => 'hourly_driver'])
            ->log('فتح بوابة السائق بحساب '.$driver->name);
        auth('hourly_driver')->login($driver);
        $request->session()->put('hourly_driver_version', $driver->auth_version);
        $request->session()->put('hourly_driver_impersonator', ['id' => $admin->id, 'name' => $admin->name]);
        return redirect()->route('hourly-driver.home');
    }

    public function status(Request $request, HourlyDriver $driver)
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $driver->fill($data);
        if ($driver->isDirty('is_active')) $driver->auth_version++;
        $driver->save();
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'تم تحديث حالة السائق.',
                'is_active' => $driver->is_active,
                'counts' => [
                    'active' => HourlyDriver::where('is_active', true)->count(),
                    'inactive' => HourlyDriver::where('is_active', false)->count(),
                    'canLogin' => HourlyDriver::where('is_active', true)->where('account_enabled', true)->count(),
                ],
            ]);
        }
        return back()->with('success', 'تم تحديث حالة السائق.');
    }

    public function destroy(HourlyDriver $driver)
    {
        DB::transaction(function () use ($driver) {
            $driver = HourlyDriver::lockForUpdate()->findOrFail($driver->id);
            $driver->increment('auth_version');
            $driver->delete();
        });
        return redirect()->route('admin.hourly-drivers.index')->with('success', 'تم نقل السائق إلى المحذوفين. يمكنك استرجاعه أو حذفه نهائيًا.');
    }

    public function restore($deletedDriver)
    {
        DB::transaction(function () use ($deletedDriver) {
            $driver = HourlyDriver::onlyTrashed()->lockForUpdate()->findOrFail($deletedDriver);
            $driver->increment('auth_version');
            $driver->restore();
        });
        return redirect()->route('admin.hourly-drivers.index', ['deleted' => 1])->with('success', 'تم استرجاع السائق. يلزم تسجيل الدخول مجددًا.');
    }

    public function forceDestroy($deletedDriver)
    {
        DB::transaction(function () use ($deletedDriver) {
            $driver = HourlyDriver::onlyTrashed()->lockForUpdate()->findOrFail($deletedDriver);
            if (Schema::hasTable('hourly_team_members') && DB::table('hourly_team_members')
                ->where('member_type', 'driver')->where('member_id', $driver->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['driver' => 'لا يمكن حذف السائق نهائيًا لارتباطه بسجل عضوية فريق.']);
            }
            $driver->forceDelete();
        });
        return redirect()->route('admin.hourly-drivers.index', ['deleted' => 1])->with('success', 'تم حذف السائق نهائيًا.');
    }
}
