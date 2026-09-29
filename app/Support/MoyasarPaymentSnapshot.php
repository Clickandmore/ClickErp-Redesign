<?php

namespace App\Support;

/** Normalize a verified provider invoice without confusing its total with its refund. */
class MoyasarPaymentSnapshot
{
    public static function fromInvoice(array $invoice, string $id, float $amount, ?string $paymentId = null): ?array
    {
        $minor = (int) round($amount * 100);
        if (($invoice['id'] ?? '') !== $id || ($invoice['currency'] ?? '') !== 'SAR'
            || !is_numeric($invoice['amount'] ?? null) || (float) $invoice['amount'] !== (float) $minor) {
            throw new \RuntimeException('Moyasar invoice does not match the stored payment.');
        }
        if (!in_array($invoice['status'] ?? '', ['paid', 'refunded'], true)) return null;
        foreach ($invoice['payments'] ?? [] as $payment) {
            if (!in_array($payment['status'] ?? '', ['paid', 'captured', 'refunded'], true)) continue;
            if ($paymentId && ($payment['id'] ?? '') !== $paymentId) continue;
            if (($payment['currency'] ?? '') !== 'SAR' || (float) ($payment['amount'] ?? -1) !== (float) $minor) continue;
            $refund = $payment['refunded'] ?? 0;
            if (!is_numeric($refund) || (float) $refund !== (float) (int) $refund || $refund < 0 || $refund > $minor) {
                throw new \RuntimeException('Invalid Moyasar refund amount.');
            }
            return ['id' => $payment['id'], 'refunded' => (int) $refund / 100,
                'status' => $refund > 0 ? ($refund >= $minor ? 'refunded' : 'partially_refunded') : 'paid'];
        }
        // Older invoice responses may omit attempts; they can confirm payment, never a refund.
        if (!array_key_exists('payments', $invoice) && ($invoice['status'] ?? '') === 'paid' && !$paymentId) {
            return ['id' => null, 'refunded' => 0, 'status' => 'paid'];
        }
        throw new \RuntimeException('Moyasar invoice has no matching settled payment.');
    }
}
