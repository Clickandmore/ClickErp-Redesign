<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PermissionSetting extends HrModel
{
    use HasFactory;
    protected $table = 'hr_perrmission_setting';
    protected $fillable = ['number_of_hours','per_month'];

    public function shifts()
    {
        return $this->belongsToMany(Shift::class, 'hr_permission_shifts', 'permission_id', 'shift_id');
    }

    public function PermissionShift()
    {
        return $this->hasMany(PermissionShift::class, 'permission_id');
    }
}
