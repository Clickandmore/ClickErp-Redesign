<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\InvoiceController;
use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Models\ingaz\Invoice;
use App\Services\ingaz\RentalTransferInvoiceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class RentalTransferInvoiceTest extends TestCase
{
    public function createApplication()
    {
        $server = $_SERVER; $env = $_ENV;
        $_SERVER['DB_CONNECTION'] = $_ENV['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = $_ENV['DB_DATABASE'] = ':memory:';
        try { return parent::createApplication(); }
        finally { $_SERVER = $server; $_ENV = $env; }
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('rental_contracts', function ($table) {
            $table->id(); $table->integer('user_id'); $table->integer('maid_id')->nullable(); $table->integer('branch_id')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('status')->default('active');
            $table->string('contract_number'); $table->string('closure_type')->nullable(); $table->timestamps();
        });
        Schema::create('rental_contract_infos', function ($table) {
            $table->id(); $table->integer('rental_contract_id');
            $table->decimal('sponsorship_transfer_total_amount', 12, 2)->nullable();
            $table->decimal('sponsorship_transfer_amount', 12, 2)->nullable();
            $table->decimal('sponsorship_transfer_paid_amount', 12, 2)->nullable(); $table->timestamps();
        });
        Schema::create('invoices', function ($table) {
            $table->id(); $table->integer('client_id'); $table->integer('branch_id')->nullable();
            $table->integer('sponsorship_transfer_id')->nullable();
            $table->integer('contract_id')->nullable(); $table->integer('ingaz_delegation_id')->nullable();
            $table->string('general_description'); $table->text('line_items');
            foreach (['direct_recruitment_cost', 'external_recruitment_cost', 'government_cost', 'internal_ticket_cost',
                'subtotal', 'discount', 'vat_amount', 'total_due'] as $field) { $table->decimal($field, 12, 2); }
            foreach (['direct_recruitment_count', 'external_recruitment_count', 'government_cost_count',
                'internal_ticket_count', 'visa_recruited_count', 'insured_count'] as $field) { $table->integer($field); }
            $table->boolean('is_paid'); $table->boolean('send_to_zatca'); $table->timestamp('paid_at')->nullable(); $table->timestamps();
        });
        Schema::create('contracts', function ($table) { $table->id(); $table->integer('maid_id'); });
        Schema::create('contract_infos', function ($table) { $table->id(); $table->integer('contract_id'); });
        Schema::create('sponsorship_transfers', function ($table) {
            $table->id(); $table->integer('contract_id'); $table->integer('new_sponsor_id');
            $table->string('workflow_source'); $table->string('status'); $table->timestamps(); $table->softDeletes();
            $table->decimal('contract_value', 12, 2)->default(24000); $table->decimal('paid_amount', 12, 2)->default(0);
        });
        Schema::create('users', function ($table) { $table->id(); $table->string('name'); $table->softDeletes(); });
        Schema::create('maids', function ($table) { $table->id(); $table->softDeletes(); });
        Schema::create('maid_translations', function ($table) { $table->id(); $table->integer('maid_id'); $table->string('locale'); $table->string('name'); });
        Schema::create('nationalities', function ($table) { $table->id(); $table->softDeletes(); });
        Schema::create('nationality_translations', function ($table) { $table->id(); $table->integer('nationality_id'); $table->string('locale'); });
        DB::table('users')->insert(['id' => 9, 'name' => 'Transfer customer']);
        DB::table('maids')->insert(['id' => 12]);
        (require database_path('migrations/2026_10_06_120000_add_rental_transfer_to_invoices.php'))->up();
        foreach (['fn_bonds', 'fn_constraints'] as $name) {
            Schema::create($name, function ($table) { $table->id(); $table->decimal('amount', 12, 2); });
            DB::table($name)->insert(['amount' => 24000]);
        }
        DB::table('rental_contracts')->insert(['id' => 72, 'user_id' => 9, 'maid_id' => 12, 'branch_id' => 3,
            'contract_number' => 'RC-2026-0072', 'closure_type' => 'transferred']);
        DB::table('rental_contract_infos')->insert(['rental_contract_id' => 72,
            'sponsorship_transfer_total_amount' => 24000, 'sponsorship_transfer_amount' => 24000,
            'sponsorship_transfer_paid_amount' => 24000]);
        DB::table('contracts')->insert(['id' => 5, 'maid_id' => 12]);
        DB::table('sponsorship_transfers')->insert(['id' => 163, 'contract_id' => 5, 'new_sponsor_id' => 9,
            'workflow_source' => 'rental', 'status' => 'transferred']);
    }

    public function test_issue_documents_existing_transfer_without_posting_or_collecting_again(): void
    {
        $invoice = $this->issue();
        $this->assertSame(72, (int) $invoice->rental_transfer_contract_id);
        $this->assertSame(9, (int) $invoice->client_id);
        $this->assertSame(3, (int) $invoice->branch_id);
        $this->assertSame(24000.0, (float) $invoice->total_due);
        $this->assertSame(20869.57, (float) $invoice->subtotal);
        $this->assertSame(3130.43, (float) $invoice->vat_amount);
        $this->assertTrue($invoice->is_paid);
        $this->assertSame(0.0, (float) $invoice->rental_transfer_remaining_amount);
        foreach (['fn_bonds', 'fn_constraints'] as $name) {
            $this->assertSame(1, DB::table($name)->count());
            $this->assertSame(24000.0, (float) DB::table($name)->sum('amount'));
        }
        $this->assertSame($invoice->id, RentalContract::findOrFail(72)->transferInvoice->id);
        $this->assertSame(72, $invoice->rentalTransferContract->id);
    }

    public function test_duplicate_request_returns_existing_document_and_preserves_original_tax(): void
    {
        $invoice = $this->issue();
        $again = Model::withoutEvents(fn () => app(RentalTransferInvoiceService::class)->issue(72, 0, '2026-10-06'));
        $this->assertSame($invoice->id, $again->id);
        $this->assertSame(1, Invoice::count());
        $this->assertSame(3130.43, (float) $again->vat_amount);
    }

    public function test_partial_payment_and_zero_tax_are_taken_from_transfer_not_rent(): void
    {
        DB::table('rental_contract_infos')->update(['sponsorship_transfer_paid_amount' => 4000]);
        $invoice = $this->issue(0);
        $this->assertFalse($invoice->is_paid);
        $this->assertSame(24000.0, (float) $invoice->subtotal);
        $this->assertSame(0.0, (float) $invoice->vat_amount);
        $this->assertSame(20000.0, (float) $invoice->rental_transfer_remaining_amount);
        $method = new ReflectionMethod(InvoiceController::class, 'buildInvoiceItems');
        $method->setAccessible(true);
        $items = $method->invoke(app(InvoiceController::class), $invoice);
        $this->assertSame('rental_transfer', $items[0]['key']);
        $this->assertStringContainsString('RC-2026-0072', $items[0]['label']);
        $this->assertSame(24000.0, $items[0]['line_grand_total']);
    }

    public function test_contract_without_completed_transfer_cannot_be_invoiced(): void
    {
        DB::table('rental_contracts')->update(['closure_type' => null]);
        $this->expectException(ValidationException::class);
        $this->issue();
    }

    public function test_zero_total_cannot_be_replaced_by_the_other_transfer_amount(): void
    {
        DB::table('rental_contract_infos')->update(['sponsorship_transfer_total_amount' => 0]);
        $this->expectException(ValidationException::class);
        $this->issue();
    }

    public function test_null_legacy_total_uses_recorded_transfer_value(): void
    {
        DB::table('rental_contract_infos')->update(['sponsorship_transfer_total_amount' => null]);
        $this->assertSame(24000.0, (float) $this->issue()->total_due);
    }

    public function test_database_prevents_a_second_invoice_for_the_same_transfer(): void
    {
        $invoice = $this->issue();
        $payload = $invoice->getAttributes();
        unset($payload['id']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('invoices')->insert($payload);
    }

    public function test_action_changes_from_issue_to_view_and_print_and_respects_permissions(): void
    {
        $admin = new class extends \App\Models\auth\Admin {
            public bool $allowInvoiceActions = true;
            public function can($abilities, $arguments = []) { return $this->allowInvoiceActions; }
        };
        auth('admin')->setUser($admin);
        $contract = RentalContract::with('info')->findOrFail(72);
        $html = view('admin.ingaz.RentContract.transfer-invoice-actions', compact('contract'))->render();
        $this->assertStringContainsString(route('rental.transfer-invoice.create', 72), $html);
        $invoice = $this->issue();
        $contract->unsetRelation('transferInvoice');
        $html = view('admin.ingaz.RentContract.transfer-invoice-actions', compact('contract'))->render();
        $this->assertStringContainsString(route('invoices.show', $invoice->id), $html);
        $this->assertStringContainsString('طباعة فاتورة نقل الخدمات', $html);
        $this->assertStringNotContainsString('إصدار فاتورة نقل الخدمات', $html);
        $admin->allowInvoiceActions = false;
        $html = view('admin.ingaz.RentContract.transfer-invoice-actions', compact('contract'))->render();
        $this->assertStringNotContainsString('<a ', $html);
        auth()->forgetGuards();
    }

    public function test_routes_use_invoice_creation_permissions_for_both_form_and_write(): void
    {
        $controller = app(InvoiceController::class);
        foreach (['createRentalTransfer', 'storeRentalTransfer'] as $action) {
            $middleware = collect($controller->getMiddleware())->filter(fn ($entry) => in_array($action, $entry['options']['only'] ?? [], true))->pluck('middleware');
            $this->assertContains('permission:' . \App\PermKeys::SPONSOR_TRANSFER_CREATE_INVOICE . ',admin', $middleware->all());
        }
        $this->assertSame('GET', app('router')->getRoutes()->getByName('rental.transfer-invoice.create')->methods()[0]);
        $this->assertSame(['POST'], app('router')->getRoutes()->getByName('rental.transfer-invoice.store')->methods());
    }

    private function issue(int $vatPercent = 15): Invoice
    {
        return Model::withoutEvents(fn () => app(RentalTransferInvoiceService::class)->issue(72, $vatPercent, '2026-10-06'));
    }

    public function test_completed_transfer_uses_its_matching_rental_and_links_the_invoice_once(): void
    {
        $service = app(RentalTransferInvoiceService::class);
        $transfer = \App\Models\SponsorshipTransfer::findOrFail(163);
        $this->assertSame([72], $service->contractsForTransfer($transfer)->pluck('id')->all());
        $invoice = Model::withoutEvents(fn () => $service->issueForTransfer(163, 72, 15, '2026-10-06'));
        $again = Model::withoutEvents(fn () => $service->issueForTransfer(163, 72, 15, '2026-10-06'));
        $this->assertSame($invoice->id, $again->id);
        $this->assertSame($invoice->id, $transfer->fresh()->invoice->id);
        $this->assertSame(1, Invoice::count());
        $this->assertSame(1, DB::table('fn_bonds')->count());
        $this->assertSame(1, DB::table('fn_constraints')->count());
    }

    public function test_existing_rental_transfer_invoice_is_reused_from_the_transfer_page(): void
    {
        $existing = $this->issue();
        $invoice = Model::withoutEvents(fn () => app(RentalTransferInvoiceService::class)->issueForTransfer(163, 72, 0, '2026-10-06'));
        $this->assertSame($existing->id, $invoice->id);
        $this->assertSame(163, (int) $invoice->sponsorship_transfer_id);
        $this->assertSame(3130.43, (float) $invoice->vat_amount);
    }

    public function test_transfer_cannot_issue_against_another_customer_rental(): void
    {
        DB::table('sponsorship_transfers')->where('id', 163)->update(['new_sponsor_id' => 10]);
        $this->expectException(ValidationException::class);
        Model::withoutEvents(fn () => app(RentalTransferInvoiceService::class)->issueForTransfer(163, 72, 15, '2026-10-06'));
    }

    public function test_pending_rental_request_can_issue_without_financial_entries_and_reuses_invoice_after_completion(): void
    {
        DB::table('sponsorship_transfers')->where('id', 163)->update(['status' => 'pending']);
        DB::table('rental_contracts')->where('id', 72)->update(['closure_type' => null]);
        $this->mock(\App\Services\BranchAccessService::class)->shouldReceive('assertCanAccessModel')->once();
        $method = new ReflectionMethod(InvoiceController::class, 'storeSponsorshipTransferInvoice');
        $method->setAccessible(true);
        $request = \Illuminate\Http\Request::create('/', 'POST', [
            'sponsorship_transfer_id' => 163, 'invoice_date' => '2026-10-06',
            'line_items' => [['key' => 'licensee_cost', 'label' => 'نقل الخدمات', 'quantity' => 1, 'unit_price' => 20869.57]],
        ]);
        $constraintService = $this->mock(\Nami\Finance\Services\ConstraintService::class);
        $yearService = $this->mock(\Nami\Finance\Services\YearService::class);
        $result = Model::withoutEvents(fn () => $method->invoke(app(InvoiceController::class), $request, false, $constraintService, $yearService));
        $invoice = $result['invoice'];
        $this->assertSame(24000.0, (float) $invoice->total_due);
        $this->assertSame(72, (int) $invoice->rental_transfer_contract_id);
        $this->assertNull($result['invoice_constraint']);
        $this->assertNull($result['receipt_bond']);
        $this->assertSame(1, DB::table('fn_constraints')->count());
        $this->assertSame(1, DB::table('fn_bonds')->count());
        DB::table('sponsorship_transfers')->where('id', 163)->update(['status' => 'transferred']);
        DB::table('rental_contracts')->where('id', 72)->update(['closure_type' => 'transferred']);
        $again = Model::withoutEvents(fn () => app(RentalTransferInvoiceService::class)->issueForTransfer(163, 72, 15, '2026-10-06'));
        $this->assertSame($invoice->id, $again->id);
        $this->assertSame(1, Invoice::count());
    }

    public function test_request_invoice_rejects_a_total_different_from_the_recorded_transfer_amount(): void
    {
        $transfer = \App\Models\SponsorshipTransfer::findOrFail(163);
        $this->expectException(ValidationException::class);
        app(RentalTransferInvoiceService::class)->validateRequestInvoiceTotal($transfer, 25000);
    }

    public function test_pending_request_opens_the_invoice_form_without_waiting_for_transfer_completion(): void
    {
        DB::table('sponsorship_transfers')->where('id', 163)->update(['status' => 'pending']);
        $this->mock(\App\Services\BranchAccessService::class)->shouldReceive('assertCanAccessModel')->once();
        $view = app(InvoiceController::class)->createSponsorshipTransfer(163);
        $this->assertSame('admin.ingaz.SponsorshipTransfer.invoice-create', $view->name());
        $this->assertSame(24000.0, $view->getData()['grossContractValue']);
    }

    public function test_transfer_invoice_permission_is_registered_once_under_transfer_permissions(): void
    {
        Schema::create('permissions', function ($table) {
            $table->id(); $table->string('name'); $table->string('guard_name');
            $table->string('display_name'); $table->string('group_name'); $table->string('section_name'); $table->boolean('is_active');
        });
        $migration = require database_path('migrations/2026_10_06_130000_add_sponsor_transfer_create_invoice_permission.php');
        $migration->up(); $migration->up();
        $permission = DB::table('permissions')->where('name', \App\PermKeys::SPONSOR_TRANSFER_CREATE_INVOICE)->first();
        $this->assertSame(1, DB::table('permissions')->count());
        $this->assertSame('إضافة فاتورة لنقل الخدمات', $permission->display_name);
        $this->assertSame('sponsor_transfer', $permission->group_name);
        $this->assertSame('admin', $permission->guard_name);
    }

    public function test_general_and_transfer_invoice_permissions_are_independent_for_the_shared_post_route(): void
    {
        $admin = new class extends \App\Models\auth\Admin {
            public array $allowed = [];
            public function can($abilities, $arguments = []) { return in_array($abilities, $this->allowed, true); }
        };
        auth('admin')->setUser($admin);
        foreach ([\App\PermKeys::INVOICES_CREATE, \App\PermKeys::SPONSOR_TRANSFER_CREATE_INVOICE] as $grant) {
            $admin->allowed = [$grant];
            $this->assertSame($grant === \App\PermKeys::INVOICES_CREATE, \App\Support\InvoiceAuthorization::canCreate());
            $this->assertSame($grant === \App\PermKeys::SPONSOR_TRANSFER_CREATE_INVOICE, \App\Support\InvoiceAuthorization::canCreateTransfer());
            $payload = $grant === \App\PermKeys::INVOICES_CREATE ? ['sponsorship_transfer_id' => 163] : ['client_id' => 9];
            try {
                app(InvoiceController::class)->store(\Illuminate\Http\Request::create('/', 'POST', $payload),
                    $this->mock(\Nami\Finance\Services\ConstraintService::class),
                    $this->mock(\Nami\Finance\Services\YearService::class),
                    $this->mock(\Nami\Finance\Services\BondService::class));
                $this->fail('The unrelated invoice permission must not allow issuance.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $error) {
                $this->assertSame(403, $error->getStatusCode());
            }
        }
        $matrix = \App\Support\AdminRoutePermissionMatrix::class;
        $this->assertSame([\App\PermKeys::SPONSOR_TRANSFER_CREATE_INVOICE], $matrix::resolve('invoices.store', \Illuminate\Http\Request::create('/', 'POST', ['sponsorship_transfer_id' => 163])));
        $this->assertSame([\App\PermKeys::INVOICES_CREATE, \App\PermKeys::INVOICES_CREATE_DRAFT], $matrix::resolve('invoices.store', \Illuminate\Http\Request::create('/', 'POST', ['client_id' => 9])));
        $this->assertSame([\App\PermKeys::SPONSOR_TRANSFER_CREATE_INVOICE], $matrix::resolve('SponsorshipTransfer.invoice.create'));
        auth()->forgetGuards();
    }
}
