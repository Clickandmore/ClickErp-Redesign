<?php

namespace App\Support;

use App\Models\IngazDelegation;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\Constraint;

class IngazDelegationFinancialDocuments
{
    public static function for(IngazDelegation $delegation): array
    {
        $delegation->loadMissing(['invoice.recruitmentCreditNotes', 'invoice.musanedTransferItem.transfer']);
        $invoice = $delegation->invoice;
        if (!$invoice) {
            return [];
        }

        $documents = [];
        $add = function (string $key, string $label, string $url) use (&$documents): void {
            $documents[$key] = compact('label', 'url');
        };
        $constraint = function ($id, string $label) use ($add): void {
            if ($id) {
                $add('constraint-' . $id, $label, route('finance.add-constraint.show', ['add_constraint' => $id]));
            }
        };

        if (InvoiceAuthorization::canView()) {
            $add('invoice', 'الفاتورة #' . $invoice->id, route('invoices.show', $invoice->id));
        }
        if (InvoiceAuthorization::canPrint()) {
            $add('invoice-print', 'طباعة / حفظ الفاتورة PDF', route('invoice.download', ['id' => $invoice->id, 'autoprint' => 1]));
        }
        if ($invoice->finance_sale_id) {
            $add('finance-sale', 'فاتورة المبيعات المحاسبية', route('finance.bill-sales.show', $invoice->finance_sale_id));
        }
        foreach ([
            'invoice_constraint_id' => 'القيد المالي للفاتورة',
            'ingaz_delegation_cost_constraint_id' => 'قيد تكلفة التفويض',
            'arrival_revenue_constraint_id' => 'قيد إيراد الوصول',
            'external_office_cost_constraint_id' => 'قيد تكلفة المكتب الخارجي',
        ] as $field => $label) {
            $constraint($invoice->{$field}, $label);
        }

        // Older invoices may have a journal reference only in its statement.
        $journals = Constraint::query()->where(function ($query) use ($delegation, $invoice) {
            $query->where('statement', 'like', "قيد فاتورة تفويض إنجاز #{$delegation->id} / الفاتورة #{$invoice->id} /%")
                ->orWhere('statement', 'like', "قيد تكلفة تفويض إنجاز #{$delegation->id} / الفاتورة #{$invoice->id} /%");
        })->orderBy('id')->get();
        foreach ($journals as $journal) {
            if (!isset($documents['constraint-' . $journal->id])) {
                $constraint($journal->id, 'قيد مالي #' . $journal->code . ' — ' . $journal->statement);
            }
        }

        // Invoice issuance stores this exact reference in the receipt statement.
        // Include the separator after each ID to avoid matching another delegation/invoice.
        $prefix = "سند قبض سداد فاتورة تفويض إنجاز #{$delegation->id} / الفاتورة #{$invoice->id} /";
        $bonds = Bond::query()->where('type', 'qabd')
            ->where('statement', 'like', $prefix . '%')->orderBy('id')->get();
        foreach ($bonds as $bond) {
            $label = 'سند قبض العميل #' . ($bond->code ?: $bond->id);
            $add('bond-' . $bond->id, $label, route('finance.bonds-print.show', ['bonds_print' => $bond->id, 'type' => 'qabd']));
            $constraint($bond->constraint_id, 'القيد المالي — ' . $label);
        }

        foreach ($invoice->recruitmentCreditNotes as $note) {
            if (InvoiceAuthorization::canPrintCreditNotes()) {
                $add('credit-note-' . $note->id, 'إشعار دائن #' . $note->id, route('recruitment-credit-notes.download', ['id' => $note->id, 'autoprint' => 1]));
            }
            $constraint($note->constraint_id, 'قيد إشعار الدائن #' . $note->id);
        }
        $transfer = $invoice->musanedTransferItem?->transfer;
        if ($transfer) {
            $constraint($transfer->settlement_constraint_id, 'قيد تسوية مساند');
            $constraint($transfer->bank_transfer_constraint_id, 'قيد تحويل البنك لمساند');
        }

        return array_values($documents);
    }
}
