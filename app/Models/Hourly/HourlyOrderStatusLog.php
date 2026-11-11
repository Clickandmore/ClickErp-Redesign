<?php
namespace App\Models\Hourly;
use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;
class HourlyOrderStatusLog extends Model
{
    use AssignsBranch, BranchScoped;

    protected $guarded = [];
    protected $casts = [];
    public function order() { return $this->belongsTo(HourlyRentalOrder::class, 'hourly_rental_order_id'); }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromTable('hourly_rental_orders', $this->hourly_rental_order_id ?? null)
            ?: $this->resolveBranchIdFromCurrentAdmin()
            ?: $this->resolveMainBranchId();
    }
}
