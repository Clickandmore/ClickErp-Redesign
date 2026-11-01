<?php

namespace App\Mcp\Tools;

use App\Mcp\McpToolException;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;

/** ملف عاملة واحدة. BranchScope على Maid يعزل الفروع تلقائياً. */
class GetMaidTool extends McpTool
{
    public function name(): string
    {
        return 'get_maid';
    }

    public function description(): string
    {
        return 'ملف عاملة واحدة بالمعرّف: الجنسية والديانة والعمر والخبرة والمهن والراتب والسعر والحالة والمكتب ومواعيد الجواز. '
            . 'Full profile of one domestic worker by id.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['id' => ['type' => 'integer', 'description' => 'معرّف العاملة من search_maids']],
            'required' => ['id'],
        ];
    }

    protected function gateRoute(): ?string
    {
        // نفس حقول search_maids وأكثر قليلاً؛ maids.show مربوط بصلاحية تبديل الحالة.
        return 'maids.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $maid = Maid::query()->with(['nationality', 'religion', 'jobTitles', 'branch'])->find((int) ($arguments['id'] ?? 0));

        if (!$maid) {
            throw new McpToolException('العاملة غير موجودة أو خارج نطاق فرعك.');
        }

        return [
            'id' => $maid->id,
            'url' => $this->link('maids.show', $maid->id),
            'worker_number' => $maid->arabia_worker_number,
            'name' => $maid->name,
            'nationality' => $maid->nationality?->title,
            'religion' => $maid->religion?->title,
            'age' => $maid->age,
            'status' => $maid->status,
            'job_titles' => $maid->jobTitles->pluck('title')->filter()->values()->all(),
            'experience_years' => $maid->experience_years,
            'experience_country' => $maid->experience_country,
            'salary' => $maid->salary,
            'price' => $maid->price,
            'arrival_date' => $this->date($maid->arrival_date),
            'passport_number' => $this->mask($maid->passport_num),
            'passport_expiry_date' => $this->date($maid->passport_expiry_date),
            'id_expiry_date' => $this->date($maid->id_expiry_date),
            'branch' => $maid->branch?->name,
            'notes' => $this->maskText($maid->additional_notes),
        ];
    }
}
