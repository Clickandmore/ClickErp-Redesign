<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nami\Finance\Models\Account;

class EmployeeCustody extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_LOST = 'lost';

    protected $table = 'hr_employee_custodies';

    protected $fillable = [
        'employee_id',
        'account_id',
        'location_id',
        'name',
        'code',
        'value',
        'location',
        'received_at',
        'returned_at',
        'status',
        'file',
        'notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'received_at' => 'date',
        'returned_at' => 'date',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => __('hr::hr.custody_status_active'),
            self::STATUS_RETURNED => __('hr::hr.custody_status_returned'),
            self::STATUS_DAMAGED => __('hr::hr.custody_status_damaged'),
            self::STATUS_LOST => __('hr::hr.custody_status_lost'),
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function custodyLocation()
    {
        return $this->belongsTo(EmployeeCustodyLocation::class, 'location_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }
}
