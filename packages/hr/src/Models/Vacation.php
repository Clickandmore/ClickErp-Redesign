<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacation extends Model
{
    use HasFactory;
    protected $table = 'hr_vacations';
    protected $fillable = ['title','number_of_days','with_salary'];
}
