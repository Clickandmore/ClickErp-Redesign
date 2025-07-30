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
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('required_maid_nationality_id')->nullable()->after('maid_id');
            $table->foreign('required_maid_nationality_id')->references('id')->on('nationalities')->onDelete('set null');
            $table->index('required_maid_nationality_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['required_maid_nationality_id']);
            $table->dropIndex(['required_maid_nationality_id']);
            $table->dropColumn('required_maid_nationality_id');
        });
    }
};
