<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialStatusTranslation extends Model
{
    use HasFactory;

    protected $table = 'social_status_translations';
    protected $guarded = [];
}
