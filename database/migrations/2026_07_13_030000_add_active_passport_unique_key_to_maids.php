<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('maids', 'active_passport_key')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->string('active_passport_key', 150)
                    ->nullable()
                    ->storedAs("CASE WHEN deleted_at IS NULL AND passport_num IS NOT NULL AND TRIM(passport_num) <> '' THEN UPPER(TRIM(passport_num)) ELSE NULL END");
            });
        }

        if (!$this->indexExists('maids_active_passport_key_unique')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->unique('active_passport_key', 'maids_active_passport_key_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('maids_active_passport_key_unique')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->dropUnique('maids_active_passport_key_unique');
            });
        }

        if (Schema::hasColumn('maids', 'active_passport_key')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->dropColumn('active_passport_key');
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        return collect(DB::select('SHOW INDEX FROM `maids` WHERE Key_name = ?', [$indexName]))->isNotEmpty();
    }
};
