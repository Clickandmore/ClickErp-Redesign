<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewContract extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;

    protected $guarded = [];
    public function user()
{
    return $this->belongsTo(\App\Models\ingaz\User::class, 'user_id');
}

public function nationality()
{
    return $this->belongsTo(\App\Models\ingaz\Nationality::class, 'nationality_id');
}
}
