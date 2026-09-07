<?php

namespace App\Models;

use App\Models\ingaz\Nationality;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecruitmentContractStageStep extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'nationality_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function completions()
    {
        return $this->hasMany(RecruitmentContractStepCompletion::class, 'stage_step_id');
    }

    public function displayName(?string $locale = null): string
    {
        return $locale === 'en' && trim((string) $this->name_en) !== ''
            ? $this->name_en
            : $this->name_ar;
    }
}
