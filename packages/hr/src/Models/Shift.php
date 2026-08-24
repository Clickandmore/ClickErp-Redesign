<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends HrModel
{
    use HasFactory;
    protected $table = 'hr_shifts';
    protected $fillable = ['name','from_hour','to_hour','month_days','absence_sanctions_id','status'];

    public function sanctions()
    {
        return $this->belongsTo(Sanction::class,'absence_sanctions_id');
    }

    public function shiftRoles()
    {
        return $this->hasMany(ShiftRole::class);
    }

    public function shiftDays()
    {
        return $this->hasMany(ShiftDay::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(PermissionSetting::class, 'hr_permission_shifts','shift_id',  'permission_id');
    }

    public function PermissionShift()
    {
        return $this->hasMany(PermissionShift::class, 'permission_id');
    }
}
