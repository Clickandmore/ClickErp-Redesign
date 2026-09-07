<?php

namespace App\Models;

use App\Models\ingaz\JobTitle;
use App\Support\ExperiencePeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaidExpertise extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $fillable = ['maid_id', 'country_id', 'work_city', 'start_date', 'end_date', 'number_of_years', 'job_title_id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function country(){
        return $this->belongsTo(Country::class, 'country_id');
    }
    public function jobTitle(){
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }

    /**
     * عدد أشهر الخبرة المحسوبة من البداية والنهاية.
     */
    public function getDurationMonthsAttribute(): ?int
    {
        return ExperiencePeriod::months($this->start_date, $this->end_date);
    }

    /**
     * "4 سنوات و 5 أشهر" — يرجع للقيمة المخزّنة في number_of_years للسجلات القديمة بلا تواريخ.
     */
    public function getDurationLabelAttribute(): ?string
    {
        $label = ExperiencePeriod::durationLabel($this->duration_months);

        if ($label !== null) {
            return $label;
        }

        $years = (float) ($this->number_of_years ?? 0);

        return $years > 0
            ? ExperiencePeriod::durationLabel((int) round($years * 12))
            : null;
    }

    /**
     * "2018م – 2022م".
     */
    public function getPeriodLabelAttribute(): ?string
    {
        return ExperiencePeriod::periodLabel($this->start_date, $this->end_date);
    }
}
