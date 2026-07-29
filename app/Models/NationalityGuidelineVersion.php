<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NationalityGuidelineVersion extends Model
{
    protected $fillable = ['language', 'client_file_path', 'worker_file_path'];

    public function nationalityGuideline()
    {
        return $this->belongsTo(NationalityGuideline::class);
    }
}
