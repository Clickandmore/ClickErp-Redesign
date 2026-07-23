<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_batch_items', function (Blueprint $table) {
            $table->string('legacy_source')->nullable()->index()->after('commission_amount');
            $table->string('legacy_source_file')->nullable()->after('legacy_source');
            $table->unsignedInteger('legacy_source_row')->nullable()->after('legacy_source_file');
        });
    }

    public function down(): void
    {
        Schema::table('commission_batch_items', function (Blueprint $table) {
            $table->dropIndex(['legacy_source']);
            $table->dropColumn([
                'legacy_source',
                'legacy_source_file',
                'legacy_source_row',
            ]);
        });
    }
};
