<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhyChooseUsTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $fillable = ['title', 'description', 'cta_label'];
}
