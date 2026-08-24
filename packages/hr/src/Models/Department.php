<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends HrModel
{
    use HasFactory;
    protected $table = 'hr_department';
    protected $fillable = ['title'];


    public function employees()
    {
        return $this->hasMany(Employee::class,'deparment_id');
    }
}
