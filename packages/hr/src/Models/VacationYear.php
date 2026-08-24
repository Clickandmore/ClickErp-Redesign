<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class VacationYear extends HrModel
{
    use HasFactory;
    protected $table = 'hr_vacation_years';
    protected $guarded = [];

    public function vacationBalances()
    {
        return $this->hasMany(EmployeeVacationBalance::class, 'vacation_year_id');
    }
}
