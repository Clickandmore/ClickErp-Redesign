<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class airport extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    protected $fillable = [
        'name',
        'country_id',
        'city_id',
    ];
    protected $guarded = [];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
