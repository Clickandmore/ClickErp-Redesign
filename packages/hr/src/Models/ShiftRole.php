<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftRole extends Model
{
    use HasFactory;
    protected $table = 'hr_shift_roles';
    protected $fillable = ['minutes','sanctions_id','shift_id'];

    public function shifts()
    {
        return $this->belongsTo(Shift::class,'shift_id');
    }
}
