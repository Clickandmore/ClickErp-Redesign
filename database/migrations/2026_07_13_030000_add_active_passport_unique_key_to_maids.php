<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->string('active_passport_key', 150)
                ->nullable()
                ->storedAs("CASE WHEN deleted_at IS NULL AND passport_num IS NOT NULL AND TRIM(passport_num) <> '' THEN UPPER(TRIM(passport_num)) ELSE NULL END");
            $table->unique('active_passport_key', 'maids_active_passport_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->dropUnique('maids_active_passport_key_unique');
            $table->dropColumn('active_passport_key');
        });
    }
};
