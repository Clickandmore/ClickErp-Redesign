<?php

namespace Nami\Hr\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;

class PermissionShift extends HrModel
{
    use HasFactory;
    protected $table = 'hr_permission_shifts';
    protected $fillable = ['shift_id','permission_id'];

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
