<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'client_id')) {
                $table->foreignId('client_id')
                    ->nullable()
                    ->after('ingaz_delegation_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('invoices', 'general_description')) {
                $table->text('general_description')->nullable()->after('insurance_company');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'client_id')) {
                $table->dropConstrainedForeignId('client_id');
            }

            if (Schema::hasColumn('invoices', 'general_description')) {
                $table->dropColumn('general_description');
            }
        });
    }
};
