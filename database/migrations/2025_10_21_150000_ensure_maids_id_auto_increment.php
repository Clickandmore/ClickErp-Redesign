<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE maids MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        $maxId = (int) DB::table('maids')->max('id');
        $nextId = $maxId > 0 ? $maxId + 1 : 1;

        DB::statement('ALTER TABLE maids AUTO_INCREMENT = '.$nextId);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Previous schema state was inconsistent across environments,
        // so we intentionally leave the column as auto-incrementing.
    }
};
