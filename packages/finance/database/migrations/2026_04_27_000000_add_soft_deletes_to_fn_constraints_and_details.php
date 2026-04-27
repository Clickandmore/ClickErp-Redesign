<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fn_constraints', function (Blueprint $table) {
            if (!Schema::hasColumn('fn_constraints', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        Schema::table('fn_constraint_details', function (Blueprint $table) {
            if (!Schema::hasColumn('fn_constraint_details', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fn_constraint_details', function (Blueprint $table) {
            if (Schema::hasColumn('fn_constraint_details', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('fn_constraints', function (Blueprint $table) {
            if (Schema::hasColumn('fn_constraints', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
