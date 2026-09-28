<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'external_booking_employee_selection')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->boolean('external_booking_employee_selection')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'external_booking_employee_selection')) {
            Schema::table('settings', fn (Blueprint $table) => $table->dropColumn('external_booking_employee_selection'));
        }
    }
};
