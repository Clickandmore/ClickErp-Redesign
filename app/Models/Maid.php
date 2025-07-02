<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maid extends Model
{
    protected $fillable = [
        'name',
        'passport_num',
        'id_number',
        'id_expiry_date',
        'skills',
        'languages',
        'experience_years',
        'nationality_id',
        'cv_file',
        'price',
        'salary',
        'status',
        'available',
        'is_pinned',
        'available_at',
        'is_show',
        'cv_preview',
        'office_id',
        'image',
        'height',
        'weight',
        'birth_date',
        'age',
        'religion_id',
    ];

    protected $casts = [
        'skills' => 'array',
        'languages' => 'array',
    ];

    public function nationality()
    {
        return $this->belongsTo(\App\Models\ingaz\Nationality::class, 'nationality_id');
    }
}
