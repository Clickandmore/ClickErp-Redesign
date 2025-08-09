<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixDeportationProceduresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // التحقق من وجود الجدول أولاً
        if (!Schema::hasTable('deportation_procedures')) {
            // إذا لم يكن الجدول موجوداً، أنشئه
            Schema::create('deportation_procedures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
                $table->foreignId('maid_id')->constrained('maids')->onDelete('cascade');
                $table->foreignId('trip_id')->nullable()->constrained('travel_histories')->onDelete('set null');

                // معلومات الراتب
                $table->boolean('salary_received')->nullable()->default(null);
                $table->date('salary_received_date')->nullable();

                // الوثائق
                $table->boolean('has_passport')->nullable()->default(null);
                $table->boolean('has_deportation_letter')->nullable()->default(null);
                $table->boolean('has_settlement_session')->nullable()->default(null);
                $table->boolean('has_final_exit')->nullable()->default(null);

                // التأكيد
                $table->boolean('procedures_confirmed')->default(false);
                $table->timestamp('confirmed_at')->nullable();
                $table->foreignId('confirmed_by')->nullable()->constrained('admins')->onDelete('set null');

                // الملفات والملاحظات
                $table->text('attachments')->nullable();
                $table->text('notes')->nullable();

                // الحالة
                $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');

                // التواريخ والمسؤول
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('admins')->onDelete('set null');
                $table->foreignId('completed_by')->nullable()->constrained('admins')->onDelete('set null');

                $table->timestamps();

                // Indexes
                $table->index('status');
                $table->index('contract_id');
                $table->index('maid_id');
            });
        } else {
            // إذا كان الجدول موجوداً، تحقق من الأعمدة المفقودة وأضفها
            Schema::table('deportation_procedures', function (Blueprint $table) {
                // إضافة trip_id إذا لم يكن موجوداً
                if (!Schema::hasColumn('deportation_procedures', 'trip_id')) {
                    $table->foreignId('trip_id')->nullable()->after('maid_id')
                        ->constrained('travel_histories')->onDelete('set null');
                }

                // إضافة أعمدة التأكيد إذا لم تكن موجودة
                if (!Schema::hasColumn('deportation_procedures', 'procedures_confirmed')) {
                    $table->boolean('procedures_confirmed')->default(false);
                }

                if (!Schema::hasColumn('deportation_procedures', 'confirmed_at')) {
                    $table->timestamp('confirmed_at')->nullable();
                }

                if (!Schema::hasColumn('deportation_procedures', 'confirmed_by')) {
                    $table->foreignId('confirmed_by')->nullable()->constrained('admins')->onDelete('set null');
                }
            });
        }

        // وضع علامة في جدول migrations أن هذه migrations تمت
        $migrations = [
            '2025_07_29_162540_create_deportation_procedures_table',
            '2025_07_29_175000_add_trip_id_to_deportation_procedures_table',
            '2025_07_29_180833_add_procedures_confirmed_to_deportation_procedures_table',
            '2025_07_29_181903_update_deportation_procedures_boolean_defaults'
        ];

        foreach ($migrations as $migration) {
            DB::table('migrations')->updateOrInsert(
                ['migration' => $migration],
                ['migration' => $migration, 'batch' => DB::table('migrations')->max('batch') + 1]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // لا نريد حذف الجدول في down لأنه قد يحتوي على بيانات مهمة
        // يمكن التعامل مع هذا يدوياً إذا لزم الأمر
    }
}
