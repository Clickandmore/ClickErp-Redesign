<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionalApplication extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'facility_name', 'phone', 'number_of_workers_required',
        'job_title_id', 'visa_number', 'other_requirement','status'];
public function user()
{
    return $this->belongsTo(\App\Models\ingaz\User::class, 'user_id');
}

public function jobtitle()
{
    return $this->belongsTo(\App\Models\ingaz\JobTitle::class, 'job_title_id');
}
    }
