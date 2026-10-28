<?php

namespace App\Mcp\Tools;

use App\Models\auth\Admin;
use App\Models\ingaz\Complaint;
use Illuminate\Support\Str;

/** الشكاوى. Complaint عليه BranchScoped. */
class ListComplaintsTool extends McpTool
{
    public function name(): string
    {
        return 'list_complaints';
    }

    public function description(): string
    {
        return 'قائمة الشكاوى مع العدد حسب الحالة والأولوية، وفلترة بالتاريخ أو الحالة أو اسم/جوال المشتكي. '
            . 'Complaints with counts by status and priority.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => ['type' => 'string', 'description' => 'pending, in_progress, resolved, closed...'],
                'priority' => ['type' => 'string', 'description' => 'low, medium, high, urgent'],
                'from' => ['type' => 'string', 'format' => 'date'],
                'to' => ['type' => 'string', 'format' => 'date'],
                'query' => ['type' => 'string', 'description' => 'اسم المشتكي أو جواله أو الموضوع'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'complaints.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $query = Complaint::query();

        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            if (!empty($arguments[$key])) {
                $query->whereDate('complaints.created_at', $operator, $arguments[$key]);
            }
        }

        if (!empty($arguments['query'])) {
            $term = (string) $arguments['query'];
            $query->where(fn ($q) => $q->where('complainant_name', 'like', "%{$term}%")
                ->orWhere('complainant_phone', 'like', "%{$term}%")
                ->orWhere('subject', 'like', "%{$term}%"));
        }

        $byStatus = $this->countBy($query, 'status');
        $byPriority = $this->countBy($query, 'priority');

        foreach (['status', 'priority'] as $column) {
            if (!empty($arguments[$column])) {
                $query->where($column, (string) $arguments[$column]);
            }
        }

        $total = (clone $query)->count();
        $complaints = $query->with(['complaintType', 'assignedTo', 'contract'])->latest('id')->limit($this->limit($arguments))->get();

        return [
            'total' => $total,
            'count_by_status' => $byStatus,
            'count_by_priority' => $byPriority,
            'complaints' => $complaints->map(fn (Complaint $complaint) => [
                'id' => $complaint->id,
                'date' => $this->date($complaint->complaint_date ?: $complaint->created_at),
                'status' => $complaint->status,
                'priority' => $complaint->priority,
                'type' => $complaint->complaintType?->name ?? $complaint->complaintType?->title,
                'subject' => Str::limit((string) ($complaint->subject ?: $complaint->message), 150),
                'complainant' => $complaint->complainant_name,
                'complainant_phone' => $this->mask($complaint->complainant_phone),
                'against' => $complaint->against_name,
                'assigned_to' => $complaint->assignedTo?->name,
                'contract_number' => $complaint->contract?->arabia_order_number,
                'due_at' => $this->date($complaint->due_at),
                'resolved_at' => $this->date($complaint->resolved_at),
            ])->all(),
        ];
    }

    private function countBy($query, string $column): array
    {
        return (clone $query)->selectRaw("{$column} as k, count(*) as total")->groupBy($column)
            ->pluck('total', 'k')->map(fn ($total) => (int) $total)->all();
    }
}
