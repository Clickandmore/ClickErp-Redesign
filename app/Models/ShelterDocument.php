<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShelterDocument extends Model
{
    protected $guarded = [];
    protected $casts = ['data' => 'array', 'enabled' => 'boolean', 'release_completed_at' => 'datetime'];

    public function template()
    {
        return $this->belongsTo(ShelterDocumentTemplate::class, 'template_id');
    }
}
