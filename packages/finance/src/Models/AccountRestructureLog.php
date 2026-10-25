<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class AccountRestructureLog extends Model
{
    public const ACTION_DELETE = 'delete';
    public const ACTION_MOVE = 'move';
    public const ACTION_MERGE = 'merge';
    public const ACTION_MERGE_UNDO = 'merge_undo';

    public const ACTION_LABELS = [
        self::ACTION_DELETE => 'حذف',
        self::ACTION_MOVE => 'نقل',
        self::ACTION_MERGE => 'دمج',
        self::ACTION_MERGE_UNDO => 'تراجع عن دمج',
    ];

    protected $table = 'fn_account_restructure_logs';

    protected $guarded = [];

    protected $casts = [
        'snapshot' => 'array',
        'details' => 'array',
        'reversed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'created_by_id');
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }
}
