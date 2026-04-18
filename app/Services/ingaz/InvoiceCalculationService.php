<?php

namespace App\Services\ingaz;

class InvoiceCalculationService
{
    public const VAT_RATE = 0.15;
    private const AMOUNT_TOLERANCE = 0.011;

    public function calculateTotals(array $payload): array
    {
        $lineTotals = $this->calculateLineTotals($payload);
        $subtotal = $this->roundMoney(array_sum($lineTotals));
        $discount = $this->normalizeMoney($payload['discount'] ?? 0);
        $taxableSubtotal = $this->roundMoney(
            ($lineTotals['direct_recruitment'] ?? 0)
            + ($lineTotals['internal_ticket'] ?? 0)
        );
        $vatAmount = $this->roundMoney($taxableSubtotal * self::VAT_RATE);
        $totalDue = $this->roundMoney(max(0, $subtotal + $vatAmount - $discount));

        return [
            'line_totals' => $lineTotals,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'vat_amount' => $vatAmount,
            'total_due' => $totalDue,
        ];
    }

    public function calculateLineTotals(array $payload): array
    {
        return [
            'direct_recruitment' => $this->lineTotal(
                $payload['direct_recruitment_cost'] ?? 0,
                $payload['direct_recruitment_count'] ?? 0
            ),
            'external_recruitment' => $this->lineTotal(
                $payload['external_recruitment_cost'] ?? 0,
                $payload['external_recruitment_count'] ?? 0
            ),
            'government' => $this->lineTotal(
                $payload['government_cost'] ?? 0,
                $payload['government_cost_count'] ?? 0
            ),
            'internal_ticket' => $this->lineTotal(
                $payload['internal_ticket_cost'] ?? 0,
                $payload['internal_ticket_count'] ?? 0
            ),
        ];
    }

    public function distributeStoredVat(float $storedVatAmount, array $taxableLineTotals): array
    {
        $storedVatAmount = $this->roundMoney($storedVatAmount);
        $distribution = [];

        foreach ($taxableLineTotals as $key => $amount) {
            $distribution[$key] = 0.0;
            $taxableLineTotals[$key] = $this->roundMoney((float) $amount);
        }

        if ($storedVatAmount <= 0) {
            return $distribution;
        }

        $positiveLineTotals = array_filter($taxableLineTotals, static fn (float $amount) => $amount > 0);
        if (empty($positiveLineTotals)) {
            return $distribution;
        }

        $selectedKeys = $this->resolveStoredTaxableKeys($positiveLineTotals, $storedVatAmount);
        $selectedTotals = array_intersect_key($positiveLineTotals, array_flip($selectedKeys));

        if (empty($selectedTotals)) {
            $selectedTotals = $positiveLineTotals;
        }

        $selectedDistribution = $this->allocateVatAmount($storedVatAmount, $selectedTotals);

        foreach ($selectedDistribution as $key => $amount) {
            $distribution[$key] = $amount;
        }

        return $distribution;
    }

    public function normalizeMoney($value): float
    {
        return $this->roundMoney(max(0, (float) $value));
    }

    public function normalizeCount($value): int
    {
        return max(0, (int) $value);
    }

    private function resolveStoredTaxableKeys(array $taxableLineTotals, float $storedVatAmount): array
    {
        $directTotal = $taxableLineTotals['direct'] ?? 0.0;
        $ticketTotal = $taxableLineTotals['ticket'] ?? 0.0;

        $directVat = $this->roundMoney($directTotal * self::VAT_RATE);
        $ticketVat = $this->roundMoney($ticketTotal * self::VAT_RATE);
        $combinedVat = $this->roundMoney(($directTotal + $ticketTotal) * self::VAT_RATE);

        if ($this->amountsMatch($storedVatAmount, $combinedVat)) {
            return array_keys($taxableLineTotals);
        }

        if ($this->amountsMatch($storedVatAmount, $directVat) && $directTotal > 0) {
            return ['direct'];
        }

        if ($this->amountsMatch($storedVatAmount, $ticketVat) && $ticketTotal > 0) {
            return ['ticket'];
        }

        return array_keys($taxableLineTotals);
    }

    private function allocateVatAmount(float $vatAmount, array $lineTotals): array
    {
        $distribution = [];
        $baseTotal = array_sum($lineTotals);
        $remainingVat = $this->roundMoney($vatAmount);
        $lastKey = array_key_last($lineTotals);

        foreach ($lineTotals as $key => $lineTotal) {
            if ($baseTotal <= 0) {
                $distribution[$key] = 0.0;
                continue;
            }

            if ($key === $lastKey) {
                $distribution[$key] = $remainingVat;
                continue;
            }

            $allocated = $this->roundMoney($vatAmount * ($lineTotal / $baseTotal));
            $distribution[$key] = $allocated;
            $remainingVat = $this->roundMoney($remainingVat - $allocated);
        }

        return $distribution;
    }

    private function lineTotal($cost, $count): float
    {
        return $this->roundMoney($this->normalizeMoney($cost) * $this->normalizeCount($count));
    }

    private function amountsMatch(float $left, float $right): bool
    {
        return abs($left - $right) < self::AMOUNT_TOLERANCE;
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }
}
