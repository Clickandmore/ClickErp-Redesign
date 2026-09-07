<?php

namespace Tests\Feature\Admin;

use App\Jobs\DeliverAdminDispatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDispatchQueueSchemaTest extends TestCase
{
    public function test_jobs_migration_supports_real_database_queue_insertion(): void
    {
        config([
            'database.connections.queue_schema_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ],
            'queue.connections.schema_test' => [
                'driver' => 'database', 'connection' => 'queue_schema_test',
                'table' => 'jobs', 'queue' => 'schema-test', 'retry_after' => 90,
            ],
        ]);
        $original = DB::getDefaultConnection();
        DB::setDefaultConnection('queue_schema_test');
        try {
            $migration = require database_path('migrations/2026_09_07_130000_create_jobs_table.php');
            $migration->up();
            $id = Queue::connection('schema_test')->push(new DeliverAdminDispatch(649));
            $row = DB::table('jobs')->find($id);
            $this->assertSame('schema-test', $row->queue);
            $this->assertSame(0, (int) $row->attempts);
            $this->assertNull($row->reserved_at);
            $this->assertSame(DeliverAdminDispatch::class, json_decode($row->payload, true)['displayName']);
            // Re-running against an existing queue must preserve waiting jobs.
            $migration->up();
            $this->assertSame(1, DB::table('jobs')->count());
            $migration->down();
            $this->assertFalse(Schema::hasTable('jobs'));
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('queue_schema_test');
        }
    }
}
