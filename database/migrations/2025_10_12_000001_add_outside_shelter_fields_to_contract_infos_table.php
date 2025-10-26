<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_infos', 'is_outside_shelter')) {
                $table->boolean('is_outside_shelter')
                    ->default(false)
                    ->after('wants_to_work')
                    ->comment('يشير إلى أن العاملة تم تسليمها خارج الإيواء');
            }

            if (!Schema::hasColumn('contract_infos', 'outside_shelter_at')) {
                $table->timestamp('outside_shelter_at')
                    ->nullable()
                    ->after('is_outside_shelter')
                    ->comment('تاريخ ووقت إخراج العاملة من الإيواء');
            }

            if (!Schema::hasColumn('contract_infos', 'outside_shelter_reason')) {
                $table->string('outside_shelter_reason')
                    ->nullable()
                    ->after('outside_shelter_at')
                    ->comment('السبب أو السياق (تأجير، نقل كفالة، ...)');
            }

            if (!Schema::hasColumn('contract_infos', 'outside_shelter_reference_type')) {
                $table->string('outside_shelter_reference_type')
                    ->nullable()
                    ->after('outside_shelter_reason')
                    ->comment('نوع المرجع المرتبط بالإخراج (نموذج العقد)');
            }

            if (!Schema::hasColumn('contract_infos', 'outside_shelter_reference_id')) {
                $table->unsignedBigInteger('outside_shelter_reference_id')
                    ->nullable()
                    ->after('outside_shelter_reference_type')
                    ->comment('معرف المرجع المرتبط بالإخراج');
            }

            if (!Schema::hasColumn('contract_infos', 'outside_shelter_by')) {
                $table->unsignedBigInteger('outside_shelter_by')
                    ->nullable()
                    ->after('outside_shelter_reference_id')
                    ->comment('المستخدم الذي قام بإخراج العاملة من الإيواء');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            if (Schema::hasColumn('contract_infos', 'outside_shelter_by')) {
                $table->dropColumn('outside_shelter_by');
            }

            if (Schema::hasColumn('contract_infos', 'outside_shelter_reference_id')) {
                $table->dropColumn('outside_shelter_reference_id');
            }

            if (Schema::hasColumn('contract_infos', 'outside_shelter_reference_type')) {
                $table->dropColumn('outside_shelter_reference_type');
            }

            if (Schema::hasColumn('contract_infos', 'outside_shelter_reason')) {
                $table->dropColumn('outside_shelter_reason');
            }

            if (Schema::hasColumn('contract_infos', 'outside_shelter_at')) {
                $table->dropColumn('outside_shelter_at');
            }

            if (Schema::hasColumn('contract_infos', 'is_outside_shelter')) {
                $table->dropColumn('is_outside_shelter');
            }
        });
    }
};
