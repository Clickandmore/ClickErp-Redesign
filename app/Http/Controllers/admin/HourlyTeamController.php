<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Hourly\HourlyTeam;
use App\Models\Hourly\HourlyTeamArea;
use App\Models\Hourly\HourlyTeamMember;
use App\Models\ingaz\Maid;
use App\Models\ShelterPlaces;
use App\Services\Rental\RentalMaidQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * بيانات فرق الساعات وقائمتها، مع الإبقاء على خريطة التغطية القديمة.
 */
class HourlyTeamController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search'=>['nullable','string','max:150'], 'status'=>['nullable','in:active,inactive']]);
        $query = HourlyTeam::query()
            ->with(['districts.city', 'driverMembers.driver'])
            ->orderBy('sort_order')->orderBy('id');

        if ($request->filled('shelter_place_id')) {
            $query->where('shelter_place_id', (int) $request->shelter_place_id);
        }
        if ($request->filled('city_id')) {
            $query->where('city_id', (int) $request->city_id);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
        }

        return view('admin.hourly_teams.list', [
            'teams' => $query->get(),
            'filters' => $request->only(['shelter_place_id', 'city_id', 'status', 'search']),
        ]);
    }

    public function create()
    {
        return view('admin.hourly_teams.basic', ['team' => new HourlyTeam(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        try {
            $team = DB::transaction(function () use ($data, $request) {
                $team = HourlyTeam::create($data + ['created_by' => auth('admin')->id()]);

                return $team;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Failed to create hourly team', ['message' => $e->getMessage()]);

            return back()->withInput()->with('error', 'تعذر حفظ الفريق حاليًا.');
        }

        return redirect()->route('admin.hourly-teams.manage', $team)->with('success', 'تم إنشاء الفريق «' . $team->name . '».');
    }

    public function edit(HourlyTeam $team)
    {
        return view('admin.hourly_teams.basic', ['team' => $team]);
    }

    public function update(Request $request, HourlyTeam $team)
    {
        $data = $this->validated($request, $team);

        try {
            DB::transaction(function () use ($team, $data, $request) {
                $team->update($data);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Failed to update hourly team', ['team' => $team->id, 'message' => $e->getMessage()]);

            return back()->withInput()->with('error', 'تعذر حفظ الفريق حاليًا.');
        }

        // التعديل من مودال قائمة الفرق يعود إلى القائمة؛ ومن صفحة التعديل يبقى فيها.
        return $request->input('_form') === 'team-edit'
            ? redirect()->route('admin.hourly-teams.index')->with('success', 'تم حفظ الفريق «' . $team->name . '».')
            : redirect()->route('admin.hourly-teams.edit', $team)->with('success', 'تم حفظ الفريق.');
    }

    public function toggle(HourlyTeam $team)
    {
        $team->update(['is_active' => !$team->is_active]);

        return back()->with('success', $team->is_active ? 'تم تفعيل الفريق.' : 'تم إيقاف الفريق.');
    }

    public function destroy(HourlyTeam $team)
    {
        // الزيارات والمشاوير تُربط بالفريق في مراحل لاحقة؛ عندها يُمنع الحذف مع وجود استخدام.
        foreach (['hourly_visits', 'driver_trips'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'hourly_team_id')
                && DB::table($table)->where('hourly_team_id', $team->id)->exists()) {
                return back()->with('error', 'لا يمكن حذف فريق له زيارات أو مشاوير. أوقفه بدل حذفه.');
            }
        }

        DB::transaction(function () use ($team) {
            $team = HourlyTeam::lockForUpdate()->findOrFail($team->id);
            $team->members()->whereNull('left_at')->update(['left_at' => now()->toDateString()]);
            $team->delete();
        });

        return redirect()->route('admin.hourly-teams.index')->with('success', 'تم حذف الفريق.');
    }

    /** خريطة كل الفرق: مضلعاتها ونقاط انطلاقها. */
    public function map(Request $request)
    {
        return view('admin.hourly_teams.map', [
            'cities' => $this->cities($request),
            'shelters' => $this->shelters(),
            'geo' => $this->geoPayload($request),
        ]);
    }

    public function mapData(Request $request)
    {
        return response()->json($this->geoPayload($request));
    }

    /** عاملات مكان إيواء بعينه — لاختيار الأعضاء. */
    public function shelterMaids(Request $request)
    {
        $shelterId = (int) $request->query('shelter_place_id');
        if ($shelterId <= 0) {
            return response()->json([]);
        }

        return response()->json(
            $this->maidsForShelter($shelterId)->map(fn (Maid $m) => [
                'id' => $m->id,
                'text' => trim(($m->name ?? '') . ' — ' . ($m->passport_num ?? ('#' . $m->id))),
            ])->values()
        );
    }

    // ------------------------------------------------------------------

    private function validated(Request $request, ?HourlyTeam $team = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
    private function maidsForShelter(int $shelterId)
    {
        $base = fn () => Maid::query()
            ->whereHas('contracts.info', fn ($q) => $q->where('shelter_id', $shelterId))
            ->orderBy('name');

        try {
            return RentalMaidQuery::applyShelterPool($base())->get(['maids.id', 'maids.name', 'maids.passport_num']);
        } catch (Throwable $e) {
            Log::warning('Hourly team maid pool fell back to simple shelter query', ['message' => $e->getMessage()]);

            return $base()->get(['maids.id', 'maids.name', 'maids.passport_num']);
        }
    }

    private function geoPayload(Request $request): array
    {
        $query = HourlyTeam::query()->with(['areas' => fn ($q) => $q->where('is_active', true), 'shelterPlace', 'city'])
            ->withCount([
                'members as maids_count' => fn ($q) => $q->whereNull('left_at')->where('member_type', HourlyTeamMember::TYPE_MAID),
            ]);
        if ($request->filled('city_id')) {
            $query->where('city_id', (int) $request->city_id);
        }
        if ($request->filled('shelter_place_id')) {
            $query->where('shelter_place_id', (int) $request->shelter_place_id);
        }
        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        $teams = $query->orderBy('sort_order')->get()->map(fn (HourlyTeam $t) => [
            'id' => $t->id,
            'name' => $t->name,
            'code' => $t->code,
            'color' => $t->color,
            'is_active' => $t->is_active,
            'shelter' => optional($t->shelterPlace)->title ?? ('#' . $t->shelter_place_id),
            'city' => optional($t->city)->name_ar,
            'maids_count' => $t->maids_count,
            'base' => ($t->base_lat !== null && $t->base_lng !== null) ? [$t->base_lat, $t->base_lng] : null,
            'edit_url' => route('admin.hourly-teams.edit', $t),
            'areas' => $t->areas->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'polygon' => $a->polygon])->values(),
        ])->values();

        return ['teams' => $teams, 'generated_at' => now()->toDateTimeString()];
    }

    private function shelters()
    {
        return Schema::hasTable('shelter_places') ? ShelterPlaces::query()->orderBy('sort_order')->orderBy('id')->get() : collect();
    }

    /** المدن التي ستُرجع فرقًا فعلية وفق بقية فلاتر الخريطة. */
    private function cities(Request $request)
    {
        if (!Schema::hasTable('cities') || !Schema::hasTable('hourly_teams')) {
            return collect();
        }

        $teamCities = HourlyTeam::query()
            ->select('city_id')
            ->whereNotNull('city_id')
            ->when($request->filled('shelter_place_id'), fn ($query) => $query->where('shelter_place_id', (int) $request->shelter_place_id))
            ->when(!$request->boolean('include_inactive'), fn ($query) => $query->where('is_active', true));

        return City::query()
            ->where('is_active', true)
            ->whereIn('id', $teamCities)
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'latitude', 'longitude']);
    }
}
