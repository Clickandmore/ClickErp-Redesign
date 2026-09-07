<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicesTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    protected $guarded = [];
}
