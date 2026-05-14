<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('nationalities', function (Blueprint $table) {
            if (!Schema::hasColumn('nationalities', 'direct_recruitment_cost')) {
                $table->decimal('direct_recruitment_cost', 10, 2)->nullable()->after('price');
            }

            if (!Schema::hasColumn('nationalities', 'external_recruitment_cost')) {
                $table->decimal('external_recruitment_cost', 10, 2)->nullable()->after('direct_recruitment_cost');
            }

            if (!Schema::hasColumn('nationalities', 'government_cost')) {
                $table->decimal('government_cost', 10, 2)->nullable()->after('external_recruitment_cost');
            }
        });
    }

    public function down()
    {
        Schema::table('nationalities', function (Blueprint $table) {
            foreach (['government_cost', 'external_recruitment_cost', 'direct_recruitment_cost'] as $column) {
                if (Schema::hasColumn('nationalities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
