<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rental_contract_extensions')) {
            return;
        }

        Schema::table('rental_contract_extensions', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contract_extensions', 'gross_value')) {
                // قيمة التمديد قبل الخصم — extension_value يبقى القيمة الصافية المستحقة على العميل
                $table->decimal('gross_value', 12, 2)->nullable()->after('extension_value');
            }

            if (!Schema::hasColumn('rental_contract_extensions', 'discount_type')) {
                $table->string('discount_type', 20)->nullable()->after('gross_value');
            }

            if (!Schema::hasColumn('rental_contract_extensions', 'discount_value')) {
                // القيمة المدخلة: نسبة مئوية أو مبلغ مباشر بحسب discount_type
                $table->decimal('discount_value', 12, 2)->nullable()->after('discount_type');
            }

            if (!Schema::hasColumn('rental_contract_extensions', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_value');
            }

            if (!Schema::hasColumn('rental_contract_extensions', 'discount_notes')) {
                $table->string('discount_notes', 255)->nullable()->after('discount_amount');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rental_contract_extensions')) {
            return;
        }

        $columns = array_values(array_filter(
            ['gross_value', 'discount_type', 'discount_value', 'discount_amount', 'discount_notes'],
            fn (string $column) => Schema::hasColumn('rental_contract_extensions', $column)
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('rental_contract_extensions', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
