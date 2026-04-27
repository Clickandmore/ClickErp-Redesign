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
            $table->dropForeign(['type_id']);
            $table->foreignId('parent_account_id')->nullable()->after('name')->constrained('fn_accounts')->onDelete('cascade');
            $table->dropColumn('type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_principles', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->after('name')->constrained('fn_accounts')->onDelete('cascade');
            $table->dropForeign(['parent_account_id']);
            $table->dropColumn('parent_account_id');
        });
    }
};
