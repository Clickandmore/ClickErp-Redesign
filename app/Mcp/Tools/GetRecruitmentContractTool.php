<?php

namespace App\Mcp\Tools;

use App\Mcp\McpToolException;
use App\Mcp\Tools\Concerns\PresentsRecruitmentContracts;
use App\Models\auth\Admin;

class GetRecruitmentContractTool extends McpTool
{
    use PresentsRecruitmentContracts;

    public function name(): string
    {
        return 'get_recruitment_contract';
    }

    public function description(): string
    {
        return 'تفاصيل عقد استقدام واحد بالمعرّف أو رقم العقد: العميل والعاملة والتكلفة والضمان والتأشيرة وآخر الملاحظات. '
            . 'Full details of one recruitment contract by id or contract number.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'contract_number' => ['type' => 'integer'],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'RecruitmentContract.show';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $query = $this->recruitmentContracts()->with(['user', 'maid.nationality', 'marketer', 'branch', 'invoice']);

        if (!empty($arguments['id'])) {
            $query->whereKey((int) $arguments['id']);
        } elseif (ctype_digit((string) ($arguments['contract_number'] ?? ''))) {
            $query->where('arabia_order_number', (int) $arguments['contract_number']);
        } else {
            throw new McpToolException('أرسل id أو contract_number.');
        }

        // BranchScope يُخفي عقود الفروع الأخرى، فتظهر هنا كأنها غير موجودة.
        $contract = $query->first();

        if (!$contract) {
            throw new McpToolException('العقد غير موجود أو خارج نطاق فرعك.');
        }

        return $this->contractSummary($contract) + [
            'type' => $contract->type,
            'maid_nationality' => $contract->maid?->nationality?->title,
            'visa_number' => $contract->visa_number,
            'musaned_number' => $contract->msaned_number,
            'musaned_date' => $this->date($contract->musand_date),
            // قيمة العقد الفعلية في فاتورته؛ contract_cost حقل قديم شبه فارغ.
            'invoice' => $contract->invoice ? [
                'id' => $contract->invoice->id,
                'subtotal' => $contract->invoice->subtotal,
                'discount' => $contract->invoice->discount,
                'vat' => $contract->invoice->vat_amount,
                'total_due' => $contract->invoice->total_due,
                'is_paid' => (bool) $contract->invoice->is_paid,
                'paid_at' => $this->date($contract->invoice->paid_at),
            ] : null,
            'warranty' => [
                'status' => $contract->warranty_status,
                'start' => $this->date($contract->warranty_start_date),
                'end' => $this->date($contract->warranty_end_date),
            ],
            'completed_at' => $this->date($contract->completed_at),
            'throwback' => $contract->throwback_date ? [
                'date' => $this->date($contract->throwback_date),
                'reason' => $contract->throwback_reason,
            ] : null,
            'latest_notes' => $contract->notes()->latest('id')->limit(5)->get()
                ->map(fn ($note) => [
                    'date' => $this->date($note->created_at),
                    'note' => $note->note,
                ])->all(),
        ];
    }
}
