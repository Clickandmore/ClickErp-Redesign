<?php

namespace App\Services\Rental;

use App\Models\Hourly\HourlyDriver;
use App\Models\ingaz\Maid;
use App\Services\Hourly\MaidTeamService;
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;

/**
 * التحويل بين الإيجار العادي وإيجار الساعات لعاملة الإيجار.
 *
 * - إلى الساعات: عاملة داخل الإيواء، جاهزة للتأجير، وليس لها عقد إيجار نشط.
 * - إلى العادي: عاملة ساعات ليست في زيارة الآن وليس لها زيارات مفتوحة قادمة.
 */
final class RentalModeSwitcher
{
    /** زيارات تُبقي العاملة مرتبطة بالساعات فلا يجوز تحويلها للإيجار العادي. */
    public const OPEN_VISIT_STATUSES = ['unscheduled', 'scheduled', 'en_route', 'delivered', 'in_progress', 'worker_finished', 'picked_up'];

    public static function hourlyAvailable(): bool
    {
        return HourlyRentalSettings::isEnabled() && Schema::hasColumn('maids', 'rental_mode');
    }

    /** شارة نوع الإيجار الموحّدة بين صفحة عاملات الإيجار والإيواء. */
    public static function badge(Maid $maid, string $extraClass = ''): string
    {
        [$label, $hint, $icon, $style] = $maid->isHourly()
            ? [__('rental_mode.hourly'), __('rental_mode.hourly_hint'), 'fa-user-clock', 'background:#ede9fe;color:#5b21b6;border:1px solid #c4b5fd']
            : [__('rental_mode.regular'), __('rental_mode.regular_hint'), 'fa-file-signature', 'background:#e0f2fe;color:#075985;border:1px solid #7dd3fc'];

        return '<span class="badge rounded-pill rental-mode-badge ' . e($extraClass) . '" style="' . $style . ';font-weight:600"'
            . ' data-bs-toggle="tooltip" title="' . e($hint) . '">'
            . '<i class="fas ' . $icon . ' me-1"></i>' . e($label) . '</span>';
    }

    /** عاملات الإيجار العادي المؤهلات للتحويل إلى الساعات. */
    public function hourlyCandidates(): Builder
    {
        $query = Maid::query()
            ->whereHas('jobTypes', fn ($q) => $q->where('job_types.id', RentalMaidQuery::rentalJobTypeId()))
            ->notHourly();

        // applyReadyForRental يشترط عقد إيواء ساري، وapplyNoActiveRental يستبعد العقد الشهري النشط.
        return RentalMaidQuery::applyNoActiveRental(RentalMaidQuery::applyReadyForRental(
            RentalMaidQuery::applyArrivalGate($query)
        ));
    }

    /** سبب منع التحويل إلى الساعات، أو null إن كان مسموحًا. */
    public function hourlyBlocker(Maid $maid): ?string
    {
        if (!self::hourlyAvailable()) return __('rental_mode.errors.hourly_disabled');
        if ($maid->isHourly()) return __('rental_mode.errors.already_hourly');
        // جدول العقود مباشرة: عقد في فرع آخر يمنع التحويل أيضًا.
        if (DB::table('rental_contracts')->where('maid_id', $maid->id)
            ->when(Schema::hasColumn('rental_contracts', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->whereIn('status', RentalMaidQuery::ACTIVE_RENTAL_STATUSES)->exists()) {
            return __('rental_mode.errors.active_rental');
        }
        if (!RentalMaidQuery::applyInsideShelter(Maid::query()->whereKey($maid->id))->exists()) {
            return __('rental_mode.errors.not_inside_shelter');
        }
        if (!$this->hourlyCandidates()->whereKey($maid->id)->exists()) {
            return __('rental_mode.errors.not_ready');
        }

        return null;
    }

    /** سبب منع التحويل إلى الإيجار العادي، أو null إن كان مسموحًا. */
    public function regularBlocker(Maid $maid): ?string
    {
        if (!$maid->isHourly()) return __('rental_mode.errors.already_regular');
        $open = $this->openVisitsCount($maid->id);
        if ($open > 0) return __('rental_mode.errors.open_visits', ['count' => $open]);

        return null;
    }

    public function openVisitsCount(int $maidId): int
    {
        if (!Schema::hasTable('hourly_visits') || !Schema::hasTable('hourly_visit_workers')) return 0;

        $visitIds = DB::table('hourly_visit_workers')->where('maid_id', $maidId)->pluck('hourly_visit_id');
        if (Schema::hasTable('hourly_visit_reservations')) {
            $visitIds = $visitIds->merge(DB::table('hourly_visit_reservations')->where('maid_id', $maidId)->pluck('hourly_visit_id'));
        }
        if ($visitIds->isEmpty()) return 0;

        return DB::table('hourly_visits as v')
            ->join('hourly_rental_orders as o', 'o.id', '=', 'v.hourly_rental_order_id')
            ->whereIn('v.id', $visitIds->unique()->all())
            ->whereIn('v.status', self::OPEN_VISIT_STATUSES)
            ->whereNotIn('o.status', ['cancelled', 'expired'])
            ->count();
    }

    public function toHourly(Maid $maid): Maid
    {
        return DB::transaction(function () use ($maid) {
            $locked = Maid::whereKey($maid->id)->lockForUpdate()->firstOrFail();
            if ($reason = $this->hourlyBlocker($locked)) {
                throw ValidationException::withMessages(['maid_id' => $reason]);
            }

            // إعدادات افتراضية لا تُطمس ما حُفظ سابقًا لعاملة عادت إلى الساعات.
            $settings = ($locked->hourly_settings ?? []) + [
                'requires_driver' => true,
                'max_orders_per_hour' => 1,
                'account_enabled' => false,
                'login_access' => 'both',
                'work_schedule' => array_fill_keys(
                    array_keys(HourlyDriver::DAYS),
                    ['mode' => 'all_day', 'start' => null, 'end' => null]
                ),
            ];

            $locked->forceFill([
                'rental_mode' => 'hourly',
                'hourly_username' => $locked->hourly_username ?: 'HM-' . $locked->id,
                'hourly_settings' => $settings,
                'hourly_is_suspended' => false,
                'hourly_auth_version' => ((int) $locked->hourly_auth_version) + 1,
            ])->save();

            return $locked;
        });
    }

    public function toRegular(Maid $maid): Maid
    {
        return DB::transaction(function () use ($maid) {
            $locked = Maid::whereKey($maid->id)->lockForUpdate()->firstOrFail();
            if ($reason = $this->regularBlocker($locked)) {
                throw ValidationException::withMessages(['maid_id' => $reason]);
            }

            // الخروج من فرق الساعات كما يفعل نموذج العاملة عند اختيار "عادي".
            if (Schema::hasTable('hourly_team_members')) {
                app(MaidTeamService::class)->sync($locked, null, []);
            }

            // رفع الإصدار يُبطل جلسات تطبيق العاملة فورًا؛ الإعدادات تبقى لو أُعيدت للساعات.
            $locked->forceFill([
                'rental_mode' => 'regular',
                'hourly_auth_version' => ((int) $locked->hourly_auth_version) + 1,
            ])->save();

            return $locked;
        });
    }
}
