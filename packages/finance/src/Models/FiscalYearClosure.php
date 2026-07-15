<?php

namespace Nami\Finance\Models;

use App\Models\auth\Admin;

class FiscalYearClosure extends Finance
{
    protected $table = 'fn_fiscal_year_closures';

    protected $guarded = [];

    protected $casts = [
        'closing_date' => 'date',
        'reversed_at' => 'datetime',
    ];

    public function constraint()
    {
        return $this->belongsTo(Constraint::class, 'constraint_id')->withTrashed();
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function reversedBy()
    {
        return $this->belongsTo(Admin::class, 'reversed_by_id');
    }

    public function getIsReversedAttribute(): bool
    {
        return $this->reversed_at !== null;
    }

    public function getDisplayConstraintCodeAttribute()
    {
        return $this->legacy_constraint_code ?? $this->constraint?->code ?? 0;
    }
}
