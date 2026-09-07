<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequirementItemTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $fillable = ['text'];
}
