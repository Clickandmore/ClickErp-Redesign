<?php
namespace App\Models\Hourly;
use Illuminate\Database\Eloquent\Model;
class HourlyOrderPayment extends Model
{
    protected $guarded = [];
    protected $casts = ['provider_payload'=>'array','paid_at'=>'datetime'];
    public function order() { return $this->belongsTo(HourlyRentalOrder::class, 'hourly_rental_order_id'); }
}
