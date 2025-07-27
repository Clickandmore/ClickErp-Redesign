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
        Schema::table('contracts', function (Blueprint $table) {
            $table->enum('warranty_status', ['active', 'expired', 'not_applicable'])->default('not_applicable')->after('status');
            $table->timestamp('warranty_start_date')->nullable()->after('warranty_status');
            $table->timestamp('warranty_end_date')->nullable()->after('warranty_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['warranty_status', 'warranty_start_date', 'warranty_end_date']);
        });
    }
};
