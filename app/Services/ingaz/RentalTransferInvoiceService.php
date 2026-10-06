<?php

namespace App\Services\ingaz;

use App\Models\RentalContract;
use App\Models\ingaz\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentalTransferInvoiceService
{
    public function validateRequestInvoiceTotal(\App\Models\SponsorshipTransfer $transfer, float $total): void
    {
        if ((float) $transfer->contract_value <= 0 || abs(round($total, 2) - round((float) $transfer->contract_value, 2)) > 0.011) {
            throw ValidationException::withMessages(['line_items' => 'يجب أن يساوي إجمالي الفاتورة مبلغ نقل الخدمات المسجل على الطلب.']);
        }
    }

    public function activeContractForTransfer(\App\Models\SponsorshipTransfer $transfer): ?RentalContract
    {
        $transfer->loadMissing('contract');
        $contracts = RentalContract::query()->with('transferInvoice')
            ->where('maid_id', $transfer->contract?->maid_id ?? 0)
            ->where('user_id', $transfer->new_sponsor_id)
            ->whereNull('closure_type')
            ->whereIn('status', ['new', 'waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active', 'frozen'])
            ->limit(2)->lockForUpdate()->get();
        if ($contracts->count() > 1) {
            throw ValidationException::withMessages(['contract' => 'توجد عدة عقود تأجير لنفس العميل والعاملة؛ راجع العقود قبل إصدار الفاتورة.']);
        }
        return $contracts->first();
    }
    public function contractsForTransfer(\App\Models\SponsorshipTransfer $transfer)
    {
        $transfer->loadMissing('contract');
        return RentalContract::query()->with(['info', 'transferInvoice'])
            ->where('maid_id', $transfer->contract?->maid_id ?? 0)
            ->where('user_id', $transfer->new_sponsor_id)
            ->where('closure_type', 'transferred')
            ->orderByDesc('closed_at');
    }

    public function assertTransferMatches(\App\Models\SponsorshipTransfer $transfer, RentalContract $contract): void
    {
        $transfer->loadMissing('contract');
        if (!$transfer->isRentalWorkflow() || $transfer->status !== 'transferred'
            || (int) $transfer->new_sponsor_id !== (int) $contract->user_id
            || (int) $transfer->contract?->maid_id !== (int) $contract->maid_id
            || $contract->closure_type !== 'transferred') {
            throw ValidationException::withMessages(['transfer_id' => 'عقد التأجير غير مرتبط بنفس العميل والعاملة في عملية النقل المكتملة.']);
        }
        $invoice = $contract->transferInvoice;
        if ($invoice?->sponsorship_transfer_id && (int) $invoice->sponsorship_transfer_id !== (int) $transfer->id) {
            throw ValidationException::withMessages(['transfer_id' => 'فاتورة هذا العقد مرتبطة بعملية نقل خدمات أخرى.']);
        }
    }
    public function amounts(RentalContract $contract, int $vatPercent = 15): array
    {
        if ($contract->closure_type !== 'transferred' || !$contract->user_id) {
            throw ValidationException::withMessages(['contract' => 'يلزم عقد تأجير تم نقل خدماته ومرتبط بعميل.']);
        }
        if (!in_array($vatPercent, [0, 15], true)) {
            throw ValidationException::withMessages(['vat_percent' => 'نسبة الضريبة غير صحيحة.']);
        }
        // The recorded transfer total is authoritative; tax is included, never added to the customer's balance.
        $total = round((float) ($contract->info?->sponsorship_transfer_total_amount
            ?? $contract->info?->sponsorship_transfer_amount ?? 0), 2);
        if ($total <= 0) {
            throw ValidationException::withMessages(['contract' => 'لا توجد قيمة نقل خدمات موجبة مسجلة على العقد.']);
        }
        $subtotal = round($total / (1 + $vatPercent / 100), 2);
        $paid = round(min($total, max(0, (float) $contract->info?->sponsorship_transfer_paid_amount)), 2);

        return ['subtotal' => $subtotal, 'vat_amount' => round($total - $subtotal, 2),
            'total_due' => $total, 'paid_amount' => $paid, 'remaining_amount' => round($total - $paid, 2)];
    }

    public function issueForTransfer(int $transferId, int $contractId, int $vatPercent, string $issueDate): Invoice
    {
        return DB::transaction(function () use ($transferId, $contractId, $vatPercent, $issueDate) {
            $transfer = \App\Models\SponsorshipTransfer::query()->lockForUpdate()->findOrFail($transferId);
            $contract = RentalContract::query()->with('transferInvoice')->lockForUpdate()->findOrFail($contractId);
            $this->assertTransferMatches($transfer, $contract);
            if ($transfer->invoice) {
                $existing = $transfer->invoice;
                if ($existing->rental_transfer_contract_id && (int) $existing->rental_transfer_contract_id !== $contractId) {
                    throw ValidationException::withMessages(['transfer_id' => 'توجد فاتورة سابقة لعملية النقل على عقد آخر.']);
                }
                $amounts = $this->amounts($contract, $vatPercent);
                if (abs((float) $existing->total_due - $amounts['total_due']) > 0.01) {
                    throw ValidationException::withMessages(['transfer_id' => 'مبلغ النقل المسجل على عقد التأجير يختلف عن الفاتورة الصادرة؛ راجع المبالغ قبل الربط.']);
                }
                if ($contract->transferInvoice && $contract->transferInvoice->id !== $existing->id) {
                    throw ValidationException::withMessages(['transfer_id' => 'توجد فاتورة أخرى على عقد التأجير.']);
                }
                $existing->update(['rental_transfer_contract_id' => $contractId]);
                return $existing;
            }
            $invoice = $this->issue($contractId, $vatPercent, $issueDate);
            if (!$invoice->sponsorship_transfer_id) {
                $invoice->update(['sponsorship_transfer_id' => $transferId]);
            }
            return $invoice;
        });
    }

    public function issue(int $contractId, int $vatPercent, string $issueDate): Invoice
    {
        return DB::transaction(function () use ($contractId, $vatPercent, $issueDate) {
            $contract = RentalContract::query()->with('info')->lockForUpdate()->findOrFail($contractId);
            $existing = $contract->transferInvoice()->first();
            if ($existing) {
                return $existing;
            }
            $amounts = $this->amounts($contract, $vatPercent);
            $label = 'نقل خدمات العاملة من عقد التأجير ' . ($contract->contract_number ?: $contract->id);

            // Document the existing transfer. Do not create another journal entry or collection bond.
            return Invoice::query()->create([
                'rental_transfer_contract_id' => $contract->id,
                'client_id' => $contract->user_id, 'branch_id' => $contract->branch_id,
                'general_description' => $label,
                'line_items' => [[
                    'key' => 'rental_transfer', 'label' => $label, 'quantity' => 1,
                    'unit_price' => $amounts['subtotal'], 'line_subtotal' => $amounts['subtotal'],
                    'tax_rate' => $vatPercent / 100, 'taxable' => $vatPercent > 0,
                    'vat_amount' => $amounts['vat_amount'], 'line_total' => $amounts['total_due'],
                ]],
                'direct_recruitment_cost' => $vatPercent > 0 ? $amounts['subtotal'] : 0,
                'direct_recruitment_count' => $vatPercent > 0 ? 1 : 0,
                'external_recruitment_cost' => $vatPercent === 0 ? $amounts['subtotal'] : 0,
                'external_recruitment_count' => $vatPercent === 0 ? 1 : 0,
                'government_cost' => 0, 'government_cost_count' => 0,
                'internal_ticket_cost' => 0, 'internal_ticket_count' => 0,
                'visa_recruited_count' => 0, 'insured_count' => 0,
                'subtotal' => $amounts['subtotal'], 'discount' => 0,
                'vat_amount' => $amounts['vat_amount'], 'total_due' => $amounts['total_due'],
                'rental_transfer_paid_amount' => $amounts['paid_amount'],
                'rental_transfer_remaining_amount' => $amounts['remaining_amount'],
                'is_paid' => $amounts['remaining_amount'] <= 0,
                'paid_at' => $amounts['remaining_amount'] <= 0
                    ? ($contract->info?->sponsorship_transfer_payment_confirmed_at ?? $contract->closed_at) : null,
                'send_to_zatca' => false, 'created_at' => $issueDate . ' ' . now()->format('H:i:s'),
            ]);
        });
    }
}
