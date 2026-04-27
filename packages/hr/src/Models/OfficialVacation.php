<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialVacation extends Model
{
    use HasFactory;
    protected $table = 'hr_official_vacation';
    protected $fillable = ['name','from_date','to_date'];
}
