<?php

namespace App\Services\Rental;

use App\Models\ingaz\Maid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;

final class RentalModeGuard
{
    public const MONTHLY_MESSAGE = 'العاملة تعمل بنظام الساعات ولا يمكن إسنادها لعقد إيجار شهري.';

    public function applyMonthlySelection(Builder $query, ?int $currentMaidId = null, bool $includeCurrentMaid = false): Builder
    {
        // Click can run without installing the optional hourly module.
        if (!Schema::hasColumn('maids', 'rental_mode')) return $query;

        return $query->where(function ($query) use ($currentMaidId, $includeCurrentMaid) {
            $query->whereNull('maids.rental_mode')->orWhere('maids.rental_mode', '!=', 'hourly');
            // Preserve the current worker only when editing an existing conflicting contract.
            if ($includeCurrentMaid && $currentMaidId) $query->orWhere('maids.id', $currentMaidId);
        });
    }

    public function assertMonthly(Maid $maid): void
    {
        if ($maid->rental_mode === 'hourly') {
            throw ValidationException::withMessages(['maid_id' => self::MONTHLY_MESSAGE]);
        }
    }

    public function assertHourlyConversion(Maid $maid): void
    {
        // A contract in another branch must still prevent renting the same worker twice.
        // No contract identifiers or details from that branch are disclosed.
        if ($maid->rental_mode !== 'hourly' && DB::table('rental_contracts')
            ->where('maid_id', $maid->id)->when(Schema::hasColumn('rental_contracts', 'deleted_at'), fn($q)=>$q->whereNull('deleted_at'))
            ->whereIn('status', RentalMaidQuery::ACTIVE_RENTAL_STATUSES)->exists()) {
            throw ValidationException::withMessages([
                'rental_mode' => 'لا يمكن تحويل العاملة إلى الساعات ولديها عقد إيجار شهري قائم. أغلق العقد أو استبدل العاملة فيه أولًا.',
            ]);
        }
    }
}