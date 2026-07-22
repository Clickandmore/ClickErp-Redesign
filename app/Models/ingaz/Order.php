<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use App\Enums\ingaz\OrderEnum;
use App\Models\Concerns\AssignsOrderBranch;
use App\Support\AutomationSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use AssignsOrderBranch, HasFactory, LogsActivity, SoftDeletes;

    protected $guarded = [];

    protected $with = ['details'];

    protected $casts = [
        'expiration_time' => 'datetime',
        'status_changed_at' => 'datetime',
        'deadline_at' => 'datetime',
        'branch_id' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function (Order $order) {
            if (self::shouldTrackDeadline($order->status)) {
                $order->deadline_at = now()->addHours($order->getDeadlineHours());
            }
        });

        static::updating(function (Order $order) {
            if ($order->isDirty('status')) {
                if (self::shouldTrackDeadline($order->status)) {
                    $order->deadline_at = now()->addHours($order->getDeadlineHours());
                } else {
                    $order->deadline_at = null;
                }
            }
        });
    }

    protected static function shouldTrackDeadline(?string $status): bool
    {
        return in_array($status, [OrderEnum::New->value, OrderEnum::UnderProcess->value], true);
    }

    public function transfers()
    {
        return $this->hasMany(OrderTransfers::class);
    }

    public function notes()
    {
        return $this->hasMany(OrderNote::class)->latest();
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

    public function customer()
    {
        return $this->belongsTo(Admin::class, 'customer_id');
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

    public function rentalContracts()
    {
        return $this->hasMany(\App\Models\RentalContract::class, 'order_id');
    }

    public function rentalOrderPayments()
    {
        return $this->hasMany(\App\Models\RentalOrderPayment::class, 'order_id');
    }

    public function latestMoyasarPayment()
    {
        return $this->hasOne(\App\Models\RentalOrderPayment::class, 'order_id')
            ->where('provider', \App\Models\RentalOrderPayment::PROVIDER_MOYASAR)
            ->latestOfMany();
    }

    public function resolveResponsibleAdmin(): ?Admin
    {
        if ($this->details?->admin) {
            return $this->details->admin;
        }

        if ($this->relationLoaded('customer')) {
            return $this->customer;
        }

        return $this->customer()->first();
    }

    public function resolveResponsibleAdminId(): ?int
    {
        return $this->details?->admin_id ?: $this->customer_id;
    }

    public function syncResponsibleAdmin(?int $adminId): void
    {
        $this->forceFill([
            'customer_id' => $adminId,
        ])->save();

        if ($adminId !== null) {
            $this->details()->updateOrCreate(
                ['order_id' => $this->id],
                ['admin_id' => $adminId]
            );
        } elseif ($this->details()->exists()) {
            $this->details()->update(['admin_id' => null]);
        }

        $this->unsetRelation('details');
        $this->unsetRelation('customer');

        $this->syncBranchFromResponsibleAdmin($adminId, true);
    }

    public function scopeWhereResponsibleAdmin(Builder $query, int $adminId): Builder
    {
        return $query->where(function (Builder $responsibleQuery) use ($adminId) {
            $responsibleQuery->where('customer_id', $adminId)
                ->orWhereHas('details', function (Builder $detailsQuery) use ($adminId) {
                    $detailsQuery->where('admin_id', $adminId);
                });
        });
    }

    public function scopeRecruitmentRequests(Builder $query): Builder
    {
        return $query->where('job_type_id', 1)
            ->whereDoesntHave('rentalContracts')
            ->where(function (Builder $recruitmentQuery) {
                $recruitmentQuery->whereDoesntHave('contracts')
                    ->orWhereHas('contracts', function (Builder $contractQuery) {
                        $contractQuery->whereIn('type', ['recruitment', 'maid']);
                    });
            });
    }

    public function scopeRentalRequests(Builder $query): Builder
    {
        return $query->where(function (Builder $rentalQuery) {
            $rentalQuery->where('job_type_id', 2)
                ->orWhereHas('rentalContracts');
        });
    }

    public function getAdminAttribute(): ?Admin
    {
        return $this->resolveResponsibleAdmin();
    }

    public static function generateUniqueCode()
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
        $hours = $hours ?: AutomationSettings::orderExpirationHours();
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
        $baseTime = $this->status_changed_at ?: $this->created_at ?: now();
        $this->deadline_at = $baseTime->copy()->addHours($deadlineHours);
    }

    public function getDeadlineHours()
    {
        switch ($this->status) {
            case OrderEnum::New->value:
                return AutomationSettings::orderNewDeadlineHours();
            case OrderEnum::UnderProcess->value:
                return AutomationSettings::orderUnderProcessDeadlineHours();
            default:
                return 0;
        }
    }

    public function isOverdue()
    {
        $deadline = $this->getEffectiveDeadline();

        if (!$deadline) {
            return false;
        }

        return now()->isAfter($deadline);
    }

    public function getRemainingTime()
    {
        $deadline = $this->getEffectiveDeadline();

        if (!$deadline) {
            return null;
        }

        $remaining = now()->diff($deadline);

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

    /**
     * Return the stored deadline, or derive it for legacy active orders whose
     * timer columns were never populated.
     */
    public function getEffectiveDeadline(): ?Carbon
    {
        if ($this->deadline_at) {
            return $this->deadline_at;
        }

        if (!self::shouldTrackDeadline($this->status)) {
            return null;
        }

        $baseTime = $this->status_changed_at ?: $this->created_at;

        return $baseTime?->copy()->addHours($this->getDeadlineHours());
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

        if ($remaining['hours'] < AutomationSettings::orderUrgentThresholdHours()) {
            return 'urgent';
        }

        return 'normal';
    }

}
