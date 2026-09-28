<?php
namespace App\Models\Hourly;
use Illuminate\Database\Eloquent\Model;
class HourlyOrderStatusLog extends Model
{
    protected $guarded = [];
    protected $casts = [];
    public function order() { return $this->belongsTo(HourlyRentalOrder::class, 'hourly_rental_order_id'); }
}
