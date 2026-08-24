<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sanction extends HrModel
{
    use HasFactory;
    protected $table = 'hr_sanctions';
    protected $fillable = ['title','type','value','is_delays'];
}
