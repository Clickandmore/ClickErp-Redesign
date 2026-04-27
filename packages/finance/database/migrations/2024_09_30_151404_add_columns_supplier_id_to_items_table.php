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
        Schema::table('fn_items', function (Blueprint $table) {
            $table->foreignId('supplier_id')->after('sub_category_id')->nullable()->constrained('fn_suppliers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_items', function (Blueprint $table) {
            $table->dropColumn('supplier_id');
        });
    }
};
