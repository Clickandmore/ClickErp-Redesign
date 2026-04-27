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
            $table->dropColumn('junk_value');
            $table->dropColumn('perished_value');
            $table->dropForeign(['perished_account_id']);
            $table->dropColumn('perished_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_principles', function (Blueprint $table) {
            $table->double('junk_value')->nullable()->default(0);
            $table->string('perished_value')->nullable();
            $table->foreignId('perished_account_id')->nullable()->constrained('fn_accounts')->onDelete('cascade');
        });
    }
};
