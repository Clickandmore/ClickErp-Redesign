<?php

namespace Nami\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;
    protected $table = 'crm_areas';
    protected $fillable = ['title','parent_id'];

    public function city()
    {
        return $this->belongsTo(Area::class, 'parent_id');
    }

    public function areas()
    {
        return $this->hasMany(Area::class, 'parent_id');
    }

}
