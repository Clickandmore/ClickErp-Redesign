<?php

namespace App\Models;

use App\Models\ingaz\JobTitle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaidExpertise extends Model
{
    protected $fillable = ['maid_id', 'country_id', 'work_city', 'number_of_years', 'job_title_id'];

    public function country(){
        return $this->belongsTo(Country::class, 'country_id');
    }
    public function jobTitle(){
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }
}
