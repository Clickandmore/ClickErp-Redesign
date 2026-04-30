<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'send_to_zatca')) {
                $table->boolean('send_to_zatca')->default(false)->after('is_paid');
            }

            if (!Schema::hasColumn('invoices', 'finance_sale_id')) {
                $table->unsignedBigInteger('finance_sale_id')->nullable()->after('send_to_zatca');
                $table->foreign('finance_sale_id', 'invoices_finance_sale_fk')
                    ->references('id')
                    ->on('fn_sales')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'finance_sale_id')) {
                $table->dropForeign('invoices_finance_sale_fk');
                $table->dropColumn('finance_sale_id');
            }

            if (Schema::hasColumn('invoices', 'send_to_zatca')) {
                $table->dropColumn('send_to_zatca');
            }
        });
    }
};
