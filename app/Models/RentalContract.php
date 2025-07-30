<?php

namespace App\Models;

use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\ingaz\Order;
use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RentalContract extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'maid_id',
        'marketer_id',
        'order_id',
        'status',
        'contract_date',
        'contract_number',
        'warranty_status',
        'warranty_start_date',
        'warranty_end_date',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
    ];

    protected $with = ['info'];

    // العلاقات
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function marketer()
    {
        return $this->belongsTo(Admin::class, 'marketer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function info()
    {
        return $this->hasOne(RentalContractInfo::class, 'rental_contract_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(RentalContractStatusLog::class, 'rental_contract_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'completed']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeWithWarranty($query)
    {
        return $query->where('warranty_status', 'active')
                    ->where('warranty_end_date', '>', now());
    }

    // Accessors & Mutators
    public function getDescAttribute()
    {
        return 'عقد تأجير رقم #' . $this->id . ' للعميل ' . $this->user->name;
    }

    // Helper Methods
    public function calculateContractValue()
    {
        if ($this->info && $this->info->monthly_cost && $this->info->contract_duration_months) {
            return $this->info->monthly_cost * $this->info->contract_duration_months;
        }
        return 0;
    }

    public function calculateRemainingDays()
    {
        if ($this->info && $this->info->ingaz_to) {
            return now()->diffInDays($this->info->ingaz_to, false);
        }
        return null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
