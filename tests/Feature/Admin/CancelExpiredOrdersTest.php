<?php
namespace Tests\Feature\Admin;

use App\Enums\ingaz\OrderEnum;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CancelExpiredOrdersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_expired_order_releases_maid_and_repeat_run_is_safe(): void
    {
        $maid = $this->makeMaid('Expired reservation');
        $maid->update(['status' => 'reserved', 'available' => false]);
        $order = $this->makeOrder($maid, OrderEnum::New->value);
        $order->forceFill(['deadline_at' => now()->subMinute()])->saveQuietly();
        $this->artisan('orders:cancel-expired', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame('new', $order->fresh()->status);
        $this->assertFalse($maid->fresh()->available);
        $this->artisan('orders:cancel-expired')->assertSuccessful();
        $this->assertSame('canceled_by_system', $order->fresh()->status);
        $this->assertNull($order->fresh()->deadline_at);
        $this->assertSame('available', $maid->fresh()->status);
        $this->assertTrue($maid->fresh()->available);
        $this->artisan('orders:cancel-expired')->assertSuccessful();
        $this->assertSame('canceled_by_system', $order->fresh()->status);
    }

    public function test_legacy_deadline_and_other_active_reservation(): void
    {
        $maid = $this->makeMaid('Shared reservation');
        $maid->update(['status' => 'reserved', 'available' => false]);
        $expired = $this->makeOrder($maid, OrderEnum::New->value);
        $expired->forceFill(['deadline_at' => null, 'status_changed_at' => now()->subHours(1000)])->saveQuietly();
        $active = $this->makeOrder($maid, OrderEnum::UnderProcess->value);
        $this->artisan('orders:cancel-expired')->assertSuccessful();
        $this->assertSame('canceled_by_system', $expired->fresh()->status);
        $this->assertSame('under_process', $active->fresh()->status);
        $this->assertFalse($maid->fresh()->available);
    }

    public function test_legacy_expiration_field_does_not_override_alert_deadline(): void
    {
        $maid = $this->makeMaid('Future reservation');
        $order = $this->makeOrder($maid, OrderEnum::New->value);
        $order->forceFill(['deadline_at' => now()->addDay(), 'expiration_time' => now()->subDay()])->saveQuietly();
        $this->artisan('orders:handle-expired')->assertSuccessful();
        $this->assertSame('new', $order->fresh()->status);
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
