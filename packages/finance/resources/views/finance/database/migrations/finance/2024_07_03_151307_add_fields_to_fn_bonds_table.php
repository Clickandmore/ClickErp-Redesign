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
        Schema::table('fn_bonds', function (Blueprint $table) {
            $table->boolean('is_tax')->after('value')->default(0);
            $table->double('tax_rate')->after('is_tax')->nullable();
            $table->double('tax_amount')->after('tax_rate')->nullable();
            $table->double('net')->after('tax_amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_bonds', function (Blueprint $table) {
            $table->dropColumn('is_tax');
            $table->dropColumn('tax_rate');
            $table->dropColumn('tax_amount');
            $table->dropColumn('net');
        });
    }
};
