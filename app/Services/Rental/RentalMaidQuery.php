<?php

namespace App\Services\Rental;

use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use Illuminate\Database\Eloquent\Builder;

final class RentalMaidQuery
{
    /** نوع سيرة الإيجار حسب البيانات الأولية، يُستخدم عند تعذّر إيجاده بالاسم. */
    public const FALLBACK_RENTAL_JOB_TYPE_ID = 2;

    public const ACTIVE_RENTAL_STATUSES = [
        'new',
        'waiting_contract_send',
        'contract_sent',
        'contract_signed',
        'maid_delivered',
        'active',
        'transferred',
        'suspended',
        'frozen',
    ];

    /** Legacy data contains both title-case and lower-case maid statuses. */
    public const UNAVAILABLE_MAID_STATUSES = [
        'contracted',
        'Contracted',
        'transferred',
        'Transferred',
        'reserved',
        'Reserved',
        'pending_arrival_data',
    ];

    public const NOT_READY_MAID_STATUSES = [
        ...self::UNAVAILABLE_MAID_STATUSES,
        'escaped',
        'Escaped',
        'backout',
        'Backout',
    ];

    /** اسم حالة العاملة بلغة الواجهة لرسائل الاستبعاد، مع إبقاء القيمة الخام إن لم تُترجم. */
    public static function statusLabel(?string $status): string
    {
        $key = 'hourly.maid_statuses.'.strtolower((string) $status);

        return trans()->has($key) ? __($key) : (string) $status;
    }

    /** أسباب خروج العاملة من الجاهزية، لوسم كل صف في شاشة الإيواء. */
    public const REASON_NOT_RENTAL = 'not_rental';
    public const REASON_ACTIVE_RENTAL = 'active_rental';
    public const REASON_MAID_STATUS = 'maid_status';
    public const REASON_DEPORTED = 'deported';
    public const REASON_REFUSED = 'refused';
    public const REASON_UNDECIDED = 'undecided';

    /**
     * سبب عدم جاهزية العاملة للتأجير، أو null إن كانت جاهزة.
     * يعمل على نموذج محمَّل بعلاقاته (jobTypes, rentalContracts, contracts.info).
     */
    public static function readinessReason(?Maid $maid, int $jobTypeId): ?string
    {
        if (!$maid || !$maid->jobTypes->contains('id', $jobTypeId)) {
            return self::REASON_NOT_RENTAL;
        }

        foreach ($maid->contracts as $contract) {
            $procedure = $contract->deportationProcedure;
            if ($procedure && $procedure->status === 'completed' && $procedure->completed_at
                && !$procedure->logs->contains(fn ($log) => $log->action_type === 'restore_available'
                    && $log->created_at >= $procedure->completed_at)) {
                return self::REASON_DEPORTED;
            }
        }

        $hasActiveRental = $maid->rentalContracts
            ->whereIn('status', self::ACTIVE_RENTAL_STATUSES)
            ->reject(fn ($contract) => $contract->requiresMaidOnResume())
            ->isNotEmpty();

        if ($hasActiveRental) {
            return self::REASON_ACTIVE_RENTAL;
        }

        if (in_array((string) $maid->status, self::NOT_READY_MAID_STATUSES, true)) {
            return self::REASON_MAID_STATUS;
        }

        $intents = $maid->contracts
            ->filter(fn ($contract) => (bool) $contract->info)
            ->map(fn ($contract) => (string) ($contract->info->wants_to_work ?? ''));

        if ($intents->contains('no')) {
            return self::REASON_REFUSED;
        }

        $hasUndecided = $intents->contains(fn ($value) => $value === '' || $value === 'unknown');

        if (!$intents->contains('yes') || $hasUndecided) {
            return self::REASON_UNDECIDED;
        }

        return null;
    }

    /**
     * نوع سيرة الإيجار: عضوية العاملة فيه هي ما يجعلها ظاهرة في "سير إيجار العاملات"،
     * أي أنها عاملة شركة تُدار من هناك لا من كشوف الاستقدام أو الإيواء.
     */
    public static function rentalJobTypeId(): ?int
    {
        $jobTypeId = JobType::query()
            ->whereHas('translations', function ($query) {
                $query->where(function ($inner) {
                    foreach (['ايجار', 'إيجار', 'تأجير', 'rent'] as $keyword) {
                        $inner->orWhere('title', 'LIKE', '%' . $keyword . '%');
                    }
                });
            })
            ->value('id');

        return $jobTypeId ? (int) $jobTypeId : self::FALLBACK_RENTAL_JOB_TYPE_ID;
    }

    public static function forJobType(int $jobTypeId): Builder
    {
        $query = Maid::query()->whereHas('jobTypes', function (Builder $jobTypeQuery) use ($jobTypeId) {
            $jobTypeQuery->where('job_types.id', $jobTypeId);
        });

        return self::applyArrivalGate($query);
    }

    public static function applyArrivalGate(Builder $query): Builder
    {
        return $query->where(function (Builder $arrivalQuery) {
            $arrivalQuery->whereNull('rental_source')
                ->orWhere('rental_source', '!=', 'outside')
                ->orWhereNotNull('arrival_date');
        });
    }

    public static function applyInsideShelter(Builder $query): Builder
    {
        return $query->whereHas('contracts', function (Builder $contractQuery) {
            $contractQuery->insideShelter();
        });
    }

    public static function applyShelterPool(Builder $query): Builder
    {
        self::applyInsideShelter($query);
        self::applyNotDeported($query);

        return $query
            ->whereNotIn('status', self::UNAVAILABLE_MAID_STATUSES)
            ->whereDoesntHave('rentalContracts', function (Builder $rentalQuery) {
                $rentalQuery->whereIn('status', self::ACTIVE_RENTAL_STATUSES)->holdingMaid();
            });
    }

    /** A completed departure blocks rental until explicitly restored. */
    public static function applyNotDeported(Builder $query): Builder
    {
        return $query->whereDoesntHave('contracts.deportationProcedure', self::blockingDeportation());
    }

    /** Whether a completed departure still stands against the worker. */
    public static function isDeported(int $maidId): bool
    {
        return Maid::query()
            ->whereKey($maidId)
            ->whereHas('contracts.deportationProcedure', self::blockingDeportation())
            ->exists();
    }

    /** Shared by the list filter and the single-worker check so the two cannot drift apart. */
    private static function blockingDeportation(): \Closure
    {
        return function (Builder $procedure) {
            $procedure->where('status', 'completed')->whereNotNull('completed_at')
                ->whereDoesntHave('logs', function (Builder $logs) {
                    $logs->where('action_type', 'restore_available')
                        ->whereColumn('deportation_procedure_logs.created_at', '>=', 'deportation_procedures.completed_at');
                });
        };
    }

    public static function applyInDeportation(Builder $query): Builder
    {
        self::applyShelterPool($query);

        return self::applyShelterIntent(
            $query,
            fn (Builder $infoQuery) => $infoQuery->where('wants_to_work', 'no')
        );
    }

    public static function applyWantsToWork(Builder $query): Builder
    {
        self::applyShelterPool($query);

        $wantsYes = fn (Builder $infoQuery) => $infoQuery->where('wants_to_work', 'yes');
        $wantsNo = fn (Builder $infoQuery) => $infoQuery->where('wants_to_work', 'no');
        $wantsUnknown = fn (Builder $infoQuery) => $infoQuery->where(function (Builder $condition) {
            $condition->whereNull('wants_to_work')->orWhere('wants_to_work', 'unknown');
        });

        self::applyShelterIntent($query, $wantsYes);
        self::applyShelterIntent($query, $wantsNo, true);

        return self::applyShelterIntent($query, $wantsUnknown, true);
    }

    public static function applyUndecided(Builder $query): Builder
    {
        self::applyShelterPool($query);

        $wantsYes = fn (Builder $infoQuery) => $infoQuery->where('wants_to_work', 'yes');
        $wantsNo = fn (Builder $infoQuery) => $infoQuery->where('wants_to_work', 'no');
        $wantsUnknown = fn (Builder $infoQuery) => $infoQuery->where(function (Builder $condition) {
            $condition->whereNull('wants_to_work')->orWhere('wants_to_work', 'unknown');
        });

        self::applyShelterIntent($query, $wantsNo, true);

        return $query->where(function (Builder $decisionQuery) use ($wantsYes, $wantsUnknown) {
            $decisionQuery->whereHas('contracts', function (Builder $contractQuery) use ($wantsUnknown) {
                $contractQuery->insideShelter()->whereHas('info', $wantsUnknown);
            })->orWhereDoesntHave('contracts', function (Builder $contractQuery) use ($wantsYes) {
                $contractQuery->insideShelter()->whereHas('info', $wantsYes);
            });
        });
    }

    /**
     * استبعاد العاملات المرتبطات بعقد إيجار شهري نشط.
     *
     * applyReadyForRental يفحص الرغبة وحالة العاملة فقط، بينما readinessReason
     * يفحص العقد النشط أيضًا. موديول الساعات يحتاج الفحصين معًا حتى لا تُسنَد
     * زيارة لعاملة مسلَّمة أصلًا لعميل شهري.
     */
    public static function applyNoActiveRental(Builder $query): Builder
    {
        return $query->whereDoesntHave('rentalContracts', function (Builder $rentalQuery) {
            $rentalQuery->whereIn('status', self::ACTIVE_RENTAL_STATUSES)->holdingMaid();
        });
    }

    public static function applyReadyForRental(Builder $query): Builder
    {
        self::applyWantsToWork($query);

        return $query->whereNotIn('status', self::NOT_READY_MAID_STATUSES);
    }

    private static function applyShelterIntent(
        Builder $query,
        callable $intent,
        bool $negate = false
    ): Builder {
        $constraint = function (Builder $contractQuery) use ($intent) {
            $contractQuery->insideShelter()->whereHas('info', $intent);
        };

        return $negate
            ? $query->whereDoesntHave('contracts', $constraint)
            : $query->whereHas('contracts', $constraint);
    }
}
