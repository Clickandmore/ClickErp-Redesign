<?php

namespace App\Mcp\Tools;

use App\Models\auth\Admin;
use App\Models\ingaz\Invoice;

/** فواتير العملاء: الإجماليات والمحصّل والمتبقي في فترة. Invoice عليه BranchScoped. */
class InvoicesSummaryTool extends McpTool
{
    public function name(): string
    {
        return 'invoices_summary';
    }

    public function description(): string
    {
        return 'ملخص فواتير العملاء في فترة: عدد الفواتير، الإجمالي قبل وبعد الضريبة، المدفوع وغير المدفوع، وأحدث الفواتير. '
            . 'Customer invoices summary for a period: totals, VAT, paid vs unpaid, latest invoices.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'from' => ['type' => 'string', 'format' => 'date', 'description' => 'تاريخ الفاتورة من (Y-m-d)'],
                'to' => ['type' => 'string', 'format' => 'date', 'description' => 'تاريخ الفاتورة إلى (Y-m-d)'],
                'paid' => ['type' => 'boolean', 'description' => 'true المدفوعة فقط، false غير المدفوعة فقط'],
                'client' => ['type' => 'string', 'description' => 'اسم العميل أو جواله'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'invoices.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $query = Invoice::query();

        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            if (!empty($arguments[$key])) {
                $query->whereDate('invoices.created_at', $operator, $arguments[$key]);
            }
        }

        if (!empty($arguments['client'])) {
            $term = (string) $arguments['client'];
            $match = fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%");
            $query->where(fn ($q) => $q->whereHas('client', $match)->orWhereHas('contract.user', $match));
        }

        $totals = (clone $query)->selectRaw('
            count(*) as invoices,
            coalesce(sum(subtotal), 0) as subtotal,
            coalesce(sum(discount), 0) as discount,
            coalesce(sum(vat_amount), 0) as vat,
            coalesce(sum(total_due), 0) as total_due,
            coalesce(sum(case when is_paid = 1 then total_due else 0 end), 0) as paid,
            coalesce(sum(case when is_paid = 1 then 0 else total_due end), 0) as unpaid,
            sum(case when is_paid = 1 then 0 else 1 end) as unpaid_count
        ')->first();

        if (array_key_exists('paid', $arguments) && $arguments['paid'] !== null) {
            $query->where('is_paid', (bool) $arguments['paid']);
        }

        $invoices = $query->with(['client', 'contract.user'])->latest('id')->limit($this->limit($arguments, 10))->get();

        return [
            'totals' => [
                'invoices' => (int) $totals->invoices,
                'subtotal' => round((float) $totals->subtotal, 2),
                'discount' => round((float) $totals->discount, 2),
                'vat' => round((float) $totals->vat, 2),
                'total_due' => round((float) $totals->total_due, 2),
                'paid' => round((float) $totals->paid, 2),
                'unpaid' => round((float) $totals->unpaid, 2),
                'unpaid_count' => (int) $totals->unpaid_count,
            ],
            'currency' => 'SAR',
            'latest' => $invoices->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'date' => $this->date($invoice->created_at),
                // فواتير الاستقدام غالباً بلا client_id؛ العميل على العقد.
                'client' => ($invoice->client ?? $invoice->contract?->user)?->name,
                'contract_id' => $invoice->contract_id,
                'contract_number' => $invoice->contract?->arabia_order_number,
                'total_due' => $invoice->total_due,
                'vat' => $invoice->vat_amount,
                'is_paid' => (bool) $invoice->is_paid,
                'paid_at' => $this->date($invoice->paid_at),
            ])->all(),
        ];
    }
}
