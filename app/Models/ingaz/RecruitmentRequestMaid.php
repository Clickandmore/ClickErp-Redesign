<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentRequestMaid extends Model
{
    protected $table = 'recruitment_requests';

    protected $fillable = [
        'client_id',
        'maid_id',
        'admin_id',
        'job_title',
        'nationality',
        'experience_years',
        'salary',
        'notes',
        'status',
        'request_type',
        'visa_number',
        'social_status',
        'age',
        'religion_id',
        'language',
        'worker_status',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class, 'religion_id');
    }

    public function maid(): BelongsTo
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }
}
