<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'musaned_commission_rate')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('musaned_commission_rate', 8, 4)
                    ->nullable()
                    ->after('musaned_commission_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'musaned_commission_rate')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('musaned_commission_rate');
            });
        }
    }
};
