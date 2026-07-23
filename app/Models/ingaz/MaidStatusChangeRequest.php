<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use App\Models\office\Office;
use Illuminate\Database\Eloquent\Model;

class MaidStatusChangeRequest extends Model
{
    public const TYPE_SUSPEND = 'suspend';
    public const TYPE_BACKOUT = 'backout';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'maid_id',
        'office_id',
        'requested_by_admin_id',
        'type',
        'reason',
        'status',
        'reviewed_by_admin_id',
        'review_note',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function maid()
    {
        return $this->belongsTo(Maid::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(Admin::class, 'requested_by_admin_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === self::TYPE_BACKOUT ? 'Back Out' : 'تعليق السيرة';
    }
}
