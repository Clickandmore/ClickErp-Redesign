<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'client_type')) {
                $table->string('client_type', 20)->default('person')->after('client_category_id');
            }

            if (!Schema::hasColumn('users', 'tax_number')) {
                $table->string('tax_number', 50)->nullable()->after('national_num');
            }

            if (!Schema::hasColumn('users', 'license_number')) {
                $table->string('license_number', 100)->nullable()->after('tax_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['license_number', 'tax_number', 'client_type'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
