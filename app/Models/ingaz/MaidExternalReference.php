<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Model;

class MaidExternalReference extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $fillable = ['maid_id', 'source', 'external_id'];

    public function maid()
    {
        return $this->belongsTo(Maid::class);
    }
}
