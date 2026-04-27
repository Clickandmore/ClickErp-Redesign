<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fn_purchases', function (Blueprint $table) {
            $table->foreignId('stock_id')
                ->comment('المخزن في حالة الاضافة لفاتورة واحدة')
                ->after('created_by_id')
                ->nullable()
                ->constrained('fn_stocks')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_purchases', function (Blueprint $table) {
            $table->dropColumn('stock_id');

        });
    }
};
