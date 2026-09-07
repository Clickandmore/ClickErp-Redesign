<?php

namespace App\Models;

use App\Models\auth\Admin;
use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BranchScoped;

class ContractStatusLog extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use AssignsBranch, HasFactory, BranchScoped;

    protected $guarded = [];

    protected $casts = [
        'branch_id' => 'integer',
        'performed_by_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $contractStatusLog) {
            if (self::hasInvalidDate($contractStatusLog->date ?? null)) {
                $contractStatusLog->date = now()->toDateString();
            }

            if (!$contractStatusLog->performed_by_id && auth('admin')->check()) {
                $contractStatusLog->performed_by_id = auth('admin')->id();
            }
        });
    }

    public function performer()
    {
        return $this->belongsTo(Admin::class, 'performed_by_id');
    }

    private static function hasInvalidDate($value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return (int) $value->format('Y') <= 1;
        }

        $normalized = trim((string) $value);

        if ($normalized === '' || in_array($normalized, ['0000-00-00', '0000-00-00 00:00:00'], true)) {
            return true;
        }

        try {
            return (int) \Carbon\Carbon::parse($normalized)->format('Y') <= 1;
        } catch (\Throwable $exception) {
            return true;
        }
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
