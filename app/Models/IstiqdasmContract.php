<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class IstiqdasmContract extends Model
{
    use HasFactory;

    protected $table = 'istiqdam_contracts';

    protected $fillable = [
        'istiqdam_contract_id',
        'contract_number',
        'package_id',
        'package_details',
        'beneficiary_id',
        'beneficiary_birthdate',
        'beneficiary_name',
        'start_date',
        'end_date',
        'created_at_istiqdam',
        'status',
        'contact_details',
        'address_details',
        'payment_details',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'commitments',
        'work_permit_number',
        'assigned_maid_id',
        'maid_assigned_at',
        'special_specification',
        'notes',
        'last_api_response',
        'last_synced_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'package_details' => 'array',
        'beneficiary_birthdate' => 'date',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at_istiqdam' => 'datetime',
        'contact_details' => 'array',
        'address_details' => 'array',
        'payment_details' => 'array',
        'commitments' => 'array',
        'maid_assigned_at' => 'datetime',
        'last_api_response' => 'array',
        'last_synced_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    // Relationships
    public function assignedMaid(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ingaz\Maid::class, 'assigned_maid_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeWithMaid($query)
    {
        return $query->whereNotNull('assigned_maid_id');
    }

    public function scopeWithoutMaid($query)
    {
        return $query->whereNull('assigned_maid_id');
    }

    public function scopeRecentlyCreated($query, $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeNeedingSync($query, $hours = 24)
    {
        return $query->where(function ($q) use ($hours) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', Carbon::now()->subHours($hours));
        });
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statusMap = [
            'draft' => 'مسودة',
            'active' => 'نشط',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'expired' => 'منتهي الصلاحية',
            'pending_approval' => 'في انتظار الموافقة',
            'rejected' => 'مرفوض',
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    public function getStatusBadgeClassAttribute()
    {
        $badgeMap = [
            'draft' => 'bg-secondary',
            'active' => 'bg-success',
            'completed' => 'bg-primary',
            'cancelled' => 'bg-danger',
            'expired' => 'bg-warning',
            'pending_approval' => 'bg-info',
            'rejected' => 'bg-danger',
        ];

        return $badgeMap[$this->status] ?? 'bg-secondary';
    }

    public function getDurationInDaysAttribute()
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date);
        }
        return null;
    }

    public function getRemainingDaysAttribute()
    {
        if ($this->end_date && $this->status === 'active') {
            $remaining = Carbon::now()->diffInDays($this->end_date, false);
            return $remaining > 0 ? $remaining : 0;
        }
        return null;
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->start_date && $this->end_date && $this->status === 'active') {
            $total = $this->start_date->diffInDays($this->end_date);
            $elapsed = $this->start_date->diffInDays(Carbon::now());

            if ($total > 0) {
                return min(100, max(0, ($elapsed / $total) * 100));
            }
        }
        return 0;
    }

    public function getPaymentProgressPercentageAttribute()
    {
        if ($this->total_amount && $this->total_amount > 0) {
            return min(100, max(0, ($this->paid_amount / $this->total_amount) * 100));
        }
        return 0;
    }

    public function getIsMaidAssignedAttribute()
    {
        return !is_null($this->assigned_maid_id);
    }

    public function getIsExpiredAttribute()
    {
        return $this->end_date && Carbon::now()->greaterThan($this->end_date);
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && !$this->is_expired;
    }

    // Mutators
    public function setPackageDetailsAttribute($value)
    {
        $this->attributes['package_details'] = is_array($value) ? json_encode($value) : $value;
    }

    public function setContactDetailsAttribute($value)
    {
        $this->attributes['contact_details'] = is_array($value) ? json_encode($value) : $value;
    }

    public function setAddressDetailsAttribute($value)
    {
        $this->attributes['address_details'] = is_array($value) ? json_encode($value) : $value;
    }

    public function setPaymentDetailsAttribute($value)
    {
        $this->attributes['payment_details'] = is_array($value) ? json_encode($value) : $value;
    }

    public function setCommitmentsAttribute($value)
    {
        $this->attributes['commitments'] = is_array($value) ? json_encode($value) : $value;
    }

    public function setLastApiResponseAttribute($value)
    {
        $this->attributes['last_api_response'] = is_array($value) ? json_encode($value) : $value;
    }

    // Helper methods
    public function assignMaid($maidId, $workPermitNumber = null)
    {
        $this->update([
            'assigned_maid_id' => $maidId,
            'work_permit_number' => $workPermitNumber,
            'maid_assigned_at' => Carbon::now(),
        ]);

        // Update maid status
        if ($maid = $this->assignedMaid) {
            $maid->update(['status' => 'assigned']);
        }

        return $this;
    }

    public function unassignMaid()
    {
        // Update maid status back to available
        if ($maid = $this->assignedMaid) {
            $maid->update(['status' => 'available']);
        }

        $this->update([
            'assigned_maid_id' => null,
            'work_permit_number' => null,
            'maid_assigned_at' => null,
        ]);

        return $this;
    }

    public function updateStatus($status, $notes = null)
    {
        $this->update([
            'status' => $status,
            'notes' => $notes ?: $this->notes,
            'updated_by' => auth('admin')->id(),
        ]);

        return $this;
    }

    public function syncWithIstiqdam($apiResponse)
    {
        $this->update([
            'last_api_response' => $apiResponse,
            'last_synced_at' => Carbon::now(),
        ]);

        return $this;
    }

    public function calculateRemainingAmount()
    {
        if ($this->total_amount) {
            $remaining = $this->total_amount - $this->paid_amount;
            $this->update(['remaining_amount' => max(0, $remaining)]);
        }

        return $this;
    }

    // Static methods
    public static function createFromIstiqdam($istiqdasmData, $localData = [])
    {
        $contractData = array_merge([
            'istiqdam_contract_id' => $istiqdasmData['id'] ?? null,
            'package_id' => $istiqdasmData['packageId'] ?? null,
            'beneficiary_id' => $istiqdasmData['beneficiary']['id'] ?? null,
            'beneficiary_birthdate' => $istiqdasmData['beneficiary']['birthdate'] ?? null,
            'start_date' => $istiqdasmData['startDate'] ?? null,
            'status' => self::mapIstiqdasmStatus($istiqdasmData['status'] ?? 'draft'),
            'package_details' => $istiqdasmData['package'] ?? null,
            'contact_details' => $istiqdasmData['details']['beneficiary']['contact'] ?? null,
            'address_details' => $istiqdasmData['details']['beneficiary']['address'] ?? null,
            'payment_details' => $istiqdasmData['details']['payment'] ?? null,
            'commitments' => $istiqdasmData['details']['commitments'] ?? null,
            'special_specification' => $istiqdasmData['details']['specialSpecification'] ?? null,
            'created_at_istiqdam' => $istiqdasmData['createdAt'] ?? null,
            'last_api_response' => $istiqdasmData,
            'last_synced_at' => Carbon::now(),
            'created_by' => auth('admin')->id(),
        ], $localData);

        return self::create($contractData);
    }

    private static function mapIstiqdasmStatus($istiqdasmStatus)
    {
        $statusMap = [
            'DRAFT' => 'draft',
            'ACTIVE' => 'active',
            'COMPLETED' => 'completed',
            'CANCELLED' => 'cancelled',
            'EXPIRED' => 'expired',
            'PENDING_APPROVAL' => 'pending_approval',
            'REJECTED' => 'rejected',
        ];

        return $statusMap[strtoupper($istiqdasmStatus)] ?? 'draft';
    }
}
