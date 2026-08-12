<?php

namespace Tests\Feature\Console;

use App\Services\commissions\LegacyCommissionMarketerResolver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class CleanupLegacyMarketerAdminsTest extends TestCase
{
    private string $originalDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = (string) config('database.default');
        config([
            'database.default' => 'legacy_cleanup_test',
            'database.connections.legacy_cleanup_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('legacy_cleanup_test');

        Schema::connection('legacy_cleanup_test')->create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
        });

        foreach (['contracts' => 'marketer_id', 'orders' => 'customer_id', 'order_details' => 'admin_id'] as $table => $column) {
            Schema::connection('legacy_cleanup_test')->create($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->id();
                $blueprint->unsignedBigInteger($column)->nullable();
            });
        }
    }

    protected function tearDown(): void
    {
        DB::purge('legacy_cleanup_test');
        config(['database.default' => $this->originalDefaultConnection]);

        parent::tearDown();
    }

    public function test_preview_does_not_change_matching_accounts_or_assignments(): void
    {
        $adminId = $this->insertLegacyProfile();
        $this->insertAssignments($adminId);

        $exitCode = Artisan::call('admins:cleanup-legacy-marketers');

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseHas('admins', ['id' => $adminId], 'legacy_cleanup_test');
        $this->assertDatabaseHas('contracts', ['marketer_id' => $adminId], 'legacy_cleanup_test');
    }

    public function test_execute_nulls_faulty_assignments_and_deletes_only_exact_profiles(): void
    {
        $adminId = $this->insertLegacyProfile();
        $this->insertAssignments($adminId);

        $similarId = DB::connection('legacy_cleanup_test')->table('admins')->insertGetId([
            'name' => LegacyCommissionMarketerResolver::HISTORICAL_PROFILE_NAMES[0],
            'username' => 'legacy_marketer_not_the_generated_key',
            'email' => 'real@example.com',
        ]);

        $exitCode = Artisan::call('admins:cleanup-legacy-marketers', ['--execute' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseMissing('admins', ['id' => $adminId], 'legacy_cleanup_test');
        $this->assertDatabaseHas('admins', ['id' => $similarId], 'legacy_cleanup_test');
        $this->assertDatabaseHas('contracts', ['marketer_id' => null], 'legacy_cleanup_test');
        $this->assertDatabaseHas('orders', ['customer_id' => null], 'legacy_cleanup_test');
        $this->assertDatabaseHas('order_details', ['admin_id' => null], 'legacy_cleanup_test');
    }

    private function insertLegacyProfile(): int
    {
        $name = LegacyCommissionMarketerResolver::HISTORICAL_PROFILE_NAMES[0];
        $key = substr(hash('sha256', $name), 0, 16);

        return DB::connection('legacy_cleanup_test')->table('admins')->insertGetId([
            'name' => $name,
            'username' => 'legacy_marketer_' . $key,
            'email' => "legacy-marketer-{$key}@invalid.local",
        ]);
    }

    private function insertAssignments(int $adminId): void
    {
        DB::connection('legacy_cleanup_test')->table('contracts')->insert(['marketer_id' => $adminId]);
        DB::connection('legacy_cleanup_test')->table('orders')->insert(['customer_id' => $adminId]);
        DB::connection('legacy_cleanup_test')->table('order_details')->insert(['admin_id' => $adminId]);
    }
}
