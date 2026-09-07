<?php

namespace App\Models\office;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JopTypeOffice extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    protected $guarded = [];
}
