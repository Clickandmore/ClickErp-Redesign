<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShiftDay extends HrModel
{
    use HasFactory;
    protected $table = 'hr_shift_days';
    protected $fillable = ['days','shift_id'];

    public function shifts()
    {
        return $this->belongsTo(Shift::class,'shift_id');
    }
}
