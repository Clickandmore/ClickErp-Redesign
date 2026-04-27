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
            $table->dropColumn(['first_tax', 'second_tax']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_principles', function (Blueprint $table) {
            $table->double('first_tax')->default(0)->after('account_id');
            $table->double('second_tax')->default(0)->after('first_tax');
        });
    }
};
