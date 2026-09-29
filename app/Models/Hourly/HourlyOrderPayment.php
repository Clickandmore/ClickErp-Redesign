<?php
namespace App\Models\Hourly;
use Illuminate\Database\Eloquent\Model;
class HourlyOrderPayment extends Model
{
    protected $guarded = [];
    protected $casts = ['provider_payload'=>'array','paid_at'=>'datetime','refunded_amount'=>'decimal:2'];
    public function getSettlementStatusAttribute(): string
    {
        if ((float) $this->amount < 0 && $this->status === 'paid') return 'refunded';
        if ((float) $this->refunded_amount > 0) return (float) $this->refunded_amount >= (float) $this->amount ? 'refunded' : 'partially_refunded';
        return $this->status;
    }
    public function order() { return $this->belongsTo(HourlyRentalOrder::class, 'hourly_rental_order_id'); }
}
