<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class MusanedSection extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'is_active',
        'allowed_period_commitment_rate',
        'workforce_arrival_rate',
        'complaint_handling_rate',
        'arrival_duration_commitment_rate',
        'customer_recommendation_rating',
        'inquiry_response_rating',
        'recruitment_duration_rating',
        'complaint_response_rating',
        'profile_url',
    ];

    public $translatedAttributes = [
        'title',
        'subtitle',
        'overall_performance',
        'allowed_period_commitment_title',
        'workforce_arrival_title',
        'complaint_handling_title',
        'arrival_duration_commitment_title',
        'customer_recommendation_title',
        'inquiry_response_title',
        'recruitment_duration_title',
        'complaint_response_title',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_period_commitment_rate' => 'float',
        'workforce_arrival_rate' => 'float',
        'complaint_handling_rate' => 'float',
        'arrival_duration_commitment_rate' => 'float',
        'customer_recommendation_rating' => 'float',
        'inquiry_response_rating' => 'float',
        'recruitment_duration_rating' => 'float',
        'complaint_response_rating' => 'float',
    ];
}
