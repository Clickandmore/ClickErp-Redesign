<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeCustodyLocation extends HrModel
{
    use HasFactory;

    protected $table = 'hr_custody_locations';

    protected $fillable = [
        'name',
        'code',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function custodies()
    {
        return $this->hasMany(EmployeeCustody::class, 'location_id');
    }
}
