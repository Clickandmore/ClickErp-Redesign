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
        'experience_years',
        'nationality_id',
        'cv_file',
        'price',
        'salary',
        'status',
        'job_id',
        'job_type_id',
        'job_2',
        'job_types_2',
        'created_at',
        'updated_at',
    ];
}
