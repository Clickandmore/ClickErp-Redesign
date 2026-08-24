<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShiftRole extends HrModel
{
    use HasFactory;
    protected $table = 'hr_shift_roles';
    protected $fillable = ['minutes','sanctions_id','shift_id'];

    public function shifts()
    {
        return $this->belongsTo(Shift::class,'shift_id');
    }
}
