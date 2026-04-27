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
        Schema::table('hr_employee', function (Blueprint $table) {
            $table->double('net_salary')
                ->after('salary')
                ->default(0)
                ->comment('السعر بعد الضرايب والحوافز');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employee', function (Blueprint $table) {
            //
        });
    }
};
