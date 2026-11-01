<?php

namespace App\Mcp\Tools;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use Illuminate\Support\Facades\DB;

/** البحث في العاملات. عزل الفروع يطبّقه BranchScope على موديل Maid تلقائياً. */
class SearchMaidsTool extends McpTool
{
    public function name(): string
    {
        return 'search_maids';
    }

    public function description(): string
    {
        return 'البحث عن العاملات بالاسم أو رقم الجواز أو الجنسية أو الديانة أو المهنة أو العمر أو الراتب أو الخبرة أو الحالة، لمطابقة طلب العميل. '
            . 'Search domestic workers by name, passport number, nationality or status.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'جزء من الاسم أو رقم الجواز أو رقم العاملة'],
                'status' => [
                    'type' => 'string',
                    'description' => 'حالة العاملة',
                    'enum' => ['available', 'reserved', 'backout', 'deported', 'suspended'],
                ],
                'nationality_id' => ['type' => 'integer'],
                'nationality' => ['type' => 'string', 'description' => 'اسم الجنسية، مثل: إثيوبيا أو الفلبين'],
                'religion' => ['type' => 'string', 'description' => 'اسم الديانة، مثل: مسلمة'],
                'job_title' => ['type' => 'string', 'description' => 'اسم المهنة، مثل: عاملة منزلية أو طباخة'],
                'min_age' => ['type' => 'integer'],
                'max_age' => ['type' => 'integer'],
                'max_salary' => ['type' => 'number', 'description' => 'أقصى راتب شهري'],
                'min_experience_years' => ['type' => 'integer'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20],
                'offset' => $this->offsetProperty(),
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'maids.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $query = Maid::query()->with(['nationality', 'branch'])->latest('id');

        $term = trim((string) ($arguments['query'] ?? ''));
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('passport_num', 'like', "%{$term}%");

                if (ctype_digit($term)) {
                    $q->orWhere('arabia_worker_number', (int) $term);
                }
            });
        }

        if (!empty($arguments['status'])) {
            $query->where('status', $arguments['status']);
        }

        if (!empty($arguments['nationality_id'])) {
            $query->where('nationality_id', (int) $arguments['nationality_id']);
        }

        // الأسماء في جداول الترجمة؛ يقبل المساعد الاسم نصاً بدل أن يحتاج إلى المعرّف.
        if (!empty($arguments['nationality'])) {
            $query->whereIn('maids.nationality_id', DB::table('nationalities_translations')
                ->where('title', 'like', '%' . $arguments['nationality'] . '%')->select('nationality_id'));
        }

        if (!empty($arguments['religion'])) {
            $query->whereIn('maids.religion_id', DB::table('religions_translations')
                ->where('title', 'like', '%' . $arguments['religion'] . '%')->select('religion_id'));
        }

        if (!empty($arguments['job_title'])) {
            $query->whereIn('maids.id', DB::table('maid_job_titles')
                ->join('job_titles_translations as jt', 'jt.job_title_id', '=', 'maid_job_titles.job_title_id')
                ->where('jt.title', 'like', '%' . $arguments['job_title'] . '%')->select('maid_job_titles.maid_id'));
        }

        foreach (['min_age' => ['age', '>='], 'max_age' => ['age', '<='], 'max_salary' => ['salary', '<='], 'min_experience_years' => ['experience_years', '>=']] as $key => [$column, $operator]) {
            if (isset($arguments[$key]) && is_numeric($arguments[$key])) {
                $query->where("maids.{$column}", $operator, $arguments[$key]);
            }
        }

        $total = (clone $query)->count();
        $maids = $query->offset($this->offset($arguments))->limit($this->limit($arguments))->get();

        return [
            ...$this->pageInfo($total, $maids->count(), $arguments),
            'maids' => $maids->map(fn (Maid $maid) => [
                'id' => $maid->id,
                'url' => $this->link('maids.show', $maid->id),
                'worker_number' => $maid->arabia_worker_number,
                'name' => $maid->name,
                'nationality' => $maid->nationality?->title,
                'age' => $maid->age,
                'status' => $maid->status,
                'passport_number' => $this->mask($maid->passport_num),
                'salary' => $maid->salary,
                'price' => $maid->price,
                'arrival_date' => $this->date($maid->arrival_date),
                'branch' => $maid->branch?->name,
            ])->all(),
        ];
    }
}
