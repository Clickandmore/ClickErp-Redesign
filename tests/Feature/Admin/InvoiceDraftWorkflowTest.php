<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\User;
use App\Models\InvoiceDraft;
use App\Models\Permission;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceDraftWorkflowTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private Admin $actor;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->actor = Admin::factory()->forBranch(Branch::factory()->main()->create())->create();
        $this->client = User::withoutEvents(fn () => User::create(['name' => 'عميل مسودات الاختبار', 'phone' => uniqid('draft-')]));
        DB::table('fn_confirm_settings')->updateOrInsert(['title' => 'automatic_journal_entries'], ['value' => 'off']);
        $this->actingAs($this->actor, 'admin');
    }

    private function grant(string ...$names): void
    {
        foreach ($names as $name) $this->actor->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']));
    }

    private function payload(): array
    {
        return ['client_id' => $this->client->id, 'invoice_date' => now()->toDateString(),
            'direct_recruitment_cost' => 100, 'direct_recruitment_count' => 1, 'send_to_zatca' => false];
    }

    private function submit(): InvoiceDraft
    {
        $response = $this->postJson(route('invoices.store'), $this->payload())->assertOk()->assertJsonPath('is_draft', true);
        return InvoiceDraft::findOrFail($response->json('draft_id'));
    }

    public function test_draft_permission_saves_request_without_financial_effects(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE_DRAFT);
        $invoices = Invoice::count();
        $constraints = DB::table('fn_constraints')->count();
        $this->get(route('invoices.create'))->assertOk()->assertSee('حفظ وإرسال للاعتماد');
        $draft = $this->submit();
        $this->assertSame('pending', $draft->status);
        $this->assertSame($this->actor->id, $draft->created_by);
        $this->assertEquals(115, $draft->payload['total_due']);
        $this->assertSame($invoices, Invoice::count());
        $this->assertSame($constraints, DB::table('fn_constraints')->count());
        $this->get(route('invoice-drafts.index'))->assertOk()->assertSee('عميل مسودات الاختبار');
        $this->post(route('invoice-drafts.approve', $draft->id))->assertForbidden();
    }

    public function test_direct_issue_takes_priority_over_draft_permission(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE_DRAFT, PermKeys::INVOICES_CREATE);
        $before = InvoiceDraft::count();
        $this->postJson(route('invoices.store'), $this->payload())->assertOk()->assertJsonPath('success', true)->assertJsonMissing(['is_draft' => true]);
        $this->assertSame($before, InvoiceDraft::count());
        $this->assertTrue(Invoice::where('client_id', $this->client->id)->exists());
    }

    public function test_approver_can_issue_once_without_direct_create_permission(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE_DRAFT);
        $draft = $this->submit();
        $this->grant(PermKeys::INVOICES_APPROVE);
        $this->post(route('invoice-drafts.approve', $draft->id))->assertRedirect();
        $draft->refresh();
        $this->assertSame('approved', $draft->status);
        $this->assertNotNull($draft->invoice_id);
        $this->assertSame($this->actor->id, $draft->reviewed_by);
        $this->assertEquals($draft->payload['total_due'], Invoice::findOrFail($draft->invoice_id)->total_due);
        $this->post(route('invoice-drafts.approve', $draft->id))->assertStatus(409);
        $this->assertSame(1, Invoice::where('client_id', $this->client->id)->count());
    }

    public function test_return_requires_reason_and_cannot_be_approved(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE_DRAFT, PermKeys::INVOICES_APPROVE);
        $draft = $this->submit();
        $this->postJson(route('invoice-drafts.return', $draft->id), [])->assertUnprocessable();
        $this->post(route('invoice-drafts.return', $draft->id), ['review_note' => 'تصحيح الوصف'])->assertRedirect();
        $this->assertSame('returned', $draft->fresh()->status);
        $this->assertSame('تصحيح الوصف', $draft->fresh()->review_note);
        $this->post(route('invoice-drafts.approve', $draft->id))->assertStatus(409);
        $this->assertFalse(Invoice::where('client_id', $this->client->id)->exists());
    }

    public function test_view_permission_does_not_allow_approval_or_creation(): void
    {
        $this->grant(PermKeys::INVOICES_DRAFTS_VIEW);
        $this->get(route('invoice-drafts.index'))->assertOk();
        $this->post(route('invoice-drafts.approve', 1))->assertForbidden();
        $this->postJson(route('invoices.store'), $this->payload())->assertForbidden();
    }

    public function test_creator_only_sees_own_requests_without_review_permission(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE_DRAFT);
        $draft = $this->submit();
        $other = Admin::factory()->forBranch($this->actor->branch)->create();
        $other->givePermissionTo(Permission::findByName(PermKeys::INVOICES_CREATE_DRAFT, 'admin'));
        $this->actingAs($other, 'admin')->get(route('invoice-drafts.index'))->assertOk()->assertDontSee('طلب #' . $draft->id);
    }

    public function test_contract_pending_request_is_unique_and_eligibility_is_rechecked(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE_DRAFT, PermKeys::INVOICES_EDIT_LINE_ITEMS, PermKeys::INVOICES_APPROVE);
        $contract = Contract::withoutEvents(fn () => Contract::create(['user_id' => $this->client->id, 'status' => 'new', 'type' => 'recruitment', 'branch_id' => $this->actor->branch_id]));
        $payload = array_merge($this->payload(), ['client_id' => null, 'contract_id' => $contract->id, 'external_recruitment_cost' => 100]);
        $response = $this->postJson(route('invoices.store'), $payload)->assertOk()->assertJsonPath('is_draft', true);
        $this->postJson(route('invoices.store'), $payload)->assertUnprocessable();
        Contract::withoutEvents(fn () => $contract->update(['status' => 'completed']));
        $this->postJson(route('invoice-drafts.approve', $response->json('draft_id')))->assertUnprocessable();
        $this->assertSame('pending', InvoiceDraft::findOrFail($response->json('draft_id'))->status);
        $this->assertFalse(Invoice::where('contract_id', $contract->id)->exists());
    }

    public function test_review_is_restricted_to_the_active_branch(): void
    {
        $this->enableBranchesModule();
        $this->lockToOwnBranch($this->actor);
        $this->grant(PermKeys::INVOICES_APPROVE);
        $otherBranch = Branch::factory()->create();
        $draft = InvoiceDraft::create(['created_by' => $this->actor->id, 'branch_id' => $otherBranch->id, 'payload' => $this->payload()]);
        $this->get(route('invoice-drafts.index'))->assertOk()->assertDontSee('طلب #' . $draft->id);
        $this->post(route('invoice-drafts.approve', $draft->id))->assertNotFound();
        $this->post(route('invoice-drafts.return', $draft->id), ['review_note' => 'تصحيح'])->assertNotFound();
    }

    public function test_index_filters_by_status_search_and_date(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE_DRAFT, PermKeys::INVOICES_APPROVE);
        $pending = $this->submit();
        $returned = InvoiceDraft::create(['created_by' => $this->actor->id, 'branch_id' => $this->actor->branch_id, 'status' => 'returned',
            'payload' => array_merge($this->payload(), ['client_id' => 0])]);
        $this->get(route('invoice-drafts.index', ['status' => 'pending']))->assertOk()->assertSee('طلب #' . $pending->id . '<', false)->assertDontSee('طلب #' . $returned->id . '<', false);
        $this->get(route('invoice-drafts.index', ['status' => 'returned']))->assertOk()->assertSee('طلب #' . $returned->id . '<', false)->assertDontSee('طلب #' . $pending->id . '<', false);
        $this->get(route('invoice-drafts.index', ['search' => 'عميل مسودات الاختبار']))->assertOk()->assertSee('طلب #' . $pending->id . '<', false)->assertDontSee('طلب #' . $returned->id . '<', false);
        $this->get(route('invoice-drafts.index', ['search' => (string) $returned->id]))->assertOk()->assertSee('طلب #' . $returned->id . '<', false);
        $this->get(route('invoice-drafts.index', ['date_from' => now()->addDay()->toDateString()]))->assertOk()->assertDontSee('طلب #' . $pending->id . '<', false);
        $this->get(route('invoice-drafts.index', ['status' => 'bogus']))->assertRedirect();
    }

    public function test_supplier_total_is_only_rendered_with_its_specific_permission(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE_DRAFT, PermKeys::INVOICES_CONTROL);
        DB::table('fn_confirm_settings')->updateOrInsert(['title' => \App\Support\InvoiceSupplierWorkflow::SETTING_KEY], ['value' => \App\Support\InvoiceSupplierWorkflow::UPDATED]);
        $maid = \App\Models\ingaz\Maid::withoutEvents(fn () => \App\Models\ingaz\Maid::create(['name' => 'Supplier visibility worker', 'supplier_cost_usd' => 9876.54]));
        $contract = Contract::withoutEvents(fn () => Contract::create(['user_id' => $this->client->id, 'maid_id' => $maid->id, 'status' => 'new', 'type' => 'recruitment', 'branch_id' => $this->actor->branch_id]));
        $url = route('invoices.create', ['contract_id' => $contract->id]);
        $this->get($url)->assertOk()->assertDontSee('name="supplier_total_cost_usd"', false)->assertDontSee('9876.54')->assertDontSee('9,876.54');
        $this->grant(PermKeys::INVOICES_VIEW_SUPPLIER_TOTAL_COST);
        $this->get($url)->assertOk()->assertSee('name="supplier_total_cost_usd"', false)->assertSee('9,876.54');
    }

    public function test_supplier_total_is_hidden_in_draft_details_without_specific_permission(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE_DRAFT, PermKeys::INVOICES_APPROVE);
        InvoiceDraft::create(['created_by' => $this->actor->id, 'branch_id' => $this->actor->branch_id,
            'payload' => array_merge($this->payload(), ['supplier_total_cost_usd' => 9876.54])]);
        $this->get(route('invoice-drafts.index'))->assertOk()->assertDontSee('9,876.54')->assertDontSee('9876.54');
        $this->grant(PermKeys::INVOICES_VIEW_SUPPLIER_TOTAL_COST);
        $this->get(route('invoice-drafts.index'))->assertOk()->assertSee('9,876.54');
    }

    public function test_unauthorized_supplier_total_is_ignored_in_submitted_drafts(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE_DRAFT);
        $response = $this->postJson(route('invoices.store'), array_merge($this->payload(), ['supplier_total_cost_usd' => 9876.54]))->assertOk()->assertJsonPath('is_draft', true);
        $this->assertNull(InvoiceDraft::findOrFail($response->json('draft_id'))->payload['supplier_total_cost_usd']);
    }

    public function test_failed_issuance_rolls_back_and_leaves_request_pending(): void
    {
        $this->grant(PermKeys::INVOICES_CREATE_DRAFT, PermKeys::INVOICES_APPROVE);
        $draft = $this->submit();
        $this->mock(\App\Http\Controllers\ingaz\InvoiceController::class, function ($mock) {
            $mock->shouldReceive('issueApprovedDraft')->once()->andReturnUsing(function () {
                Invoice::withoutEvents(fn () => Invoice::create(['client_id' => $this->client->id, 'total_due' => 115]));
                return response()->json(['success' => false, 'message' => 'تعذر الترحيل المحاسبي'], 422);
            });
        });
        $this->postJson(route('invoice-drafts.approve', $draft->id))->assertUnprocessable();
        $this->assertSame('pending', $draft->fresh()->status);
        $this->assertNull($draft->fresh()->invoice_id);
        $this->assertFalse(Invoice::where('client_id', $this->client->id)->exists());
    }
}
