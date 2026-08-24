<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class OfficialVacation extends HrModel
{
    use HasFactory;
    protected $table = 'hr_official_vacation';
    protected $fillable = ['name','from_date','to_date'];
}
