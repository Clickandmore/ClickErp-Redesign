<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'orders_created_by_admin_id_index';

    public function up(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'created_by_admin_id')) {
            return;
        }

        if (Schema::hasTable('activity_log')) {
            DB::statement(<<<'SQL'
                UPDATE orders AS orders_to_update
                INNER JOIN (
                    SELECT subject_id, MAX(causer_id) AS creator_id
                    FROM activity_log
                    WHERE subject_type = 'App\\Models\\ingaz\\Order'
                      AND event = 'created'
                      AND causer_type = 'App\\Models\\auth\\Admin'
                      AND causer_id IS NOT NULL
                    GROUP BY subject_id
                ) AS creators ON creators.subject_id = orders_to_update.id
                SET orders_to_update.created_by_admin_id = creators.creator_id
                WHERE orders_to_update.created_by_admin_id IS NULL
            SQL);
        }

        if (!$this->indexExists()) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('created_by_admin_id', self::INDEX);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && $this->indexExists()) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(self::INDEX);
            });
        }
    }

    private function indexExists(): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'orders')
            ->where('index_name', self::INDEX)
            ->exists();
    }
};
