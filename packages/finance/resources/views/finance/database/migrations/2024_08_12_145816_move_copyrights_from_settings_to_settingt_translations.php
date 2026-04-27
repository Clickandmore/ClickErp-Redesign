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
        // Remove the column from the first table
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('copyrights');
        });

        // Add the column to the second table
        Schema::table('settings_translations', function (Blueprint $table) {
            $table->string('copyrights'); // Adjust the column type as needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add the column back to the first table
        Schema::table('settings', function (Blueprint $table) {
            $table->string('copyrights'); // Adjust the column type as needed
        });

        // Remove the column from the second table
        Schema::table('settings_translations', function (Blueprint $table) {
            $table->dropColumn('copyrights');
        });
    }
};
