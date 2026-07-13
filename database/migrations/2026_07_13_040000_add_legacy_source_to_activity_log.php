<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->string('legacy_source', 100)->nullable()->after('batch_uuid');
            $table->unsignedBigInteger('legacy_id')->nullable()->after('legacy_source');
            $table->unique(['legacy_source', 'legacy_id'], 'activity_log_legacy_source_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->dropUnique('activity_log_legacy_source_id_unique');
            $table->dropColumn(['legacy_source', 'legacy_id']);
        });
    }
};
