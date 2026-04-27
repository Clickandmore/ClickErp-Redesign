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
            $table->unsignedBigInteger('created_by_id')->nullable()->after('have_permission');
            $table->foreign('created_by_id')->references('id')->on('admins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_attendances', function (Blueprint $table) {
            //
        });
    }
};
