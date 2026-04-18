<?php

namespace Tests\Unit;

use App\Services\ingaz\InvoiceCalculationService;
use PHPUnit\Framework\TestCase;

class InvoiceCalculationServiceTest extends TestCase
{
    public function test_it_calculates_vat_for_direct_recruitment_and_internal_ticket(): void
    {
        $service = new InvoiceCalculationService();

        $totals = $service->calculateTotals([
            'direct_recruitment_cost' => 1533,
            'direct_recruitment_count' => 1,
            'external_recruitment_cost' => 3023,
            'external_recruitment_count' => 1,
            'government_cost' => 140,
            'government_cost_count' => 1,
            'internal_ticket_cost' => 100,
            'internal_ticket_count' => 1,
            'discount' => 0,
        ]);

        $this->assertSame(4796.0, $totals['subtotal']);
        $this->assertSame(244.95, $totals['vat_amount']);
        $this->assertSame(5040.95, $totals['total_due']);
    }

    public function test_it_preserves_legacy_direct_only_vat_distribution(): void
    {
        $service = new InvoiceCalculationService();

        $distribution = $service->distributeStoredVat(1212.60, [
            'direct' => 8084.0,
            'ticket' => 767.0,
        ]);

        $this->assertSame(1212.6, $distribution['direct']);
        $this->assertSame(0.0, $distribution['ticket']);
    }

    public function test_it_distributes_new_vat_across_direct_and_ticket_lines(): void
    {
        $service = new InvoiceCalculationService();

        $distribution = $service->distributeStoredVat(1327.65, [
            'direct' => 8084.0,
            'ticket' => 767.0,
        ]);

        $this->assertSame(1212.6, $distribution['direct']);
        $this->assertSame(115.05, $distribution['ticket']);
    }
}
