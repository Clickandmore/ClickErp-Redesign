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
        Schema::table('hr_employee_vacations', function (Blueprint $table) {
            $table->unsignedBigInteger('vacation_year_id')->nullable()->after('vacation_id');
            $table->foreign('vacation_year_id')->references('id')->on('hr_vacation_years')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_vacations', function (Blueprint $table) {
            //
        });
    }
};
