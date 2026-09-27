<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Models\FinancialRequest;
use App\Models\Permission;
use App\PermKeys;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FinancialRequestAmountPermissionTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private Admin $actor;

    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_09_21_130000_add_financial_request_edit_amount_permission.php'))->up();
        $this->actor = Admin::factory()->forBranch(Branch::factory()->main()->create())->create();
        $this->actingAs($this->actor, 'admin');
    }

    private function grant(string $name): void
    {
        $this->actor->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']));
    }

    private function item(array $attributes = []): FinancialRequest
    {
        return FinancialRequest::create(array_merge([
            'request_no' => 'FR-AMOUNT-' . uniqid(),
            'type' => 'other_financial',
            'priority' => 'normal',
            'status' => FinancialRequest::STATUS_FOR_APPROVAL,
            'amount' => 100,
            'branch_id' => $this->actor->branch_id,
            'created_by_id' => $this->actor->id,
        ], $attributes));
    }

    public function test_general_permissions_cannot_change_amount_or_show_the_amount_form(): void
    {
        $this->grant(PermKeys::FINANCIAL_REQUESTS_CONTROL);
        $item = $this->item();
        $this->get(route('financial-requests.show', $item))->assertOk()->assertDontSee('id="request-amount"', false);
        $this->patchJson(route('financial-requests.amount', $item), ['amount' => 250])->assertForbidden();
        $this->putJson(route('financial-requests.update', $item), [
            'type' => 'other_financial', 'priority' => 'normal', 'amount' => 250,
            'branch_id' => $this->actor->branch_id,
            'payload' => ['paid_to' => 'Test', 'accounting_name' => 'Test', 'debit_credit' => 'debit', 'payment_method' => 'cash'],
        ])->assertForbidden();
        $this->assertSame('100.00', $item->fresh()->amount);
    }

    public function test_permission_allows_amount_only_edit_and_records_old_and_new_amounts(): void
    {
        $this->grant(PermKeys::FINANCIAL_REQUESTS_EDIT_AMOUNT);
        $owner = Admin::factory()->forBranch($this->actor->branch_id)->create();
        $item = $this->item(['created_by_id' => $owner->id]);
        $this->get(route('financial-requests.show', $item))->assertOk()->assertSee('id="request-amount"', false);
        $this->patch(route('financial-requests.amount', $item), ['amount' => '250.75', 'description' => 'must not change', 'status' => 'closed'])
            ->assertRedirect(route('financial-requests.show', $item));
        $item->refresh();
        $this->assertSame('250.75', $item->amount);
        $this->assertNull($item->description);
        $this->assertSame(FinancialRequest::STATUS_FOR_APPROVAL, $item->status);
        $this->assertSame($this->actor->id, (int) $item->updated_by_id);
        $log = $item->statusLogs()->firstOrFail();
        $this->assertStringContainsString('100.00', $log->remarks);
        $this->assertStringContainsString('250.75', $log->remarks);
        $this->assertSame($this->actor->id, (int) $log->performed_by_id);
        $this->assertContains(PermKeys::FINANCIAL_REQUESTS_EDIT_AMOUNT, PermissionCatalog::grantablePermissions('admin')->all());
    }

    public function test_approved_amount_change_requires_approval_again(): void
    {
        $this->grant(PermKeys::FINANCIAL_REQUESTS_EDIT_AMOUNT);
        $item = $this->item(['status' => 'approved', 'approved_by_id' => $this->actor->id]);
        $this->patch(route('financial-requests.amount', $item), ['amount' => 200])->assertRedirect();
        $this->assertSame('for_approval', $item->fresh()->status);
        $this->assertNull($item->fresh()->approved_by_id);
    }

    public function test_saving_the_same_amount_does_not_reset_approval(): void
    {
        $this->grant(PermKeys::FINANCIAL_REQUESTS_EDIT_AMOUNT);
        $item = $this->item(['status' => 'approved', 'approved_by_id' => $this->actor->id]);
        $this->patch(route('financial-requests.amount', $item), ['amount' => '100.00'])->assertRedirect();
        $this->assertSame('approved', $item->fresh()->status);
        $this->assertSame(0, $item->statusLogs()->count());
    }

    public function test_paid_closed_and_bond_linked_requests_cannot_change_amount(): void
    {
        $this->grant(PermKeys::FINANCIAL_REQUESTS_EDIT_AMOUNT);
        foreach ([['status' => 'paid'], ['status' => 'request_closing'], ['status' => 'receipt_attached'], ['status' => 'closed'], ['paid_at' => now()], ['closed_at' => now()], ['finance_bond_id' => 1]] as $attributes) {
            $item = $this->item($attributes);
            $this->patchJson(route('financial-requests.amount', $item), ['amount' => 200])->assertStatus(422);
            $this->assertSame('100.00', $item->fresh()->amount);
        }
    }

    public function test_invalid_amounts_are_rejected(): void
    {
        $this->grant(PermKeys::FINANCIAL_REQUESTS_EDIT_AMOUNT);
        $item = $this->item();
        foreach ([0, -1, 'abc', '1.234', '10000000000', null] as $amount) {
            $this->patchJson(route('financial-requests.amount', $item), ['amount' => $amount])->assertUnprocessable()->assertJsonValidationErrors('amount');
        }
        $this->assertSame('100.00', $item->fresh()->amount);
    }

    public function test_amount_permission_does_not_bypass_branch_access(): void
    {
        $this->enableBranchesModule();
        $this->grant(PermKeys::FINANCIAL_REQUESTS_EDIT_AMOUNT);
        $otherBranch = Branch::factory()->create();
        $item = $this->item(['branch_id' => $otherBranch->id]);
        $response = $this->patchJson(route('financial-requests.amount', $item), ['amount' => 200]);
        $this->assertContains($response->status(), [403, 404]);
        $this->assertSame('100.00', FinancialRequest::withoutGlobalScopes()->findOrFail($item->id)->amount);
    }
}
