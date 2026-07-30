<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuidelineSection extends Model
{
    protected $fillable = ['client_file_path', 'worker_file_path'];

    public function videos()
    {
        return $this->hasMany(GuidelineVideo::class)->orderBy('id');
    }

    public function nationalityGuidelines()
    {
        return $this->hasMany(NationalityGuideline::class)->orderBy('id');
    }
}
