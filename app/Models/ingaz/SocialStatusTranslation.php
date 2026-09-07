<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialStatusTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    protected $table = 'social_status_translations';
    protected $guarded = [];
}
