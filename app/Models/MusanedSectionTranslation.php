<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MusanedSectionTranslation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $fillable = [
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
}
