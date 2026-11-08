<?php

namespace App\Services;

use App\Models\{MoyasarContractPayment, RentalContract, RentalContractInfo, RentalContractExpiryReminder, RentalContractExtension};
use App\Services\ingaz\RentalContractFinanceService;
use App\Support\MoyasarPaymentSnapshot;
use Illuminate\Support\Facades\DB;

class MoyasarContractPaymentService
{
    public function __construct(private MoyasarService $gateway, private RentalContractFinanceService $finance,
        private RentalContractExpiryReminderService $reminders) {}

    public function register(RentalContractInfo $info, string $invoiceId, float $amount, ?int $reminderId = null): MoyasarContractPayment
    {
        $payment = MoyasarContractPayment::firstOrCreate(['invoice_id'=>$invoiceId], [
            'rental_contract_id'=>$info->rental_contract_id, 'amount'=>$amount, 'reminder_id'=>$reminderId,
        ]);
        if ((int)$payment->rental_contract_id !== (int)$info->rental_contract_id) throw new \RuntimeException('Moyasar contract reference mismatch.');
        if ($reminderId && ((int) $payment->reminder_id !== $reminderId || (int) round((float) $payment->amount * 100) !== (int) round($amount * 100))) {
            throw new \RuntimeException('Moyasar renewal reference or amount mismatch.');
        }
        return $payment;
    }

    public function resolve(string $reference): ?MoyasarContractPayment
    {
        $payment = MoyasarContractPayment::where('invoice_id',$reference)->orWhere('provider_payment_id',$reference)->first();
        if ($payment) return $payment;
        $reminder = RentalContractExpiryReminder::where('payment_id',$reference)->first();
        $info = $reminder ? RentalContractInfo::where('rental_contract_id',$reminder->rental_contract_id)->first()
            : RentalContractInfo::where('moyasar_payment_id',$reference)->first();
        if (!$reminder) {
            // Older renewal callbacks replaced reminder.payment_id with a transaction ID.
            // Resolve only from the provider's verified metadata/attempts, never webhook metadata alone.
            $invoice = $this->gateway->getInvoice($reference);
            if (!$invoice && $info) throw new \RuntimeException('Unable to verify legacy Moyasar reference.');
            if ($invoice && ($invoice['id'] ?? '') === $reference) {
                $verifiedContract = (int)data_get($invoice,'metadata.contract_id',0);
                if (!$info && $verifiedContract) $info = RentalContractInfo::where('rental_contract_id',$verifiedContract)->first();
                if ($info) {
                    $ids = array_filter(array_column($invoice['payments'] ?? [],'id'));
                    $reminderId = (int)data_get($invoice,'metadata.reminder_id',0);
                    if ($ids || $reminderId) {
                        $reminder = RentalContractExpiryReminder::where('rental_contract_id',$info->rental_contract_id)
                            ->where(function($q) use($ids,$reminderId) {
                                $q->whereIn('payment_id',$ids);
                                if ($reminderId) $q->orWhere('id',$reminderId);
                            })->first();
                    }
                }
            }
        }
        if (!$info) return null;
        $amount = $reminder ? ($reminder->extension_id
            ? (float) RentalContractExtension::findOrFail($reminder->extension_id)->extension_value
            : (float) ($reminder->renewal_quote['amount'] ?? $this->reminders->renewalAmount($info->rentalContract))) : (float)$info->contract_value;
        return $this->register($info, $reference, $amount, $reminder?->id);
    }

    public function synchronize(MoyasarContractPayment $payment): MoyasarContractPayment
    {
        $invoice = $this->gateway->getInvoice($payment->invoice_id);
        if (!$invoice) {
            // Legacy reminders sometimes stored the transaction ID instead of the invoice ID.
            $transaction = $this->gateway->getPayment($payment->invoice_id);
            if (!$transaction || ($transaction['id'] ?? '') !== $payment->invoice_id) throw new \RuntimeException('Unable to verify Moyasar invoice.');
            $invoice = ['id'=>$payment->invoice_id,'currency'=>$transaction['currency'] ?? null,
                'amount'=>$transaction['amount'] ?? null,'status'=>$transaction['status'] ?? null,'payments'=>[$transaction]];
        }
        $snapshot = MoyasarPaymentSnapshot::fromInvoice($invoice,$payment->invoice_id,(float)$payment->amount,$payment->provider_payment_id);
        if (!$snapshot) {
            if (in_array($invoice['status'] ?? '', ['failed','expired'], true)) {
                if ($payment->reminder_id && !$payment->paid_at) {
                    RentalContractExpiryReminder::whereKey($payment->reminder_id)->whereNull('contract_effect_applied_at')
                        ->update(['payment_status' => $invoice['status']]);
                }
                RentalContractInfo::where('rental_contract_id',$payment->rental_contract_id)
                    ->where('moyasar_payment_id',$payment->invoice_id)->whereNull('moyasar_paid_at')
                    ->update(['moyasar_payment_status'=>$invoice['status']]);
            }
            return $payment;
        }
        return DB::transaction(function () use ($payment,$snapshot) {
            $contract = RentalContract::query()->whereKey($payment->rental_contract_id)->lockForUpdate()->firstOrFail();
            $info = RentalContractInfo::where('rental_contract_id',$contract->id)->lockForUpdate()->firstOrFail();
            $payment = MoyasarContractPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $reminder = $payment->reminder_id ? RentalContractExpiryReminder::whereKey($payment->reminder_id)->lockForUpdate()->firstOrFail() : null;
            if ($reminder && (int)$reminder->rental_contract_id !== (int)$contract->id) throw new \RuntimeException('Moyasar reminder mismatch.');
            $refunded = max((float)$payment->refunded_amount,$snapshot['refunded']);
            $delta = round($refunded-(float)$payment->refunded_amount,2);
            $contract->setRelation('info',$info);
            $newRenewalPayment = $reminder && !$payment->paid_at;

            if (!$payment->paid_at) {
                if ($reminder) {
                    // A refunded notification never extends dates or reactivates a closed contract.
                    if ($refunded == 0 && !$reminder->contract_effect_applied_at) {
                        $this->reminders->applyPaidExtensionForMoyasarPayment($info,$snapshot['id'],$reminder->id);
                        $reminder->refresh(); $info->refresh();
                    }
                    $extension = $reminder->extension_id ? RentalContractExtension::findOrFail($reminder->extension_id) : null;
                    if ($extension && $refunded == 0 && !$extension->invoice_constraint_id) {
                        $this->finance->recordExtensionForContract($contract, $extension, now());
                    }
                    $receipt = $extension?->receiptBond;
                    if (!$receipt) $receipt = $this->finance->recordMoyasarReceipt($contract,(float)$payment->amount,$payment->invoice_id);
                    if ($extension) $extension->update(['payment_amount'=>$payment->amount,'payment_type'=>'moyasar','receipt_bond_id'=>$receipt->id,
                        'payment_date'=>today(), 'receipt_account_id'=>$receipt->debit_account_id, 'receipt_constraint_id'=>$receipt->constraint_id]);
                } else {
                    // Imported/legacy paid contracts already include this money in their balance.
                    if (!$info->moyasar_paid_at) {
                        // Use Click's additional-receipt workflow so a previous cash receipt
                        // is neither reused nor reversed for this electronic payment.
                        $result = $this->finance->recordPaymentForContract($contract,(float)$payment->amount,'moyasar',now());
                        $info->refresh();
                        $info->update(['moyasar_paid_at'=>now()]);
                    } else {
                        $result = $this->finance->syncPaymentForContract($contract);
                    }
                    $receipt = $result['receipt_bond'] ?? null;
                    if (!$receipt) throw new \RuntimeException('Moyasar receipt could not be recorded.');
                }
                $payment->update(['paid_at'=>now(),'receipt_bond_id'=>$receipt->id]);
            }

            if ($delta > 0) {
                $bond = $this->finance->recordMoyasarRefund($contract,$delta,$payment->invoice_id,$payment->receipt_bond_id);
                DB::table('moyasar_contract_refunds')->insert(['moyasar_contract_payment_id'=>$payment->id,
                    'amount'=>$delta,'cumulative_amount'=>$refunded,'bond_id'=>$bond->id,'created_at'=>now(),'updated_at'=>now()]);
                if (!$reminder) {
                    $paid = round((float)$info->paid_amount-$delta,2);
                    if ($paid < 0) throw new \RuntimeException('Refund exceeds the recorded contract balance; reconcile the original receipt.');
                    $remaining = max(0,round((float)$info->contract_value-$paid,2));
                    $info->update(['paid_amount'=>$paid,'remaining_amount'=>$remaining,
                        'payment_status'=>$remaining <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending')]);
                    $invoice = $contract->financeInvoice()->lockForUpdate()->firstOrFail();
                    $invoicePaid = min((float)$invoice->total_due,$paid);
                    $invoiceRemaining = max(0,round((float)$invoice->total_due-$invoicePaid,2));
                    $invoice->update(['paid_amount'=>$invoicePaid,'remaining_amount'=>$invoiceRemaining,
                        'payment_status'=>$invoiceRemaining <= 0 ? 'paid' : ($invoicePaid > 0 ? 'partial' : 'pending'),
                        'is_paid'=>$invoiceRemaining <= 0]);
                }
                $info->update(['moyasar_refunded_amount'=>round((float)$info->moyasar_refunded_amount+$delta,2)]);
            }
            $status = $refunded > 0 ? ($refunded >= (float)$payment->amount ? 'refunded' : 'partially_refunded') : 'paid';
            if ($reminder) {
                $reminder->update(['payment_status'=>$status]);
                if ($reminder->extension_id) {
                    $net = round((float)$payment->amount-$refunded,2);
                    RentalContractExtension::whereKey($reminder->extension_id)->update(['payment_amount'=>$net,
                        'payment_status'=>$net <= 0 ? 'pending' : ($refunded > 0 ? 'partial' : 'paid')]);
                }
            }
            if ($info->moyasar_payment_id === $payment->invoice_id) $info->update(['moyasar_payment_status'=>$status]);
            $payment->update(['refunded_amount'=>$refunded,'provider_payment_id'=>$snapshot['id'] ?: $payment->provider_payment_id]);
            if ($newRenewalPayment && $refunded == 0) {
                $reminderId = $reminder->id;
                DB::afterCommit(function () use ($reminderId) {
                    try {
                        $reminder = RentalContractExpiryReminder::find($reminderId);
                        if ($reminder) $this->reminders->notifyResponsibleEmployee($reminder, $reminder->extension_id ? 'paid' : 'payment_review');
                    } catch (\Throwable $e) { report($e); }
                });
            }
            return $payment;
        },3);
    }
}
