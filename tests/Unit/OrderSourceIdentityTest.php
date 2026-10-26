<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OrderSourceIdentityTest extends TestCase
{
    public function test_orders_page_exposes_source_filter_cards_and_column(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/ingaz/orders/index.blade.php');
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/ingaz/OrderController.php');
        $model = file_get_contents(dirname(__DIR__, 2).'/app/Models/ingaz/Order.php');
        $migration = file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_10_12_120000_backfill_and_index_order_creators.php');

        $this->assertStringContainsString('طلبات الموقع الخارجي', $view);
        $this->assertStringContainsString('طلبات موظفي الفروع', $view);
        $this->assertStringContainsString('<th>مصدر الطلب</th>', $view);
        $this->assertStringContainsString('"data": "order_source"', $view);
        $this->assertStringContainsString("source === 'employee'", $controller);
        $this->assertStringContainsString("source === 'external'", $controller);
        $this->assertStringContainsString('createdByEmployee()', $controller);
        $this->assertStringContainsString('createdExternally()', $controller);
        $this->assertStringContainsString('resolveCreatorAdmin()', $model);
        $this->assertStringContainsString('orders_created_by_admin_id_index', $migration);
        $this->assertStringContainsString('activity_log', $migration);
        $this->assertStringContainsString("->with('source_counts'", $controller);
        $this->assertStringContainsString('sourceCountExternal', $view);
    }
}
