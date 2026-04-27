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
        Schema::table('fn_principles', function (Blueprint $table) {
            $table->foreignId('first_tax')->nullable()->after('account_id')->constrained('fn_tax_settings')->onDelete('cascade');
            $table->foreignId('second_tax')->nullable()->after('first_tax')->constrained('fn_tax_settings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_principles', function (Blueprint $table) {
            $table->dropForeign(['first_tax']);
            $table->dropForeign(['second_tax']);
            $table->dropColumn(['first_tax', 'second_tax']);
        });
    }
};
