<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Model;

class MaidExternalReference extends Model
{
    protected $fillable = ['maid_id', 'source', 'external_id'];

    public function maid()
    {
        return $this->belongsTo(Maid::class);
    }
}
