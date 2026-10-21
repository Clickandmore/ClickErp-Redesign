<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['musaned_transfers', 'fn_constraints'] as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'is_multi_branch')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->boolean('is_multi_branch')->default(false);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['musaned_transfers', 'fn_constraints'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'is_multi_branch')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('is_multi_branch'));
            }
        }
    }
};
