<?php

namespace App\Services;

use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;

class ExternalBookingAvailabilityService
{
    /** Call inside the transaction that creates the booking. */
    public function isReserved(int $maidId): bool
    {
        // All booking endpoints lock the same worker before checking or creating orders.
        Maid::withoutGlobalScopes()->setEagerLoads([])->whereNull('deleted_at')
            ->whereKey($maidId)->lockForUpdate()->firstOrFail();

        // A locking read sees the latest committed reservation, including other branches.
        return Order::withoutGlobalScopes()->whereNull('deleted_at')
            ->where('maid_id', $maidId)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', Order::nonBlockingMaidAssignmentStatuses());
            })
            ->lockForUpdate()->toBase()->first(['id']) !== null;
    }
}
