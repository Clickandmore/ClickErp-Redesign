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
        Schema::table('maids', function (Blueprint $table) {
            $table->enum('experience_status', ['has_experience', 'no_experience'])->default('no_experience')->after('experience_years')->comment('حالة الخبرة: سبق لها العمل أم لا');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->dropColumn('experience_status');
        });
    }
};
