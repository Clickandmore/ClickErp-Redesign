<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Maid;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\JobType;
use App\Models\ingaz\User as ClientUser;
use App\Enums\ingaz\ContractStatusEnum;
use App\Models\MaidExpertise;
use App\Support\ExperiencePeriod;
use Illuminate\Support\Facades\DB;

class MaidExpertiseAction extends MainAction
{
    public function __construct(MaidExpertise $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Maid/';
    }

    public function store($data)
    {
        return parent::store($this->normalizeExperiencePeriod($data));
    }

    /**
     * البداية والنهاية تصل من الواجهة بصيغة "YYYY-MM"، ومدة الخبرة تُحسب هنا
     * لا في المتصفح حتى لا تُرسل قيمة لا تطابق التاريخين.
     */
    public function normalizeExperiencePeriod(array $data): array
    {
        $start = ExperiencePeriod::normalize($data['start_date'] ?? null);
        $end = ExperiencePeriod::normalize($data['end_date'] ?? null);

        $data['start_date'] = $start;
        $data['end_date'] = $end;

        $years = ExperiencePeriod::years($start, $end);

        if ($years !== null) {
            $data['number_of_years'] = $years;
        }

        return $data;
    }
}
