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
        // إضافة indexes لتحسين أداء الفلاتر
        Schema::table('maids', function (Blueprint $table) {
            $table->index('nationality_id');
            $table->index('religion_id');
            $table->index('office_id');
            $table->index('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('city');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->index('status');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->dropIndex(['nationality_id']);
            $table->dropIndex(['religion_id']);
            $table->dropIndex(['office_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['city']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
            $table->dropIndex(['created_at']);
        });
    }
};
