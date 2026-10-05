<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\User;
use App\Models\Permission;
use App\NewPermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RecruitmentContractClientProtectionTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;
    private User $client;
    private Contract $contract;
    private Contract $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringEndsWith('_testing', DB::connection()->getDatabaseName());
        $this->admin = Admin::factory()->create();
        $permission = Permission::firstOrCreate(['name' => NewPermKeys::REC_CONTRACTS_EDIT, 'guard_name' => 'admin']);
        $this->admin->givePermissionTo($permission);
        $this->actingAs($this->admin, 'admin');
        $this->client = User::create([
            'name' => 'Original client', 'phone' => '5' . random_int(10000000, 99999999),
            'phone_code' => '+966', 'password' => bcrypt('test'), 'client_type' => 'person',
            'national_num' => '1234567890',
        ]);
        $this->client->refresh();
        $this->contract = Contract::create(['user_id' => $this->client->id, 'status' => 'new', 'marketer_id' => $this->admin->id]);
        $this->other = Contract::create(['user_id' => $this->client->id, 'status' => 'new', 'marketer_id' => $this->admin->id]);
        ContractInfo::create(['contract_id' => $this->contract->id, 'contract_date' => '2026-10-01', 'msaned_number' => 'original', 'visa_number' => 'original']);
    }

    private function payload(array $extra = []): array
    {
        return $extra + [
            'contract_date' => '2026-10-02', 'msaned_number' => 'updated', 'visa_number' => 'updated',
            'contract_duration' => 2, 'marketer_id' => $this->admin->id,
        ];
    }

    public function test_edit_page_displays_client_without_editable_client_inputs(): void
    {
        $this->get(route('RecruitmentContract.edit', $this->contract->id))
            ->assertOk()->assertSee('Original client')
            ->assertDontSee('name="client_name"', false)
            ->assertDontSee('name="client_phone"', false)
            ->assertDontSee('name="client_national_num"', false)
            ->assertDontSee('name="account_client_type"', false);
    }

    public function test_regular_contract_edit_does_not_write_to_its_shared_client(): void
    {
        $before = $this->client->getAttributes();
        $this->putJson(route('RecruitmentContract.update', $this->contract->id), $this->payload())
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame($before, $this->client->fresh()->getAttributes());
        $this->assertSame($this->client->id, $this->other->fresh()->user_id);
        $this->assertSame('updated', $this->contract->fresh()->info->msaned_number);
    }

    /** @dataProvider protectedFields */
    public function test_direct_requests_cannot_change_client_data_or_ownership(string $field, $value): void
    {
        $before = $this->client->getAttributes();
        $this->putJson(route('RecruitmentContract.update', $this->contract->id), $this->payload([$field => $value]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame($before, $this->client->fresh()->getAttributes());
        $this->assertSame($this->client->id, $this->contract->fresh()->user_id);
        $this->assertSame('original', $this->contract->fresh()->info->msaned_number);
    }

    public function protectedFields(): array
    {
        return [
            ['client_name', 'Replacement client'], ['client_national_num', '9999999999'],
            ['client_phone', '0551234567'], ['account_client_type', 'company'],
            ['user_id', 999], ['client_id', 999], ['existing_client_id', 999],
            ['new_existing_client_id', 999], ['address', 'Replacement address'],
        ];
    }

    public function test_failed_contract_save_rolls_back_the_info_update(): void
    {
        // Clone the dispatcher so the failure hook is removed without losing model listeners.
        $dispatcher = Event::getFacadeRoot();
        $original = clone $dispatcher;
        $dispatcher->listen('eloquent.updating: ' . Contract::class, function () {
            throw new \RuntimeException('Simulated contract save failure');
        });
        try {
            $this->putJson(route('RecruitmentContract.update', $this->contract->id), $this->payload())
                ->assertStatus(500)->assertJsonPath('success', false);
            $this->assertSame('original', $this->contract->fresh()->info->msaned_number);
        } finally {
            Event::swap($original);
            \Illuminate\Database\Eloquent\Model::setEventDispatcher($original);
        }
    }
}
