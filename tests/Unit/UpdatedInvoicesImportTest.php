<?php

namespace Tests\Unit;

use App\Services\ingaz\DataImportTemplateService;
use Tests\TestCase;

class UpdatedInvoicesImportTest extends TestCase
{
    public function test_template_contains_supplier_and_commission_inputs(): void
    {
        $service = app(DataImportTemplateService::class);
        $keys = array_column($service->template('updated_invoices')['columns'], 'key');
        foreach (['musaned_commission_amount', 'create_supplier_invoice', 'supplier_recognition_mode',
            'supplier_total_cost_usd', 'supplier_partial_cost_usd', 'supplier_arrival_station'] as $key) {
            $this->assertContains($key, $keys);
            $this->assertNotContains($key, array_column($service->template('invoices')['columns'], 'key'));
        }
    }
}
