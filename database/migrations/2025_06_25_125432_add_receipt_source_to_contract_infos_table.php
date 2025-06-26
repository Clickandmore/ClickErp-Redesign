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
        Schema::table('contract_infos', function (Blueprint $table) {
            $table->string('receipt_source')->nullable()->comment('مصدر الاستلام');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            $table->dropColumn('receipt_source');
        });
    }
};
