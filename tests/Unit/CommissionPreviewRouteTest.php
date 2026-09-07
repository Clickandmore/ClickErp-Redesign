<?php

namespace Tests\Unit;

use App\Models\commissions\CommissionMarketerOverride;
use App\Models\commissions\CommissionSetting;
use Tests\TestCase;

class CommissionPreviewRouteTest extends TestCase
{
    public function test_preview_route_accepts_direct_get_and_form_post(): void
    {
        $route = app('router')->getRoutes()->getByName('commissions.marketers.preview');

        $this->assertNotNull($route);
        $this->assertContains('GET', $route->methods());
        $this->assertContains('POST', $route->methods());
    }

    public function test_bulk_application_defaults_to_a_fixed_amount(): void
    {
        $view = file_get_contents(resource_path('views/commissions/calculation/preview.blade.php'));

        $this->assertStringContainsString('<option value="fixed" selected>مبلغ ثابت</option>', $view);
        $this->assertStringContainsString("\$('#bulkMethod').val('fixed');", $view);
        $this->assertStringContainsString("\$('#bulkValue').val(@json(\$bulkDefaultValue));", $view);
        $this->assertStringContainsString("\$('.method-input').val('fixed');", $view);
        $this->assertStringContainsString("headers: { 'Accept': 'application/json' }", $view);
        $this->assertStringContainsString('id="approvalError"', $view);

        $settingsView = file_get_contents(resource_path('views/commissions/settings/index.blade.php'));
        $this->assertStringContainsString("(!\$s || \$s->method === 'fixed')", $settingsView);
        $this->assertStringContainsString('<option value="fixed" selected>مبلغ ثابت</option>', $settingsView);
        $this->assertSame('fixed', (new CommissionSetting())->method);
        $this->assertSame('fixed', (new CommissionMarketerOverride())->method);

        $calculationService = file_get_contents(app_path('Services/commissions/CommissionCalculationService.php'));
        $this->assertStringContainsString("\$method = \$adjustment['method'] ?? 'fixed';", $calculationService);
    }

    public function test_payments_page_links_approval_batches_to_their_accrual_constraints(): void
    {
        $view = file_get_contents(resource_path('views/commissions/payments/index.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/commissions/CommissionPaymentController.php'));

        $this->assertStringContainsString("->with('accrualConstraint')", $controller);
        $this->assertStringContainsString("route('finance.add-constraint.show', \$accrualConstraint->id)", $view);
        $this->assertStringContainsString("route('finance.constraints.pending'", $view);
        $this->assertStringContainsString('قيود اعتماد العمولات', $view);

        $constraintController = file_get_contents(base_path('packages/finance/src/Http/Controllers/ConstraintController.php'));
        $this->assertStringContainsString("redirect()->route('finance.add-constraint.show', \$id)", $constraintController);
    }
}
