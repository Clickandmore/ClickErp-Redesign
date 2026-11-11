<?php

namespace App\Models\Hourly;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class HourlyCashCollection extends Model
{
    use AssignsBranch, BranchScoped;

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(HourlyRentalOrder::class, 'order_id');
    }

    public function payment()
    {
        return $this->belongsTo(HourlyOrderPayment::class, 'payment_id');
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromTable('hourly_rental_orders', $this->order_id ?? null)
            ?: $this->resolveBranchIdFromTable('hourly_order_payments', $this->payment_id ?? null)
            ?: $this->resolveBranchIdFromCurrentAdmin()
            ?: $this->resolveMainBranchId();
    }
}
