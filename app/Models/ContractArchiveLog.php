<?php

namespace App\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

/** سجل عمليات أرشفة واسترجاع العقود. */
class ContractArchiveLog extends Model
{
    public const ACTION_ARCHIVED = 'archived';
    public const ACTION_RESTORED = 'restored';

    protected $fillable = [
        'contract_type',
        'contract_id',
        'action',
        'reason',
        'admin_id',
        'branch_id',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function actionLabel(): string
    {
        return $this->action === self::ACTION_ARCHIVED ? 'أرشفة' : 'استرجاع';
    }
}
