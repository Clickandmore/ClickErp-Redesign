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
        Schema::table('fn_suppliers', function (Blueprint $table) {
            $table->string('days_number')->default(0)->nullable()->after('credit_limit');
            $table->boolean('exceeding_minimum_cash_limit')->default(0)->nullable()->after('days_number');
            $table->boolean('exceeding_minimum_days_limit')->default(0)->nullable()->after('exceeding_minimum_cash_limit');
        });

        Schema::table('fn_clients', function (Blueprint $table) {
            $table->string('days_number')->default(0)->nullable()->after('credit_limit');
            $table->boolean('exceeding_minimum_cash_limit')->default(0)->nullable()->after('days_number');
            $table->boolean('exceeding_minimum_days_limit')->default(0)->nullable()->after('exceeding_minimum_cash_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_suppliers', function (Blueprint $table) {
           $table->dropColumn(['days_number','exceeding_minimum_cash_limit','exceeding_minimum_days_limit']);
        });
        Schema::table('fn_clients', function (Blueprint $table) {
            $table->dropColumn(['days_number','exceeding_minimum_cash_limit','exceeding_minimum_days_limit']);
        });
    }
};
