<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelHistoryFiles extends Model
{
    use HasFactory;

    protected $fillable = [
        'travel_id',
        'file',
    ];

    public function travel()
    {
        return $this->belongsTo(TravelHistory::class);
    }
}
