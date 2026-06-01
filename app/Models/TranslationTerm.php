<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranslationTerm extends Model
{
    protected $fillable = [
        'group',
        'key',
        'key_hash',
        'locale',
        'value',
    ];
}
