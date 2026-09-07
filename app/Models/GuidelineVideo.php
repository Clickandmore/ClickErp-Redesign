<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuidelineVideo extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $fillable = ['youtube_url'];

    public function section()
    {
        return $this->belongsTo(GuidelineSection::class, 'guideline_section_id');
    }
}
