<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $guarded = [];

    protected $with = ['details'];

    protected $casts = [
        'expiration_time' => 'datetime',
        'status_changed_at' => 'datetime',
        'deadline_at' => 'datetime'
    ];

    public function transfers()
    {
        return $this->hasMany(OrderTransfers::class);
    }

    // relations
    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function jobType()
    {
        return $this->belongsTo(JobType::class, 'job_type_id');
    }

    public function details()
    {
        return $this->hasOne(OrderDetail::class, 'order_id')->with(['admin']);
    }

    public function package()
    {
        return $this->hasOneThrough(
            Package::class,
            OrderDetail::class,
            'order_id', // Foreign key on the OrderDetail table
            'id',       // Local key on the Order table
            'id',       // Local key on the Package table
            'package_id' // Foreign key on the Package table
        );
    }

    public function address()
    {
        return $this->hasOneThrough(
            UserAddress::class,
            OrderDetail::class,
            'order_id', // Foreign key on the OrderDetail table
            'id',       // Local key on the Order table
            'id',       // Local key on the Package table
            'address_id' // Foreign key on the Package table
        );
    }

    public function contracts()
    {
        return $this->hasMany(\App\Models\Contract::class, 'order_id');
    }

    protected static function generateUniqueCode()
    {
        $code = mt_rand(10000, 99999);

        // Check if the code is unique, generate a new one if needed
        while (static::where('order_code', $code)->exists()) {
            $code = mt_rand(10000000, 99999999);
        }

        return $code;
    }

    public function getPackageTitleAttribute()
    {
        return $this->package->title ?? null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    public function setExpirationTime($hours = 24)
    {
        $this->expiration_time = now()->addHours($hours);
        $this->save();
    }

    public function isExpired()
    {
        return $this->expiration_time && $this->expiration_time->isPast();
    }

    // Timer methods
    public function updateStatusChangedAt()
    {
        $this->status_changed_at = now();
        $this->updateDeadline();
        $this->save();
    }

    public function updateDeadline()
    {
        $deadlineHours = $this->getDeadlineHours();
        $this->deadline_at = now()->addHours($deadlineHours);
    }

    public function getDeadlineHours()
    {
        switch ($this->status) {
            case \App\Enums\ingaz\OrderEnum::New->value:
                return 24; // 24 ساعة للطلبات الجديدة
            case \App\Enums\ingaz\OrderEnum::UnderProcess->value:
                return 48; // 48 ساعة للطلبات تحت الإجراء
            default:
                return 0; // لا يوجد موعد نهائي للحالات الأخرى
        }
    }

    public function isOverdue()
    {
        if (!$this->deadline_at) {
            return false;
        }
        return now()->isAfter($this->deadline_at);
    }

    public function getRemainingTime()
    {
        if (!$this->deadline_at) {
            return null;
        }

        $remaining = now()->diff($this->deadline_at);

        if ($remaining->invert) {
            // الوقت انتهى
            return [
                'hours' => 0,
                'minutes' => 0,
                'is_overdue' => true,
                'overdue_hours' => abs($remaining->h + ($remaining->days * 24)),
                'overdue_minutes' => $remaining->i
            ];
        }

        return [
            'hours' => $remaining->h + ($remaining->days * 24),
            'minutes' => $remaining->i,
            'is_overdue' => false
        ];
    }

    public function getTimerStatus()
    {
        $remaining = $this->getRemainingTime();

        if (!$remaining) {
            return 'no_deadline';
        }

        if ($remaining['is_overdue']) {
            return 'overdue';
        }

        if ($remaining['hours'] < 2) {
            return 'urgent';
        }

        return 'normal';
    }

}
