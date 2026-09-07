<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhyChooseUsFeatureTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $fillable = ['title', 'description'];
}
