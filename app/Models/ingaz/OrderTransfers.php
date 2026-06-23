<?php

namespace App\Models\ingaz;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OrderTransfers extends Model
{
    use AssignsBranch, HasFactory, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'branch_id' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromOrder($this->order_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
