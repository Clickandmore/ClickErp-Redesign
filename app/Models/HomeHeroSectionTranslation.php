<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeHeroSectionTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $fillable = ['title', 'subtitle', 'video_title'];
}
