<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FindUsLocation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    protected $fillable = [
        'branch_name',
        'branch_address',
        'sort_order',
        'map_embed_url',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
