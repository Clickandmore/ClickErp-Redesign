<?php

namespace Nami\Hr\Models;

use Nami\Hr\Enums\AdvanceValueStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdvanceValue extends HrModel
{
    use HasFactory;
    protected $table = 'hr_advance_values';
    protected $fillable = ['advance_id','amount','date'];
    protected $casts = [
        'status' => AdvanceValueStatus::class
    ];

    public function advance()
    {
        return $this->belongsTo(Advance::class,'advance_id');
    }
}
