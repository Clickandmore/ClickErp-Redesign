<?php

namespace App\Models\Hourly;

use App\Models\ingaz\Maid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * حركة خروج وعودة مؤقتة للعاملة. الحركة المفتوحة (in_at = null) تعني أنها خارج الإيواء الآن.
 * لا علاقة لها بـ contract_infos.is_outside_shelter الذي يبقى للخروج التعاقدي وحده.
 */
class WorkerDailyMovement extends Model
{
    public const SOURCE_HOURLY_VISIT = 'hourly_visit';
    public const SOURCE_MANUAL = 'manual';

    public const STATUS_OUT = 'out';
    public const STATUS_RETURNED = 'returned';
    /** محجوزة لمن تأخّرت عن موعد العودة؛ فحص الإطفاء الآمن يعدّها ضمن الخارجات. */
    public const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'maid_id', 'shelter_place_id', 'source', 'reference_type', 'reference_id',
        'out_at', 'in_at', 'status', 'out_by_type', 'out_by_id', 'in_by_type', 'in_by_id', 'note',
    ];

    protected $casts = ['out_at' => 'datetime', 'in_at' => 'datetime'];

    public function maid(): BelongsTo
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('in_at');
    }

    public function scopeStillOut($query)
    {
        return $query->whereIn('status', [self::STATUS_OUT, self::STATUS_OVERDUE]);
    }
}
