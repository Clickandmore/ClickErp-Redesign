<?php

namespace Tests\Feature\Admin;

use App\Enums\ingaz\OrderEnum;
use App\Models\Permission;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderChangeMaidReturnStatusTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function a_returned_order_does_not_prevent_assigning_its_available_maid_to_another_order(): void
    {
        $admin = $this->adminWithChangeMaidPermission();
        $oldMaid = $this->makeMaid('Old order maid');
        $newMaid = $this->makeMaid('Returned order maid');
        $targetOrder = $this->makeOrder($oldMaid, OrderEnum::New->value);
        $this->makeOrder($newMaid, OrderEnum::Return->value);

        $this->actingAs($admin, 'admin')
            ->postJson(route('orders.changeMaidById', $targetOrder->id), [
                'maid_id' => $newMaid->id,
                'old_maid_status' => 'available',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame($newMaid->id, (int) $targetOrder->fresh()->maid_id);
        $this->assertSame('reserved', $newMaid->fresh()->status);
        $this->assertFalse((bool) $newMaid->fresh()->available);
    }

    /** @test */
    public function an_active_order_still_prevents_assigning_the_same_maid_to_another_order(): void
    {
        $admin = $this->adminWithChangeMaidPermission();
        $oldMaid = $this->makeMaid('Old active-test maid');
        $newMaid = $this->makeMaid('Active order maid');
        $targetOrder = $this->makeOrder($oldMaid, OrderEnum::New->value);
        $this->makeOrder($newMaid, OrderEnum::UnderProcess->value);

        $this->actingAs($admin, 'admin')
            ->postJson(route('orders.changeMaidById', $targetOrder->id), [
                'maid_id' => $newMaid->id,
                'old_maid_status' => 'available',
            ])
            ->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertSame($oldMaid->id, (int) $targetOrder->fresh()->maid_id);
    }

    private function adminWithChangeMaidPermission(): Admin
    {
        $permission = Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => PermKeys::ORDERS_CHANGE_MAID, 'guard_name' => 'admin'],
            [
                'display_name' => 'Change maid in order',
                'group_name' => 'orders',
                'section_name' => 'orders_management',
                'is_active' => true,
            ]
        );
        $admin = Admin::factory()->create();
        $admin->givePermissionTo($permission);

        return $admin;
    }

    private function makeMaid(string $name): Maid
    {
        $nationalityId = DB::table('nationalities')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $religionId = DB::table('religions')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $maidId = DB::table('maids')->insertGetId([
            'name' => $name,
            'passport_num' => 'CHANGE-' . str_replace('.', '', uniqid('', true)),
            'nationality_id' => $nationalityId,
            'religion_id' => $religionId,
            'cv_file' => '',
            'height' => '',
            'weight' => '',
            'status' => 'available',
            'available' => true,
            'is_show' => true,
            'available_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Maid::query()->findOrFail($maidId);
    }

    private function makeOrder(Maid $maid, string $status): Order
    {
        return Order::query()->create([
            'user_id' => $this->makeClient(),
            'maid_id' => $maid->id,
            'job_type_id' => 1,
            'status' => $status,
            'payment_method' => 'cash',
            'order_code' => 'CHANGE-' . random_int(100000, 999999),
        ]);
    }

    private function makeClient(): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Change maid test client',
            'phone_code' => '+966',
            'phone' => '5' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
