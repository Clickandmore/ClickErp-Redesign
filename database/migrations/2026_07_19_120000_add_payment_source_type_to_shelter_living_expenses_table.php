<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shelter_living_expenses')
            && !Schema::hasColumn('shelter_living_expenses', 'payment_source_type')) {
            Schema::table('shelter_living_expenses', function (Blueprint $table) {
                $table->string('payment_source_type', 20)
                    ->default('bank')
                    ->after('payment_account_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shelter_living_expenses')
            && Schema::hasColumn('shelter_living_expenses', 'payment_source_type')) {
            Schema::table('shelter_living_expenses', function (Blueprint $table) {
                $table->dropColumn('payment_source_type');
            });
        }
    }
};
