<?php

namespace App\Models;

use App\Enums\ingaz\RequestsStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnownServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_name',
        'client_phone',
        'client_email',
        'client_address',
        'city',
        'worker_name',
        'nationality_id',
        'passport_number',
        'passport_expiration_date',
        'age',
        'social_status_id',
        'religion_id',
        'skills',
        'required_salary',
        'worker_phone',
        'worker_country_address',
        'relative_in_saudi_name',
        'relative_in_saudi_phone',
        'worker_passport_copy',
        'worker_personal_photo',
        'client_identity_copy',
        'status',
    ];

    protected $attributes = [
        'status' => RequestsStatusEnum::Pending->value,
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\ingaz\User::class, 'user_id');
    }

    public function nationality()
    {
        return $this->belongsTo(\App\Models\ingaz\Nationality::class, 'nationality_id');
    }

    public function religion()
    {
        return $this->belongsTo(\App\Models\ingaz\Religion::class, 'religion_id');
    }

    public function socialStatus()
    {
        return $this->belongsTo(\App\Models\ingaz\SocialStatus::class, 'social_status_id');
    }
}
