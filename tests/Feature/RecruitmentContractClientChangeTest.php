<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\Models\Permission;
use App\NewPermKeys;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class RecruitmentContractClientChangeTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBranches;

    private Admin $admin;
    private User $oldClient;
    private User $newClient;
    private Contract $contract;
    private Contract $other;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringEndsWith('_testing', DB::connection()->getDatabaseName());
        $this->setUpBranches();
        $this->admin = Admin::factory()->create();
        $this->grant($this->admin, [NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_EDIT, PermKeys::CLIENTS_VIEW_ALL_BRANCHES]);
        $this->actingAsAdmin($this->admin);
        $this->oldClient = $this->client('Original customer');
        $this->newClient = $this->client('Replacement customer');
        $this->order = Order::create(['user_id' => $this->oldClient->id, 'status' => 'under_process', 'order_code' => Order::generateUniqueCode(), 'job_type_id' => 1]);
        $this->contract = Contract::create(['user_id' => $this->oldClient->id, 'order_id' => $this->order->id, 'status' => 'new', 'marketer_id' => $this->admin->id]);
        $this->other = Contract::create(['user_id' => $this->oldClient->id, 'status' => 'new', 'marketer_id' => $this->admin->id]);
    }

    private function client(string $name): User
    {
        return User::create(['name' => $name, 'phone' => '5' . random_int(10000000, 99999999), 'phone_code' => '+966', 'password' => bcrypt('test'), 'client_type' => 'person', 'national_num' => (string) random_int(1000000000, 1999999999)])->fresh();
    }

    private function grant(Admin $admin, array $permissions): void
    {
        foreach ($permissions as $name) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']));
        }
    }

    private function change(array $extra = [])
    {
        return $this->postJson(route('RecruitmentContract.changeClient', $this->contract), $extra + [
            'client_id' => $this->newClient->id, 'current_client_id' => $this->oldClient->id, 'reason' => 'Correct customer selected against original contract',
        ]);
    }

    private function assertOwnershipUnchanged(): void
    {
        $this->assertSame($this->oldClient->id, $this->contract->fresh()->user_id);
        $this->assertSame($this->oldClient->id, $this->order->fresh()->user_id);
        $this->assertSame(0, DB::table('activity_log')->where('event', 'client_changed')->where('subject_id', $this->contract->id)->count());
    }

    public function test_change_updates_contract_and_order_without_overwriting_clients_or_other_contracts(): void
    {
        $old = $this->oldClient->getAttributes();
        $new = $this->newClient->getAttributes();
        $this->change()->assertOk()->assertJsonPath('success', true);
        $this->assertSame($this->newClient->id, $this->contract->fresh()->user_id);
        $this->assertSame($this->newClient->id, $this->order->fresh()->user_id);
        $this->assertSame($this->oldClient->id, $this->other->fresh()->user_id);
        $this->assertSame($old, $this->oldClient->fresh()->getAttributes());
        $this->assertSame($new, $this->newClient->fresh()->getAttributes());
        $log = DB::table('activity_log')->where('subject_id', $this->contract->id)->where('event', 'client_changed')->first();
        $this->assertSame($this->admin->id, $log->causer_id);
        $properties = json_decode($log->properties, true);
        $this->assertSame($this->oldClient->id, $properties['old_user_id']);
        $this->assertSame($this->newClient->id, $properties['new_user_id']);
        $this->assertSame('Correct customer selected against original contract', $properties['reason']);
    }

    public function test_details_page_contains_button_modal_and_correct_action(): void
    {
        $this->get(route('contracts.details', $this->contract->id))->assertOk()
            ->assertSee('id="changeContractClientButton"', false)
            ->assertSee('id="changeContractClientModal"', false)
            ->assertSee(route('RecruitmentContract.changeClient', $this->contract), false);
    }

    public function test_unpaid_invoice_blocks_button_search_and_direct_save(): void
    {
        Invoice::create(['contract_id' => $this->contract->id, 'total_due' => 100, 'is_paid' => false]);
        $this->get(route('contracts.details', $this->contract->id))->assertOk()
            ->assertSee('id="changeContractClientButton"', false)
            ->assertSee('لا يمكن تغيير العميل لوجود فاتورة')
            ->assertDontSee('id="changeContractClientModal"', false);
        $this->getJson(route('RecruitmentContract.clientChangeOptions', $this->contract))->assertUnprocessable();
        $this->change()->assertUnprocessable()->assertJsonValidationErrors('client_id');
        $this->assertOwnershipUnchanged();
    }

    public function test_payment_blocks_change_without_an_invoice(): void
    {
        DB::table('contract_payments')->insert(['contract_id' => $this->contract->id, 'paid_amount' => 10, 'payment_date' => now()]);
        $this->change()->assertUnprocessable()->assertJsonValidationErrors('client_id');
        $this->assertOwnershipUnchanged();
    }

    public function test_invoice_added_after_page_load_is_rechecked_on_save(): void
    {
        $this->get(route('contracts.details', $this->contract->id))->assertOk()->assertSee('id="changeContractClientModal"', false);
        Invoice::create(['contract_id' => $this->contract->id, 'total_due' => 100]);
        $this->change()->assertUnprocessable();
        $this->assertOwnershipUnchanged();
    }

    public function test_edit_permission_is_required_and_create_permission_alone_is_insufficient(): void
    {
        $viewer = Admin::factory()->create();
        $this->grant($viewer, [NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_CREATE]);
        $this->actingAsAdmin($viewer);
        $this->change()->assertForbidden();
        $this->getJson(route('RecruitmentContract.clientChangeOptions', $this->contract))->assertForbidden();
        $this->get(route('contracts.details', $this->contract->id))->assertOk()->assertDontSee('id="changeContractClientButton"', false);
        $this->assertOwnershipUnchanged();
    }

    public function test_search_works_without_contract_creation_permission_and_excludes_current_client(): void
    {
        $this->getJson(route('RecruitmentContract.clientChangeOptions', $this->contract) . '?term=customer')
            ->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.id', $this->newClient->id);
    }

    /** @dataProvider invalidChanges */
    public function test_invalid_change_is_refused(array $extra, string $field): void
    {
        if (isset($extra['client_id']) && $extra['client_id'] === 'same') $extra['client_id'] = $this->oldClient->id;
        $this->change($extra)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertOwnershipUnchanged();
    }

    public function invalidChanges(): array
    {
        return [
            [['client_id' => 'same'], 'client_id'], [['client_id' => 999999999], 'client_id'],
            [['current_client_id' => 999999999], 'current_client_id'], [['reason' => '   '], 'reason'],
        ];
    }

    public function test_deleted_or_blocked_client_cannot_be_selected(): void
    {
        $this->newClient->update(['is_blocked' => 1]);
        $this->change()->assertUnprocessable();
        $this->newClient->update(['is_blocked' => 0, 'deleted_at' => now()]);
        $this->change()->assertUnprocessable();
        $this->assertOwnershipUnchanged();
    }

    public function test_shared_order_prevents_overwriting_another_contract_order(): void
    {
        $this->other->update(['order_id' => $this->order->id]);
        $this->change()->assertUnprocessable();
        $this->assertOwnershipUnchanged();
    }

    public function test_order_failure_rolls_back_contract_and_audit_changes(): void
    {
        $dispatcher = Event::getFacadeRoot();
        $original = clone $dispatcher;
        $dispatcher->listen('eloquent.updating: ' . Order::class, function () {
            throw new \RuntimeException('Simulated order save failure');
        });
        try {
            $this->change()->assertStatus(500);
            $this->assertOwnershipUnchanged();
        } finally {
            Event::swap($original);
            \Illuminate\Database\Eloquent\Model::setEventDispatcher($original);
        }
    }

    public function test_client_selection_respects_branch_access_on_search_and_save(): void
    {
        $this->enableBranchesModule();
        $branch = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $editor = $this->branchAdmin($branch);
        $this->grant($editor, [NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_EDIT]);
        $this->oldClient->update(['branch_id' => $branch->id]);
        $this->newClient->update(['branch_id' => $other->id]);
        $this->contract->update(['branch_id' => $branch->id]);
        $this->order->update(['branch_id' => $branch->id]);
        $this->actingAsAdmin($editor);
        $this->getJson(route('RecruitmentContract.clientChangeOptions', $this->contract) . '?term=Replacement')
            ->assertOk()->assertJsonCount(0, 'results');
        $this->change()->assertUnprocessable();
        $this->assertOwnershipUnchanged();
    }

    public function test_contract_in_another_branch_cannot_be_changed(): void
    {
        $this->enableBranchesModule();
        $own = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $editor = $this->branchAdmin($own);
        $this->grant($editor, [NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_EDIT]);
        $this->contract->update(['branch_id' => $other->id]);
        $this->actingAsAdmin($editor);
        $this->change()->assertNotFound();
        $this->assertOwnershipUnchanged();
    }
}
