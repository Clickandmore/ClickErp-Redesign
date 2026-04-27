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
        Schema::table('fn_accounts', function (Blueprint $table) {
            $table->tinyInteger('operation_type')->default(1)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_accounts', function (Blueprint $table) {
            $table->dropColumn('operation_type');
        });
    }
};
