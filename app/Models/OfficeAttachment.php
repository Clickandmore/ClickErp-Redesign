<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeAttachment extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    protected $guarded = [];
}
