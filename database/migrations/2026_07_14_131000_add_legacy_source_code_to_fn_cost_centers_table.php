<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fn_cost_centers', function (Blueprint $table) {
            $table->string('legacy_source_code')->nullable()->after('code')->index();
        });
    }

    public function down(): void
    {
        Schema::table('fn_cost_centers', function (Blueprint $table) {
            $table->dropIndex(['legacy_source_code']);
            $table->dropColumn('legacy_source_code');
        });
    }
};
