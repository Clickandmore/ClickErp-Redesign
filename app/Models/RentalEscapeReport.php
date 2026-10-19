<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalEscapeReport extends Model
{
    use \App\Models\Concerns\BranchScoped;
    use \App\Models\Concerns\AuditsChanges;

    protected $guarded = [];
    protected $casts = ['escape_date' => 'date', 'returned_at' => 'datetime', 'ready_at' => 'datetime', 'attachment' => 'array',
        'closed_without_return_date' => 'date', 'closed_without_return_at' => 'datetime', 'no_return_attachment' => 'array'];

    public function closedWithoutReturnBy() { return $this->belongsTo(\App\Models\auth\Admin::class, 'closed_without_return_by'); }

    public function contract() { return $this->belongsTo(RentalContract::class, 'rental_contract_id'); }
    public function maid() { return $this->belongsTo(\App\Models\ingaz\Maid::class); }
    public function reportedBy() { return $this->belongsTo(\App\Models\auth\Admin::class, 'reported_by'); }
}
