<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Model;

class EducationQualification extends Model
{
    protected $fillable = ['title_ar', 'title_en', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
