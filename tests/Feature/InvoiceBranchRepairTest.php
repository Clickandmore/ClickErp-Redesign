<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InvoiceBranchRepairTest extends TestCase
{
    private string $auditRoot;

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'invoice_branch_test');
            $app['config']->set('database.connections.invoice_branch_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditRoot = sys_get_temp_dir().'/invoice-branch-test-'.bin2hex(random_bytes(8));
        $this->app->useStoragePath($this->auditRoot);
        foreach (['branches', 'contracts', 'ingaz_delegations'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->integer('branch_id')->nullable();
                $table->timestamp('deleted_at')->nullable();
            });
        }
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            foreach (['contract_id', 'ingaz_delegation_id', 'branch_id', 'rental_transfer_contract_id', 'sponsorship_transfer_id'] as $column) {
                $table->integer($column)->nullable();
            }
            $table->decimal('total_due')->default(100);
        });
        DB::table('branches')->insert([['id' => 1], ['id' => 2]]);
        DB::table('contracts')->insert(['id' => 10, 'branch_id' => 2]);
        DB::table('ingaz_delegations')->insert(['id' => 20, 'branch_id' => 1]);
    }

    protected function tearDown(): void
    {
        // Only remove files in this test's randomly generated temporary directory.
        if (is_dir($this->auditRoot)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->auditRoot, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $entry) {
                $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
            }
            rmdir($this->auditRoot);
        }
        parent::tearDown();
    }

    public function test_preview_apply_and_repeat_preserve_amounts_and_record_previous_branches(): void
    {
        DB::table('invoices')->insert([
            ['id' => 1, 'contract_id' => 10, 'ingaz_delegation_id' => null, 'branch_id' => 1],
            ['id' => 2, 'contract_id' => null, 'ingaz_delegation_id' => 20, 'branch_id' => null],
        ]);
        $this->artisan('invoices:repair-branches', ['--database' => ':memory:'])->assertSuccessful();
        $this->assertEquals(1, DB::table('invoices')->where('id', 1)->value('branch_id'));
        $this->assertNull(DB::table('invoices')->where('id', 2)->value('branch_id'));
        $this->artisan('invoices:repair-branches', ['--database' => ':memory:', '--apply' => true])->assertSuccessful();
        $this->assertEquals(2, DB::table('invoices')->where('id', 1)->value('branch_id'));
        $this->assertEquals(1, DB::table('invoices')->where('id', 2)->value('branch_id'));
        $this->assertEquals(200, DB::table('invoices')->sum('total_due'));
        $files = glob($this->auditRoot.'/app/invoice-branch-repairs/*/*.json');
        $this->assertCount(2, $files);
        $before = json_decode(file_get_contents($files[0]), true);
        $this->assertSame(1, $before['old_branch_id']);
        $this->assertSame(2, $before['new_branch_id']);
        $this->artisan('invoices:repair-branches', ['--database' => ':memory:', '--apply' => true])
            ->expectsOutput('Changes: 0; already correct: 2; skipped: 0.')->assertSuccessful();
    }

    public function test_conflicting_sources_are_skipped(): void
    {
        DB::table('invoices')->insert(['id' => 1, 'contract_id' => 10, 'ingaz_delegation_id' => 20]);
        $this->artisan('invoices:repair-branches', ['--database' => ':memory:', '--apply' => true])->assertFailed();
        $this->assertNull(DB::table('invoices')->value('branch_id'));
    }

    public function test_missing_parent_and_parent_without_branch_are_skipped(): void
    {
        DB::table('contracts')->insert(['id' => 11]);
        DB::table('invoices')->insert([['id' => 1, 'contract_id' => 999], ['id' => 2, 'contract_id' => 11]]);
        $this->artisan('invoices:repair-branches', ['--database' => ':memory:', '--apply' => true])
            ->expectsOutput('Changes: 0; already correct: 0; skipped: 2.')->assertFailed();
        $this->assertEquals(2, DB::table('invoices')->whereNull('branch_id')->count());
    }

    public function test_database_guard_prevents_updates(): void
    {
        DB::table('invoices')->insert(['id' => 1, 'contract_id' => 10]);
        $this->artisan('invoices:repair-branches', ['--database' => 'hajar_live', '--apply' => true])->assertFailed();
        $this->assertNull(DB::table('invoices')->value('branch_id'));
    }

    public function test_invoice_filter_and_unrelated_invoices(): void
    {
        DB::table('invoices')->insert([['id' => 1, 'contract_id' => 10], ['id' => 2, 'contract_id' => 10], ['id' => 3, 'contract_id' => null]]);
        $this->artisan('invoices:repair-branches', ['--database' => ':memory:', '--invoice' => 1, '--apply' => true])->assertSuccessful();
        $this->assertEquals(2, DB::table('invoices')->where('id', 1)->value('branch_id'));
        $this->assertEquals(2, DB::table('invoices')->whereNull('branch_id')->count());
    }
}
