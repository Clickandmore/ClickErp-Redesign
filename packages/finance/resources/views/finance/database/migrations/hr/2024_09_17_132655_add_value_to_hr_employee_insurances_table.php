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
        Schema::table('hr_employee_insurances', function (Blueprint $table) {
            $table->double('value')
                ->default(0)
                ->after('insurances_id')
                ->comment('القيمة النهائية للتأمين');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee_insurances', function (Blueprint $table) {
            //
        });
    }
};
