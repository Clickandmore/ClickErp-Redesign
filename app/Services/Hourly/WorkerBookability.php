<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyPackage, HourlyServiceCategory, HourlyTeam};
use App\Models\ingaz\Maid;
use App\Services\Rental\RentalMaidQuery;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * لماذا لا تظهر العاملة في مواعيد الحجز؟ يفحص العاملة بنفس شروط حساب التوفر:
 * HourlyWorkerAssignment::candidates() (الإيواء والإيجار والإيقاف والفريق والخدمات والجنسية)
 * و availabilityReason() (الجدول والإجازة)، وتحديد فريق الحي كما في HourlyBookingRules::assertDistrictCovered().
 *
 * blockers: تمنع ظهورها في أي حجز. warnings: تستبعدها من بعض الباقات أو الفترات فقط.
 */
class WorkerBookability
{
    private const DAYS = ['saturday' => 'السبت', 'sunday' => 'الأحد', 'monday' => 'الاثنين', 'tuesday' => 'الثلاثاء', 'wednesday' => 'الأربعاء', 'thursday' => 'الخميس', 'friday' => 'الجمعة'];

    /** @return array{blockers: list<array{area:string,text:string}>, warnings: list<array{area:string,text:string}>, served_districts: int} */
    public function report(Maid $maid): array
    {
        $blockers = $warnings = [];
        $block = function (string $area, string $text) use (&$blockers) { $blockers[] = compact('area', 'text'); };
        $warn = function (string $area, string $text) use (&$warnings) { $warnings[] = compact('area', 'text'); };
        if($returnReason=OverdueWorkerReturn::reason((int)$maid->id))$block('العودة للإيواء',$returnReason);
        $passes = fn (callable $scope) => $scope(Maid::whereKey($maid->id))->exists();

        // الإيواء والإيجار: نفس نطاقات RentalMaidQuery التي يبدأ بها candidates().
        if ($maid->rental_mode !== 'hourly') $block('النظام', 'العاملة ليست ضمن عاملات الساعات (نظام الإيجار: '.($maid->rental_mode ?: 'غير محدد').').');
        if (!$passes(fn ($q) => RentalMaidQuery::applyArrivalGate($q))) $block('الإيواء', 'لم يُسجل تاريخ وصول العاملة.');
        $inside = $passes(fn ($q) => RentalMaidQuery::applyInsideShelter($q));
        $noRental = $passes(fn ($q) => RentalMaidQuery::applyNoActiveRental($q));
        $notDeported = $passes(fn ($q) => RentalMaidQuery::applyNotDeported($q));
        $statusOk = !in_array((string) $maid->status, RentalMaidQuery::NOT_READY_MAID_STATUSES, true);
        if (!$inside) $block('الإيواء', 'لا يوجد عقد إيواء داخل المركز للعاملة.');
        if (!$noRental) $block('الإيجار', 'لدى العاملة عقد إيجار شهري قائم، فهي مسلَّمة لعميل آخر.');
        if (!$notDeported) $block('الإيواء', 'العاملة مستبعدة بسبب إجراء ترحيل مكتمل.');
        if (!$statusOk) $block('الإيواء', 'حالة العاملة الحالية لا تسمح بالإيجار: '.RentalMaidQuery::statusLabel($maid->status).'.');
        if ($inside && $noRental && $notDeported && $statusOk && !$passes(fn ($q) => RentalMaidQuery::applyReadyForRental($q))) {
            $block('الإيواء', 'رغبة العمل غير مؤكدة أو يوجد رفض للعمل في بيانات الإيواء.');
        }
        if (!$maid->jobTypes()->where('job_types.id', RentalMaidQuery::rentalJobTypeId())->exists()) $block('الإيجار', 'العاملة غير مرتبطة بنوع العمل «إيجار».');

        if ($maid->hourly_is_suspended) $block('الإيقاف', 'العاملة موقوفة عن العمل بنظام الساعات.');

        // الفريق: العضوية وحدها لا تكفي؛ يجب أن يكون فريقها هو الفريق الذي يُسند إليه الحي.
        $teamIds = $maid->hourlyTeamMemberships()->where('is_active', true)->pluck('hourly_team_id');
        $activeTeams = HourlyTeam::whereIn('id', $teamIds)->where('is_active', true)->pluck('name', 'id');
        if ($teamIds->isEmpty()) $block('الفريق', 'العاملة ليست عضوًا نشطًا في أي فريق.');
        elseif ($activeTeams->isEmpty()) $block('الفريق', 'كل فرق العاملة متوقفة.');

        $services = $maid->hourly_settings['service_category_ids'] ?? null;
        $categories = HourlyServiceCategory::query()->where('is_active', true)->get(['id', 'name_ar']);
        $allowed = $services === null ? $categories : $categories->whereIn('id', array_map('intval', (array) $services));
        if ($services === []) $block('الخدمات', 'لم تُحدد للعاملة أي خدمة في «نوع الخدمة»، فلا تُرشَّح لأي باقة. حدد خدمة واحدة على الأقل.');
        elseif ($allowed->isEmpty()) $block('الخدمات', 'الخدمات المحددة للعاملة متوقفة أو محذوفة.');

        $served = $activeTeams->isNotEmpty() && $allowed->isNotEmpty() ? $this->servedLinks($activeTeams->keys()->all(), $allowed->pluck('id')->all()) : collect();
        if ($activeTeams->isNotEmpty() && $allowed->isNotEmpty() && $served->isEmpty()) {
            $block('الفريق', 'فريق العاملة ('.$activeTeams->implode('، ').') لا يُسنَد إليه أي حي في مناطق خدماتها. الحي يُسند لفريق الفئة المحدد له، وإلا لأول فريق نشط مرتبط بالحي.');
        }

        // الجنسية وسمة المنتج: تستبعدها من باقات بعينها ضمن الخدمات التي تغطيها.
        $packages = HourlyPackage::query()->where('is_active', true)->whereIn('service_category_id', $served->pluck('service_category_id')->unique())
            ->with(['translations', 'nationalities:nationalities.id'])->get();
        $byNationality = $packages->filter(fn ($p) => $p->nationalities->isNotEmpty() && !$p->nationalities->contains('id', $maid->nationality_id));
        $rules = app(HourlyBookingRules::class)->settings();
        $hasAttribute = fn ($p) => $maid->productAttributes()->where('hourly_product_attributes.id', $p->attribute_id)->exists();
        $byAttribute = $packages->filter(fn ($p) => $p->attribute_id && $rules->value($p, 'assign_by_product_attribute') && !$hasAttribute($p));
        if ($packages->isNotEmpty()) {
            $excluded = $byNationality->merge($byAttribute)->unique('id');
            if ($excluded->count() === $packages->count()) $block('الجنسية', 'كل الباقات المتاحة في مناطقها تشترط جنسية أو سمة لا تطابق العاملة.');
            else {
                if ($byNationality->isNotEmpty()) $warn('الجنسية', 'جنسية العاملة غير مسموحة في: '.$byNationality->pluck('title')->implode('، ').'.');
                if ($byAttribute->isNotEmpty()) $warn('السمات', 'سمة العاملة لا تطابق: '.$byAttribute->pluck('title')->implode('، ').'.');
            }
        }

        // الجدول والإجازة: نفس قراءة availabilityReason() لأيام العمل والإجازات المعتمدة.
        $schedule = $maid->hourly_settings['work_schedule'] ?? [];
        $offDays = collect(self::DAYS)->filter(fn ($label, $day) => ($schedule[$day]['mode'] ?? 'off') === 'off');
        if ($offDays->count() === count(self::DAYS)) $block('الجدول', 'كل أيام الأسبوع إجازة أو لم يُحدد لها جدول عمل.');
        elseif ($offDays->isNotEmpty()) $warn('الجدول', 'لا تظهر في أيام: '.$offDays->implode('، ').'.');

        if (Schema::hasTable('hourly_worker_leaves')) {
            $leaves = DB::table('hourly_worker_leaves')->where('maid_id', $maid->id)->where('is_active', true)->where('ends_at', '>', now())->orderBy('starts_at')->get();
            foreach ($leaves as $leave) {
                $range = substr((string) $leave->starts_at, 0, 16).' ← '.substr((string) $leave->ends_at, 0, 16);
                if ($leave->starts_at <= now()->toDateTimeString()) $block('الإجازة', 'في إجازة معتمدة الآن حتى '.substr((string) $leave->ends_at, 0, 16).'.');
                else $warn('الإجازة', 'إجازة معتمدة قادمة: '.$range.'.');
            }
        }

        return ['blockers' => $blockers, 'warnings' => $warnings, 'served_districts' => $served->pluck('district_id')->unique()->count()];
    }

    /** روابط (فئة، حي) النشطة التي يُسند حيها لأحد فرق العاملة، بنفس قاعدة assertDistrictCovered(). */
    private function servedLinks(array $teamIds, array $categoryIds): \Illuminate\Support\Collection
    {
        if (!Schema::hasTable('hourly_category_district')) return collect();
        $links = DB::table('hourly_category_district as l')->join('hourly_service_districts as d', 'd.id', '=', 'l.district_id')
            ->where('l.is_active', true)->where('d.is_active', true)->whereIn('l.service_category_id', $categoryIds)
            ->get(['l.service_category_id', 'l.district_id', 'l.team_id']);
        $activeTeams = HourlyTeam::where('is_active', true)->pluck('id')->flip();
        $districtTeams = [];

        return $links->filter(function ($link) use ($teamIds, $activeTeams, &$districtTeams) {
            $team = $link->team_id && $activeTeams->has($link->team_id) ? (int) $link->team_id
                : ($districtTeams[$link->district_id] ??= \App\Models\Hourly\ServiceDistrict::find($link->district_id)?->teams()
                    ->where('hourly_teams.is_active', true)->wherePivot('is_active', true)->orderBy('hourly_teams.id')->value('hourly_teams.id'));

            return $team && in_array((int) $team, array_map('intval', $teamIds), true);
        })->values();
    }
}
