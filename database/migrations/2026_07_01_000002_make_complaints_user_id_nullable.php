<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('complaints') || !Schema::hasColumn('complaints', 'user_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $this->dropMysqlForeignKeyIfExists('complaints', 'user_id');
            DB::statement('ALTER TABLE complaints MODIFY user_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE complaints ADD CONSTRAINT complaints_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('complaints') || !Schema::hasColumn('complaints', 'user_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $this->dropMysqlForeignKeyIfExists('complaints', 'user_id');
            DB::statement('ALTER TABLE complaints MODIFY user_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE complaints ADD CONSTRAINT complaints_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
        }
    }

    private function dropMysqlForeignKeyIfExists(string $table, string $column): void
    {
        $database = DB::getDatabaseName();
        $constraints = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$database, $table, $column]
        );

        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraint->CONSTRAINT_NAME}");
        }
    }
};
