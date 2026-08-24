<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Allowance extends HrModel
{
    use HasFactory;
    protected $table = 'hr_allowances';
    protected $fillable = ['title','type','value'];

}
