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
        Schema::table('fn_principles', function (Blueprint $table) {
            // Dropping columns
            $table->dropColumn('registration_date');
            $table->dropColumn('monthly_value');

            // Adding the new description column
            $table->string('description', 1000)->nullable()->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_principles', function (Blueprint $table) {
            // Re-adding the columns that were dropped
            $table->date('registration_date')->nullable();
            $table->decimal('monthly_value', 10, 2)->nullable();

            // Dropping the description column
            $table->dropColumn('description');
        });
    }
};
