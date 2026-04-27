<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    protected $table = 'hr_department';
    protected $fillable = ['title'];


    public function employees()
    {
        return $this->hasMany(Employee::class,'deparment_id');
    }
}
