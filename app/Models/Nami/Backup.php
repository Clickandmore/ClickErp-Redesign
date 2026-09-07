<?php

namespace App\Models\Nami;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $table = 'backups';
    protected $fillable = ['backup_date'];
}
