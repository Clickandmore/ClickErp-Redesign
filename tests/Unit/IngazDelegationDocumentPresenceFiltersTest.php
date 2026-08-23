<?php

namespace Tests\Unit;

use App\Http\Controllers\admin\IngazDelegationController;
use App\Models\IngazDelegation;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class IngazDelegationDocumentPresenceFiltersTest extends TestCase
{
    public function test_invoice_presence_filter_uses_the_invoice_relation(): void
    {
        $withInvoiceSql = $this->filteredSql(['has_invoice' => '1']);
        $withoutInvoiceSql = $this->filteredSql(['has_invoice' => '0']);

        $this->assertStringContainsString('exists (select * from `invoices`', $withInvoiceSql);
        $this->assertStringContainsString('not exists (select * from `invoices`', $withoutInvoiceSql);
    }

    public function test_musaned_transfer_presence_filter_uses_the_complete_transfer_relation(): void
    {
        $withTransferSql = $this->filteredSql(['has_musaned_transfer' => '1']);
        $withoutTransferSql = $this->filteredSql(['has_musaned_transfer' => '0']);

        foreach (['`invoices`', '`musaned_transfer_items`', '`musaned_transfers`'] as $table) {
            $this->assertStringContainsString($table, $withTransferSql);
            $this->assertStringContainsString($table, $withoutTransferSql);
        }

        $this->assertStringContainsString('exists (select * from `invoices`', $withTransferSql);
        $this->assertStringContainsString('not exists (select * from `invoices`', $withoutTransferSql);
    }

    private function filteredSql(array $filters): string
    {
        $query = IngazDelegation::query();
        $method = new ReflectionMethod(IngazDelegationController::class, 'applyDocumentPresenceFilters');
        $method->invoke(app(IngazDelegationController::class), $query, Request::create('/', 'GET', $filters));

        return $query->toSql();
    }
}
