<?php

namespace Nami\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;
    protected $table = 'hr_jobs';
    protected $fillable = ['title'];

    public function employees()
    {
        return $this->hasMany(Employee::class,'job_id');
    }
}
