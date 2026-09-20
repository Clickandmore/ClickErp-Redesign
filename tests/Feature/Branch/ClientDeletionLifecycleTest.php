<?php

namespace Tests\Feature\Branch;

use App\Models\ingaz\User;
use App\Models\ingaz\Order;
use App\Models\ingaz\Maid;
use App\Models\Permission;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class ClientDeletionLifecycleTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBranches;
    private $branch;
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
        $this->branch = $this->makeBranch();
        $this->admin = $this->branchAdmin($this->branch);
        foreach ([PermKeys::CLIENTS_DELETE, PermKeys::CLIENTS_CREATE, PermKeys::CLIENTS_VIEW, PermKeys::ORDERS_DELETE, PermKeys::ORDERS_EDIT] as $name) {
            Permission::updateOrCreate(['name' => $name, 'guard_name' => 'admin'], ['display_name' => $name, 'group_name' => 'clients', 'section_name' => 'users_management', 'is_active' => true]);
            $this->admin->givePermissionTo($name);
        }
        $this->actingAsAdmin($this->admin);
    }

    private function client(): User
    {
        return User::create(['name' => 'Lifecycle client', 'phone' => '5' . random_int(10000000, 99999999), 'phone_code' => '+966', 'password' => bcrypt('test'), 'branch_id' => $this->branch->id]);
    }

    public function test_return_of_legacy_order_without_client_releases_maid(): void
    {
        $maid = $this->maid();
        $order = $this->order($this->client(), 'return', $maid->id);
        DB::table('orders')->where('id', $order->id)->update(['user_id' => null]);
        $deletedOrder = $this->order($this->client(), 'under_process', $maid->id);
        $deletedOrder->delete();
        $this->postJson(route('orders.refuseReturn', $order->id), ['action' => 'return'])
            ->assertOk()->assertJsonPath('data.success', true);
        $this->assertSame('return', $order->fresh()->status);
        $this->assertSame('available', $maid->fresh()->status);
        $this->assertTrue((bool) $maid->fresh()->available);
    }

    public function test_sms_failure_does_not_prevent_return_and_maid_release(): void
    {
        $controller = \Mockery::mock(\App\Http\Controllers\ingaz\OrderController::class)->makePartial();
        $controller->shouldReceive('sendSMS')->once()->andThrow(new \RuntimeException('Test SMS outage'));
        $this->app->instance(\App\Http\Controllers\ingaz\OrderController::class, $controller);
        $maid = $this->maid();
        $order = $this->order($this->client(), 'under_process', $maid->id);
        $this->postJson(route('orders.refuseReturn', $order->id), ['action' => 'return'])
            ->assertOk()->assertJsonPath('data.success', true);
        $this->assertSame('return', $order->fresh()->status);
        $this->assertTrue((bool) $maid->fresh()->available);
    }

    private function order(User $client, string $status = 'new', ?int $maidId = null): Order
    {
        $id = DB::table('orders')->insertGetId(['user_id' => $client->id, 'maid_id' => $maidId, 'status' => $status, 'branch_id' => $this->branch->id, 'created_at' => now(), 'updated_at' => now()]);
        return Order::findOrFail($id);
    }

    private function maid(): Maid
    {
        $nationality = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $religion = DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('maids')->insertGetId(['name' => 'Lifecycle maid', 'passport_num' => uniqid('LC'), 'nationality_id' => $nationality, 'religion_id' => $religion, 'age' => 30, 'cv_file' => '', 'height' => '', 'weight' => '', 'status' => 'reserved', 'available' => false, 'branch_id' => $this->branch->id, 'created_at' => now(), 'updated_at' => now()]);
        return Maid::withoutGlobalScopes()->findOrFail($id);
    }

    public function test_client_is_soft_deleted_restored_and_can_be_permanently_deleted_when_unreferenced(): void
    {
        $client = $this->client();
        $this->deleteJson(route('clients.destroy', $client->id))->assertOk();
        $this->assertNull(User::find($client->id));
        $this->assertTrue(User::withTrashed()->findOrFail($client->id)->trashed());
        $this->get(route('clients.trashed'))->assertOk()->assertSee($client->name);
        $this->post(route('clients.restore', $client->id))->assertRedirect();
        $this->assertNotNull(User::find($client->id));
        $this->deleteJson(route('clients.destroy', $client->id))->assertOk();
        $this->delete(route('clients.forceDelete', $client->id))->assertRedirect();
        $this->assertNull(User::withTrashed()->find($client->id));
    }

    public function test_active_orders_and_contracts_prevent_client_deletion(): void
    {
        $client = $this->client();
        $order = $this->order($client);
        $this->deleteJson(route('clients.destroy', $client->id))->assertUnprocessable()->assertSee((string) $order->id);
        DB::table('orders')->where('id', $order->id)->update(['status' => 'refused']);
        DB::table('contracts')->insert(['user_id' => $client->id, 'type' => 'recruitment', 'status' => 'completed', 'branch_id' => $this->branch->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->deleteJson(route('clients.destroy', $client->id))->assertUnprocessable();
        $this->assertNotNull(User::find($client->id));
    }

    public function test_historical_orders_keep_client_details_and_block_permanent_deletion(): void
    {
        $client = $this->client();
        $order = $this->order($client, 'refused');
        $this->deleteJson(route('clients.destroy', $client->id))->assertOk();
        $this->assertSame($client->name, $order->fresh()->user->name);
        $this->deleteJson(route('clients.forceDelete', $client->id))->assertUnprocessable();
    }

    public function test_duplicate_creation_explains_all_matching_fields_and_deleted_client(): void
    {
        $client = $this->client();
        $client->update(['email' => 'lifecycle-' . $client->id . '@example.test', 'national_num' => 'LC-' . $client->id]);
        $this->deleteJson(route('clients.destroy', $client->id))->assertOk();
        $response = $this->postJson(route('clients.store'), ['name' => 'Duplicate client', 'phone' => '0' . $client->phone, 'email' => $client->email, 'national_num' => $client->national_num, 'client_type' => 'person']);
        $response->assertUnprocessable()->assertJsonValidationErrors(['phone', 'email', 'national_num']);
        $this->assertStringContainsString('العميل محذوف', $response->json('errors.phone.0'));
    }

    public function test_deleting_order_releases_maid_and_restoring_reserves_her(): void
    {
        $maid = $this->maid();
        $order = $this->order($this->client(), 'new', $maid->id);
        $this->deleteJson(route('orders.destroy', $order->id))->assertOk();
        $this->assertTrue((bool) $maid->fresh()->available);
        $this->postJson(route('orders.restore', $order->id))->assertOk();
        $this->assertFalse((bool) $maid->fresh()->available);
        $this->assertSame('reserved', $maid->fresh()->status);
    }

    public function test_other_booking_prevents_release_and_restore(): void
    {
        $maid = $this->maid();
        $client = $this->client();
        $first = $this->order($client, 'new', $maid->id);
        $this->order($client, 'new', $maid->id);
        $this->deleteJson(route('orders.destroy', $first->id))->assertOk();
        $this->assertFalse((bool) $maid->fresh()->available);
        $this->postJson(route('orders.restore', $first->id))->assertUnprocessable();
    }

    public function test_deleted_clients_remain_isolated_by_branch(): void
    {
        $client = $this->client();
        $client->update(['branch_id' => $this->makeBranch()->id]);
        $client->delete();
        $this->actingAsAdmin($this->lockToOwnBranch($this->admin));
        $this->get(route('clients.trashed'))->assertOk()->assertDontSee($client->name);
        $this->post(route('clients.restore', $client->id))->assertNotFound();
        $this->delete(route('clients.forceDelete', $client->id))->assertNotFound();
        $this->assertTrue(User::withTrashed()->findOrFail($client->id)->trashed());
    }

    public function test_pending_known_service_request_blocks_client_deletion(): void
    {
        $client = $this->client();
        DB::table('known_service_requests')->insert(['user_id' => $client->id, 'client_name' => $client->name, 'client_phone' => $client->phone, 'worker_name' => 'Worker', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        $this->deleteJson(route('clients.destroy', $client->id))->assertUnprocessable();
        $this->assertNotNull(User::find($client->id));
    }

    public function test_delete_action_is_hidden_for_clients_with_orders_or_contracts(): void
    {
        $free = $this->client();
        $withOrder = $this->client();
        $withContract = $this->client();
        $this->order($withOrder, 'refused')->delete();
        DB::table('contracts')->insert(['user_id' => $withContract->id, 'type' => 'recruitment', 'status' => 'completed', 'branch_id' => $this->branch->id, 'created_at' => now(), 'updated_at' => now()]);

        $response = $this->getJson(route('clients.index', ['length' => 100]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $rows = collect($response->json('data'))->keyBy('id');
        $this->assertStringContainsString('delete-route="' . route('clients.destroy', $free->id) . '"', $rows[$free->id]['actions']);
        $this->assertStringNotContainsString('delete-route=', $rows[$withOrder->id]['actions']);
        $this->assertStringNotContainsString('delete-route=', $rows[$withContract->id]['actions']);
        $this->get(route('clients.index'))->assertOk()->assertSee('يظهر خيار الحذف فقط');
    }
}
