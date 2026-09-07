<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationTerm extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $fillable = [
        'group',
        'key',
        'key_hash',
        'locale',
        'value',
    ];
}
