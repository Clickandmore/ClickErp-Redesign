<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Models\IngazDelegation;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\User;
use App\Models\Permission;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DelegationReplacementInvoiceTest extends TestCase
{
    use DatabaseTransactions;

    private IngazDelegation $delegation;
    private Invoice $oldInvoice;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $branch = Branch::factory()->main()->create();
        $actor = Admin::factory()->forBranch($branch)->create();
        foreach ([PermKeys::INVOICES_CREATE, PermKeys::INVOICES_CONTROL, PermKeys::INGAZ_DELEGATIONS_VIEW] as $permission) {
            $actor->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'admin']));
        }
        $this->actingAs($actor, 'admin');
        $client = User::withoutEvents(fn () => User::create(['name' => 'Replacement test', 'phone' => uniqid('replacement-')]));
        $this->delegation = IngazDelegation::withoutEvents(fn () => IngazDelegation::create([
            'user_id' => $client->id, 'branch_id' => $branch->id, 'ingaz_number' => uniqid('replacement-'),
            'status' => 'under_process', 'delegation_date_gregorian' => now()->toDateString(),
        ]));
        $this->oldInvoice = Invoice::withoutEvents(fn () => Invoice::create([
            'ingaz_delegation_id' => $this->delegation->id, 'branch_id' => $branch->id, 'total_due' => 115,
        ]));
        DB::table('fn_confirm_settings')->where('title', 'automatic_journal_entries')->delete();
        DB::table('fn_confirm_settings')->insert(['title' => 'automatic_journal_entries', 'value' => 'off']);
    }

    private function credit(float $total): void
    {
        DB::table('recruitment_invoice_credit_notes')->insert([
            'invoice_id' => $this->oldInvoice->id, 'ingaz_delegation_id' => $this->delegation->id,
            'issue_date' => now()->toDateString(), 'reason' => 'Replacement test', 'total' => $total,
        ]);
    }

    private function payload(): array
    {
        return ['ingaz_delegation_id' => $this->delegation->id, 'invoice_date' => now()->toDateString(),
            'direct_recruitment_cost' => 100, 'direct_recruitment_count' => 1,
            'external_recruitment_cost' => 100, 'external_recruitment_count' => 1, 'send_to_zatca' => false];
    }

    public function test_full_credit_allows_one_replacement_and_preserves_history(): void
    {
        $this->credit(115);
        $this->get(route('ingaz-delegations.show', $this->delegation->id))->assertOk()->assertSee('إضافة فاتورة أخرى')
            ->assertSee('مغطاة بإشعار دائن كامل')->assertSee('الصافي بعد الإشعارات');
        $this->get(route('invoices.create', ['ingaz_delegation_id' => $this->delegation->id]))->assertOk();
        $this->postJson(route('invoices.store'), $this->payload())->assertOk()->assertJsonPath('success', true);
        $this->assertSame(2, $this->delegation->invoices()->count());
        $new = $this->delegation->fresh()->invoice;
        $this->assertNotEquals($this->oldInvoice->id, $new->id);
        $this->assertEquals($this->delegation->branch_id, $new->branch_id);
        $this->get(route('ingaz-delegations.show', $this->delegation->id))->assertOk()
            ->assertSee('فواتير التفويض السابقة والحالية')->assertDontSee('إضافة فاتورة أخرى');
        $this->postJson(route('invoices.store'), $this->payload())->assertUnprocessable()->assertJsonPath('message', 'هذا التفويض لديه فاتورة سابقة');
        $this->assertSame(2, $this->delegation->invoices()->count());
    }

    public function test_partial_credit_and_no_credit_block_creation(): void
    {
        $this->postJson(route('invoices.store'), $this->payload())->assertUnprocessable()->assertJsonPath('message', 'هذا التفويض لديه فاتورة سابقة');
        $this->credit(114.99);
        $this->postJson(route('invoices.store'), $this->payload())->assertUnprocessable()->assertJsonPath('message', 'هذا التفويض لديه فاتورة سابقة');
        $this->assertTrue($this->delegation->blockingInvoices()->exists());
        $this->assertSame(1, $this->delegation->invoices()->count());
    }

    public function test_multiple_credit_notes_must_cover_entire_invoice(): void
    {
        $this->credit(100);
        $this->assertTrue($this->delegation->blockingInvoices()->exists());
        $this->credit(15);
        $this->assertFalse($this->delegation->blockingInvoices()->exists());
    }

    public function test_fully_credited_invoice_is_excluded_from_musaned_selection(): void
    {
        $this->credit(115);
        $query = app(\App\Http\Controllers\ingaz\MusanedTransferController::class)->getEligibleInvoicesQuery();
        $this->assertFalse($query->whereKey($this->oldInvoice->id)->exists());
    }
}
