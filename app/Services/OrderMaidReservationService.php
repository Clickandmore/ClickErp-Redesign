<?php

namespace App\Services;

use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderMaidReservationService
{
    public function hasOtherCommitment(int $maidId, int $orderId): bool
    {
        if (DB::table('orders')->where('maid_id', $maidId)->where('id', '!=', $orderId)
            ->whereNull('deleted_at')->where(function ($q) {
                $q->whereNull('status')->orWhereNotIn('status', Order::nonBlockingMaidAssignmentStatuses());
            })->exists()) { return true; }
        foreach (['contracts', 'rental_contracts', 'transfer_service_contracts'] as $table) {
            if (DB::table($table)->where('maid_id', $maidId)->where(function ($q) {
                $q->whereNull('status')->orWhereNotIn('status', ['cancelled', 'canceled', 'refused', 'back', 'completed', 'closed', 'refunded']);
            })->exists()) { return true; }
        }
        return false;
    }

    public function release(Order $order): void
    {
        if (!$order->maid_id) { return; }
        DB::transaction(function () use ($order) {
            $maid = Maid::withoutGlobalScopes()->lockForUpdate()->find($order->maid_id);
            if (!$maid || $maid->trashed() || !in_array($maid->status, ['reserved', 'available'], true)
                || $this->hasOtherCommitment($maid->id, $order->id)) { return; }
            $maid->update(['status' => 'available', 'available' => true, 'available_at' => now()]);
        });
    }

    public function reserveForRestore(Order $order): void
    {
        if (in_array($order->status, Order::nonBlockingMaidAssignmentStatuses(), true) || !$order->maid_id) { return; }
        $maid = Maid::withoutGlobalScopes()->lockForUpdate()->find($order->maid_id);
        if (!$maid || !$maid->available || $maid->status !== 'available' || $this->hasOtherCommitment($maid->id, $order->id)) {
            throw ValidationException::withMessages(['order' => 'لا يمكن استعادة الطلب لأن العاملة غير متاحة أو مرتبطة بطلب أو عقد آخر.']);
        }
        $maid->update(['status' => 'reserved', 'available' => false]);
    }
}
