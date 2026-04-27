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
        Schema::table('hr_employee_permissions', function (Blueprint $table) {
            $table->time('from_time')->nullable()->after('date');
            $table->time('to_time')->nullable()->after('from_time');
            $table->string('file', 1000)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_permissions', function (Blueprint $table) {
            $table->dropColumn('from_time');
            $table->dropColumn('to_time');
            $table->string('file', 255)->change();
        });
    }
};
