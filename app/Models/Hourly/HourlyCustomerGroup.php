<?php

namespace App\Models\Hourly;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** مجموعة عملاء الساعات («مجموعة العضوية»): للتسعير أو التسويق، مع نسبة خصم اختيارية. */
class HourlyCustomerGroup extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];
    protected $casts = ['is_active' => 'boolean', 'discount_percent' => 'decimal:2'];

    public function profiles()
    {
        return $this->hasMany(HourlyCustomerProfile::class, 'group_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs()->useLogName('hourly_customers');
    }
}
