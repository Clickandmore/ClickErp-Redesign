<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vacation extends HrModel
{
    use HasFactory;
    protected $table = 'hr_vacations';
    protected $fillable = ['title','number_of_days','with_salary'];
}
