<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataImportRow extends Model
{
    protected $guarded = [];
    protected $casts = ['payload' => 'array', 'issues' => 'array', 'resolved_references' => 'array'];
}
