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
        Schema::table('fn_account_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_center_id')->nullable()->after('constraint_id');
            $table->foreign('cost_center_id')->references('id')->on('fn_cost_centers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_account_transfers', function (Blueprint $table) {
            $table->dropColumn('cost_center_id');
        });
    }
};
