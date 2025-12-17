<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_infos', 'workers_have_residency')) {
                $table->enum('workers_have_residency', ['yes', 'no'])
                    ->nullable()
                    ->comment('هل العاملة لديها اقامة');
            }

            if (!Schema::hasColumn('contract_infos', 'performing_medical_examination')) {
                $table->enum('performing_medical_examination', ['yes', 'no'])
                    ->nullable()
                    ->comment('هل تم عمل الفحص الطبي');
            }

            if (!Schema::hasColumn('contract_infos', 'worker_service_type')) {
                $table->enum('worker_service_type', ['deportation', 'transfer'])
                    ->nullable()
                    ->comment('هل العاملة للتسفير ام لنقل الخدمات');
            }

            if (!Schema::hasColumn('contract_infos', 'receipt_form_text')) {
                $table->text('receipt_form_text')->nullable()->comment('نص نموذج الاستلام');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            $columns = [
                'workers_have_residency',
                'performing_medical_examination',
                'worker_service_type',
                'receipt_form_text',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('contract_infos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
