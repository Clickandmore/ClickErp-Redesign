<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractReturnRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'user_id',
        'notes',
        'status',
        'admin_notes',
        'processed_by',
        'processed_at'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\ingaz\User::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'pending' => 'قيد الانتظار',
            'approved' => 'تمت الموافقة',
            'rejected' => 'مرفوض',
            default => $this->status
        };
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => '<span class="badge bg-warning">قيد الانتظار</span>',
            'approved' => '<span class="badge bg-success">تمت الموافقة</span>',
            'rejected' => '<span class="badge bg-danger">مرفوض</span>',
            default => '<span class="badge bg-secondary">' . $this->status . '</span>'
        };
    }
}
