<?php

namespace Tests\Feature\Finance;

use App\Enums\AdminTypeisEnum;
use App\Enums\ingaz\ContractStatusEnum;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\User as ClientUser;
use App\Support\ConstraintReferenceGuard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\FnYear;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;
use Tests\TestCase;

/**
 * حارس حذف القيود: القيد اليدوي يُحذف، أما القيد الآلي المرتبط بمستند
 * (فاتورة، استرداد عقد، ...) فيُرفض برسالة عربية ويُعكس بدلاً من حذفه.
 */
class ConstraintDeletionGuardTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
        $this->ensureActiveFiscalYear();
        $this->ensureConstraintNumberSequence();
        ConstraintReferenceGuard::flushSchemaCache();
    }

    public function test_a_manual_constraint_with_no_references_is_deleted(): void
    {
        $constraint = $this->storeManualConstraint('قيد يدوي للحذف');

        $this->assertFalse(ConstraintReferenceGuard::isReferenced($constraint->id));
        $this->assertSame([], ConstraintReferenceGuard::referencesFor($constraint->id));

        app(ConstraintService::class)->deleteConstraint($constraint->id);

        $this->assertNull(Constraint::query()->find($constraint->id));
        $this->assertNotNull(DB::table('fn_constraints')->where('id', $constraint->id)->value('deleted_at'));
        $this->assertSame(0, DB::table('fn_constraint_details')->where('constraint_id', $constraint->id)->whereNull('deleted_at')->count());
    }

    public function test_a_constraint_referenced_by_an_invoice_is_refused_with_an_arabic_message(): void
    {
        $constraint = $this->storeManualConstraint('قيد فاتورة استقدام');
        $invoice = $this->createInvoice();
        DB::table('invoices')->where('id', $invoice->id)->update(['invoice_constraint_id' => $constraint->id]);

        $references = ConstraintReferenceGuard::referencesFor($constraint->id);
        $this->assertCount(1, $references);
        $this->assertSame('invoices', $references[0]['table']);
        $this->assertSame('invoice_constraint_id', $references[0]['column']);
        $this->assertSame($invoice->id, $references[0]['id']);
        $this->assertSame('فاتورة', $references[0]['label']);

        $this->assertSame(
            'هذا قيد آلي مرتبط بـ فاتورة #' . $invoice->id . '. القيود الآلية تُعكس ولا تُحذف.',
            ConstraintReferenceGuard::describe($constraint->id)
        );

        try {
            app(ConstraintService::class)->deleteConstraint($constraint->id);
            $this->fail('Expected a ValidationException for a referenced constraint.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['هذا قيد آلي مرتبط بـ فاتورة #' . $invoice->id . '. القيود الآلية تُعكس ولا تُحذف.'],
                $exception->errors()['constraint']
            );
        }

        $this->assertNotNull(Constraint::query()->find($constraint->id));
        $this->assertNull(DB::table('fn_constraints')->where('id', $constraint->id)->value('deleted_at'));
    }

    public function test_the_destroy_endpoint_returns_422_json_for_a_referenced_constraint(): void
    {
        $constraint = $this->storeManualConstraint('قيد فاتورة لنقطة النهاية');
        $invoice = $this->createInvoice();
        DB::table('invoices')->where('id', $invoice->id)->update(['invoice_constraint_id' => $constraint->id]);

        // مسار finance محمي بـ package.route.permission:finance؛ المطوّر يتجاوزه.
        $developer = Admin::factory()->create(['admin_type' => AdminTypeisEnum::Developer->value]);
        $response = $this->actingAs($developer, 'admin')
            ->deleteJson(route('finance.constraints.destroy', $constraint->id));

        $response->assertStatus(422);
        $response->assertJsonPath('code', 422);
        $response->assertJsonPath('message', 'هذا قيد آلي مرتبط بـ فاتورة #' . $invoice->id . '. القيود الآلية تُعكس ولا تُحذف.');
        $this->assertNull(DB::table('fn_constraints')->where('id', $constraint->id)->value('deleted_at'));
    }

    public function test_a_constraint_referenced_by_a_contract_refund_is_refused(): void
    {
        $constraint = $this->storeManualConstraint('قيد استرداد عقد');
        $invoice = $this->createInvoice();
        $refundId = DB::table('recruitment_contract_refunds')->insertGetId([
            'contract_id' => $invoice->contract_id,
            'invoice_id' => $invoice->id,
            'refund_constraint_id' => $constraint->id,
            'refund_date' => now()->toDateString(),
            'created_by_id' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue(ConstraintReferenceGuard::isReferenced($constraint->id));
        $this->assertSame(
            'هذا قيد آلي مرتبط بـ استرداد عقد استقدام #' . $refundId . '. القيود الآلية تُعكس ولا تُحذف.',
            ConstraintReferenceGuard::describe($constraint->id)
        );

        $this->expectException(ValidationException::class);
        app(ConstraintService::class)->deleteConstraint($constraint->id);
    }

    public function test_the_force_flag_bypasses_the_guard(): void
    {
        $constraint = $this->storeManualConstraint('قيد آلي يُحذف قسراً');
        $invoice = $this->createInvoice();
        DB::table('invoices')->where('id', $invoice->id)->update(['invoice_constraint_id' => $constraint->id]);

        $this->assertTrue(ConstraintReferenceGuard::isReferenced($constraint->id));

        app(ConstraintService::class)->deleteConstraint($constraint->id, true);

        $this->assertNull(Constraint::query()->find($constraint->id));
        $this->assertNotNull(DB::table('fn_constraints')->where('id', $constraint->id)->value('deleted_at'));
    }

    public function test_multiple_references_are_listed_once_per_document(): void
    {
        $constraint = $this->storeManualConstraint('قيد مرتبط بأكثر من مستند');
        $invoice = $this->createInvoice();
        DB::table('invoices')->where('id', $invoice->id)->update([
            'invoice_constraint_id' => $constraint->id,
            'arrival_revenue_constraint_id' => $constraint->id,
        ]);
        $transferId = DB::table('musaned_transfers')->insertGetId([
            'reference_number' => 'GUARD-' . uniqid(),
            'transfer_date' => now()->toDateString(),
            'settlement_constraint_id' => $constraint->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertCount(3, ConstraintReferenceGuard::referencesFor($constraint->id));
        $this->assertSame(
            'هذا قيد آلي مرتبط بـ فاتورة #' . $invoice->id . '، تحويل مساند #' . $transferId . '. القيود الآلية تُعكس ولا تُحذف.',
            ConstraintReferenceGuard::describe($constraint->id)
        );
    }

    private function storeManualConstraint(string $statement): Constraint
    {
        $debtor = $this->createAccount('حساب مدين ' . uniqid(), 'debtor');
        $creditor = $this->createAccount('حساب دائن ' . uniqid(), 'creditor');

        $service = app(ConstraintService::class);
        $year = app(YearService::class)->checkDateRange(now()->toDateString());
        $rows = [
            [$debtor->id, 'debtor', 100, 0],
            [$creditor->id, 'creditor', 0, 100],
        ];
        $data = [
            'code' => $service->lastCode(),
            'date' => now()->toDateString(),
            'statement' => $statement,
            'account_id' => array_column($rows, 0),
            'account_type' => array_column($rows, 1),
            'debtor_value' => array_column($rows, 2),
            'creditor_value' => array_column($rows, 3),
            'statement_account' => array_fill(0, count($rows), $statement),
            'cost_center_required' => 'off',
            'cost_center_id' => array_fill(0, count($rows), null),
            'value' => 100,
        ];

        $constraint = $service->storeConstraint($data, $year);
        $service->storeConstraintDetails($data['account_id'], $constraint->id, $data);

        return $constraint;
    }

    private function createAccount(string $name, string $status): Account
    {
        $code = ((int) DB::table('fn_accounts')->max('code')) + random_int(100, 999);

        return Account::query()->create([
            'name' => $name,
            'code' => $code,
            'parent_id' => null,
            'level' => 1,
            'operation_type' => 1,
            'status' => $status,
            'statement_type' => 'income_statement',
            'type' => 1,
            'created_by_id' => $this->admin->id,
        ]);
    }

    private function createInvoice(): Invoice
    {
        $user = ClientUser::query()->create([
            'name' => 'عميل حارس القيود',
            'email' => 'guard' . uniqid() . '@example.test',
            'phone' => '05' . random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
        ]);

        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::New->value,
            'type' => 'recruitment',
            'user_id' => $user->id,
            'marketer_id' => $this->admin->id,
        ]);

        return Invoice::query()->create([
            'contract_id' => $contract->id,
            'direct_recruitment_cost' => 285,
            'direct_recruitment_count' => 1,
            'external_recruitment_cost' => 3653,
            'external_recruitment_count' => 1,
            'government_cost' => 0,
            'government_cost_count' => 0,
            'internal_ticket_cost' => 0,
            'internal_ticket_count' => 0,
            'subtotal' => 3938,
            'discount' => 0,
            'vat_amount' => 42.75,
            'total_due' => 3980.75,
            'is_paid' => true,
            'send_to_zatca' => false,
        ]);
    }

    private function ensureActiveFiscalYear(): void
    {
        $exists = FnYear::query()
            ->whereDate('from_date', '<=', now())
            ->whereDate('to_date', '>=', now())
            ->where('status', 1)
            ->exists();

        if (! $exists) {
            FnYear::query()->create([
                'title' => 'اختبار ' . now()->year,
                'from_date' => now()->startOfYear()->toDateString(),
                'to_date' => now()->endOfYear()->toDateString(),
                'status' => 1,
                'created_by_id' => $this->admin->id,
            ]);
        }
    }

    private function ensureConstraintNumberSequence(): void
    {
        if (DB::table('fn_constraint_code_sequences')->where('id', 1)->exists()) {
            return;
        }

        DB::table('fn_constraint_code_sequences')->insert([
            'id' => 1,
            'last_code' => (int) DB::table('fn_constraints')->max('code'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
