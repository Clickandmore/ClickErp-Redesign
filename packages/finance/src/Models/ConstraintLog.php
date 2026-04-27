<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;

class ConstraintLog extends Finance
{
    protected $table = 'fn_constraint_logs';

    protected $fillable = [
        'constraint_id',
        'admin_id',
        'action',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function constraint()
    {
        return $this->belongsTo(Constraint::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => __('finance::finance.constraint_log_created'),
            'updated' => __('finance::finance.constraint_log_updated'),
            'deleted' => __('finance::finance.constraint_log_deleted'),
            'approved' => __('finance::finance.constraint_log_approved'),
            'file_added' => __('finance::finance.constraint_log_file_added'),
            'file_deleted' => __('finance::finance.constraint_log_file_deleted'),
            default => $this->action,
        };
    }
}
