<?php

namespace App\Models;

use App\Models\ingaz\Nationality;
use Illuminate\Database\Eloquent\Model;

class NationalityGuideline extends Model
{
    protected $fillable = ['nationality_id'];

    public function section()
    {
        return $this->belongsTo(GuidelineSection::class, 'guideline_section_id');
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function versions()
    {
        return $this->hasMany(NationalityGuidelineVersion::class)->orderBy('id');
    }
}
