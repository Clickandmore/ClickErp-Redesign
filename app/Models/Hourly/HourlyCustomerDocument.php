<?php

namespace App\Models\Hourly;

use Illuminate\Database\Eloquent\Model;

/** بطاقة هوية العميل (هوية/إقامة/جواز) بوجه وخلف وتاريخ انتهاء وحالة تحقق. */
class HourlyCustomerDocument extends Model
{
    public const TYPES = ['national_id', 'iqama', 'passport'];
    public const STATUSES = ['pending', 'verified', 'rejected'];

    protected $guarded = ['id'];
    protected $casts = ['expires_at' => 'date', 'reviewed_at' => 'datetime'];

    public function expired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
