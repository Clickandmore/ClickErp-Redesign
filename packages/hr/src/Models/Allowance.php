<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allowance extends Model
{
    use HasFactory;
    protected $table = 'hr_allowances';
    protected $fillable = ['title','type','value'];

}
