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
            $table->string('account_text', 500)->default("-")->after('statement');
            $table->boolean('general_type')->default(0)->after('account_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_bonds', function (Blueprint $table) {
            $table->dropColumn('account_text');
            $table->dropColumn('general_type');
        });
    }
};
