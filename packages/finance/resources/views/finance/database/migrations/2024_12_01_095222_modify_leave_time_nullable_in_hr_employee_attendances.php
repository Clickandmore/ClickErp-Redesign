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
        Schema::table('hr_employee_attendances', function (Blueprint $table) {
            $table->time('leave_time')->nullable()->change();
            $table->time('attendance_time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_attendances', function (Blueprint $table) {
            $table->time('leave_time')->nullable(false)->change();
            $table->time('attendance_time')->nullable(false)->change();

        });
    }
};
