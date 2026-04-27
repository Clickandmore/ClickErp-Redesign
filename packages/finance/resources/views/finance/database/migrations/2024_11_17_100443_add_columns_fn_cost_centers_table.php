<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fn_cost_centers', function (Blueprint $table) {
            $table->dropColumn('table');
        });

        Schema::table('fn_cost_centers', function (Blueprint $table) {
            $table->string('table_name')->after('parent_id');
            $table->unsignedBigInteger('table_related_id')->after('table_name');
            $table->string('code')->after('table_related_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_cost_centers', function (Blueprint $table) {
            $table->dropColumn('table_name');
            $table->dropColumn('table_related_id');
            $table->dropColumn('code');
        });

        Schema::table('fn_cost_centers', function (Blueprint $table) {
            $table->string('table')->nullable()->after('parent_id');
        });
    }
};
