<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\Models\Permission;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderChangeClientTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private Admin $actor;
    private User $oldClient;
    private User $newClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actor = Admin::factory()->forBranch(Branch::factory()->main()->create())->create();
        $this->actingAs($this->actor, 'admin');
        $this->oldClient = $this->client();
        $this->newClient = $this->client();
    }

    private function client(array $attributes = []): User
    {
        return User::withoutEvents(fn () => User::create(array_merge([
            'name' => 'عميل تغيير الطلب ' . uniqid(), 'phone' => uniqid('client-'),
            'is_blocked' => 0, 'branch_id' => $this->actor->branch_id,
        ], $attributes)));
    }

    private function grant(): void
    {
        $this->actor->givePermissionTo(Permission::firstOrCreate(['name' => PermKeys::ORDERS_CHANGE_CLIENT, 'guard_name' => 'admin']));
    }

    private function order(array $attributes = []): Order
    {
        $id = DB::table('orders')->insertGetId(array_merge([
            'status' => 'new', 'user_id' => $this->oldClient->id,
            'created_by_admin_id' => $this->actor->id, 'customer_id' => $this->actor->id,
            'branch_id' => $this->actor->branch_id,
        ], $attributes));
        return Order::withoutBranchScope()->findOrFail($id);
    }

    private function payload(): array
    {
        return ['client_id' => $this->newClient->id, 'expected_client_id' => $this->oldClient->id, 'reason' => 'تصحيح العميل في الحجز'];
    }

    public function test_permission_is_required_for_search_and_change_even_for_the_creator(): void
    {
        $order = $this->order();
        $this->postJson(route('orders.changeClient', $order->id), $this->payload())->assertForbidden();
        $this->getJson(route('orders.searchChangeClient', ['order_id' => $order->id, 'q' => $this->newClient->phone]))->assertForbidden();
        $this->assertSame($this->oldClient->id, $order->fresh()->user_id);
    }

    public function test_dashboard_and_website_orders_change_client_preserving_reservation_and_recording_audit(): void
    {
        $this->grant();
        config(['activitylog.enabled' => true]);
        foreach ([$this->actor->id, null] as $creatorId) {
            $order = $this->order(['created_by_admin_id' => $creatorId]);
            $addressId = DB::table('user_addresses')->insertGetId(['user_id' => $this->oldClient->id, 'address' => 'عنوان العميل السابق']);
            DB::table('order_details')->insert(['order_id' => $order->id, 'address_id' => $addressId, 'admin_id' => $this->actor->id]);
            $before = $order->getAttributes();
            $this->postJson(route('orders.changeClient', $order->id), $this->payload())->assertOk()->assertJsonPath('success', true);
            $order->refresh();
            $this->assertSame($this->newClient->id, $order->user_id);
            foreach (['created_by_admin_id', 'customer_id', 'maid_id', 'branch_id', 'status', 'deadline_at'] as $field) {
                $this->assertEquals($before[$field] ?? null, $order->getRawOriginal($field));
            }
            $this->assertNull(DB::table('order_details')->where('order_id', $order->id)->value('address_id'));
            $this->assertDatabaseHas('user_addresses', ['id' => $addressId, 'user_id' => $this->oldClient->id]);
            $log = \Spatie\Activitylog\Models\Activity::where('subject_id', $order->id)->where('description', 'تغيير العميل في الطلب')->latest('id')->firstOrFail();
            $this->assertEquals($this->oldClient->id, $log->properties['old_client_id']);
            $this->assertEquals($this->newClient->id, $log->properties['new_client_id']);
            $this->assertEquals($this->actor->id, $log->causer_id);
            $method = new \ReflectionMethod(\App\Http\Controllers\ingaz\OrderController::class, 'orderClientChanges');
            $method->setAccessible(true);
            $history = $method->invoke(app(\App\Http\Controllers\ingaz\OrderController::class), $order);
            $this->assertCount(1, $history);
            $this->assertSame($this->oldClient->name, $history[0]['old']['name']);
            $this->assertSame($this->oldClient->phone, $history[0]['old']['phone']);
            $this->assertSame($this->newClient->name, $history[0]['new']['name']);
            $this->assertSame($this->payload()['reason'], $history[0]['reason']);
        }
    }

    public function test_paid_or_pending_payment_and_linked_contract_prevent_change(): void
    {
        $this->grant();
        foreach (['paid', 'pending'] as $status) {
            $order = $this->order();
            DB::table('rental_order_payments')->insert([
                'order_id' => $order->id, 'user_id' => $this->oldClient->id,
                'public_id' => (string) \Illuminate\Support\Str::uuid(), 'amount' => 100, 'status' => $status,
            ]);
            $this->postJson(route('orders.changeClient', $order->id), $this->payload())->assertUnprocessable();
            $this->assertSame($this->oldClient->id, $order->fresh()->user_id);
        }
        $order = $this->order();
        DB::table('contracts')->insert(['order_id' => $order->id, 'status' => 'new', 'type' => 'recruitment']);
        $this->postJson(route('orders.changeClient', $order->id), $this->payload())->assertUnprocessable();
    }

    public function test_invalid_status_client_and_stale_form_are_rejected(): void
    {
        $this->grant();
        foreach (['done', 'refused', 'return', 'contracted'] as $status) {
            $order = $this->order(['status' => $status]);
            $this->postJson(route('orders.changeClient', $order->id), $this->payload())->assertUnprocessable();
        }
        $order = $this->order();
        $this->postJson(route('orders.changeClient', $order->id), array_merge($this->payload(), ['expected_client_id' => 0]))->assertUnprocessable();
        $this->newClient->update(['is_blocked' => 1]);
        $this->postJson(route('orders.changeClient', $order->id), $this->payload())->assertUnprocessable();
        $this->assertSame($this->oldClient->id, $order->fresh()->user_id);
    }

    public function test_client_search_and_change_respect_branch_access(): void
    {
        $this->enableBranchesModule();
        $this->grant();
        $this->lockToOwnBranch($this->actor);
        $otherBranch = Branch::factory()->create();
        $otherClient = $this->client(['branch_id' => $otherBranch->id]);
        $order = $this->order();
        $this->getJson(route('orders.searchChangeClient', ['order_id' => $order->id, 'q' => $otherClient->phone]))->assertOk()->assertJsonCount(0, 'results');
        $this->getJson(route('orders.searchChangeClient', ['order_id' => $order->id, 'q' => $this->newClient->phone]))->assertOk()->assertJsonPath('results.0.id', $this->newClient->id);
        $this->postJson(route('orders.changeClient', $order->id), array_merge($this->payload(), ['client_id' => $otherClient->id]))->assertUnprocessable();
        $otherOrder = $this->order(['branch_id' => $otherBranch->id]);
        $this->postJson(route('orders.changeClient', $otherOrder->id), $this->payload())->assertNotFound();
    }
}
