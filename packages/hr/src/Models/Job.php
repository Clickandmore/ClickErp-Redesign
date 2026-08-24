<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Job extends HrModel
{
    use HasFactory;
    protected $table = 'hr_jobs';
    protected $fillable = ['title'];

    public function employees()
    {
        return $this->hasMany(Employee::class,'job_id');
    }
}
