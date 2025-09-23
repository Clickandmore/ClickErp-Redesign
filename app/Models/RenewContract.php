<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewContract extends Model
{
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
