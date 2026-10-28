<?php

namespace App\Mcp\Tools;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;

/** البحث في العاملات. عزل الفروع يطبّقه BranchScope على موديل Maid تلقائياً. */
class SearchMaidsTool extends McpTool
{
    public function name(): string
    {
        return 'search_maids';
    }

    public function description(): string
    {
        return 'البحث عن العاملات بالاسم أو رقم الجواز أو الجنسية أو الحالة. '
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
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20],
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

        $total = (clone $query)->count();
        $maids = $query->limit($this->limit($arguments))->get();

        return [
            'total' => $total,
            'returned' => $maids->count(),
            'maids' => $maids->map(fn (Maid $maid) => [
                'id' => $maid->id,
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
