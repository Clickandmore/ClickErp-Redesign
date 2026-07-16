<?php

namespace App\Models;

use App\Models\ingaz\Order;
use App\Models\ingaz\Package;
use App\Models\ingaz\User;
use Illuminate\Database\Eloquent\Model;

class RentalOrderPayment extends Model
{
    public const PROVIDER_MOYASAR = 'moyasar';
    public const STATUS_CREATING = 'creating';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REFUNDED = 'refunded';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
