<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractStatusLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $contractStatusLog) {
            if (self::hasInvalidDate($contractStatusLog->date ?? null)) {
                $contractStatusLog->date = now()->toDateString();
            }
        });
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
}
