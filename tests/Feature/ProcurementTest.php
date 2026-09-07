<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Services\ProcurementService;
use Nami\Finance\Models\{Account, Supplier, FnYear, ProcurementDocument};
use App\Models\auth\Admin;
class ProcurementTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;
    private function data(string $kind = 'invoice'): array
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');
        $makeAccount = fn($type, $name) => Account::create(['name' => $name, 'code' => random_int(80000000, 90000000), 'type' => $type, 'status' => 'debtor', 'level' => 1, 'created_by_id' => $admin->id]);
        $account = $makeAccount(8, 'مصروف مشتريات اختبار');
        $supplierAccount = $makeAccount(13, 'مورد اختبار');
        $supplier = Supplier::create(['name' => 'مورد اختبار', 'code' => (string) Str::uuid(), 'phone' => '0500000000', 'account_id' => $supplierAccount->id]);
        $tax = $makeAccount(10, 'ضريبة مشتريات اختبار');
        $makeAccount(17, 'خزينة اختبار');
        $year = FnYear::create(['title' => 'اختبار', 'status' => 1, 'from_date' => '2026-01-01', 'to_date' => '2026-12-31']);
        return ['kind' => $kind, 'supplier_id' => $supplier->id, 'date' => $year->to_date, 'tax_account_id' => $tax->id, 'lines' => [['description' => 'خدمة اختبار', 'account_id' => $account->id, 'quantity' => 1, 'unit_price' => 100, 'discount' => 0, 'tax_rate' => 15]]];
    }
    public function test_request_conversion_posting_and_partial_payment(): void
    {
        $service = app(ProcurementService::class);
        $request = $service->save($this->data('request'));
        $service->transition($request->id, 'approve');
        $invoice = $service->transition($request->id, 'convert');
        $this->assertEquals(115, $invoice->total);
        $invoice = $service->transition($invoice->id, 'approve');
        $details = $invoice->constraint->constraintDetails;
        $this->assertEquals(115, $details->sum('debtor_value'));
        $this->assertEquals(115, $details->sum('creditor_value'));
        $this->assertEquals(15, $details->where('account_id', $invoice->tax_account_id)->sum('debtor_value'));
        $cash = ProcurementService::cashAccounts()->firstOrFail();
        $payment = ['amount' => 40, 'date' => $invoice->date, 'account_id' => $cash->id, 'token' => (string) Str::uuid()];
        $service->pay($invoice->id, $payment);
        $service->pay($invoice->id, $payment);
        $invoice->refresh();
        $this->assertEquals(75, $invoice->balance);
        $this->assertCount(1, $invoice->payments);
        $this->expectException(ValidationException::class);
        $service->pay($invoice->id, ['amount' => 76, 'date' => $invoice->date, 'account_id' => $cash->id, 'token' => (string) Str::uuid()]);
    }
    public function test_conversion_cannot_be_repeated(): void
    {
        $service = app(ProcurementService::class);
        $doc = $service->save($this->data('request'));
        $service->transition($doc->id, 'approve');
        $service->transition($doc->id, 'convert');
        $this->expectException(ValidationException::class);
        $service->transition($doc->id, 'convert');
    }
    public function test_posted_invoice_cannot_be_edited_or_posted_twice(): void
    {
        $service = app(ProcurementService::class);
        $data = $this->data();
        $doc = $service->save($data);
        $service->transition($doc->id, 'approve');
        try {
            $service->save($data, $doc->id);
            $this->fail('Posted document was editable');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
        $this->expectException(ValidationException::class);
        $service->transition($doc->id, 'approve');
    }
    public function test_rounding_and_discount_are_applied_before_tax(): void
    {
        $result = ProcurementService::calculate([['description' => 'خدمة', 'account_id' => 1, 'quantity' => 2, 'unit_price' => 100, 'discount' => 20, 'tax_rate' => 15]]);
        $this->assertEquals(['subtotal' => 200, 'discount' => 20, 'tax' => 27, 'total' => 207], $result['totals']);
        $this->assertEquals(180, $result['lines'][0]['net']);
    }
    public function test_invalid_discount_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        ProcurementService::calculate([['description' => 'خدمة', 'account_id' => 1, 'quantity' => 1, 'unit_price' => 100, 'discount' => 101, 'tax_rate' => 15]]);
    }
    public function test_permissions_do_not_treat_approval_or_payment_as_read(): void
    {
        $middleware = new \App\Http\Middleware\EnsurePackageRoutePermission();
        $this->assertSame(['finance_procurement_approve', 'finance_module'], $middleware->resolvePermissions('finance.procurement.approve', 'finance'));
        $this->assertSame(['finance_procurement_pay', 'finance_module'], $middleware->resolvePermissions('finance.procurement.pay', 'finance'));
    }
    public function test_screens_render_and_branch_access_is_enforced(): void
    {
        $data = $this->data();
        $this->enableBranchesModule();
        $branch = $this->makeBranch();
        $other = $this->makeBranch();
        $admin = $this->branchAdmin($branch);
        $this->actingAs($admin, 'admin');
        $doc = app(ProcurementService::class)->save($data + ['branch_id' => $branch->id]);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $controller = app(\Nami\Finance\Http\Controllers\ProcurementController::class);
        $this->assertStringContainsString($doc->number, $controller->show($doc->id)->render());
        $form = $controller->create(new \Illuminate\Http\Request())->render();
        $this->assertStringContainsString('purchase-lines', $form);
        $this->assertStringNotContainsString('@selected(', $form);
        $this->assertStringNotContainsString('@disabled(', $form);
        $this->assertStringContainsString($doc->number, $controller->index(new \Illuminate\Http\Request())->render());
        $doc->update(['branch_id' => $other->id]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->show($doc->id);
    }
    public function test_invalid_tax_account_rolls_back_posting(): void
    {
        $data = $this->data();
        $data['tax_account_id'] = null;
        $service = app(ProcurementService::class);
        $doc = $service->save($data);
        try {
            $service->transition($doc->id, 'approve');
            $this->fail('Missing tax account accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('account_id', $e->errors());
        }
        $doc->refresh();
        $this->assertSame('draft', $doc->status);
        $this->assertNull($doc->constraint_id);
    }
    public function test_general_supplier_needs_no_office_agent_or_contract_for_requests_and_invoices(): void
    {
        $data = $this->data();
        $data['contract_id'] = null;
        $data['tax_account_id'] = null;
        $data['lines'][0]['tax_rate'] = 0;
        $data['lines'][0]['description'] = 'شراء مستلزمات مكتبية من مورد عام';
        $controller = app(\Nami\Finance\Http\Controllers\ProcurementController::class);
        $service = app(ProcurementService::class);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        foreach (['request', 'invoice'] as $kind) {
            $data['kind'] = $kind;
            $response = $controller->store(\Illuminate\Http\Request::create('/finance/procurement', 'POST', $data));
            $doc = ProcurementDocument::where('kind', $kind)->latest('id')->firstOrFail();
            $this->assertEquals(302, $response->getStatusCode());
            $this->assertNull($doc->contract_id);
            $this->assertEquals($data['supplier_id'], $doc->supplier_id);
            $doc = $service->transition($doc->id, 'approve');
            if ($kind === 'request') {
                $doc = $service->transition($doc->id, 'convert');
                $doc = $service->transition($doc->id, 'approve');
            }
            $this->assertEquals(100, $doc->total);
            $this->assertEquals(0, $doc->tax);
            $this->assertStringContainsString('إضافة دفعة', $controller->show($doc->id)->render());
            $service->pay($doc->id, ['amount' => 100, 'date' => $doc->date, 'account_id' => ProcurementService::cashAccounts()->firstOrFail()->id, 'token' => (string) Str::uuid()]);
            $this->assertEquals(0, $doc->fresh()->balance);
            $this->assertStringContainsString('مدفوعة', $controller->show($doc->id)->render());
        }
    }
}
